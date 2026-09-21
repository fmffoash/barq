<?php

namespace App\Services;

use App\Console\Commands\SeedTemplateLibrary;
use App\Models\AiChatMessage;
use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * مساعد "أنشئ/عدّل مشروع بالذكاء الاصطناعي" (Phase 10) — واجهة شات بدل الفورم التقليدي:
 * الأدمن بيكتب وصف النشاط + أي بيانات (منظمة أو ملخبطة) في رسالة واحدة، والمساعد بيختار
 * أنسب قالب من مكتبة الـ300 ويملّي خاناته منها، وبيرد كمحادثة. بعد الإنشاء، أي رسالة تانية
 * على نفس المشروع بتتفسّر كطلب تعديل (غيّر القالب / عدّل خانة / غيّر لون أو خط).
 *
 * كل الأفعال بتتطبّق على نفس أعمدة/جداول الفورم العادي (project.template_id،
 * generated_site.content_json/colors_override_json/font_override) — صفر منطق تخزين
 * جديد، بس طريقة إدخال تانية. لو الذكاء الاصطناعي مش متاح أو رجّع حاجة مش مفهومة، بيرجّع
 * رسالة واضحة للأدمن بدل ما يفشل بصمت أو يكسر المشروع.
 */
class AiProjectAssistantService
{
    public function __construct(private readonly OllamaService $ollama) {}

    /**
     * أول رسالة — لسه معندناش مشروع. بتختار فئة وقالب مناسبين، تعمل المشروع، وتملّي محتواه.
     */
    public function createFromMessage(string $message): array
    {
        $categories = Template::query()
            ->where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        if ($categories->isEmpty()) {
            return ['ok' => false, 'reply' => 'مفيش قوالب متاحة خالص دلوقتي — لازم تتضاف فئات وقوالب الأول.'];
        }

        // نداء واحد بس بيرجّع اختيار القالب والمحتوى مع بعض (بدل نداءين متتاليين) — كل نداء
        // على قالب حقيقي (17 خانة) بياخد 26-49 ثانية لوحده في التجربة الحية (2026-09-20)،
        // فنداءين ورا بعض كانوا بيخطّوا مهلة nginx (504 Gateway Timeout بعد 60 ثانية بالظبط).
        $picked = $this->ollama->generateJson($this->buildCreatePrompt($message, $categories));

        $category = is_array($picked) && in_array($picked['category'] ?? null, $categories->all(), true)
            ? $picked['category']
            : $categories->first();

        $styleHint = is_string($picked['style_hint'] ?? null) ? trim($picked['style_hint']) : '';
        $projectName = is_string($picked['project_name'] ?? null) && trim($picked['project_name']) !== ''
            ? trim($picked['project_name'])
            : Str::limit($message, 40, '');

        $template = $this->pickTemplateInCategory($category, $styleHint);

        if (! $template) {
            return ['ok' => false, 'reply' => 'معرفتش ألاقي قالب مناسب — جرّب توصف النشاط بشكل مختلف شوية.'];
        }

        $variant = $template->defaultVariant();
        $template->loadMissing('slots');
        $suggestableSlots = $template->slots->where('slot_type', '!=', 'image');
        // أحياناً qwen3 بيرجّع "content" كـstring فيه JSON متكرر ترميزه (double-encoded)
        // بدل object متداخل فعلي — رغم إن الـprompt طالب object بالحرف (تفاوت طبيعي لنموذج
        // صغير مع JSON متداخل، لوحظ حياً 2026-09-20). نتعامل مع الحالتين بدل ما نسيب
        // المشروع من غير محتوى خالص.
        $rawContentField = $picked['content'] ?? null;
        $rawContent = match (true) {
            is_array($rawContentField) => $rawContentField,
            is_string($rawContentField) => (array) (json_decode($rawContentField, true) ?? []),
            default => [],
        };
        $content = $this->filterToKnownSlotKeys($suggestableSlots, $rawContent);

        // fallback دفاعي: لو النداء المدموج فشل يرجّع محتوى (النموذج مش متاح، أو رجّع شكل
        // غير متوقع) بس القالب اتحدد صح، نجرّب نداء suggestContent العادي لوحده كـPlan B
        // بدل ما نسيب المشروع من غير محتوى خالص.
        if ($content === [] && $picked !== null) {
            $content = $this->ollama->suggestContent($template, $message);
        }

        $project = DB::transaction(function () use ($template, $variant, $projectName, $content) {
            $project = Project::create([
                'template_id' => $template->id,
                'template_variant_id' => $variant?->id,
                'name' => $projectName,
                'slug' => $this->uniqueProjectSlug($projectName),
                'status' => 'draft',
            ]);

            GeneratedSite::create([
                'project_id' => $project->id,
                'slug' => $this->uniqueSiteSlug($projectName),
                'content_json' => $content,
            ]);

            return $project;
        });

        $filledCount = count($content);
        $reply = $filledCount > 0
            ? "تمام! عملتلك مشروع \"{$projectName}\" بقالب \"{$template->name}\" (فئة {$category})، ومليت {$filledCount} خانة بمحتوى مناسب. اتفرج عليه تحت، وقولي لو عايز تغيّر حاجة."
            : "عملتلك مشروع \"{$projectName}\" بقالب \"{$template->name}\" (فئة {$category})، بس معرفتش أقترح محتوى دلوقتي — النموذج مش متاح، كمّل الخانات يدوي أو جرّب تاني بعد شوية.";

        $this->logMessages($project, $message, $reply);

        return ['ok' => true, 'project' => $project, 'reply' => $reply];
    }

