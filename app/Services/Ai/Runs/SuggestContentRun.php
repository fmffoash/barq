<?php

namespace App\Services\Ai\Runs;

use App\Models\AiRun;
use App\Models\Project;
use App\Services\Ai\AiResult;
use App\Services\OllamaService;
use Illuminate\Http\Request;

/**
 * "اقترح محتوى" في صفحة تعبئة محتوى المشروع — بيملّي الخانات الفاضية بس، وأي خانة فؤاد كتب
 * فيها بإيده بتفضل زي ما هي. (2026-10-08) الطلب نفسه بقى للخانات الفاضية بس بدل كل الخانات —
 * رد أقصر = وقت أقل على الجهاز، ومفيش فايدة من كتابة محتوى هيترمي.
 *
 * GeneratedSiteController::suggest() (المسار من غير جافاسكريبت) بيستخدم prepareFor()/apply()
 * نفسهم، فالمنطق واحد في المسارين.
 */
class SuggestContentRun implements RunHandler
{
    public function __construct(private readonly OllamaService $ollama) {}

    public function prepare(Request $request): array
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'business_description' => ['required', 'string', 'max:500'],
        ]);

        $project = Project::findOrFail($data['project_id']);

        return $this->prepareFor($project, $data['business_description']) + ['project_id' => $project->id];
    }

    /**
     * @return array{ok: bool, reply?: string, redirect: string, context?: array<string, mixed>, ai?: array{prompt: string, schema: array<string, mixed>, task: string}}
     */
    public function prepareFor(Project $project, string $description): array
    {
        $project->loadMissing(['template.slots', 'site']);
        $redirect = route('projects.site.edit', $project);
        $site = $project->site;

        if (! $site) {
            return ['ok' => false, 'reply' => 'المشروع ده مالوش موقع ناتج — راجعه من صفحته العادية.', 'redirect' => $redirect];
        }

        $content = $site->content_json ?? [];
        $emptySlots = $project->template->slots
            ->whereIn('slot_type', ['text', 'textarea', 'list'])
            ->filter(fn ($slot) => self::isEmpty($content[$slot->key] ?? null))
            ->values();

        if ($emptySlots->isEmpty()) {
            return ['ok' => true, 'reply' => 'كل الخانات معبّاة بالفعل — مفيش خانة فاضية تتقترح ليها محتوى.', 'redirect' => $redirect];
        }

        return [
            'ok' => true,
            'redirect' => $redirect,
            'context' => ['description' => $description],
            'ai' => [
                'prompt' => $this->ollama->buildPrompt($emptySlots, $description),
                'schema' => $this->ollama->contentSchema($emptySlots),
                'task' => 'content',
            ],
        ];
    }

    public function complete(AiRun $run, AiResult $result): array
    {
        $project = $run->project()->firstOrFail();
        $message = $this->apply($project, $result);

        // الصفحة اللي المتصفح هيروحلها بعد كده بتعرض الرسالة دي فوق (زي المسار العادي).
        session()->flash('status', $message);

        return ['ok' => true, 'reply' => $message, 'redirect' => route('projects.site.edit', $project)];
    }

    // بيكتب الاقتراحات في الخانات اللي لسه فاضية بس، ويرجّع رسالة لفؤاد.
    public function apply(Project $project, AiResult $result): string
    {
        if (! $result->ok) {
            return $result->message($this->ollama->model());
        }

        $project->loadMissing(['template.slots', 'site']);
        $site = $project->site()->firstOrFail();
        $content = $site->content_json ?? [];

        // filterToKnownKeys بيطهّر خانات text/textarea (RichTextSanitizer) وبيشيل أي مفتاح غريب.
        $suggestions = $this->ollama->filterToKnownKeys(
            $project->template->slots->whereIn('slot_type', ['text', 'textarea', 'list']),
            $result->data ?? [],
        );

        if ($suggestions === []) {
            return 'الذكاء الاصطناعي مرجّعش محتوى المرة دي — جرّب تاني أو كمّل الخانات يدوي.';
        }

        $filledCount = 0;

        foreach ($suggestions as $key => $value) {
            if (! self::isEmpty($content[$key] ?? null)) {
                continue;
            }

            $content[$key] = $value;
            $filledCount++;
        }

        $site->update(['content_json' => $content]);

        return $filledCount > 0
            ? "تم اقتراح محتوى لـ {$filledCount} خانة فاضية — راجعها وعدّل اللي محتاجه."
            : 'كل الخانات معبّاة بالفعل — مفيش خانة فاضية تتقترح ليها محتوى.';
    }

    public static function isEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return $value === [];
        }

        return $value === null || trim((string) $value) === '';
    }
}
