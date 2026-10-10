<?php

namespace App\Services\Ai\Runs;

use App\Models\AiRun;
use App\Models\Project;
use App\Models\TemplateSlot;
use App\Services\Ai\AiResult;
use App\Services\OllamaService;
use App\Services\RichTextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "✨ صياغة تانية" في المحرر المباشر (2026-10-10) — بيعيد كتابة نص خانة واحدة (عنوان/فقرة) بأسلوب
 * يختاره فؤاد (أحسن/أقصر/أطول/رسمي/ودود + ملاحظة اختيارية). طلب صغير (خانة واحدة) = أسرع بكتير
 * من إعادة توليد الموقع، والنتيجة بتتحفظ مكان النص القديم على طول (المحرر عنده "تراجع").
 *
 * GeneratedSiteController::rewrite() (من غير جافاسكريبت) بيستخدم prepareFor()/apply() نفسهم.
 */
class RewriteSlotRun implements RunHandler
{
    public const STYLES = [
        'better' => 'أوضح وأجذب للزائر، بنفس الطول تقريباً',
        'shorter' => 'أقصر وأقوى — حوالي نص الطول',
        'longer' => 'أطول شوية بتفاصيل مقنعة أكتر',
        'formal' => 'بنبرة راقية ورسمية أكتر',
        'friendly' => 'بنبرة ودودة وقريبة من الناس أكتر',
    ];

    public function __construct(private readonly OllamaService $ollama) {}

    public function prepare(Request $request): array
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'slot_key' => ['required', 'string', 'max:100'],
            'style' => ['required', 'string', Rule::in(array_keys(self::STYLES))],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $project = Project::findOrFail($data['project_id']);

        return $this->prepareFor($project, $data['slot_key'], $data['style'], $data['note'] ?? null) + ['project_id' => $project->id];
    }

    /**
     * @return array{ok: bool, reply?: string, redirect: string, context?: array<string, mixed>, ai?: array{prompt: string, schema: array<string, mixed>, task: string}}
     */
    public function prepareFor(Project $project, string $slotKey, string $style, ?string $note = null): array
    {
        $project->loadMissing(['template.slots', 'site']);
        $redirect = route('projects.site.live-edit', $project);
        $site = $project->site;
        $slot = $this->slot($project, $slotKey);

        if (! $site || ! $slot) {
            return ['ok' => false, 'reply' => 'الخانة دي مش نص ينفع يتعاد صياغته.', 'redirect' => $redirect];
        }

        $current = self::plain($site->content($slot->key, $slot->default_value));
        if ($current === '') {
            return ['ok' => false, 'reply' => 'الخانة فاضية — اكتب فيها حاجة الأول، أو استخدم "اقترح محتوى".', 'redirect' => $redirect];
        }

        $isHeading = $slot->slot_type === 'text';
        $business = trim($project->name.' — '.($project->template->category ?? ''), ' —');
        $note = trim((string) $note);

        $prompt = implode("\n", array_filter([
            'إنت كاتب محتوى عربي لمواقع الأنشطة التجارية الصغيرة.',
            "النشاط: {$business}.",
            'الخانة: "'.$slot->label().'" ('.($isHeading ? 'عنوان قصير' : 'فقرة').').',
            "النص الحالي:\n{$current}",
            '',
            'أعد كتابة النص ده بحيث يبقى '.self::STYLES[$style].'.',
            $note !== '' ? "ملاحظة من صاحب الموقع: {$note}" : null,
            'القواعد:',
            '- نفس اللهجة ونفس المعنى ونفس المعلومات (أرقام/أسماء/مواعيد) — متألّفش معلومات جديدة.',
            $isHeading ? '- عنوان: 12 كلمة بالكتير، من غير نقطة في الآخر.' : '- فقرة: 70 كلمة بالكتير.',
            '- من غير إيموجي، ومن غير علامات تنصيص، ومن غير تنسيق markdown.',
            'رجّع JSON بالشكل ده بالظبط: {"text": "النص الجديد"}',
        ], fn ($line) => $line !== null));

        return [
            'ok' => true,
            'redirect' => $redirect,
            'context' => ['slot_key' => $slot->key, 'previous' => $current],
            'ai' => [
                'prompt' => $prompt,
                'schema' => [
                    'type' => 'object',
                    'properties' => ['text' => ['type' => 'string']],
                    'required' => ['text'],
                ],
                'task' => 'rewrite',
            ],
        ];
    }

    public function complete(AiRun $run, AiResult $result): array
    {
        $project = $run->project()->firstOrFail();
        [$ok, $message] = $this->apply($project, (string) ($run->context_json['slot_key'] ?? ''), $result);

        return ['ok' => $ok, 'reply' => $message, 'redirect' => null, 'reload' => $ok];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public function apply(Project $project, string $slotKey, AiResult $result): array
    {
        if (! $result->ok) {
            return [false, $result->message($this->ollama->model())];
        }

        $project->loadMissing(['template.slots', 'site']);
        $slot = $this->slot($project, $slotKey);
        $text = self::plain($result->data['text'] ?? '');
        // علامات تنصيص حوالين النص — preg بـ/u مش trim() (trim بايت بايت وبيقص حرف عربي).
        $text = preg_replace('/^["\'“”«»\s]+|["\'“”«»\s]+$/u', '', $text) ?? '';

        if (! $slot || $text === '') {
            return [false, 'الذكاء الاصطناعي مرجّعش نص المرة دي — جرّب تاني أو عدّل بإيدك.'];
        }

        $site = $project->site()->firstOrFail();
        $content = $site->content_json ?? [];
        // نص عادي من الذكاء الاصطناعي ← escape الأول (أي < أو & يفضلوا حروف)، وبعدين نفس المطهّر
        // اللي بتعدّي عليه أي قيمة نص قبل ما تترندر بـ{!! !!}.
        $content[$slot->key] = RichTextSanitizer::clean(e(mb_substr($text, 0, $slot->slot_type === 'text' ? 160 : 700)));
        $site->update(['content_json' => $content]);

        return [true, '✓ اتغيّرت صياغة "'.$slot->label().'" — لو مش عاجباك دوس "↩ تراجع".'];
    }

    private function slot(Project $project, string $key): ?TemplateSlot
    {
        return $project->template->slots
            ->whereIn('slot_type', ['text', 'textarea'])
            ->firstWhere('key', $key);
    }

    private static function plain(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }
}