    /**
     * رسالة تانية على مشروع موجود بالفعل — تفسير الطلب كفعل (تغيير قالب/محتوى/لون/خط) وتنفيذه.
     */
    public function handleFollowUp(Project $project, string $message): string
    {
        $project->loadMissing(['template.slots', 'variant', 'site']);
        $site = $project->site;

        if (! $site) {
            return 'المشروع ده مالوش موقع ناتج خالص — حاجة غريبة، راجع المشروع من صفحته العادية.';
        }

        $decision = $this->ollama->generateJson($this->buildFollowUpPrompt($project, $site, $message));

        if (! is_array($decision)) {
            $reply = 'معرفتش أفهم طلبك دلوقتي — النموذج مش متاح أو الرد مش واضح. جرّب تاني أو عدّل يدوي من صفحة المشروع.';
            $this->logMessages($project, $message, $reply);

            return $reply;
        }

        $action = $decision['action'] ?? 'none';
        $knownActions = ['change_template', 'update_content', 'update_colors', 'update_font'];

        // شبكة أمان: نموذج صغير زي qwen3:8b أحياناً بيرجّع "none" أو قيمة action مش من
        // الخمسة المتفق عليها رغم إن الرسالة واضحة (لوحظ حياً 2026-09-20 — "غيّر القالب"
        // اترفضت مرتين، مرة بـaction=none ومرة بـaction غريب مش في القايمة، رغم إن رد
        // النموذج النصي نفسه فاهم المقصود صح). لو الرسالة فيها كلمة قالب/شكل/تصميم صريحة
        // والفعل مش واحد من الأربعة المعروفة، نصحّحه لـchange_template بدل رد "مش فاهم".
        if (! in_array($action, $knownActions, true) && preg_match('/قالب|شكل|تصميم|تخطيط/u', $message)) {
            $action = 'change_template';
            $decision['category'] ??= $project->template->category;
        }

        $reply = match ($action) {
            'change_template' => $this->applyChangeTemplate($project, $decision),
            'update_content' => $this->applyUpdateContent($project, $site, $decision),
            'update_colors' => $this->applyUpdateColors($site, $decision),
            'update_font' => $this->applyUpdateFont($site, $decision),
            default => is_string($decision['reply'] ?? null) && trim($decision['reply']) !== ''
                ? $decision['reply']
                : 'مش متأكد فاهم طلبك — ممكن توضحه أكتر؟',
        };

        $this->logMessages($project, $message, $reply);

        return $reply;
    }

