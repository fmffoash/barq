<?php

namespace App\Services;

use App\Console\Commands\SeedTemplateLibrary;
use App\Models\AiChatMessage;
use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Services\Ai\AiResult;
use App\Support\CategoryGuesser;
use App\Support\PastedText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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
     *
     * $templateId اختياري (Phase 14) — لو فؤاد اختار القالب بنفسه، بنستخدمه مباشرة ونطلب
     * المحتوى بس. $color/$font اختياريين كمان — بيتطبّقوا على الموقع الناتج فوراً.
     *
     * (2026-10-06) اتقسمت لخطوتين — prepareCreate() بتجهّز الطلب (البرومبت + شكل الرد) و
     * completeCreate() بتطبّق الرد — عشان المتصفح يقدر يستقبل الرد من Ollama كلمة بكلمة ويعرض
     * عدّاد (AiRunController)، والسيرفر يطبّق بس. الدالة دي هي نفس الخطوتين على السيرفر مباشرة
     * (المسار الاحتياطي + التستات).
     *
     * @return array{ok: bool, reply: string, project?: Project}
     */
    public function createFromMessage(string $message, ?int $templateId = null, ?string $color = null, ?string $font = null): array
    {
        $prepared = $this->prepareCreate($message, $templateId, $color, $font);

        if (! $prepared['ok']) {
            return $prepared;
        }

        // فحص سريع (ثانية واحدة) قبل الانتظار الطويل: Ollama شغال والنموذج متسطّب؟
        $result = $this->ollama->preflight()
            ?? $this->ollama->run($prepared['ai']['prompt'], $prepared['ai']['schema'], $prepared['ai']['task']);

        return $this->completeCreate($prepared['context'], $result);
    }

    /**
     * @return array{ok: bool, reply?: string, context?: array<string, mixed>, ai?: array{prompt: string, schema: array<string, mixed>, task: string}}
     */
    public function prepareCreate(string $message, ?int $templateId = null, ?string $color = null, ?string $font = null): array
    {
        $message = PastedText::clean($message);

        if ($message === '') {
            return ['ok' => false, 'reply' => 'اكتب وصف للنشاط الأول (أو الزق بياناته).'];
        }

        $context = ['message' => $message, 'color' => $color, 'font' => $font];

        if ($templateId) {
            $template = Template::where('is_active', true)->where('kind', 'landing')->with('slots')->find($templateId);

            if (! $template) {
                return ['ok' => false, 'reply' => 'القالب اللي اخترته مش موجود أو متعطّل — اختار قالب تاني من فوق.'];
            }

            $slots = $template->slots->whereIn('slot_type', ['text', 'textarea', 'list']);

            return [
                'ok' => true,
                'context' => $context + ['mode' => 'content', 'template_id' => $template->id],
                'ai' => [
                    'prompt' => $this->ollama->buildPrompt($slots, $this->promptMessage($message)),
                    'schema' => $this->ollama->contentSchema($slots),
                    'task' => 'content',
                ],
            ];
        }

        $categories = $this->activeCategories();

        if ($categories->isEmpty()) {
            return ['ok' => false, 'reply' => 'مفيش قوالب متاحة خالص دلوقتي — لازم تتضاف فئات وقوالب الأول.'];
        }

        $guess = CategoryGuesser::guess($message, $categories);

        return [
            'ok' => true,
            'context' => $context + ['mode' => 'pick', 'guess' => $guess['category'] ?? null],
            'ai' => [
                'prompt' => $this->buildCreatePrompt($this->promptMessage($message), $categories, $guess['category'] ?? null),
                'schema' => $this->createSchema($categories),
                'task' => 'create',
            ],
        ];
    }

    /**
     * بتطبّق رد الذكاء الاصطناعي (أو فشله) وتعمل المشروع.
     *
     * @param  array<string, mixed>  $context  نفس اللي prepareCreate() رجّعته
     * @return array{ok: bool, reply: string, project?: Project}
     */
    public function completeCreate(array $context, AiResult $result): array
    {
        $message = (string) $context['message'];
        $aiNote = $result->ok ? '' : $result->message($this->ollama->model());

        if (($context['mode'] ?? null) === 'content') {
            $template = Template::with('slots')->find($context['template_id'] ?? 0);

            if (! $template) {
                return ['ok' => false, 'reply' => 'القالب اللي اخترته اتمسح في النص — اختار قالب تاني.'];
            }

            $category = (string) $template->category;
            $projectName = $this->nameFromMessage($message) ?: $template->name;
            $content = $result->ok
                ? $this->ollama->filterToKnownKeys($template->slots, $result->data)
                : [];
        } else {
            $categories = $this->activeCategories();
            $picked = $result->ok ? $result->data : [];

            // اختيار النموذج لو صالح، وإلا تخمين الكلام نفسه — ومفيش "أول فئة أبجدياً" تاني أبداً.
            $category = in_array($picked['category'] ?? null, $categories->all(), true)
                ? $picked['category']
                : (in_array($context['guess'] ?? null, $categories->all(), true) ? $context['guess'] : null);

            if ($category === null) {
                return [
                    'ok' => false,
                    'reply' => trim(($aiNote !== '' ? $aiNote."\n" : '').'معرفتش أحدد نوع النشاط من الكلام ده — اكتب نوعه صراحة في أول السطر (مثلاً: عيادة أسنان، مطعم مشويات، صالون حريمي) وجرّب تاني.'),
                ];
            }

            $projectName = is_string($picked['project_name'] ?? null) && trim($picked['project_name']) !== ''
                ? Str::limit(PastedText::clean($picked['project_name']), 60, '')
                : ($this->nameFromMessage($message) ?: $category);

            $styleHint = is_string($picked['style_hint'] ?? null) ? trim($picked['style_hint']) : '';
            $template = $this->pickTemplateInCategory($category, $styleHint);

            if (! $template) {
                return ['ok' => false, 'reply' => 'معرفتش ألاقي قالب مناسب — جرّب توصف النشاط بشكل مختلف شوية.'];
            }

            $template->loadMissing('slots');
            $rawContent = is_array($picked['content'] ?? null) ? $picked['content'] : [];
            $content = $this->ollama->filterToKnownKeys($template->slots->whereIn('slot_type', ['text', 'textarea', 'list']), $rawContent);
        }

        $variant = $template->defaultVariant();
        $color = $context['color'] ?? null;
        $font = $context['font'] ?? null;

        $colorsOverride = is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? ['primary' => $color] : null;
        $fontOverride = is_string($font) && array_key_exists($font, TemplateVariant::FONTS) ? $font : null;

        $project = DB::transaction(function () use ($template, $variant, $projectName, $content, $colorsOverride, $fontOverride) {
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
                'colors_override_json' => $colorsOverride,
                'font_override' => $fontOverride,
            ]);

            return $project;
        });

        $filledCount = count($content);
        $reply = match (true) {
            $filledCount > 0 => "تمام! عملتلك مشروع \"{$projectName}\" بقالب \"{$template->name}\" (فئة {$category})، ومليت {$filledCount} خانة بمحتوى مناسب. اتفرج عليه تحت، وقولي لو عايز تغيّر حاجة.",
            $aiNote !== '' => "عملتلك مشروع \"{$projectName}\" بقالب \"{$template->name}\" (فئة {$category}) بالمحتوى الافتراضي للقالب.\n{$aiNote}\nلما تصلّحها اكتبلي هنا \"اكتب المحتوى من جديد\".",
            default => "عملتلك مشروع \"{$projectName}\" بقالب \"{$template->name}\" (فئة {$category})، بس الذكاء الاصطناعي مرجّعش محتوى — الخانات فيها المحتوى الافتراضي للقالب. جرّب تقولي \"اكتب المحتوى من جديد\".",
        };

        $this->logMessages($project, $message, $reply);

        return ['ok' => true, 'project' => $project, 'reply' => $reply];
    }

    /**
     * رسالة تانية على مشروع موجود بالفعل — تفسير الطلب كفعل وتنفيذه. $image اختياري — صورة
     * جاهزة عند فؤاد بيرفقها مع رسالته ("ضيف الصورة دي في كذا").
     */
    public function handleFollowUp(Project $project, string $message, ?UploadedFile $image = null): string
    {
        $prepared = $this->prepareFollowUp($project, $message, $image);

        if (! $prepared['ok']) {
            return $prepared['reply'];
        }

        $result = $this->ollama->preflight()
            ?? $this->ollama->run($prepared['ai']['prompt'], $prepared['ai']['schema'], $prepared['ai']['task']);

        return $this->completeFollowUp($project, $prepared['context'], $result);
    }

    /**
     * الصورة المرفقة بتتخزن هنا (قبل الذكاء الاصطناعي) عشان خطوة التطبيق متحتاجش الملف تاني.
     *
     * @return array{ok: bool, reply?: string, context?: array<string, mixed>, ai?: array{prompt: string, schema: array<string, mixed>|null, task: string}}
     */
    public function prepareFollowUp(Project $project, string $message, ?UploadedFile $image = null): array
    {
        $project->loadMissing(['template.slots', 'variant', 'site']);
        $site = $project->site;
        $message = PastedText::clean($message);

        if (! $site) {
            return ['ok' => false, 'reply' => 'المشروع ده مالوش موقع ناتج خالص — حاجة غريبة، راجع المشروع من صفحته العادية.'];
        }

        if ($message === '') {
            return ['ok' => false, 'reply' => 'اكتب طلبك الأول.'];
        }

        $imagePath = $image ? '/storage/'.$image->store('site-images', 'public') : null;

        return [
            'ok' => true,
            'context' => ['message' => $message, 'image_path' => $imagePath],
            'ai' => [
                'prompt' => $this->buildFollowUpPrompt($project, $site, $this->promptMessage($message), $imagePath !== null),
                'schema' => null,
                'task' => 'follow_up',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function completeFollowUp(Project $project, array $context, AiResult $result): string
    {
        $project->loadMissing(['template.slots', 'variant', 'site']);
        $site = $project->site;
        $message = (string) $context['message'];
        $imagePath = is_string($context['image_path'] ?? null) ? $context['image_path'] : null;

        if (! $result->ok) {
            $reply = $result->message($this->ollama->model());
            $this->logMessages($project, $message, $reply);

            return $reply;
        }

        $decision = $result->data;
        $action = $decision['action'] ?? 'none';
        $knownActions = [
            'change_template', 'update_content', 'update_colors', 'update_font',
            'reset_to_default', 'update_image', 'add_custom_block', 'remove_custom_block',
        ];

        // شبكة أمان: نموذج صغير أحياناً بيرجّع "none" أو فعل مش من القايمة رغم إن الرسالة واضحة.
        if (! in_array($action, $knownActions, true) && preg_match('/قالب|تصميم|تخطيط/u', $message)) {
            $action = 'change_template';
            $decision['category'] ??= $project->template->category;
        }
        if (! in_array($action, $knownActions, true) && preg_match('/رجّع|رجع|الغ[يى]|امسح كل|ارجاع/u', $message)) {
            $action = 'reset_to_default';
        }

        $reply = match ($action) {
            'change_template' => $this->applyChangeTemplate($project, $decision),
            'update_content' => $this->applyUpdateContent($project, $site, $decision),
            'update_colors' => $this->applyUpdateColors($site, $decision),
            'update_font' => $this->applyUpdateFont($site, $decision),
            'reset_to_default' => $this->applyResetToDefault($site),
            'update_image' => $this->applyUpdateImage($project, $site, $imagePath, $decision),
            'add_custom_block' => $this->applyAddCustomBlock($site, $imagePath, $decision),
            'remove_custom_block' => $this->applyRemoveCustomBlock($site, $decision),
            default => is_string($decision['reply'] ?? null) && trim($decision['reply']) !== ''
                ? $decision['reply']
                : 'مش متأكد فاهم طلبك — ممكن توضحه أكتر؟',
        };

        $this->logMessages($project, $message, $reply);

        return $reply;
    }

    /**
     * @return Collection<int, string>
     */
    private function activeCategories()
    {
        return Template::query()
            ->where('is_active', true)
            ->where('kind', 'landing')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    // الكوبي الطويل جداً بيتقص قبل ما يروح للذكاء الاصطناعي (الأول بيبقى فيه الاسم والبيانات
    // المهمة) — عشان الطلب كله يفضل جوّه مساحة القراية (num_ctx) ومتقصّش التعليمات.
    private function promptMessage(string $message): string
    {
        return Str::limit($message, 6000, ' …');
    }

    // أول سطر له معنى في الرسالة (في كوبي جوجل مابس أول سطر هو اسم المكان) كاسم مشروع لو
    // الذكاء الاصطناعي مرجّعش اسم.
    private function nameFromMessage(string $message): string
    {
        foreach (explode("\n", $message) as $line) {
            $line = trim($line);
            if (mb_strlen($line) >= 3) {
                return Str::limit($line, 60, '');
            }
        }

        return '';
    }

    /**
     * @param  Collection<int, string>  $categories
     * @return array<string, mixed>
     */
    private function createSchema($categories): array
    {
        $text = ['type' => 'string'];
        $list = ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 3, 'maxItems' => 6];

        return [
            'type' => 'object',
            'properties' => [
                'category' => ['type' => 'string', 'enum' => $categories->values()->all()],
                'project_name' => $text,
                'style_hint' => $text,
                'content' => [
                    'type' => 'object',
                    'properties' => [
                        'hero_title' => $text, 'hero_subtitle' => $text,
                        'about_title' => $text, 'about_body' => $text,
                        'services_title' => $text, 'services_list' => $list,
                        'gallery_title' => $text, 'testimonials_title' => $text,
                        'contact_title' => $text, 'contact_note' => $text,
                    ],
                    'required' => ['hero_title', 'hero_subtitle', 'about_title', 'about_body', 'services_title', 'services_list', 'gallery_title', 'testimonials_title', 'contact_title', 'contact_note'],
                ],
            ],
            'required' => ['category', 'project_name', 'style_hint', 'content'],
        ];
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
                $trimmed = trim(is_array($value) ? implode(' ', $value) : (string) $value);
                // خانات text/textarea بترندر بـ {!! !!} دلوقتي (المرحلة 1، RichTextSanitizer) —
                // link مالوش معنى "تنسيق نص" فبيفضل URL خام زي ما هو.
                $content[$key] = in_array($slot->slot_type, ['text', 'textarea'], true)
                    ? RichTextSanitizer::clean($trimmed)
                    : $trimmed;
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

    // إلغاء كل التعديلات اليدوية ورجوع الموقع لشكل القالب الأصلي (المرحلة 4، 2026-09-24) —
    // فؤاد أوضح صراحة إن قصده بـ"رجّع" هو ده بالظبط: مسح كل تخصيص (محتوى/ألوان/خط/ترتيب/
    // عناصر مضافة) ورجوع كل خانة لقيمتها الافتراضية من القالب. **مفيش نسخة سابقة محفوظة فعلياً
    // — ده مش undo خطوة بخطوة، ده reset كامل لمرة واحدة.** template_id/template_variant_id
    // نفسهم متلمسوش (لو فؤاد بدّل القالب، ده قرار منفصل عن "امسح تعديلاتي على المحتوى").
    private function applyResetToDefault(GeneratedSite $site): string
    {
        $site->update([
            'content_json' => null,
            'style_overrides_json' => null,
            'colors_override_json' => null,
            'font_override' => null,
            'font_weight_override' => null,
            'font_style_override' => null,
            'font_size_scale_override' => null,
            'sections_override_json' => null,
            'custom_blocks_json' => null,
        ]);

        return 'تمام، رجّعت الموقع لشكل القالب الأصلي — كل تعديل عملته (محتوى/ألوان/خط/ترتيب/عناصر مضافة) اتلغى. لو عايز ترجع حاجة بعينها بس مش كل حاجة، قولّي إيه بالظبط.';
    }

    // فؤاد بيرفق صورة جاهزة عنده ويقول "حطها هنا" — بنحتاج نعرف الخانة المستهدفة (hero_image،
    // gallery_image_1...) من كلام الرسالة، والصورة نفسها لازم تكون مرفقة فعلاً في نفس الرسالة
    // (المرحلة 4، 2026-09-24 — الذكاء الاصطناعي بيقرّر الخانة بس، هو مش شايف بايتات الصورة
    // خالص، التخزين الفعلي هنا في PHP زي أي رفع ملف عادي).
    private function applyUpdateImage(Project $project, GeneratedSite $site, ?string $imagePath, array $decision): string
    {
        if (! $imagePath) {
            return 'قولّي تحط الصورة فين، بس محتاج ترفق الصورة نفسها مع رسالتك (زرار إرفاق الصورة جنب مربع الكتابة).';
        }

        $slotKey = $decision['slot_key'] ?? null;
        $imageSlots = $project->template->slots->where('slot_type', 'image');

        if (! is_string($slotKey) || ! $imageSlots->contains('key', $slotKey)) {
            return 'مش متأكد عايز الصورة دي تحل محل إيه بالظبط — قولّي مثلاً "خليها الصورة الرئيسية" أو "خليها صورة المعرض التانية".';
        }

        $content = $site->content_json ?? [];
        $content[$slotKey] = $imagePath;
        $site->update(['content_json' => $content]);

        $label = $imageSlots->firstWhere('key', $slotKey)?->label() ?? $slotKey;

        return "تمام، حطّيت الصورة في \"{$label}\".";
    }

    // "ضيف مربع/قسم جديد" — نص أو صورة، بيتضاف كـsection جديد كامل آخر الصفحة (مش جوّه
    // section موجود، شوف SiteRenderer::render() وdocs/rich-text-and-image-editing-plan.md
    // للتفاصيل المعمارية). فؤاد بعدين يقدر يرتّب/يحرّك العنصر الجديد بالترتيب الحر العادي —
    // صفر UI جديد لموضعه، بيستخدم نفس الآلية الموجودة.
    private function applyAddCustomBlock(GeneratedSite $site, ?string $imagePath, array $decision): string
    {
        $type = in_array($decision['block_type'] ?? null, ['text', 'image'], true) ? $decision['block_type'] : null;

        if ($type === 'image' && ! $imagePath) {
            return 'عايز تضيف صورة جديدة — بس محتاج ترفق الصورة نفسها مع رسالتك.';
        }
        if ($type === null) {
            $type = $imagePath ? 'image' : 'text';
        }

        $content = $type === 'text'
            ? (is_string($decision['content'] ?? null) ? trim($decision['content']) : '')
            : null;

        if ($type === 'text' && $content === '') {
            return 'مش متأكد عايز تضيف إيه بالظبط — اكتب النص اللي عايزه في المربع الجديد.';
        }

        if ($type === 'image') {
            $content = $imagePath;
        }

        $blocks = $site->custom_blocks_json ?? [];
        $newKey = 'custom_'.(count($blocks) + 1).'_'.Str::random(6);

        $blocks[] = [
            'key' => $newKey,
            'type' => $type,
            'label' => is_string($decision['label'] ?? null) && trim($decision['label']) !== ''
                ? trim($decision['label'])
                : ($type === 'image' ? 'صورة مضافة' : 'نص مضاف'),
            'content' => $content,
        ];

        $site->update(['custom_blocks_json' => $blocks]);

        return 'تمام، ضفتلك '.($type === 'image' ? 'الصورة' : 'المربع').' في آخر الصفحة — تقدر تحرّكه لمكانه اللي عايزه من زرار "ترتيب حر" في المحرر.';
    }

    // مسح عنصر مضاف بالذكاء الاصطناعي (مش خانة أصلية من القالب — تلك مالهاش حذف، بس تقدر
    // تفضّيها من محتواها). المطابقة بالاسم/التسمية مش المفتاح الداخلي (فؤاد مش شايف المفتاح
    // أصلاً)، فبتاخد أقرب تطابق بدل تطابق حرفي.
    private function applyRemoveCustomBlock(GeneratedSite $site, array $decision): string
    {
        $blocks = $site->custom_blocks_json ?? [];

        if ($blocks === []) {
            return 'مفيش عناصر مضافة أصلاً تتمسح دلوقتي.';
        }

        $hint = is_string($decision['label'] ?? null) ? trim($decision['label']) : '';
        $target = $hint !== ''
            ? collect($blocks)->first(fn ($b) => str_contains($b['label'] ?? '', $hint) || str_contains($hint, $b['label'] ?? "\0"))
            : null;
        $target ??= collect($blocks)->last();

        $remaining = collect($blocks)->reject(fn ($b) => $b['key'] === $target['key'])->values()->all();
        $site->update(['custom_blocks_json' => $remaining === [] ? null : $remaining]);

        return 'تمام، مسحت "'.($target['label'] ?? 'العنصر').'".';
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
     * نداء واحد بيرجّع اختيار الفئة/الاسم/الطابع **ومحتوى الموقع كله مع بعض**. شكل الرد
     * متحدد بالـJSON schema (createSchema) — الفئة لازم تكون واحدة من القايمة بالحرف.
     * الجزء الثابت الأول والمتغيّر (كلام فؤاد) في الآخر: Ollama بيعيد استخدام قراية أول الطلب.
     *
     * @param  Collection<int, string>  $categories
     */
    private function buildCreatePrompt(string $message, $categories, ?string $guess = null): string
    {
        $list = $categories->map(fn ($c) => "- {$c}")->implode("\n");
        $hint = $guess ? "\nمن الكلمات اللي في الوصف، الأرجح إن الفئة \"{$guess}\" — اختارها إلا لو الوصف واضح إنه نشاط تاني.\n" : '';

        return <<<PROMPT
انت بتساعد تنشئ موقع ويب (صفحة هبوط) لنشاط تجاري، بناءً على وصف ممكن يكون فيه بيانات مختلطة
(اسم نشاط، تليفون، خدمات، كلام منسوخ من جوجل مابس...).

الفئات المتاحة (اختار واحدة منها بالحرف):
{$list}

رجّع JSON بالمفاتيح دي:
- category: الفئة الأنسب من القايمة فوق.
- project_name: اسم النشاط زي ما هو مكتوب في الوصف بالظبط (من غير تقييمات أو أرقام أو عنوان). لو مفيش اسم، استنتج اسم قصير.
- style_hint: كلمة أو كلمتين للطابع (فاخر، بسيط، عصري، تقليدي، شبابي، هادي...) لو واضح، وإلا "".
- content: محتوى الموقع:
  - hero_title: عنوان رئيسي جذاب (من 3 لـ8 كلمات).
  - hero_subtitle: جملة أو جملتين بتوضّح النشاط.
  - about_title / about_body: عنوان قصير + فقرة من 3 لـ4 جمل عن النشاط.
  - services_title / services_list: عنوان + من 3 لـ6 خدمات قصيرة (كل خدمة كلمتين لـ6 كلمات).
  - gallery_title, testimonials_title, contact_title: عناوين أقسام قصيرة.
  - contact_note: سطر واحد فيه المواعيد أو العنوان لو موجودين في الوصف، وإلا جملة تدعو للتواصل.

قواعد:
- اكتب بعربي بسيط وواضح بأسلوب تسويقي مناسب للسوق المصري.
- استخدم البيانات الحقيقية اللي في الوصف (الاسم، الخدمات، المواعيد، المنطقة) بدل الكلام العام.
- لو الوصف منسوخ من جوجل مابس، تجاهل كلام الواجهة (الاتجاهات، حفظ، مشاركة، المواقع القريبة، مرشد محلي، قبل شهر...).
- متخترعش أرقام تليفونات أو عناوين أو آراء عملاء أو أسماء ناس.
{$hint}
الوصف/البيانات اللي كتبها صاحب المشروع:
{$message}
PROMPT;
    }

    private function buildFollowUpPrompt(Project $project, GeneratedSite $site, string $message, bool $hasImage = false): string
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

        // خانات الصور — مستبعدة من $currentContent فوق (مش نص، مالهاش معنى هناك) بس لازم
        // النموذج يشوفها هنا عشان update_image (المرحلة 4، 2026-09-24: فؤاد بيرفق صورة
        // جاهزة ويقول "حطها في كذا") يقدر يحدد slot_key الصح من اسم الخانة/حالتها (فاضية
        // ولا معبّأة بالفعل) بدل ما يخمّن.
        $imageSlots = collect($project->template->slots)
            ->where('slot_type', 'image')
            ->map(fn ($slot) => "- key: \"{$slot->key}\" ({$slot->label()}): ".(filled($site->content($slot->key)) ? 'معبّأة بالفعل' : 'فاضية'))
            ->implode("\n");

        $imageNote = $hasImage
            ? 'فيه صورة مرفقة فعلاً مع الرسالة دي.'
            : 'مفيش صورة مرفقة مع الرسالة دي.';

        $customBlocks = collect($site->custom_blocks_json ?? [])
            ->map(fn ($b) => "- \"{$b['label']}\" (نوعها: {$b['type']})")
            ->implode("\n") ?: '(مفيش عناصر مضافة دلوقتي)';

        return <<<PROMPT
انت مساعد بيدير مشروع موقع ويب لأدمن. مهمتك: تصنّف رسالة الأدمن لواحد من 8 أنواع أفعال
بالظبط، وترجّع JSON — مفيش تفكير زيادة، بس طابق كلمات الرسالة مع القواعد تحت بالترتيب.

القواعد (اتبعها بالترتيب، أول قاعدة تتطابق هي الصح):
1. لو الرسالة بتطلب "رجّع/الغي/امسح كل" التعديلات ورجوع الموقع لأصله (زي "رجّع كل حاجة
   زي ما كانت"، "الغي كل التعديلات") → النوع reset_to_default. ده بيمسح كل تخصيص (محتوى/
   ألوان/خط/عناصر مضافة) ويرجع لشكل القالب الافتراضي — استخدمه بس لو الطلب عن "كل حاجة"،
   مش تعديل جزء واحد بعينه (ده update_content/update_colors عادي).
2. لو فيه صورة مرفقة (شوف "{$imageNote}" تحت) والرسالة بتقول "حطها/خليها/استبدلها" في
   خانة موجودة بالفعل (زي "خليها الصورة الرئيسية") → النوع update_image.
3. لو فيه صورة مرفقة والرسالة بتقول "ضيف/زوّد" صورة جديدة (مش استبدال خانة موجودة) →
   النوع add_custom_block بـblock_type="image".
4. لو الرسالة بتقول "ضيف/زوّد مربع/قسم/نص جديد" (من غير صورة مرفقة) → النوع
   add_custom_block بـblock_type="text"، والـcontent هو النص المطلوب إضافته (اكتبه إنت
   بناءً على وصف الأدمن لو مديك وصف بس مش نص جاهز، زي باقي اقتراح المحتوى).
5. لو الرسالة بتقول "امسح/شيل" عنصر مضاف (شوف قايمة "العناصر المضافة حالياً" تحت) → النوع
   remove_custom_block، والـlabel هو أقرب اسم من القايمة دي لطلب الأدمن.
6. لو الرسالة فيها كلمة "قالب" أو "شكل" أو "تصميم" أو "تخطيط" (زي "غيّر القالب"،
   "عايز شكل تاني"، "بدّل التصميم") → النوع change_template.
7. لو الرسالة فيها كلمة "لون" أو "ألوان" أو اسم لون (أزرق/أحمر/أخضر/ذهبي...) → النوع
   update_colors.
8. لو الرسالة فيها كلمة "خط" أو "الخط" أو اسم خط → النوع update_font.
9. لو الرسالة بتطلب تغيير نص/عنوان/فقرة/خدمة معيّنة في خانة موجودة بالفعل (زي "غيّر
   العنوان لـ..."، "زوّد خدمة كذا") → النوع update_content.
10. لو مفيش قاعدة فوق اتطابقت، أو الطلب مش واضح خالص → النوع none، والـreply يوضّح
    تحديداً إيه اللي مش واضح، مش رد عام "مش فاهم".

الحالة الحالية للمشروع:
- الفئة: {$project->template->category}
- القالب: {$project->template->name}
- محتوى الخانات الحالي:
{$currentContent}
- خانات الصور:
{$imageSlots}
- {$imageNote}
- العناصر المضافة حالياً (للنوع remove_custom_block بس):
{$customBlocks}

الفئات المتاحة (للنوع change_template بس): {$categories}
الخطوط المتاحة (للنوع update_font بس): {$fonts}

رسالة الأدمن: "{$message}"

بناءً على النوع اللي حددته، رجّع JSON بالشكل المطابق بالظبط:
- reset_to_default: {"action": "reset_to_default", "reply": "رد قصير يأكّد إنك هترجّع كل حاجة للأصلي"}
- update_image: {"action": "update_image", "slot_key": "مفتاح من قايمة خانات الصور فوق بالحرف", "reply": "رد قصير"}
- add_custom_block: {"action": "add_custom_block", "block_type": "text أو image", "content": "النص المطلوب إضافته (لو block_type=text بس، فاضي لو image)", "label": "اسم قصير يوصف العنصر ده (زي \"عرض خاص\" أو \"صورة الفرع الجديد\")", "reply": "رد قصير"}
- remove_custom_block: {"action": "remove_custom_block", "label": "أقرب اسم من قايمة العناصر المضافة فوق لطلب الأدمن", "reply": "رد قصير"}
- change_template: {"action": "change_template", "category": "نفس الفئة الحالية إلا لو طلب نشاط مختلف بوضوح", "style_hint": "كلمة أو كلمتين تصف الطابع الجديد المطلوب من الرسالة، أو فاضية لو مش واضح", "reply": "رد قصير بالعامية المصرية يقول إنك هتغيّر الشكل"}
- update_colors: {"action": "update_colors", "colors": {"primary": "#hex"}, "reply": "رد قصير"}
- update_font: {"action": "update_font", "font": "مفتاح من قايمة الخطوط فوق بالحرف", "reply": "رد قصير"}
- update_content: {"action": "update_content", "changes": {"key_من_القايمة_فوق": "القيمة الجديدة"}, "reply": "رد قصير"}
- none: {"action": "none", "reply": "رد قصير يوضح تحديداً إيه اللي مش واضح، مش رد عام"}

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
