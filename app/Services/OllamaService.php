<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Template;
use App\Models\TemplateSlot;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

// بيكلّم نموذج Ollama المحلي (صفر بيانات بتتبعت لأي API خارجي) عشان يقترح محتوى مبدئي
// لخانات القالب، بناءً على وصف قصير للنشاط التجاري بيكتبه الأدمن. لو Ollama مش متاح أو
// رجّع رد مش JSON صالح، بيرجّع مصفوفة فاضية من غير ما يكسر الطلب — الأدمن دايماً يقدر
// يكمّل يدوي.
//
// التشغيل على جهاز فؤاد (2026-10-06) — اتغيّر كتير عن أيام السيرفر:
// - num_ctx صريح (8192 افتراضياً): Ollama على جهاز من غير كارت شاشة قوي بيقرا 4096 توكن بس
//   افتراضياً، وأي طلب أطول (كوبي جوجل مابس) كان بيتقص من **أوله** — يعني التعليمات نفسها —
//   والنموذج يرجّع كلام مش مفهوم. ده غالباً سبب "النموذج مش متاح" اللي فؤاد شافه.
// - format بيقبل JSON schema (structured outputs) بدل 'json' بس — Ollama بيجبر النموذج على
//   الشكل المطلوب بالظبط (مفاتيح/أنواع/قيم من قايمة).
// - كل فشل بقى ليه نوع (AiResult) بدل null: Ollama مقفول/النموذج مش متسطّب/المهلة/رد بايظ.
// - توقيتات كل رد بتتسجّل (AiStats) عشان العدّاد يحسب الوقت الباقي من سرعة الجهاز الحقيقية.
// - body() هي نفس الطلب اللي المتصفح بيبعته لـOllama مباشرة في وضع البث (AiRunController)،
//   فالإعدادات واحدة في المسارين.
class OllamaService
{
    // إعدادات التوليد لكل نوع طلب: التصنيف والاستخراج محتاجين دقة (حرارة واطية)، والمحتوى
    // التسويقي محتاج تنوّع. num_predict حد أقصى لطول الرد عشان نموذج "لفّ" على نفسه ميفضلش
    // يكتب للأبد.
    private const TASK_OPTIONS = [
        'content' => ['temperature' => 0.7, 'num_predict' => 2048],
        'create' => ['temperature' => 0.6, 'num_predict' => 2560],
        'follow_up' => ['temperature' => 0.1, 'num_predict' => 1536],
        'classify' => ['temperature' => 0.1, 'num_predict' => 512],
        'extract' => ['temperature' => 0.1, 'num_predict' => 2048],
        'rewrite' => ['temperature' => 0.8, 'num_predict' => 1024],
        'benchmark' => ['temperature' => 0.2, 'num_predict' => 160],
    ];

    // ملفات .env القديمة (أيام السيرفر) فيها OLLAMA_TIMEOUT=180 — قليلة جداً على جهاز من غير
    // كارت شاشة، وهي اللي كانت بتقطع الانتظار قبل ما الرد يوصل. ده أقل حد بنقبله.
    private const MIN_TIMEOUT = 600;

    public function model(): string
    {
        $chosen = Setting::get('ai.model');

        return is_string($chosen) && $chosen !== '' ? $chosen : (string) config('services.ollama.model');
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.ollama.base_url'), '/');
    }

    public function numCtx(): int
    {
        $chosen = Setting::get('ai.num_ctx');

        return is_int($chosen) && $chosen >= 2048 ? $chosen : (int) config('services.ollama.num_ctx', 8192);
    }

    public function timeout(): int
    {
        return max((int) config('services.ollama.timeout', self::MIN_TIMEOUT), self::MIN_TIMEOUT);
    }

    /**
     * جسم طلب /api/generate بالظبط — نفسه للنداء من السيرفر ولبث المتصفح المباشر.
     *
     * @param  array<string, mixed>|null  $schema  JSON schema للرد، أو null = أي JSON object
     * @return array<string, mixed>
     */
    public function body(string $prompt, ?array $schema = null, string $task = 'content', bool $stream = false): array
    {
        return [
            'model' => $this->model(),
            'prompt' => $prompt,
            'format' => $schema ?? 'json',
            'stream' => $stream,
            // نماذج التفكير (qwen3 وما بعده) بتقعد "تفكّر" مخفي قبل الرد — بيضاعف الوقت ومش
            // محتاجينه لكتابة محتوى. النماذج اللي مبتفكرش أصلاً بتتجاهله.
            'think' => false,
            'keep_alive' => (string) config('services.ollama.keep_alive', '30m'),
            'options' => ['num_ctx' => $this->numCtx(), 'top_p' => 0.9] + (self::TASK_OPTIONS[$task] ?? self::TASK_OPTIONS['content']),
        ];
    }