    private function applyChangeTemplate(Project $project, array $decision): string
    {
        $categories = Template::where('is_active', true)->distinct()->pluck('category');
        $category = in_array($decision['category'] ?? null, $categories->all(), true)
            ? $decision['category']
            : $project->template->category;

        $styleHint = is_string($decision['style_hint'] ?? null) ? trim($decision['style_hint']) : '';

        $newTemplate = $this->pickTemplateInCategory($category, $styleHint, exceptId: $project->template_id);

        if (! $newTemplate) {
            return 'معرفتش ألاقي قالب تاني مناسب في نفس الفئة — ممكن توضح أكتر عايز شكل إيه؟';
        }

        // كل قوالب المكتبة (SeedTemplateLibrary) بتشارك نفس الـ17 مفتاح خانة بالحرف، فمحتوى
        // الموقع الحالي بيتنقل زي ما هو للقالب الجديد من غير أي تعديل — لو القالب الجديد
        // مش من نفس المكتبة (قالب مخصّص أضافه الأدمن يدوي بمفاتيح مختلفة)، الخانات اللي
        // مش موجودة فيه بترجع فاضية تلقائي (مفيش حذف أو كسر، بس مش هتتعرض).
        $project->update([
            'template_id' => $newTemplate->id,
            'template_variant_id' => $newTemplate->defaultVariant()?->id,
        ]);

        return "تمام، غيّرتلك القالب لـ\"{$newTemplate->name}\" (فئة {$category}). المحتوى اللي كان موجود اتنقل زي ما هو — اتفرج على الشكل الجديد.";
    }

    private function applyUpdateContent(Project $project, GeneratedSite $site, array $decision): string
    {
        $changes = is_array($decision['changes'] ?? null) ? $decision['changes'] : [];
        $slotsByKey = $project->template->slots->keyBy('key');
        $content = $site->content_json ?? [];
        $updatedLabels = [];

        foreach ($changes as $key => $value) {
            if (! is_string($key) || ! $slotsByKey->has($key)) {
                continue;
            }

            $slot = $slotsByKey->get($key);

            if ($slot->slot_type === 'list') {
                $content[$key] = is_array($value)
                    ? array_values(array_filter(array_map('trim', array_map('strval', $value))))
                    : array_values(array_filter(array_map('trim', explode("\n", (string) $value))));
            } else {
                $content[$key] = trim(is_array($value) ? implode(' ', $value) : (string) $value);
            }

            $updatedLabels[] = $slot->label();
        }

        if ($updatedLabels === []) {
            return 'معرفتش أحدد أي خانة تتعدّل من طلبك — ممكن تكون أوضح؟ (مثلاً: "غيّر العنوان الرئيسي لكذا")';
        }

        $site->update(['content_json' => $content]);

        return 'تمام، عدّلت: '.implode('، ', $updatedLabels).'.';
    }

    private function applyUpdateColors(GeneratedSite $site, array $decision): string
    {
        $colors = is_array($decision['colors'] ?? null) ? $decision['colors'] : [];
        $valid = array_filter(
            $colors,
            fn ($value, $key) => is_string($value)
                && preg_match('/^#[0-9a-fA-F]{6}$/', $value)
                && in_array($key, ['primary', 'background', 'surface', 'text', 'muted'], true),
            ARRAY_FILTER_USE_BOTH
        );

        if ($valid === []) {
            return 'معرفتش أفهم الألوان اللي عايزها بالظبط — ممكن تقول مثلاً "خليه أزرق غامق"؟';
        }

        $current = $site->colors_override_json ?? [];
        $site->update(['colors_override_json' => array_merge($current, $valid)]);

        return 'تمام، غيّرت الألوان — اتفرج على الموقع اتغيّر إزاي.';
    }

