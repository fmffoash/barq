<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\OllamaService;
use App\Services\SiteExportService;
use App\Services\WordPressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

// تعبئة محتوى الموقع الناتج من مشروع معيّن يدوياً — خانة بخانة حسب تعريفها في القالب —
// ونشر/إلغاء نشر الموقع لما المحتوى يخلص.
class GeneratedSiteController extends Controller
{
    public function edit(Project $project): View
    {
        $project->loadMissing(['template.slots', 'site']);

        $slotsBySection = $project->template->slots->groupBy('section_key');

        return view('projects.site-edit', [
            'project' => $project,
            'site' => $project->site,
            'slotsBySection' => $slotsBySection,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->loadMissing('template.slots');

        $site = $project->site()->firstOrFail();
        $content = $site->content_json ?? [];

        foreach ($project->template->slots as $slot) {
            $key = $slot->key;

            if ($slot->slot_type === 'image') {
                if ($request->hasFile("content_files.{$key}")) {
                    $path = $request->file("content_files.{$key}")->store('site-images', 'public');
                    $content[$key] = '/storage/'.$path;
                }

                continue;
            }

            if (! $request->has("content.{$key}")) {
                continue;
            }

            $value = $request->input("content.{$key}");

            if ($slot->slot_type === 'list') {
                $content[$key] = collect(explode("\n", (string) $value))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->values()
                    ->all();

                continue;
            }

            $content[$key] = $value;
        }

        $site->update(['content_json' => $content]);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'تم حفظ محتوى الموقع.');
    }

    // بتاخد وصف قصير للنشاط وتقترح محتوى بالذكاء الاصطناعي (Ollama) للخانات الفاضية بس —
    // أي خانة اتكتب فيها حاجة يدوي بالفعل بتفضل زي ما هي، صفر دعس على محتوى الأدمن.
    public function suggest(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'business_description' => ['required', 'string', 'max:500'],
        ]);

        $project->loadMissing('template.slots');

        $site = $project->site()->firstOrFail();
        $content = $site->content_json ?? [];

        $suggestions = app(OllamaService::class)->suggestContent(
            $project->template,
            $validated['business_description'],
        );

        if ($suggestions === []) {
            return redirect()
                ->route('projects.site.edit', $project)
                ->with('status', 'معرفناش نقترح محتوى دلوقتي — النموذج مش متاح. كمّل الخانات يدوي.');
        }

        $filledCount = 0;

        foreach ($suggestions as $key => $value) {
            if (! $this->slotIsEmpty($content[$key] ?? null)) {
                continue;
            }

            $content[$key] = $value;
            $filledCount++;
        }

        $site->update(['content_json' => $content]);

        $message = $filledCount > 0
            ? "تم اقتراح محتوى لـ {$filledCount} خانة فاضية — راجعها وعدّل اللي محتاجه."
            : 'كل الخانات معبّاة بالفعل — مفيش خانة فاضية تتقترح ليها محتوى.';

        return redirect()->route('projects.site.edit', $project)->with('status', $message);
    }

    private function slotIsEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return $value === [];
        }

        return $value === null || trim((string) $value) === '';
    }

    public function publish(Project $project): RedirectResponse
    {
        $site = $project->site()->firstOrFail();

        $site->update([
            'status' => 'published',
            'last_generated_at' => now(),
        ]);

        if ($project->status === 'draft') {
            $project->update(['status' => 'generated']);
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'الموقع دلوقتي منشور ومتاح للمعاينة.');
    }

    public function unpublish(Project $project): RedirectResponse
    {
        $project->site()->firstOrFail()->update(['status' => 'archived']);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'تم إلغاء نشر الموقع.');
    }

    // بيولّد نسخة تصدير كاملة كملفات ثابتة (HTML/CSS/الخطوط/الصور) وينزّلها zip واحد — شوف
    // SiteExportService لتفاصيل البناء. لو القالب مش "landing" (يعني wordpress، مالوش رندر
    // حقيقي أصلاً لسه) بيرجع رسالة واضحة بدل ما يحاول يصدّر صفحة "قريباً" بلا فايدة.
    public function export(Project $project): Response
    {
        $project->loadMissing(['template.slots', 'variant']);
        $site = $project->site()->firstOrFail();

        try {
            $zipPath = app(SiteExportService::class)->export($site);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('projects.show', $project)
                ->with('status', $e->getMessage());
        }

        return response()->download($zipPath)->deleteFileAfterSend();
    }

    // بيعمل site فعلي لأول مرة على شبكة WordPress Multisite — متاح بس للمشاريع من نوع
    // "ووردبريس". لو الموقع اتعمل بالفعل من قبل، النداء idempotent (WordPressService بترجع
    // true على طول من غير نداء تاني). لو الشبكة مش متاحة دلوقتي، الأدمن بيرجع رسالة واضحة
    // ويقدر يحاول تاني من نفس الزرار وقت ما يحب.
    public function provisionWordPress(Project $project): RedirectResponse
    {
        $project->loadMissing('template');
        $site = $project->site()->firstOrFail();

        try {
            $succeeded = app(WordPressService::class)->provisionSite($project, $site);
        } catch (RuntimeException $e) {
            return redirect()->route('projects.show', $project)->with('status', $e->getMessage());
        }

        $message = $succeeded
            ? 'اتعمل site فعلي على شبكة ووردبريس. تقدر دلوقتي تبعتله المحتوى من زرار "حدّث المحتوى على ووردبريس".'
            : 'معرفناش نتواصل مع شبكة ووردبريس دلوقتي — حاول تاني بعد شوية.';

        return redirect()->route('projects.show', $project)->with('status', $message);
    }

    // بيبعت محتوى الموقع الحالي (content_json — نفس اللي اتملى بالفورم اليدوي أو باقتراح
    // الذكاء الاصطناعي، صفر فورم منفصل مخصّص لووردبريس) لموقع اتعمل بالفعل على الشبكة.
    public function pushWordPressContent(Project $project): RedirectResponse
    {
        $site = $project->site()->firstOrFail();

        $succeeded = app(WordPressService::class)->pushContent($site);

        $message = $succeeded
            ? 'تم تحديث محتوى موقع ووردبريس بالمحتوى الحالي.'
            : 'معرفناش نبعت المحتوى للشبكة دلوقتي — حاول تاني بعد شوية.';

        return redirect()->route('projects.show', $project)->with('status', $message);
    }
}