    /**
     * نداء واحد من السيرفر لحد ما الرد يخلص (المسار الاحتياطي لما المتصفح مش قادر يكلّم
     * Ollama مباشرة، والتستات).
     *
     * @param  array<string, mixed>|null  $schema
     */
    public function run(string $prompt, ?array $schema = null, string $task = 'content'): AiResult
    {
        return $this->runBody($this->body($prompt, $schema, $task), $task, mb_strlen($prompt));
    }

    /**
     * نفس run() بس بجسم طلب جاهز (طلب اتجهّز قبل كده واتحفظ في ai_runs — AiRunController).
     *
     * @param  array<string, mixed>  $body
     */
    public function runBody(array $body, string $task, int $promptChars = 0): AiResult
    {
        $body['stream'] = false;
        $this->extendTimeLimit();

        try {
            $response = Http::timeout($this->timeout())
                ->connectTimeout(5)
                ->post($this->baseUrl().'/api/generate', $body);
        } catch (ConnectionException $e) {
            $timedOut = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'cURL error 28');
            Log::warning('Ollama request failed to complete.', ['message' => $e->getMessage()]);

            return AiResult::failure($timedOut ? AiResult::TIMEOUT : AiResult::DOWN, $e->getMessage());
        } catch (Throwable $e) {
            Log::warning('Ollama service is unreachable.', ['message' => $e->getMessage()]);

            return AiResult::failure(AiResult::DOWN, $e->getMessage());
        }

        if (! $response->successful()) {
            $error = (string) ($response->json('error') ?? $response->body());
            Log::warning('Ollama request failed.', ['status' => $response->status(), 'error' => $error]);

            return AiResult::failure(
                $response->status() === 404 && str_contains($error, 'not found') ? AiResult::MODEL_MISSING : AiResult::HTTP_ERROR,
                $error,
            );
        }

