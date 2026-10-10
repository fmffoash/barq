<?php

namespace App\Services;

use App\Models\GeneratedSite;
use App\Models\TemplateSlot;
use App\Models\TemplateVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// بيبني بيانات رندر الموقع (الأقسام مرتّبة ومفلترة بمحتواها الفعلي + الألوان النهائية) من
// موقع ناتج معيّن — نفس المنطق مستخدم في المعاينة الحية (SiteController) وفي التصدير كملفات
// ثابتة (SiteExportService)، عشان الاتنين يعرضوا بالظبط نفس المحتوى من غير تكرار المنطق.
//
// (2026-10-10) بقى كمان المكان المركزي لكل "ذكاء العرض" المشترك بين الـ16 تصميم: لون نص مقروء
// فوق اللون الأساسي (onPrimary)، خط العناوين، تصنيف قسم آراء العملاء وتفكيك كل رأي لـ
// {اقتباس، اسم، حرف أول، نجوم}، زرار التواصل العائم، ودوال شبكات بتحسب الأعمدة حسب العدد (صفر
// خانات فاضية في آخر صف لأي عدد من 1 لـ6). أي تصميم بيقرا الجاهز ده بدل ما يعيد الحساب.
class SiteRenderer
{
    public function render(GeneratedSite $site): array
    {
        $project = $site->project;
        $template = $project->template;
        $variant = $project->variant;
        $layout = $template->layout ?: 'classic';

        $slotsBySection = $template->slots->groupBy('section_key');

        // ترتيب الأقسام: تخصيص الموقع ده بس (لو مفعّل) بيغلب نسخة القالب المشتركة، وإلا
        // بنستخدم اللي متحدد في النسخة صراحة، وإلا بنرجع لترتيب أول ظهور للأقسام جوّه خانات
        // القالب نفسه (اللي أصلاً مرتّبة بـ sort_order).
        $sectionOrder = filled($site->sections_override_json)
            ? $site->sections_override_json
            : (filled($variant?->sections_json) ? $variant->sections_json : $slotsBySection->keys()->all());

        $sections = collect($sectionOrder)
            ->filter(fn ($key) => $slotsBySection->has($key))
            ->map(function ($key) use ($slotsBySection, $site) {
                $isTestimonials = str_contains($key, 'testimonial');

                $items = $slotsBySection->get($key)
                    ->sortBy('sort_order')
                    ->map(fn ($slot) => $this->item($slot, $site, $isTestimonials))
                    ->filter(fn (array $item) => filled($item['value']))
                    ->values();

                return ['key' => $key, 'items' => $items];
            })
            // قسم من غير أي قيمة متعبّاة فيه لسه (المشروع لسه بيتظبط) بنسيبه من غير ما يترندر
            // فاضي وسط الصفحة.
            ->filter(fn (array $section) => $section['items']->isNotEmpty())
            ->values();

        // بنحسب "نوع" كل قسم (hero/gallery/list/testimonials/cta/text) بناءً على موقعه وأنواع
        // خاناته — مش من اسم القسم نفسه (إلا الآراء والتواصل)، عشان يشتغل مع أي قالب من غير
        // ما نفرض تسميات أقسام معينة. التصميمات البصرية بتستخدم النوع ده عشان تقرر شكل العرض.
        $sectionCount = $sections->count();

        $sections = $sections->values()->map(function (array $section, int $index) use ($sectionCount) {
            $kind = $this->classifySection($section, $index === 0, $index === $sectionCount - 1);

            $label = self::navLabel($section['key']);

            return $section + [
                'kind' => $kind,
                'label' => $label,
                // سطر صغير فوق عنوان القسم (eyebrow) — نفس اسم القسم، إلا الآراء ("آراء العملاء"
                // فوق عنوان "آراء عملائنا" كان هيبقى تكرار).
                'eyebrow' => str_contains($section['key'], 'testimonial') ? 'قالوا عنّا' : $label,
                // تقييم جوجل لو مكتوب في عنوان قسم الآراء ("تقييمنا 4.7 ★ على جوجل ...") —
                // التصميم بيعرضه كنجوم جنب العنوان (العنوان نفسه بيترندر زي ما هو).
                'rating' => str_contains($section['key'], 'testimonial') ? $this->ratingFrom($section['items']) : null,
            ];
        });

        // عناصر مضافة من فؤاد بنفسه عن طريق شات الذكاء الاصطناعي (2026-09-24، "ضيف مربع/
        // صورة جديدة") — مش جزء من template_slots الثابتة، فبتتضاف هنا بعد التصنيف فوق
        // (بـkind ثابت مش محسوب) كـsection جديد كامل لكل عنصر، آخر الصفحة دايماً
        // (AiProjectAssistantService::applyAddCustomBlock()). بيستخدموا نفس بنية $sections
        // بالظبط (slot/value/style) عشان يشتغلوا مع كل الآليات الموجودة من غيرها (ترتيب حر،
        // تنسيق نص، ترتيب/إظهار أقسام) من غير أي كود إضافي.
        foreach (($site->custom_blocks_json ?? []) as $block) {
            if (! is_array($block) || ! isset($block['key'], $block['type'])) {
                continue;
            }

            $slot = new TemplateSlot([
                'key' => $block['key'],
                'section_key' => $block['key'],
                'slot_type' => $block['type'],
                'label_ar' => $block['label'] ?? 'عنصر مضاف',
                'sort_order' => 0,
            ]);

            $value = $block['type'] === 'text'
                ? RichTextSanitizer::clean((string) ($block['content'] ?? ''))
                : ($block['content'] ?? null);

            if (blank($value)) {
                continue;
            }

            $sections->push([
                'key' => $block['key'],
                'items' => collect([[
                    'slot' => $slot,
                    'value' => $value,
                    'style' => $site->styleFor($block['key']),
                ]]),
                'kind' => $block['type'] === 'image' ? 'gallery' : 'text',
                'label' => null,
                'eyebrow' => null,
                'rating' => null,
            ]);
        }

        // تخصيص ألوان/خط الموقع ده بس (لو مفعّل) بيغلب نسخة القالب المشتركة — نفس منطق
        // ترتيب الأقسام فوق، بدل ما أي تعديل يأثر على مشاريع تانية شايلة نفس النسخة.
        $colors = array_merge([
            'primary' => '#f59e0b',
            'background' => '#0b1220',
            'surface' => '#111a2e',
            'text' => '#f1f5f9',
            'muted' => '#94a3b8',
        ], $variant?->colors_json ?? [], $site->colors_override_json ?? []);

        $font = $site->font_override ?: ($variant?->font ?: 'cairo');

        return [
            'project' => $project,
            'sections' => $sections,
            'colors' => $colors,
            // لون نص/أيقونات فوق اللون الأساسي (أبيض أو غامق، أيهما أوضح) — من غيره 147 قالب من
            // 300 كانوا بيكتبوا أبيض على أصفر/ليموني/سماوي فاتح (تباين 1.5:1).
            'onPrimary' => self::onColor((string) $colors['primary'], [(string) $colors['background'], (string) $colors['text']]),
            'font' => $font,
            // خط العناوين (h1-h3) لو النسخة محددة واحد. لو فؤاد غيّر الخط العام للموقع ده
            // بنفسه (font_override)، العناوين بتمشي وراه هو كمان — وإلا هيبان إن "تغيير الخط
            // مش بيأثر" على أكبر نصوص في الصفحة.
            'headingFont' => $site->font_override ? null : self::validFont($variant?->heading_font ?? null),
            // تخين/مَيَلان/حجم الخط العام (Phase 16، 2026-09-21) — نفس منطق font_override
            // بالظبط (تخصيص الموقع ده بس بيغلب، وإلا قيمة افتراضية محايدة). fontSizeScale
            // بيتطبّق كـfont-size على <html> نفسه (شوف تعليق المايجريشن للتفصيل).
            'fontWeight' => $site->font_weight_override ?: '400',
            'fontStyle' => $site->font_style_override ?: 'normal',
            'fontSizeScale' => $site->font_size_scale_override ? (float) $site->font_size_scale_override : 1.0,
            'layout' => $layout,
            'contactAction' => $this->contactAction($sections),
            'contactAnchor' => $this->contactAnchor($sections),
            'siteMeta' => $this->siteMeta($sections, $template->slots),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(TemplateSlot $slot, GeneratedSite $site, bool $inTestimonials): array
    {
        $raw = $site->content($slot->key, $slot->default_value);

        $item = [
            'slot' => $slot,
            // لو الخانة لسه فاضية في content_json، بترجع لقيمة القالب الافتراضية
            // (default_value) بدل ما تفضل فاضية — أهم حالة عملية: خانات الصور
            // (hero_image/gallery_image_*) لما مشروع بيتعمل بالذكاء الاصطناعي،
            // لأن الذكاء الاصطناعي بيملّي النصوص بس ومش بيقدر يولّد صورة حقيقية،
            // فمن غيرها كل مشروع AI كان بيطلع بمعرض صور فاضي تماماً حتى لو القالب
            // نفسه معاه صور افتراضية جاهزة (2026-09-21).
            //
            // خانات text/textarea بترندر بـ{!! !!} (المرحلة 1، تنسيق نص جزئي —
            // RichTextSanitizer). قيم content_json المحفوظة معدّاة على المطهّر بالفعل وقت
            // الحفظ (تمريرها هنا تاني idempotent)، لكن default_value القالب نص عادي مؤلّف من
            // الأدمن ومعداش على أي تطهير — تمريره هنا بيضمن صفر مسار وصول لـ{!! !!} من غير تطهير.
            'value' => in_array($slot->slot_type, ['text', 'textarea'], true)
                ? RichTextSanitizer::clean((string) $raw)
                : $raw,
            // تخصيص لون/خط الخانة دي بس (Phase 8) — ['color' => ?, 'font' => ?]،
            // فاضي (null/null) لو الخانة من غير أي تخصيص، فبترجع للعام تلقائي.
            'style' => $site->styleFor($slot->key),
        ];

        if ($slot->slot_type === 'link' && is_string($raw) && filled($raw)) {
            $item['linkType'] = self::linkType($raw);
            $item['external'] = self::isExternal($raw);
            $item['label'] = self::linkLabel($slot, $raw);
        }

        // آراء العملاء (عقد 3): كل عنصر "الرأي — الاسم" مع " ★N" اختيارية في الآخر.
        if ($inTestimonials && $slot->slot_type === 'list' && is_array($raw)) {
            $item['testimonials'] = collect($raw)
                ->filter(fn ($entry) => is_string($entry) && trim($entry) !== '')
                ->map(fn (string $entry) => self::parseTestimonial($entry))
                ->values()
                ->all();
        }

        return $item;
    }

    /**
     * @param  array{key: string, items: Collection<int, array{slot: TemplateSlot, value: mixed}>}  $section
     */
    private function classifySection(array $section, bool $isFirst, bool $isLast): string
    {
        if ($isFirst) {
            return 'hero';
        }

        $types = $section['items']->pluck('slot.slot_type');

        if ($types->contains('image')) {
            return 'gallery';
        }

        // قسم آراء العملاء — حتى لو مفيهوش غير العنوان (سطر تقييم جوجل الحقيقي لوحده، لما
        // الذكاء الاصطناعي ميلاقيش آراء حقيقية، AiProjectAssistantService::finalizeContent).
        if (str_contains($section['key'], 'testimonial')) {
            return 'testimonials';
        }

        $onlyTextAndLinks = $types->every(fn ($type) => in_array($type, ['text', 'textarea', 'link'], true));

        // قسم التواصل بقى "cta" حتى من غير رابط متعبّي — كل قوالب المكتبة بتيجي رابطها فاضي
        // افتراضياً، فالقسم كان بيترندر نص عادي والمعاينات/صور الكروت مكانتش بتبين شكل الـCTA
        // المصمم خالص. آخر قسم برابط فعلي برضه cta (نفس السلوك القديم).
        if ($onlyTextAndLinks && (($isLast && $types->contains('link')) || self::isContactKey($section['key']))) {
            return 'cta';
        }

        if ($types->contains('list')) {
            return 'list';
        }

        return 'text';
    }

    public static function isContactKey(string $key): bool
    {
        return (bool) preg_match('/contact|cta|booking|reserv/i', $key);
    }

    // اسم القسم في النافبار/الفوتر/الـeyebrow — تسمية عربية معروفة لو الاسم شائع، وإلا الاسم
    // نفسه بعد تنظيفه. أقسام مضافة من الشات (custom_N_xxxx) مالهاش اسم يتعرض (null).
    public static function navLabel(string $key): ?string
    {
        if (str_starts_with($key, 'custom_')) {
            return null;
        }

        return match (true) {
            $key === 'hero' => 'الرئيسية',
            $key === 'about' => 'من نحن',
            in_array($key, ['services', 'menu'], true) => 'خدماتنا',
            $key === 'gallery' => 'معرض الصور',
            str_contains($key, 'testimonial') => 'آراء العملاء',
            $key === 'pricing' => 'الأسعار',
            $key === 'faq' => 'الأسئلة الشائعة',
            self::isContactKey($key) => 'تواصل معنا',
            default => Str::of($key)->replace(['_', '-'], ' ')->trim()->title()->toString(),
        };
    }

    // ---------- الروابط ----------

    public static function linkType(string $href): string
    {
        $href = trim($href);

        return match (true) {
            (bool) preg_match('~^(https?://)?(wa\.me/|api\.whatsapp\.com/|chat\.whatsapp\.com/|(www\.)?whatsapp\.com/)|^whatsapp:~i', $href) => 'whatsapp',
            str_starts_with(strtolower($href), 'tel:') => 'phone',
            str_starts_with(strtolower($href), 'mailto:') => 'email',
            str_starts_with($href, '#') => 'anchor',
            default => 'web',
        };
    }

    // target="_blank" لروابط http(s) بس — tel:/mailto:/#قسم مايفتحوش تاب جديد فاضي.
    public static function isExternal(string $href): bool
    {
        return (bool) preg_match('~^https?://~i', trim($href));
    }

    // نص الزرار المعروض للزوار — اسم الخانة نفسه، إلا لو الاسم "اسم حقل إداري" (زي
    // "رابط التواصل (واتساب/اتصال)" اللي كان بيظهر حرفياً على زرار التواصل في كل القوالب
    // القديمة) فبنحط نص مناسب لنوع الرابط.
    public static function linkLabel(TemplateSlot $slot, string $href): string
    {
        $label = trim((string) $slot->label());

        if ($label !== '' && ! str_starts_with($label, 'رابط') && ! str_contains($label, '(')) {
            return $label;
        }

        return match (self::linkType($href)) {
            'whatsapp' => 'كلّمنا على واتساب',
            'phone' => 'اتصل بينا',
            'email' => 'ابعتلنا إيميل',
            default => 'تواصل معنا',
        };
    }

    // زرار التواصل العائم: رابط التواصل لو متعبّي، وإلا رابط زرار الهيرو — بس لو واتساب أو
    // اتصال (أي رابط تاني ملوش أيقونة واضحة كفقاعة عائمة).
    /**
     * @return array{href: string, type: string, label: string}|null
     */
    private function contactAction(Collection $sections): ?array
    {
        $links = $sections->flatMap(fn ($section) => $section['items'])
            ->filter(fn ($item) => $item['slot']->slot_type === 'link' && isset($item['linkType']));

        $preferred = $links->first(fn ($item) => self::isContactKey($item['slot']->key))
            ?? $links->first(fn ($item) => $item['slot']->section_key === 'hero' || str_contains($item['slot']->key, 'hero'))
            ?? $links->first();

        $candidates = collect([$preferred])->merge($links)->filter();
        $action = $candidates->first(fn ($item) => in_array($item['linkType'], ['whatsapp', 'phone'], true));

        if (! $action) {
            return null;
        }

        return [
            'href' => trim((string) $action['value']),
            'type' => $action['linkType'],
            'label' => $action['linkType'] === 'whatsapp' ? 'كلّمنا على واتساب' : 'اتصل بينا',
        ];
    }

    // id قسم التواصل (لزرار الهيرو الاحتياطي وروابط الفوتر) — قسم مفتاحه "contact"، وإلا أي
    // قسم cta، وإلا آخر قسم.
    private function contactAnchor(Collection $sections): ?string
    {
        $section = $sections->first(fn ($s) => self::isContactKey($s['key']))
            ?? $sections->first(fn ($s) => $s['kind'] === 'cta');

        return $section['key'] ?? null;
    }

    /**
     * بيانات الفوتر/النافبار المشتركة — كلها نص عادي (من غير data-slot)، نسخة للعرض بس.
     *
     * @return array{nav: list<array{key: string, label: string}>, tagline: ?string, contactNote: ?string, heroCta: ?array{label: string, href: string}}
     */
    private function siteMeta(Collection $sections, Collection $templateSlots): array
    {
        $nav = $sections->skip(1)
            ->filter(fn ($s) => filled($s['label'] ?? null))
            ->map(fn ($s) => ['key' => $s['key'], 'label' => $s['label']])
            ->values()
            ->all();

        $plain = fn ($value) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        $firstTextarea = fn (?array $section) => $section
            ? $section['items']->first(fn ($item) => $item['slot']->slot_type === 'textarea')
            : null;

        $hero = $sections->first();
        $tagline = $firstTextarea($hero) ?? $firstTextarea($sections->first(fn ($s) => $s['key'] === 'about'));

        $contact = $sections->first(fn ($s) => self::isContactKey($s['key'])) ?? $sections->first(fn ($s) => $s['kind'] === 'cta');
        $contactTexts = $contact ? $contact['items']->filter(fn ($item) => in_array($item['slot']->slot_type, ['text', 'textarea'], true))->values() : collect();
        $contactNote = $contactTexts->count() > 1 ? $contactTexts->last() : null;

        // زرار احتياطي في الهيرو لما خانة الرابط هناك فاضية (كل قوالب المكتبة افتراضياً): بنفس
        // اسم الخانة ("اطلب دلوقتي")، وبيودّي على قسم التواصل في نفس الصفحة — مش رابط وهمي.
        $heroCta = null;
        if ($hero && $contact && $contact['key'] !== $hero['key']
            && ! $hero['items']->contains(fn ($item) => $item['slot']->slot_type === 'link')) {
            $emptySlot = $templateSlots->first(fn ($slot) => $slot->section_key === $hero['key'] && $slot->slot_type === 'link');
            $label = $emptySlot ? trim((string) $emptySlot->label()) : '';

            $heroCta = [
                'label' => $label !== '' && ! str_starts_with($label, 'رابط') && ! str_contains($label, '(') ? $label : 'تواصل معنا',
                'href' => '#'.$contact['key'],
            ];
        }

        return [
            'nav' => $nav,
            'tagline' => $tagline ? Str::limit($plain($tagline['value']), 150) : null,
            'contactNote' => $contactNote ? Str::limit($plain($contactNote['value']), 160) : null,
            'heroCta' => $heroCta,
        ];
    }

    // عنصر قايمة خدمات بصيغة "عنوان — وصف قصير" (أو "صنف — سعر") ← {title, desc}. عنصر من غير
    // " — " عنوان بس.
    /**
     * @return array{title: string, desc: ?string}
     */
    public static function splitEntry(mixed $raw): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $raw)));
        $parts = preg_split('/\s+[—–]\s+/u', $text, 2);

        return [
            'title' => $parts[0],
            'desc' => isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : null,
        ];
    }

    // ---------- آراء العملاء ----------

    /**
     * "الرأي — الاسم ★5" ← {quote, name, initial, stars}. عنصر من غير " — " اقتباس بس.
     *
     * @return array{quote: string, name: ?string, initial: ?string, stars: ?int}
     */
    public static function parseTestimonial(string $raw): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($raw)));
        $stars = null;

        if (preg_match('/\s*(?:★|⭐)\s*([1-5])\s*$/u', $text, $m)) {
            $stars = (int) $m[1];
            $text = trim(mb_substr($text, 0, mb_strlen($text) - mb_strlen($m[0])));
        } elseif (preg_match('/\s*((?:★|⭐){1,5})\s*$/u', $text, $m)) {
            $stars = mb_strlen($m[1]);
            $text = trim(mb_substr($text, 0, mb_strlen($text) - mb_strlen($m[0])));
        }

        $quote = $text;
        $name = null;

        // آخر فاصل في الجملة (الاقتباس نفسه ممكن يكون فيه شرطة)، والاسم لازم يبقى قصير فعلاً
        // (مش نص جملة) عشان مانقطعش رأي فيه " - " في نصه.
        if (preg_match('/^(.*\S)\s+[—–-]\s+(\S.*)$/su', $text, $m)
            && mb_strlen($m[2]) <= 40
            && count(preg_split('/\s+/u', $m[2])) <= 5) {
            $quote = $m[1];
            $name = trim($m[2]);
        }

        // علامات تنصيص حوالين الرأي نفسه بتتشال (الكارت بيحط علامته هو) — preg بـ/u مش trim()
        // لأن trim بيشتغل بايت بايت وكان هيقص آخر بايت من حرف عربي.
        $quote = preg_replace('/^["\'“”«»„\s]+|["\'“”«»„\s]+$/u', '', $quote);

        $initial = null;
        if ($name !== null) {
            $bare = preg_replace('/^(?:د|م|أ|ا|Dr|Mr|Mrs|Ms)\.\s*/u', '', $name);
            $initial = mb_strtoupper(mb_substr(trim($bare) ?: $name, 0, 1));
        }

        return ['quote' => $quote, 'name' => $name, 'initial' => $initial, 'stars' => $stars];
    }

    // رقم التقييم من عنوان قسم الآراء ("تقييمنا 4.7 ★ على جوجل من 437 تقييم") — للنجوم بس.
    private function ratingFrom(Collection $items): ?float
    {
        foreach ($items as $item) {
            if (! in_array($item['slot']->slot_type, ['text', 'textarea'], true)) {
                continue;
            }

            $text = strip_tags((string) $item['value']);
            if (! preg_match('/★|⭐|جوجل|google/iu', $text)) {
                continue;
            }

            if (preg_match('/(?<![\d.])([1-5](?:[.,]\d)?)(?![\d.,]*\d{2})/u', $text, $m)) {
                $rating = (float) str_replace(',', '.', $m[1]);

                return $rating >= 1 && $rating <= 5 ? $rating : null;
            }
        }

        return null;
    }

    // ---------- الألوان والخطوط ----------

    /**
     * أوضح لون نص فوق خلفية معيّنة: أبيض لو تباينه 4.5 أو أكتر، وإلا الأوضح بين الأبيض
     * وأغمق لون في اللوحة (أو كحلي شبه أسود لو اللوحة كلها فاتحة).
     *
     * @param  list<string>  $paletteCandidates
     */
    public static function onColor(string $background, array $paletteCandidates = []): string
    {
        $bg = self::luminance($background);
        if ($bg === null) {
            return '#ffffff';
        }

        // أغمق لون في اللوحة نفسها (لو غامق كفاية) عشان النص يبقى من نفس عيلة الألوان.
        $dark = '#0b0f19';
        $darkest = 0.03;
        foreach ($paletteCandidates as $candidate) {
            $l = self::luminance($candidate);
            if ($l !== null && $l < $darkest) {
                $dark = strtolower(trim($candidate));
                $darkest = $l;
            }
        }

        $onWhite = 1.05 / ($bg + 0.05);
        $onDark = ($bg + 0.05) / ((self::luminance($dark) ?? 0) + 0.05);

        return ($onWhite >= 4.5 || $onWhite >= $onDark) ? '#ffffff' : $dark;
    }

    public static function luminance(string $hex): ?float
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return null;
        }

        $channel = function (string $pair): float {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel(substr($hex, 0, 2)) + 0.7152 * $channel(substr($hex, 2, 2)) + 0.0722 * $channel(substr($hex, 4, 2));
    }

    private static function validFont(mixed $key): ?string
    {
        return is_string($key) && array_key_exists($key, TemplateVariant::FONTS) ? $key : null;
    }

    // ---------- شبكات بتحسب الأعمدة حسب العدد (صفر خانات فاضية) ----------
    // كل الكلاسات هنا نصوص حرفية كاملة عمداً — Tailwind بيلاقيها بالفحص الثابت للملف ده
    // (لازم npm run build بعد أي تعديل، قاعدة 4). متبنيش اسم كلاس من أجزاء.

    // عرض كارت جوّه "flex flex-wrap justify-center gap-6" — آخر صف دايماً في النص (5 = 3+2،
    // 4 = 2+2، 1 = كارت واحد في النص) بدل خانة فاضية على جنب.
    public static function cardWidth(int $count): string
    {
        return match (true) {
            $count <= 1 => 'w-full sm:max-w-xl',
            $count === 2 || $count === 4 => 'w-full sm:w-[calc(50%-0.75rem)]',
            default => 'w-full sm:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)]',
        };
    }

    /**
     * معرض صور (1-6 صور) على شبكة 2 عمود موبايل / 6 أعمدة من sm: أول صورة "رئيسية" كبيرة،
     * والباقي بيملّي الصفوف بالظبط لأي عدد. الحاوية لازم يبقى عليها كلاس 'grid' والـrows
     * (ارتفاع الصف) — الصور جوّه h-full w-full object-cover.
     *
     * @return array{grid: string, items: list<string>}
     */
    public static function mosaic(int $count): array
    {
        $feature = 'col-span-2 row-span-2 sm:col-span-4 sm:row-span-2';
        $small = 'col-span-1 sm:col-span-2';

        $items = match (true) {
            $count <= 1 => ['col-span-2 row-span-2 sm:col-span-6 sm:row-span-2'],
            $count === 2 => ['col-span-1 row-span-2 sm:col-span-3 sm:row-span-2', 'col-span-1 row-span-2 sm:col-span-3 sm:row-span-2'],
            $count === 3 => [$feature, $small, $small],
            $count === 4 => [$feature, $small, $small, 'col-span-2 sm:col-span-6'],
            $count === 5 => [$feature, $small, $small, 'col-span-1 sm:col-span-3', 'col-span-1 sm:col-span-3'],
            default => [$feature, $small, $small, $small, $small, 'col-span-2 sm:col-span-2'],
        };

        // أكتر من 6 (قالب معمول يدوي): الزيادة بتكمّل صفوف تلاتيات عادية.
        for ($i = count($items); $i < $count; $i++) {
            $items[] = $small;
        }

        return ['grid' => 'grid grid-cols-2 sm:grid-cols-6', 'items' => $items];
    }

    /**
     * كروت بأحجام مختلفة على شبكة 6 أعمدة (بينتو/بولد): أنصاص وأتلات بتملّي كل صف بالظبط
     * لأي عدد (5 = نصين + 3 أتلات، 7 = 4 أنصاص + 3 أتلات...). موبايل: عمودين، أول كارت عرض
     * كامل، وآخر كارت عرض كامل لو الباقي فردي.
     *
     * @return list<string>
     */
    public static function rowSpans(int $count): array
    {
        if ($count <= 1) {
            return ['col-span-2 sm:col-span-6'];
        }

        // count = 2*halves + 3*thirds — أكبر عدد أتلات ممكن.
        $thirds = intdiv($count, 3);
        while ($thirds >= 0 && ($count - 3 * $thirds) % 2 !== 0) {
            $thirds--;
        }
        $halves = $count - 3 * max($thirds, 0);

        $spans = [];
        for ($i = 0; $i < $count; $i++) {
            $desktop = $i < $halves ? 'sm:col-span-3' : 'sm:col-span-2';
            $mobileFull = $i === 0 || ($i === $count - 1 && ($count - 1) % 2 === 1);
            $spans[] = ($mobileFull ? 'col-span-2' : 'col-span-1').' '.$desktop;
        }

        return $spans;
    }
}
