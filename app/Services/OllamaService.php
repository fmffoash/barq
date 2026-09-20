<?php

namespace App\Services;

use App\Models\Template;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

// بيكلّم نموذج Ollama المحلي (صفر بيانات بتتبعت لأي API خارجي) عشان يقترح محتوى مبدئي
// لخانات القالب، بناءً على وصف قصير للنشاط التجاري بيكتبه الأدمن. لو Ollama مش متاح أو
// رجّع رد مش JSON صالح، بيرجّع مصفوفة فاضية من غير ما يكسر الطلب — الأدمن دايماً يقدر
// يكمّل يدوي.
class OllamaService
{
    /**
     * بيرجّع مصفوفة [slot_key => قيمة مقترحة] لخانات النص/القايمة/الرابط في القالب.
     * الصور مستبعدة دايماً — الذكاء الاصطناعي مبيقترحش صور.
     *
     * @return array<string, string|array<int, string>>
     */
    public function suggestContent(Template $template, string $businessDescription): array
    {
        $template->loadMissing('slots');

        $suggestableSlots = $template->slots->where('slot_type', '!=', 'image');

        if ($suggestableSlots->isEmpty()) {
            return [];
        }

        $decoded = $this->generateJson($this->buildPrompt($suggestableSlots, $businessDescription));

        if ($decoded === null) {
            return [];
        }

        return $this->filterToKnownKeys($suggestableSlots, $decoded);
    }

    /**
     * بيبعت أي prompt حر لـOllama ويرجّع الرد متفكّك كـ JSON object، أو null لو Ollama مش
     * متاح أو رجّع رد مش JSON صالح. مستخرجة من suggestContent() (Phase 2) عشان تتستخدم
     * كمان في مساعد الشات (AiProjectAssistantService، Phase 10) بنفس إعدادات الموثوقية
     * (timeout من config، think معطّل لسرعة الرد — شوف تعليق suggestContent فوق).
     *
     * @return array<mixed, mixed>|null
     */
    public function generateJson(string $prompt): ?array
    {
        try {
            $response = Http::timeout((int) config('services.ollama.timeout', 120))
                ->post(rtrim((string) config('services.ollama.base_url'), '/').'/api/generate', [
                    'model' => config('services.ollama.model'),
                    'prompt' => $prompt,
                    'format' => 'json',
                    'stream' => false,
                    'think' => false,
                ]);

            if (! $response->successful()) {
                Log::warning('Ollama request failed.', ['status' => $response->status()]);

                return null;
            }

            $decoded = json_decode((string) $response->json('response'), true);

            if (! is_array($decoded)) {
                Log::warning('Ollama returned a non-JSON-object response.');

                return null;
            }

            return $decoded;
        } catch (Throwable $e) {
            Log::warning('Ollama service is unreachable.', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\TemplateSlot>  $slots
     */
    private function buildPrompt($slots, string $businessDescription): string
    {
        $lines = [
            'انت بتكتب محتوى موقع ويب عربي بسيط لنشاط تجاري. اكتب بأسلوب تسويقي واضح ومختصر ومناسب للسوق العربي.',
            "وصف النشاط اللي كتبه صاحب المشروع: {$businessDescription}",
            '',
            'رجّع إجابتك في صورة JSON object واحد بس — كل مفتاح هو "key" الخانة المذكورة تحت بالحرف،',
            'والقيمة هي المحتوى المقترح المناسب لنوعها. لو النوع "قايمة" رجّع array من نصوص قصيرة.',
            'متكتبش أي حاجة برّه الـ JSON — صفر شرح أو مقدمة.',
            '',
        ];

        foreach ($slots->groupBy('section_key') as $sectionKey => $sectionSlots) {
            $lines[] = "قسم: {$sectionKey}";

            foreach ($sectionSlots as $slot) {
                $typeHint = match ($slot->slot_type) {
                    'list' => 'قايمة عناصر قصيرة',
                    'link' => 'رابط (URL) — لو معندكش رابط حقيقي سيبه فاضي',
                    'textarea' => 'فقرة نص متوسطة الطول',
                    default => 'نص قصير',
                };

                $lines[] = "- key: \"{$slot->key}\" | التسمية: {$slot->label()} | النوع: {$typeHint}";
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\TemplateSlot>  $slots
     * @param  array<mixed, mixed>  $suggested
     * @return array<string, string|array<int, string>>
     */
    private function filterToKnownKeys($slots, array $suggested): array
    {
        $slotsByKey = $slots->keyBy('key');
        $filtered = [];

        foreach ($suggested as $key => $value) {
            if (! is_string($key) || ! $slotsByKey->has($key)) {
                continue;
            }

            $slot = $slotsByKey->get($key);

            if ($slot->slot_type === 'list') {
                $items = is_array($value) ? $value : explode("\n", (string) $value);

                $filtered[$key] = collect($items)
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->values()
                    ->all();

                continue;
            }

            if (is_array($value)) {
                $value = implode(' ', array_map('strval', $value));
            }

            $filtered[$key] = trim((string) $value);
        }

        return $filtered;
    }
}