    private function applyUpdateFont(GeneratedSite $site, array $decision): string
    {
        $font = $decision['font'] ?? null;

        if (! is_string($font) || ! array_key_exists($font, TemplateVariant::FONTS)) {
            return 'معرفتش أحدد خط بالظبط من طلبك — ممكن تسمي خط معروف زي "كايرو" أو "تجوال"؟';
        }

        $site->update(['font_override' => $font]);

        return 'تمام، غيّرت الخط لـ"'.TemplateVariant::FONTS[$font].'".';
    }

    /**
     * بتدوّر على قالب داخل فئة معيّنة، بـ3 مستويات أولوية: (1) اسم القالب نفسه فيه كلمة
     * الطابع المطلوب حرفياً (زي "فاخر" جوه "مطعم فاخر")، (2) طابع القالب (layout) شخصيته
     * قريبة من الكلمة المطلوبة (بنستخدم نفس LAYOUT_KEYWORDS بتاعة SeedTemplateLibrary —
     * بيغطي حالة إن النموذج رجّع كلمة زي "راقي" مش موجودة حرفياً في اسم القالب بس فعلاً
     * بتوصف تصميم glass/framed/signature)، (3) عشوائي تماماً.
     *
     * ⚠️ (اتصلح 2026-09-21) المستوى الأخير **لازم يفضل عشوائي مش "أول قالب أبجدياً"** —
     * كان قبل كده `$templates->first()` بعد استبعاد القالب الحالي بس، فلو تصنيف الطابع فشل
     * (بيحصل مع نموذج صغير زي qwen3:8b)، "غيّر القالب" كان بيدور بين نفس القالبين بس كل
     * مرة (الأول أبجدياً، وبعد استبعاده القالب اللي قبله يرجع الأول تاني) بدل ما يجرّب حاجة
     * فعلاً مختلفة — لوحظ حياً: طلب "قالب فاخر" مرتين ورجع بينهم على نفس القالب الأصلي.
     *
     * $exceptId بيستبعد القالب الحالي (لما المستخدم يطلب "غيّر القالب" — الرد لازم يكون
     * قالب مختلف فعلاً مش نفسه).
     */
    private function pickTemplateInCategory(string $category, string $styleHint, ?int $exceptId = null): ?Template
    {
        $query = Template::where('is_active', true)->where('category', $category);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $templates = $query->with('variants')->get();

        if ($templates->isEmpty()) {
            return null;
        }

        if ($styleHint !== '') {
            $match = $templates->first(fn (Template $t) => str_contains($t->name, $styleHint));
            if ($match) {
                return $match;
            }

            $byLayout = $templates->first(function (Template $t) use ($styleHint) {
                foreach (SeedTemplateLibrary::LAYOUT_KEYWORDS[$t->layout] ?? [] as $keyword) {
                    if (str_contains($keyword, $styleHint) || str_contains($styleHint, $keyword)) {
                        return true;
                    }
                }

                return false;
            });

            if ($byLayout) {
                return $byLayout;
            }
        }

        return $templates->random();
    }

