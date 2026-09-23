<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Services\OllamaService;
use App\Services\RichTextSanitizer;
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

        // قوالب تانية في نفس الفئة (2026-09-21) — عشان درج "تصميم الموقع" يقدر يعرض زرار
        // "غيّر القالب" بدون ما يخرج المستخدم من وضع التعديل المباشر. مقصورة على نفس الفئة
        // عشان المحتوى (نفس الـ17 مفتاح) يتنقل صح للقالب الجديد، زي بالظبط
        // AiProjectAssistantService::applyChangeTemplate().
        $sameCategoryTemplates = Template::where('is_active', true)
            ->where('kind', 'landing')
            ->where('category', $project->template->category)
            ->where('id', '!=', $project->template_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('site.live-edit', array_merge(
            $renderer->render($site),
            [
                'site' => $site,
                'variant' => $project->variant,
                'slotsBySection' => $project->template->slots->groupBy('section_key'),
                'sameCategoryTemplates' => $sameCategoryTemplates,
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

        // تغيير القالب من درج "تصميم الموقع" في المحرر البصري المباشر (2026-09-21) — بس
        // لقالب تاني من نفس فئة القالب الحالي (عشان المحتوى، نفس الـ17 مفتاح، ينتقل صح زي
        // بالظبط AiProjectAssistantService::applyChangeTemplate()). قيمة فاضية أو غير صالحة
        // أو من فئة مختلفة = تجاهل صامت، مفيش تغيير — نفس سلوك باقي حقول "حدد بنفسك".
        $newTemplateId = $request->integer('template_id') ?: null;
        if ($newTemplateId && $newTemplateId !== $project->template_id) {
            $newTemplate = Template::where('is_active', true)
                ->where('kind', 'landing')
                ->where('category', $project->template->category)
                ->find($newTemplateId);

            if ($newTemplate) {
                $project->update([
                    'template_id' => $newTemplate->id,
                    'template_variant_id' => $newTemplate->defaultVariant()?->id,
                ]);
                $project->load('template.slots');
            }
        }

        $site = $project->site()->firstOrFail();
        $content = $site->content_json ?? [];
        $styleOverrides = $site->style_overrides_json ?? [];

        foreach ($project->template->slots as $slot) {
            $key = $slot->key;

            // تجميع أي تحديثات style للخانة دي في المتغير ده، وندمجها كلها مرة واحدة في
            // الآخر (array_merge مع القديم من style_overrides_json) — مش array_filter مباشر
            // بيكتب فوق الخانة كلها زي ما كان قبل المرحلة 3. لازم دمج مش استبدال دلوقتي لأن
            // خانة واحدة ممكن يبقى ليها كذا نوع تخصيص مستقل مع بعض (لون/خط + ترتيب حر مثلاً).
            $styleUpdates = [];

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

                $styleUpdates['color'] = $color !== '' ? $color : null;
                $styleUpdates['font'] = ($font !== '' && $font !== 'default') ? $font : null;
            }

            // ترتيب حر (نقل/تكبير أي عنصر في الصفحة — المرحلة 3، زي Canva/Wix) — بنفس مبدأ
            // partial-safe فوق، بس مش مقصور على slot_type معيّن خالص (فؤاد أكّد صراحة: أي
            // عنصر، نص وأقسام كمان، مش الصور بس — شوف docs/rich-text-and-image-editing-plan.md).
            // posX/posY نسبة مئوية من حاوية القسم (0-100)، width نسبة مئوية اختيارية للعرض.
            if ($request->has("style.{$key}.posX") || $request->has("style.{$key}.posY") || $request->has("style.{$key}.width")) {
                $posXRaw = $request->input("style.{$key}.posX");
                $posX = is_numeric($posXRaw) && (float) $posXRaw >= 0 && (float) $posXRaw <= 100
                    ? round((float) $posXRaw, 2)
                    : null;

                $posYRaw = $request->input("style.{$key}.posY");
                $posY = is_numeric($posYRaw) && (float) $posYRaw >= 0 && (float) $posYRaw <= 100
                    ? round((float) $posYRaw, 2)
                    : null;

                $widthRaw = $request->input("style.{$key}.width");
                $width = is_numeric($widthRaw) && (float) $widthRaw >= 5 && (float) $widthRaw <= 100
                    ? round((float) $widthRaw, 2)
                    : null;

                $styleUpdates['posX'] = $posX;
                $styleUpdates['posY'] = $posY;
                $styleUpdates['width'] = $width;
            }

            if ($slot->slot_type === 'image') {
                // تكبير/تصغير/تحريك الصورة جوّه إطارها الثابت (المرحلة 2، Word-style مش موجود
                // هنا — ده منتقي زوم+سحب منفصل في المحرر البصري) — بنفس مبدأ partial-safe
                // فوق (has() قبل اللمس، حقل فاضي = مسح التخصيص). رفض بدل "تنضيف" لأي قيمة
                // مش مطابقة تماماً للـregex، نفس فلسفة فحص الألوان فوق.
                if ($request->has("style.{$key}.zoom") || $request->has("style.{$key}.position")) {
                    $zoomRaw = $request->input("style.{$key}.zoom");
                    $zoom = is_numeric($zoomRaw) && (float) $zoomRaw >= 1.0 && (float) $zoomRaw <= 3.0
                        ? round((float) $zoomRaw, 2)
                        : null;

                    $positionRaw = trim((string) $request->input("style.{$key}.position"));
                    $position = null;
                    if (preg_match('/^(\d{1,3})% (\d{1,3})%$/', $positionRaw, $m) && (int) $m[1] <= 100 && (int) $m[2] <= 100) {
                        $position = $positionRaw;
                    }

                    $styleUpdates['zoom'] = $zoom;
                    $styleUpdates['position'] = $position;
                }
            }

            if ($styleUpdates !== []) {
                // array_filter العادي (من غير callback) بيشيل أي قيمة falsy زي 0 — ده غلط
                // هنا لأن posX/posY/width ممكن تبقى 0.0 بالظبط (حافة القسم) وده قيمة صحيحة
                // ومقصودة، مش "مفيش تخصيص". null بس هو معنى "امسح التخصيص".
                $merged = array_filter(
                    array_merge($styleOverrides[$key] ?? [], $styleUpdates),
                    fn ($value) => $value !== null
                );
                if ($merged === []) {
                    unset($styleOverrides[$key]);
                } else {
                    $styleOverrides[$key] = $merged;
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

            // تنسيق نص جزئي (Bold/Italic/تلوين وسط الجملة — المرحلة 1، Word-style) بيبعت
            // innerHTML بدل نص عادي من المحرر البصري. خانات text/textarea بس (list/image/link
            // متلمسش، مالهمش معنى تنسيق جزئي أصلاً). ده الاستثناء الوحيد المتعمّد من قاعدة
            // "صفر {!! !!}" في CLAUDE.md الجذر — القيمة المطهّرة هنا بس هي اللي بترندر بـ
            // {!! !!} في الـ16 layout (راجع RichTextSanitizer للتفاصيل).
            $content[$key] = in_array($slot->slot_type, ['text', 'textarea'], true)
                ? RichTextSanitizer::clean((string) $value)
                : $value;
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

        // تخين/مَيَلان/حجم الخط العام (Phase 16، 2026-09-21) — نفس مبدأ font_override بالحرف:
        // قيمة فاضية أو غير صالحة = null (يعني ورّث من القالب). أوزان محدودة عمداً (مش أي
        // رقم عشوائي) عشان تفضل متوافقة مع أوزان @fontsource المتحمّلة فعلياً لكل خط —
        // المتصفح بيختار أقرب وزن متاح لو المطلوب مش موجود بالظبط (سلوك عادي، صفر خطأ).
        $fontWeightOverride = (string) $request->input('font_weight_override');
        $fontWeightOverride = in_array($fontWeightOverride, ['400', '500', '600', '700', '800'], true) ? $fontWeightOverride : null;

        $fontStyleOverride = (string) $request->input('font_style_override');
        $fontStyleOverride = in_array($fontStyleOverride, ['normal', 'italic'], true) ? $fontStyleOverride : null;

        $fontSizeScaleOverride = $request->input('font_size_scale_override');
        $fontSizeScaleOverride = is_numeric($fontSizeScaleOverride) && $fontSizeScaleOverride >= 0.7 && $fontSizeScaleOverride <= 1.5
            ? round((float) $fontSizeScaleOverride, 2)
            : null;

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
            'font_weight_override' => $fontWeightOverride,
            'font_style_override' => $fontStyleOverride,
            'font_size_scale_override' => $fontSizeScaleOverride,
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

            // $value مطهّر بالفعل لخانات text/textarea (OllamaService::filterToKnownKeys بقى
            // بينادي RichTextSanitizer::clean() مركزياً — المرحلة 1).
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
