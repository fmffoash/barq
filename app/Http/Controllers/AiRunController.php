<?php

namespace App\Http\Controllers;

use App\Models\AiRun;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiStats;
use App\Services\Ai\Runs\CreateProjectRun;
use App\Services\Ai\Runs\FollowUpRun;
use App\Services\Ai\Runs\RunHandler;
use App\Services\Ai\Runs\SuggestContentRun;
use App\Services\OllamaService;
use App\Services\PhotoPoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * طلبات الذكاء الاصطناعي بالعدّاد (2026-10-08). قبل كده فؤاد كان بيدوس "ابدأ" ويفضل قدام
 * زرار مكتوب عليه "بيفكر..." دقيقة أو اتنين من غير ما يعرف فاضل قد إيه. دلوقتي:
 *
 * 1. start — السيرفر بيجهّز الطلب (RunHandler) ويحفظه، ويرجّع للمتصفح جسم الطلب + تقدير الوقت
 *    من سرعة الجهاز الحقيقية (AiStats).
 * 2. المتصفح بيستقبل الرد كلمة بكلمة — من Ollama مباشرة لو على نفس الجهاز (السيرفر يفضل فاضي
 *    لباقي الصفحات، مهم على ويندوز لأن السيرفر هناك بيخدم طلب واحد بس في المرة)، أو عن طريق
 *    stream هنا، أو كآخر حل server (رد واحد في الآخر، والعدّاد بيمشي بالتقدير بس).
 * 3. complete — المتصفح بيرجّع الرد الكامل، والسيرفر بيطبّقه بنفس الكود القديم بالظبط.
 *
 * الرد اللي راجع من المتصفح بيعدّي على نفس الفلترة/التطهير اللي كان بيعدّي عليها رد Ollama
 * المباشر (filterToKnownKeys/RichTextSanitizer/قوايم الأفعال المسموحة) — صفر ثقة زيادة.
 * الفورمز القديمة (من غير جافاسكريبت) لسه شغالة زي ما هي.
 */
class AiRunController extends Controller
{
    private const HEARTBEAT = '{"heartbeat":true}';

    private const HANDLERS = [
        'create' => CreateProjectRun::class,
        'follow_up' => FollowUpRun::class,
        'suggest' => SuggestContentRun::class,
    ];

    public function start(Request $request, OllamaService $ollama): JsonResponse
    {
        $kind = $request->validate(['kind' => ['required', 'string', Rule::in(array_keys(self::HANDLERS))]])['kind'];

        // طلبات قديمة متسابة (المتصفح اتقفل في النص) — مالهاش لازمة بعد يوم.
        AiRun::where('created_at', '<', now()->subDay())->delete();

        $handler = $this->handler($kind);
        $prepared = $handler->prepare($request);

        if (! $prepared['ok'] || ! isset($prepared['ai'])) {
            return response()->json([
                'done' => true,
                'ok' => (bool) $prepared['ok'],
                'reply' => (string) ($prepared['reply'] ?? ''),
                'redirect' => $prepared['redirect'] ?? null,
            ]);
        }

        $ai = $prepared['ai'];
        $body = $ollama->body($ai['prompt'], $ai['schema'], $ai['task'], stream: true);

        $run = AiRun::create([
            'kind' => $kind,
            'project_id' => $prepared['project_id'] ?? null,
            'status' => AiRun::PENDING,
            'task' => $ai['task'],
            'context_json' => $prepared['context'] ?? [],
            'body_json' => $body,
            'prompt_chars' => mb_strlen($ai['prompt']),
        ]);

        // فحص سريع (ثانية) قبل ما المتصفح يستنى: Ollama مقفول أو النموذج مش متسطّب = نطبّق الفشل
        // على طول (كل نوع عنده تصرّف احتياطي — الإنشاء مثلاً بيعمل المشروع بتخمين الفئة).
        if ($failure = $ollama->preflight()) {
            return response()->json(['done' => true] + $this->apply($run, $failure));
        }

        $model = $run->model();
        $estimate = AiStats::estimate($model, $ai['task']) + [
            'prompt_tokens' => AiStats::promptTokens($model, $run->prompt_chars),
            'loaded' => $ollama->isLoaded($model),
        ];

        return response()->json([
            'done' => false,
            'run' => $run->id,
            'model' => $model,
            'estimate' => $estimate,
            'direct' => $ollama->browserDirect($request->getHost())
                ? ['url' => $ollama->baseUrl().'/api/generate', 'body' => $body]
                : null,
            'stream_url' => route('ai-runs.stream', $run),
            'server_url' => route('ai-runs.server', $run),
            'complete_url' => route('ai-runs.complete', $run),
            'cancel_url' => route('ai-runs.cancel', $run),
        ]);
    }

