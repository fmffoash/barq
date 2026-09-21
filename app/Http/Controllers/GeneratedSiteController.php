<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TemplateVariant;
use App\Services\OllamaService;
use App\Services\SiteExportService;
use App\Services\SiteRenderer;
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
        $project->loadMissing(['template.slots', 'site', 'variant']);

        $slotsBySection = $project->template->slots->groupBy('section_key');

        return view('projects.site-edit', [
            'project' => $project,
            'site' => $project->site,
            'slotsBySection' => $slotsBySection,
        ]);
    }

    // محرر بصري مباشر (WYSIWYG click-to-edit، شوف docs/wysiwyg-editor-plan.md) — بيرندر
    // نفس الموقع الحقيقي بالظبط (نفس SiteRenderer::render() المستخدمة في site.show) لكن
    // جوّه route محمي بـ auth، وبيحقن سكريبت/CSS وضع التعديل عن طريق $editable=true بس.
    // المسار العام (`/site/{slug}`) صفر تأثير عليه خالص — مفيش query parameter بيفعّل تعديل،
    // ده route منفصل تماماً (راجع "قيد أمان إجباري" في ملف الخطة).
    public function liveEdit(Project $project, SiteRenderer $renderer): View
    {
        $project->loadMissing(['template.slots', 'site', 'variant']);
        $site = $project->site()->firstOrFail();

        return view('site.live-edit', array_merge(
            $renderer->render($site),
            [
                'site' => $site,
                'variant' => $project->variant,
                'slotsBySection' => $project->template->slots->groupBy('section_key'),
            ]
        ));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->loadMissing('template.slots');

        // نوع/حجم أي صورة مرفوعة — بدون التحقق ده أي ملف (حتى .php أو SVG فيه سكريبت) كان
        // هيتخزن في storage/site-images/ العامة زي ما هو. استبعاد svg عمداً (مش داخل mimes
        // تحت) عشان ملف SVG ممكن يحتوي <script> جواه فعلياً.
        $request->validate([
            'content_files.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:8192'],
        ]);

        $site = $project->site()->firstOrFail();
        $content = $site->content_json ?? [];
        $styleOverrides = $site->style_overrides_json ?? [];

        foreach ($project->template->slots as $slot) {
            $key = $slot->key;

            // تخصيص لون/خط الخانة دي بس (Phase 8) — بيتقرا لنص/فقرة/قايمة بس (صفر لون/خط
            // لصورة أو زرار رابط، مالهمش معنى "شكل كتابة"). حقل فاضي بيمسح التخصيص القديم
            // بدل ما يسيب قيمة فاضية عالقة في الـ JSON. القيمتين بيتحطوا مباشرة جوّه CSS
            // (`color: {value} !important;` و`var(--font-{value})`) في site/document.blade.php،
            // فلازم نتحقق من شكلهم هنا قبل التخزين — مش بس تنظيف شكلي، ده اللي بيمنع أي قيمة
            // غريبة (مسافة/فاصلة منقوطة/قوس) تكسر الـ CSS block أو تحقن قواعد تانية جواه.
            //
            // ⚠️ partial-safe إجباري (المحرر البصري المباشر، docs/wysiwyg-editor-plan.md):
            // الفورم القديم (site-edit.blade.php) بيبعت style.{key}.font لكل خانة نص دايماً
            // (الـ <select> مش disabled أبداً)، فمكانش فارق قبل كده. لكن حفظ AJAX تدريجي
            // (خانة واحدة بس في كل نداء) لازم يقدر يسيب باقي الخانات زي ما هي — من غير
            // `has()` هنا، أي نداء بيبعت style لخانة واحدة كان هيمسح تخصيص كل الخانات التانية
            // بالغلط (لأن style.{key}.font بتاعهم كان هيتقرا فاضي ويتفسّر "امسح التخصيص").
            if (
                in_array($slot->slot_type, ['text', 'textarea', 'list'], true)
                && ($request->has("style.{$key}.color") || $request->has("style.{$key}.font"))
            ) {
                $color = trim((string) $request->input("style.{$key}.color"));
                if ($color !== '' && ! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                    $color = '';
                }

                $font = (string) $request->input("style.{$key}.font");
                if ($font !== '' && $font !== 'default' && ! array_key_exists($font, TemplateVariant::FONTS)) {
                    $font = '';
                }

                if ($color !== '' || ($font !== '' && $font !== 'default')) {
                    $styleOverrides[$key] = array_filter([
                        'color' => $color !== '' ? $color : null,
                        'font' => ($font !== '' && $font !== 'default') ? $font : null,
                    ]);
                } else {
                    unset($styleOverrides[$key]);
                }
            }

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

        $site->update([
            'content_json' => $content,
            'style_overrides_json' => $styleOverrides === [] ? null : $styleOverrides,
            ...$this->designOverrides($request, $project),
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'تم حفظ محتوى الموقع.');
    }

    // تخصيص شكل الموقع ده بالكامل (ألوان/خط/ترتيب أقسام) — مستقل عن نسخة القالب المشتركة،
    // عشان تعديل تصميم مشروع واحد ميأثرش على مشاريع تانية شايلة نفس القالب. كل جزء بيتفعّل
    // بس لو الأدمن شيّك على "استخدم .. مختلف لهذا الموقع" — من غيرها القيمة بترجع null
    // (يعني ورّث من نسخة القالب زي ما كان قبل الميزة دي، 2026-09-20).
    private function designOverrides(Request $request, Project $project): array
    {
        $colorsOverride = null;
        if ($request->boolean('use_custom_colors')) {
            $raw = json_decode((string) $request->input('colors_override'), true);
            if (is_array($raw)) {
                $colorsOverride = array_filter(
                    $raw,
                    fn ($value) => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value)
                );
            }
        }

        $fontOverride = (string) $request->input('font_override');
        $fontOverride = ($fontOverride !== '' && array_key_exists($fontOverride, TemplateVariant::FONTS)) ? $fontOverride : null;

        $sectionsOverride = null;
        if ($request->boolean('use_custom_sections')) {
            $raw = json_decode((string) $request->input('sections_override'), true);
            if (is_array($raw)) {
                $validKeys = $project->template->slots->pluck('section_key')->unique();
                $sectionsOverride = collect($raw)
                    ->filter(fn ($key) => is_string($key) && $validKeys->contains($key))
                    ->values()
                    ->all();
            }
        }

        return [
            'colors_override_json' => $colorsOverride,
            'font_override' => $fontOverride,
            'sections_override_json' => $sectionsOverride,
        ];
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