        return $this->finish((string) $response->json('response'), (array) $response->json(), $task, (string) ($body['model'] ?? ''), $promptChars);
    }

    /**
     * بث الرد سطر بسطر (NDJSON زي Ollama بالظبط) — المسار اللي السيرفر بيوصّل فيه الرد للمتصفح
     * لما المتصفح مش قادر يكلّم Ollama بنفسه. أي فشل بيطلع سطر {"error": ..., "kind": ...} بدل
     * ما البث يقف ساكت.
     *
     * @param  array<string, mixed>  $body
     * @param  callable(string): void  $emit
     */
    public function stream(array $body, callable $emit): void
    {
        $body['stream'] = true;
        $this->extendTimeLimit();

        try {
            $response = Http::timeout($this->timeout())
                ->connectTimeout(5)
                ->withOptions(['stream' => true])
                ->post($this->baseUrl().'/api/generate', $body);
        } catch (ConnectionException $e) {
            $timedOut = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'cURL error 28');
            $emit($this->errorLine($timedOut ? AiResult::TIMEOUT : AiResult::DOWN, $e->getMessage()));

            return;
        } catch (Throwable $e) {
            $emit($this->errorLine(AiResult::DOWN, $e->getMessage()));

            return;
        }

        if (! $response->successful()) {
            $error = (string) ($response->json('error') ?? $response->body());
            $emit($this->errorLine($response->status() === 404 && str_contains($error, 'not found') ? AiResult::MODEL_MISSING : AiResult::HTTP_ERROR, $error));

            return;
        }

        $stream = $response->toPsrResponse()->getBody();
        $buffer = '';

        try {
            while (! $stream->eof()) {
                $buffer .= $stream->read(8192);

                while (($newline = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $newline));
                    $buffer = substr($buffer, $newline + 1);

                    if ($line !== '') {
                        $emit($line);
                    }
                }
            }
        } catch (Throwable $e) {
            $emit($this->errorLine(str_contains($e->getMessage(), 'timed out') ? AiResult::TIMEOUT : AiResult::DOWN, $e->getMessage()));

            return;
        }

        if (trim($buffer) !== '') {
            $emit(trim($buffer));
        }
    }

    private function errorLine(string $kind, string $detail): string
    {
        Log::warning('Ollama stream failed.', ['kind' => $kind, 'message' => $detail]);

        return (string) json_encode(['error' => $detail, 'kind' => $kind], JSON_UNESCAPED_UNICODE);
    }

    // سيرفر PHP المدمج بيطبّق max_execution_time، وعلى ويندوز العداد ده وقت فعلي مش وقت
    // معالج — فبنمدّه يغطي مهلة Ollama كاملة. لو الحد أصلاً 0 (مفتوح) مبنلمسوش.
    private function extendTimeLimit(): void
    {
        $currentLimit = (int) ini_get('max_execution_time');
        if ($currentLimit > 0 && function_exists('set_time_limit')) {
            @set_time_limit(max($currentLimit, $this->timeout() + 30));
        }
    }

    /**
     * بيحوّل نص الرد الكامل لنتيجة ويسجّل التوقيتات — نفس الخطوة للنداء من السيرفر وللرد اللي
     * المتصفح جمّعه من البث.
     *
     * @param  array<string, mixed>  $metrics  آخر رسالة من Ollama (فيها التوقيتات وdone_reason)
     * @param  string  $model  النموذج اللي اتبعتله الطلب فعلاً (فاضي = النموذج الحالي)
     * @param  int  $promptChars  طول البرومبت بالحروف (عشان تقدير عدد التوكنز للعدّاد)
     */
    public function finish(string $text, array $metrics, string $task, string $model = '', int $promptChars = 0): AiResult
    {
        $metrics = array_intersect_key($metrics, array_flip([
            'total_duration', 'load_duration', 'prompt_eval_count', 'prompt_eval_duration',
            'eval_count', 'eval_duration', 'done_reason',
        ]));

        if (($metrics['eval_count'] ?? 0) > 0) {
            try {
                AiStats::record($model !== '' ? $model : $this->model(), $task, $metrics, $promptChars);
            } catch (Throwable) {
                // التسجيل للعدّاد بس — فشله ميأثرش على الرد نفسه.
            }
        }

        $decoded = self::decodeJson($text);

        if ($decoded === null) {
            Log::warning('Ollama returned a non-JSON-object response.', ['done_reason' => $metrics['done_reason'] ?? null]);

            return AiResult::failure(
                ($metrics['done_reason'] ?? null) === 'length' ? AiResult::TOO_LONG : AiResult::BAD_JSON,
                '',
                $text,
                $metrics,
            );
        }

        return AiResult::success($decoded, $text, $metrics);
    }

    /**
     * بيبعت أي prompt حر لـOllama ويرجّع الرد متفكّك كـ JSON object، أو null لو فشل لأي سبب.
     * (الواجهة القديمة — الكود الجديد بيستخدم run() عشان يعرف سبب الفشل.)
     *
     * @param  array<string, mixed>|null  $schema
     * @return array<mixed, mixed>|null
     */
    public function generateJson(string $prompt, ?array $schema = null, string $task = 'content'): ?array
    {
        return $this->run($prompt, $schema, $task)->data;
    }

    // الرد المفروض JSON نضيف، بس أحياناً بيتلف في ```json أو قبله/بعده كلام.
    public static function decodeJson(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $text) ?? $text;

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            }
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * فحص سريع قبل أي طلب طويل: Ollama شغال؟ والنموذج متسطّب؟ — عشان فؤاد ميستناش دقايق
     * ويطلعله في الآخر "مش متاح". null = تمام.
     */
    public function preflight(): ?AiResult
    {
        try {
            $response = Http::timeout(4)->connectTimeout(2)->get($this->baseUrl().'/api/tags');
        } catch (Throwable $e) {
            return AiResult::failure(AiResult::DOWN, $e->getMessage());
        }

        // Ollama شغال بس الرد مش بالشكل المعروف لسبب ما — نسيب الطلب الحقيقي يحكم.
        if (! $response->successful() || ! is_array($response->json('models'))) {
            return null;
        }

        $names = collect($response->json('models'))->pluck('name')->filter()->values();
        $model = $this->model();

        $installed = $names->contains($model)
            || (! str_contains($model, ':') && $names->contains($model.':latest'));

        return $installed ? null : AiResult::failure(AiResult::MODEL_MISSING, $model);
    }

    /**
     * النموذج محمّل في الذاكرة دلوقتي ولا هياخد وقت تحميل الأول (للعدّاد بس — أي فشل = "مش
     * معروف"، فالعدّاد بيحسب وقت التحميل احتياطي).
     */
    public function isLoaded(?string $model = null): ?bool
    {
        $model ??= $this->model();

        try {
            $response = Http::timeout(2)->connectTimeout(1)->get($this->baseUrl().'/api/ps');
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful() || ! is_array($response->json('models'))) {
            return null;
        }

        return collect($response->json('models'))
            ->contains(fn ($m) => in_array($m['name'] ?? $m['model'] ?? null, [$model, $model.':latest'], true));
    }

    /**
     * المتصفح يكلّم Ollama بنفسه (بث مباشر، السيرفر فاضي لباقي الصفحات) — ده ممكن بس لو Ollama
     * والصفحة الاتنين على نفس الجهاز (127.0.0.1/localhost): Ollama بيقبل الطلبات من الأصول دي
     * بس افتراضياً (OLLAMA_ORIGINS)، ولو البرنامج مفتوح من جهاز تاني أو دومين، "127.0.0.1" في
     * المتصفح هيبقى جهاز تاني خالص. OLLAMA_BROWSER_DIRECT=false بيقفله نهائياً.
     */
    public function browserDirect(string $requestHost): bool
    {
        $setting = config('services.ollama.browser_direct', 'auto');

        if (is_bool($setting)) {
            return $setting;
        }

        $setting = strtolower((string) $setting);
        if (in_array($setting, ['0', 'false', 'off', 'no', 'never'], true)) {
            return false;
        }
        if (in_array($setting, ['1', 'true', 'on', 'yes', 'always'], true)) {
            return true;
        }

        $loopback = ['127.0.0.1', 'localhost', '::1', '[::1]'];
        $ollamaHost = strtolower((string) parse_url($this->baseUrl(), PHP_URL_HOST));

        return in_array($ollamaHost, $loopback, true) && in_array(strtolower($requestHost), $loopback, true);
    }

    /**
     * بيرجّع مصفوفة [slot_key => قيمة مقترحة] لخانات النص/القايمة/الرابط في القالب.
     * الصور مستبعدة دايماً — الذكاء الاصطناعي مبيقترحش صور.
     *
     * @return array<string, string|array<int, string>>
     */
    public function suggestContent(Template $template, string $businessDescription): array
    {
        return $this->suggestContentResult($template, $businessDescription)->data ?? [];
    }

    // نفس suggestContent بس بترجع سبب الفشل كمان.
    public function suggestContentResult(Template $template, string $businessDescription): AiResult
    {
        $template->loadMissing('slots');

        $suggestableSlots = $template->slots->whereIn('slot_type', ['text', 'textarea', 'list']);

        if ($suggestableSlots->isEmpty()) {
            return AiResult::success([], '');
        }

        $result = $this->run($this->buildPrompt($suggestableSlots, $businessDescription), $this->contentSchema($suggestableSlots), 'content');

        if (! $result->ok) {
            return $result;
        }

        return AiResult::success($this->filterToKnownKeys($suggestableSlots, $result->data), $result->text, $result->metrics);
    }

    /**
     * JSON schema لخانات قالب: نص للخانات النصية، وقايمة نصوص للقوايم. Ollama بيلتزم بيه
     * بالحرف، فمفيش مفاتيح غريبة ولا قايمة راجعة كنص واحد.
     *
     * @param  Collection<int, TemplateSlot>  $slots
     * @return array<string, mixed>
     */
    public function contentSchema($slots): array
    {
        $properties = [];

        foreach ($slots as $slot) {
            $properties[$slot->key] = match (true) {
                // آراء العملاء: حقيقية من الوصف بس، فقايمة فاضية لازم تبقى مسموحة (وإلا النموذج
                // مجبر يألّف آراء عشان يكمّل الشكل المطلوب).
                self::isTestimonials($slot) => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 3],
                $slot->slot_type === 'list' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 2, 'maxItems' => 8],
                default => ['type' => 'string'],
            };
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_keys($properties),
        ];
    }

    // خانة آراء العملاء (testimonials_list في المكتبة) — بتتعامل بحرص: حقيقية بس.
    public static function isTestimonials(TemplateSlot $slot): bool
    {
        return $slot->slot_type === 'list' && str_contains($slot->key, 'testimonial');
    }

    /**
     * @param  Collection<int, TemplateSlot>  $slots
     */
    public function buildPrompt($slots, string $businessDescription): string
    {
        // الجزء الثابت الأول والمتغيّر في الآخر: Ollama بيعيد استخدام قراية أول الطلب لو
        // اتكرر زي ما هو، فده بيوفّر وقت على الجهاز.
        $lines = [
            'انت بتكتب محتوى موقع ويب عربي بسيط لنشاط تجاري. اكتب بعربي بسيط وواضح بأسلوب تسويقي مختصر ومناسب للسوق المصري.',
            'رجّع JSON object واحد بس — كل مفتاح هو "key" الخانة المذكورة تحت بالحرف، والقيمة هي المحتوى المناسب لنوعها.',
            'لو النوع "قايمة" رجّع array من 3 لـ6 عناصر قصيرة. استخدم أي بيانات حقيقية في الوصف (اسم، خدمات، مواعيد) بدل كلام عام.',
            'متخترعش أرقام تليفونات أو عناوين أو آراء عملاء بأسماء ناس.',
            '',
        ];

        foreach ($slots->groupBy('section_key') as $sectionKey => $sectionSlots) {
            $lines[] = "قسم: {$sectionKey}";

            foreach ($sectionSlots as $slot) {
                $typeHint = match (true) {
                    self::isTestimonials($slot) => 'آراء عملاء حقيقية من الوصف بس بالصيغة "الكلام — الاسم"، ولو مفيش رجّع [] (ممنوع تألّف)',
                    $slot->slot_type === 'list' => 'قايمة عناصر قصيرة',
                    $slot->slot_type === 'textarea' => 'فقرة من جملتين لـ4 جمل',
                    default => 'نص قصير (من 2 لـ8 كلمات)',
                };

                $lines[] = "- key: \"{$slot->key}\" | التسمية: {$slot->label()} | النوع: {$typeHint}";
            }

            $lines[] = '';
        }

        $lines[] = "وصف النشاط اللي كتبه صاحب المشروع:\n{$businessDescription}";

        return implode("\n", $lines);
    }

    /**
     * @param  Collection<int, TemplateSlot>  $slots
     * @param  array<mixed, mixed>  $suggested
     * @return array<string, string|array<int, string>>
     */
    public function filterToKnownKeys($slots, array $suggested): array
    {
        $slotsByKey = $slots->keyBy('key');
        $filtered = [];

        foreach ($suggested as $key => $value) {
            if (! is_string($key) || ! $slotsByKey->has($key)) {
                continue;
            }

            $slot = $slotsByKey->get($key);

            if ($slot->slot_type === 'image') {
                continue;
            }

            if ($slot->slot_type === 'list') {
                $items = is_array($value) ? $value : explode("\n", (string) $value);

                $filtered[$key] = collect($items)
                    ->map(fn ($item) => trim(is_scalar($item) ? (string) $item : ''))
                    ->filter()
                    ->values()
                    ->all();

                continue;
            }

            if (is_array($value)) {
                $value = implode(' ', array_map(fn ($item) => is_scalar($item) ? (string) $item : '', $value));
            }

            $value = trim((string) $value);

            if ($slot->slot_type === 'link') {
                // رابط بس لو شكله رابط حقيقي (http/https/tel/mailto/واتساب) — النموذج ممكن
                // يكتب "مفيش رابط" أو يخترع دومين.
                if ($value !== '' && preg_match('#^(https?://|tel:|mailto:)#i', $value)) {
                    $filtered[$key] = $value;
                }

                continue;
            }

            if ($value === '') {
                continue;
            }

            // خانات text/textarea بترندر بـ {!! !!} (RichTextSanitizer) — محتوى الذكاء الاصطناعي
            // نص عادي، بس لازم يعدّي من نفس المطهّر عشان يفضل صفر مسار غير مطهّر.
            $filtered[$key] = RichTextSanitizer::clean($value);
        }

        return $filtered;
    }
}