    // المتصفح مش قادر يكلّم Ollama بنفسه — السيرفر بيوصّله الرد سطر بسطر.
    public function stream(AiRun $run, OllamaService $ollama): StreamedResponse|JsonResponse
    {
        if ($run->status !== AiRun::PENDING) {
            return response()->json(['message' => 'الطلب ده خلص أو اتلغى.'], 409);
        }

        return response()->stream(function () use ($run, $ollama) {
            $send = function (string $line) {
                echo $line."\n";

                // أي buffering في الطريق (output_buffering في php.ini) بيأخّر الكلام لحد الآخر
                // ويبوّظ فكرة البث — فبنزق كل سطر لوحده.
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            };

            // نبضة فورية + نبضة كل 15 ثانية لحد أول كلمة (2026-10-10، السيرفر ورا Cloudflare
            // بيقطع أي رد ساكت 100 ثانية). المتصفح بيتجاهل أي سطر مفيهوش response/done/error.
            $send(self::HEARTBEAT);
            $ollama->stream($run->body_json, $send, fn () => $send(self::HEARTBEAT));
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // آخر حل: السيرفر بيستنى الرد كامل ويطبّقه (العدّاد بيمشي بالتقدير بس).
    public function server(AiRun $run, OllamaService $ollama): StreamedResponse|JsonResponse
    {
        if ($run->status !== AiRun::PENDING) {
            return $this->alreadyHandled($run);
        }

        // الرد بيتبعت JSON عادي في الآخر، بس قبله مسافات فاضية كل 15 ثانية (JSON بيتجاهل المسافات
        // في أوله) — عشان Cloudflare ميقطعش الانتظار بعد 100 ثانية على السيرفر.
        return response()->stream(function () use ($run, $ollama) {
            $pulse = function () {
                echo ' ';
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            };

            $pulse();
            $result = $ollama->runBody($run->body_json, $run->task, $run->prompt_chars, $pulse);

            echo json_encode($this->apply($run, $result), JSON_UNESCAPED_UNICODE);
        }, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function complete(Request $request, AiRun $run, OllamaService $ollama): JsonResponse
    {
        $data = $request->validate([
            'text' => ['nullable', 'string', 'max:200000'],
            'metrics' => ['nullable', 'array'],
            'error' => ['nullable', 'string', Rule::in([AiResult::DOWN, AiResult::MODEL_MISSING, AiResult::TIMEOUT, AiResult::HTTP_ERROR, AiResult::BAD_JSON, AiResult::TOO_LONG])],
            'detail' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($run->status !== AiRun::PENDING) {
            return $this->alreadyHandled($run);
        }

        if (! empty($data['error'])) {
            $result = AiResult::failure($data['error'], (string) ($data['detail'] ?? ''));
        } else {
            $result = $ollama->finish((string) ($data['text'] ?? ''), $this->cleanMetrics($data['metrics'] ?? []), $run->task, $run->model(), $run->prompt_chars);
        }

        return response()->json($this->apply($run, $result));
    }

    public function cancel(AiRun $run, PhotoPoolService $photos): JsonResponse
    {
        $cancelled = AiRun::whereKey($run->id)->where('status', AiRun::PENDING)
            ->update(['status' => AiRun::CANCELLED, 'finished_at' => now()]);

        // صور اترفعت مع طلب اتلغى — مالهاش مكان تتحط فيه.
        if ($cancelled) {
            $photos->discard(array_merge(
                [$run->context_json['image_path'] ?? null],
                (array) ($run->context_json['photos'] ?? []),
            ));
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @return array{ok: bool, reply: string, redirect: string|null}
     */
    private function apply(AiRun $run, AiResult $result): array
    {
        // نفس الرد ميتطبّقش مرتين (دوستين ورا بعض، أو المتصفح عاد المحاولة بعد ما السيرفر طبّق).
        $claimed = AiRun::whereKey($run->id)->where('status', AiRun::PENDING)->update(['status' => AiRun::APPLYING]);

        if (! $claimed) {
            return $this->storedOutcome($run->fresh());
        }

        try {
            $outcome = $this->handler($run->kind)->complete($run, $result);
        } catch (Throwable $e) {
            $run->update(['status' => AiRun::FAILED, 'finished_at' => now()]);

            throw $e;
        }

        $outcome = [
            'ok' => (bool) $outcome['ok'],
            'reply' => (string) $outcome['reply'],
            'redirect' => $outcome['redirect'] ?? null,
            'reload' => (bool) ($outcome['reload'] ?? false),
        ];

        $run->update(['status' => AiRun::DONE, 'result_json' => $outcome, 'finished_at' => now()]);

        return $outcome;
    }

    private function alreadyHandled(AiRun $run): JsonResponse
    {
        return response()->json($this->storedOutcome($run));
    }

    /**
     * @return array{ok: bool, reply: string, redirect: string|null}
     */
    private function storedOutcome(?AiRun $run): array
    {
        if ($run && $run->status === AiRun::DONE && is_array($run->result_json)) {
            return $run->result_json + ['ok' => true, 'reply' => '', 'redirect' => null];
        }

        return [
            'ok' => false,
            'reply' => $run?->status === AiRun::CANCELLED ? 'الطلب ده اتلغى.' : 'الطلب ده بيتطبّق دلوقتي أو فشل — حدّث الصفحة.',
            'redirect' => null,
        ];
    }

    /**
     * أرقام بس (التوقيتات/عدد التوكنز) + done_reason — أي حاجة تانية مبتهمّناش.
     *
     * @param  array<mixed, mixed>  $metrics
     * @return array<string, int|string>
     */
    private function cleanMetrics(array $metrics): array
    {
        $clean = [];

        foreach (['total_duration', 'load_duration', 'prompt_eval_count', 'prompt_eval_duration', 'eval_count', 'eval_duration'] as $key) {
            if (isset($metrics[$key]) && is_numeric($metrics[$key])) {
                $clean[$key] = max(0, (int) $metrics[$key]);
            }
        }

        if (isset($metrics['done_reason']) && is_string($metrics['done_reason'])) {
            $clean['done_reason'] = substr($metrics['done_reason'], 0, 32);
        }

        return $clean;
    }

    private function handler(string $kind): RunHandler
    {
        return app(self::HANDLERS[$kind]);
    }
}