    /**
     * نداء واحد بيرجّع اختيار الفئة/الاسم/الطابع **ومحتوى الموقع كله مع بعض** — بدل نداءين
     * متتاليين (كانوا بيخطّوا مهلة nginx، شوف تعليق createFromMessage). خانات المحتوى هنا
     * ثابتة (نفس الـ17 مفتاح المشتركة بين كل قوالب SeedTemplateLibrary بالحرف) بدل ما تتقرا
     * من قالب محدد — أصلاً القالب لسه مش متحدد وقت بناء البرومبت ده.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $categories
     */
    private function buildCreatePrompt(string $message, $categories): string
    {
        $list = $categories->map(fn ($c) => "- {$c}")->implode("\n");

        return <<<PROMPT
انت بتساعد تنشئ موقع ويب لنشاط تجاري، بناءً على وصف ممكن يكون فيه بيانات مختلطة (اسم نشاط،
تليفون، خدمات، أي حاجة صاحب المشروع كتبها من غير ترتيب معيّن).

وصف/بيانات المشروع اللي كتبها الأدمن:
{$message}

الفئات المتاحة بالظبط (اختار واحدة منها بالحرف زي ما هي مكتوبة):
{$list}

رجّع إجابتك في صورة JSON object واحد بس بالمفاتيح دي بالظبط:
{
  "category": "اسم الفئة بالحرف من القايمة فوق",
  "project_name": "اسم قصير مناسب للمشروع (لو فيه اسم واضح في الوصف استخدمه، وإلا استنتج اسم مناسب من النشاط)",
  "style_hint": "كلمة أو كلمتين تصف الطابع المطلوب لو واضح من الوصف زي فاخر أو بسيط أو عصري أو تقليدي، وإلا سيبها فاضية",
  "content": {
    "hero_title": "نص قصير جذاب",
    "hero_subtitle": "فقرة نص متوسطة الطول",
    "about_title": "نص قصير",
    "about_body": "فقرة نص متوسطة الطول",
    "services_title": "نص قصير",
    "services_list": ["خدمة قصيرة", "خدمة قصيرة", "خدمة قصيرة"],
    "gallery_title": "نص قصير",
    "testimonials_title": "نص قصير",
    "testimonials_list": ["رأي عميل قصير", "رأي عميل قصير"],
    "contact_title": "نص قصير",
    "contact_note": "نص قصير (مثلاً مواعيد العمل)"
  }
}
استخدم أي بيانات حقيقية موجودة في وصف المشروع (زي اسم النشاط، الخدمات، المواعيد) بدل ما
تخترع محتوى عام.

⚠️ لو الوصف فيه نسخ ولزق من مصدر تاني (زي صفحة جوجل ماب، تقييمات عملاء حقيقية، أزرار
واجهة) هيكون فيه نصوص مالهاش لازمة (زي "الاتجاهات"، "حفظ"، "المواقع القريبة") ومراجعات
عملاء فيها شكوى أو كلام سلبي — تجاهل النصوص اللي مالهاش لازمة تماماً، **ولـ"testimonials_list"
بالذات: استخدم بس آراء العملاء الإيجابية بالكامل، ولو مفيش رأي إيجابي واضح في الوصف
اخترع رأي إيجابي عام مناسب للنشاط بدل ما تستخدم رأي فيه شكوى أو نقد.**

متكتبش أي حاجة برّه الـ JSON.
PROMPT;
    }

    /**
     * نفس فلترة OllamaService::filterToKnownKeys بس نسخة محلية هنا — بتتأكد إن كل مفتاح
     * راجع من الذكاء الاصطناعي فعلاً موجود كخانة حقيقية في القالب المختار قبل ما نحفظه،
     * وبتحوّل القوايم لمصفوفة نضيفة (الذكاء الاصطناعي أحياناً بيرجّع نص واحد بدل array).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\TemplateSlot>  $slots
     * @param  array<mixed, mixed>  $raw
     * @return array<string, string|array<int, string>>
     */
    private function filterToKnownSlotKeys($slots, array $raw): array
    {
        $slotsByKey = $slots->keyBy('key');
        $filtered = [];

        foreach ($raw as $key => $value) {
            if (! is_string($key) || ! $slotsByKey->has($key)) {
                continue;
            }

            $slot = $slotsByKey->get($key);

            if ($slot->slot_type === 'list') {
                $items = is_array($value) ? $value : explode("\n", (string) $value);
                $filtered[$key] = collect($items)->map(fn ($v) => trim((string) $v))->filter()->values()->all();

                continue;
            }

            $filtered[$key] = trim(is_array($value) ? implode(' ', array_map('strval', $value)) : (string) $value);
        }

        return $filtered;
    }

