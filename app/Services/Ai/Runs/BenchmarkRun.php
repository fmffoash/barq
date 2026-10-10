<?php

namespace App\Services\Ai\Runs;

use App\Models\AiRun;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiStatus;
use App\Services\OllamaService;
use Illuminate\Http\Request;

/**
 * "اختبار السرعة" في صفحة إعدادات الذكاء الاصطناعي (2026-10-10) — طلب صغير بنفس طريقة أي طلب
 * حقيقي (نفس العدّاد والبث)، فالسرعة اللي بتتسجّل (AiStats، في OllamaService::finish) هي سرعة
 * الجهاز ده فعلاً، وبعدها كل عدّادات البرنامج بتبقى أدق.
 */
class BenchmarkRun implements RunHandler
{
    public function __construct(
        private readonly OllamaService $ollama,
        private readonly AiStatus $status,
    ) {}

    public function prepare(Request $request): array
    {
        return [
            'ok' => true,
            'redirect' => route('ai-settings.show'),
            'ai' => [
                'prompt' => implode("\n", [
                    'إنت كاتب محتوى لمواقع الأنشطة الصغيرة.',
                    'اكتب فقرة ترحيب قصيرة (3 جمل) لزوار موقع مطعم مصري عائلي، بالعامية المصرية.',
                    'رجّع JSON بالشكل ده بالظبط: {"text": "الفقرة"}',
                ]),
                'schema' => [
                    'type' => 'object',
                    'properties' => ['text' => ['type' => 'string']],
                    'required' => ['text'],
                ],
                'task' => 'benchmark',
            ],
        ];
    }

    public function complete(AiRun $run, AiResult $result): array
    {
        if (! $result->ok) {
            return ['ok' => false, 'reply' => $result->message($this->ollama->model()), 'redirect' => null];
        }

        $speed = $this->status->speed($run->model());
        $reply = $speed['measured']
            ? '✓ السرعة على الجهاز ده: '.$speed['eval_tps'].' توكن/ثانية — إنشاء موقع كامل بياخد حوالي '.$speed['site_minutes'].' دقيقة.'
            : '✓ النموذج رد، بس الرد كان أقصر من إنه يتقاس منه سرعة — جرّب تاني.';

        session()->flash('status', $reply);

        return ['ok' => true, 'reply' => $reply, 'redirect' => route('ai-settings.show')];
    }
}