    private function buildFollowUpPrompt(Project $project, GeneratedSite $site, string $message): string
    {
        $categories = Template::where('is_active', true)->distinct()->pluck('category')->implode('، ');
        $fonts = implode('، ', array_keys(TemplateVariant::FONTS));

        $currentContent = collect($project->template->slots)
            ->reject(fn ($slot) => $slot->slot_type === 'image')
            ->map(function ($slot) use ($site) {
                $value = $site->content($slot->key);
                $value = is_array($value) ? implode(' / ', $value) : (string) $value;

                return "- key: \"{$slot->key}\" ({$slot->label()}): ".Str::limit($value, 80);
            })
            ->implode("\n");

        return <<<PROMPT
انت مساعد بيدير مشروع موقع ويب لأدمن. مهمتك: تصنّف رسالة الأدمن لواحد من 5 أنواع أفعال
بالظبط، وترجّع JSON — مفيش تفكير زيادة، بس طابق كلمات الرسالة مع القواعد تحت بالترتيب.

القواعد (اتبعها بالترتيب، أول قاعدة تتطابق هي الصح):
1. لو الرسالة فيها كلمة "قالب" أو "شكل" أو "تصميم" أو "تخطيط" (زي "غيّر القالب"،
   "عايز شكل تاني"، "بدّل التصميم") → النوع change_template.
2. لو الرسالة فيها كلمة "لون" أو "ألوان" أو اسم لون (أزرق/أحمر/أخضر/ذهبي...) → النوع
   update_colors.
3. لو الرسالة فيها كلمة "خط" أو "الخط" أو اسم خط → النوع update_font.
4. لو الرسالة بتطلب تغيير نص/عنوان/فقرة/خدمة معيّنة (زي "غيّر العنوان لـ..."، "زوّد خدمة
   كذا") → النوع update_content.
5. لو مفيش قاعدة فوق اتطابقت، أو الطلب مش واضح خالص → النوع none.

الحالة الحالية للمشروع:
- الفئة: {$project->template->category}
- القالب: {$project->template->name}
- محتوى الخانات الحالي:
{$currentContent}

الفئات المتاحة (للنوع change_template بس): {$categories}
الخطوط المتاحة (للنوع update_font بس): {$fonts}

رسالة الأدمن: "{$message}"

بناءً على النوع اللي حددته، رجّع JSON بالشكل المطابق بالظبط:
- change_template: {"action": "change_template", "category": "نفس الفئة الحالية إلا لو طلب نشاط مختلف بوضوح", "style_hint": "كلمة أو كلمتين تصف الطابع الجديد المطلوب من الرسالة، أو فاضية لو مش واضح", "reply": "رد قصير بالعامية المصرية يقول إنك هتغيّر الشكل"}
- update_colors: {"action": "update_colors", "colors": {"primary": "#hex"}, "reply": "رد قصير"}
- update_font: {"action": "update_font", "font": "مفتاح من قايمة الخطوط فوق بالحرف", "reply": "رد قصير"}
- update_content: {"action": "update_content", "changes": {"key_من_القايمة_فوق": "القيمة الجديدة"}, "reply": "رد قصير"}
- none: {"action": "none", "reply": "رد قصير يوضح إنك مش فاهم ويقترح صياغة تانية"}

متكتبش أي حاجة برّه الـ JSON.
PROMPT;
    }

    private function logMessages(Project $project, string $userMessage, string $assistantReply): void
    {
        AiChatMessage::create(['project_id' => $project->id, 'role' => 'user', 'content' => $userMessage]);
        AiChatMessage::create(['project_id' => $project->id, 'role' => 'assistant', 'content' => $assistantReply]);
    }

    private function uniqueProjectSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $suffix = 2;

        while (Project::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function uniqueSiteSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'site';
        $slug = $base;
        $suffix = 2;

        while (GeneratedSite::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
