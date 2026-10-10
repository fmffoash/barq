<?php

namespace App\Support;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;

/**
 * فهم رسالة التعديل من غير ذكاء اصطناعي (2026-10-08) — الطلبات الواضحة اللي كلمة واحدة بتحددها
 * ("غيّر القالب لحاجة أفخم"، "خليه أزرق"، "الخط تجوال"، "كبّر الخط"، "رجّع كل حاجة"، "حط الصورة
 * دي في الغلاف") بتتنفّذ في ثانية بدل ما فؤاد يستنى النموذج دقيقة عشان يصنّفها — والنموذج
 * الصغير كان بيغلط في تصنيف طلبات واضحة زي دي أصلاً (موثّق في CLAUDE.md).
 *
 * detect() بترجّع نفس شكل قرار النموذج بالظبط (action + بياناته) عشان التطبيق يفضل كود واحد،
 * أو null لو الطلب محتاج فهم حقيقي (تعديل نص بعينه، إضافة فقرة بمحتوى...). rewrite_content
 * محتاج النموذج برضه بس بطلب محتوى مباشر (مش تصنيف).
 */
class FollowUpIntent
{
    // اسم اللون (بعد التوحيد) ← درجته. العبارات الطويلة الأول ("ازرق غامق" قبل "ازرق").
    private const COLORS = [
        'ازرق غامق' => '#1e3a8a', 'ازرق فاتح' => '#38bdf8', 'اخضر غامق' => '#166534', 'اخضر فاتح' => '#4ade80',
        'احمر غامق' => '#991b1b', 'كحلي' => '#1e3a8a', 'نيلي' => '#3730a3', 'سماوي' => '#0ea5e9', 'لبني' => '#7dd3fc',
        'ازرق' => '#2563eb', 'زرقا' => '#2563eb', 'زرقاء' => '#2563eb', 'احمر' => '#dc2626', 'حمرا' => '#dc2626',
        'حمراء' => '#dc2626', 'نبيتي' => '#7f1d1d', 'عنابي' => '#7f1d1d', 'بوردو' => '#7f1d1d', 'زيتي' => '#4d7c0f',
        'اخضر' => '#16a34a', 'خضرا' => '#16a34a', 'خضراء' => '#16a34a', 'تركواز' => '#14b8a6', 'تركوازي' => '#14b8a6',
        'فيروزي' => '#14b8a6', 'تيل' => '#0d9488', 'اصفر' => '#eab308', 'صفرا' => '#eab308', 'صفراء' => '#eab308',
        'ذهبي' => '#ca8a04', 'دهبي' => '#ca8a04', 'برتقالي' => '#ea580c', 'اورنج' => '#ea580c', 'بنفسجي' => '#7c3aed',
        'موف' => '#9333ea', 'ليلكي' => '#a78bfa', 'بينك' => '#db2777', 'وردي' => '#ec4899', 'روز' => '#f472b6',
        'فوشيا' => '#c026d3', 'فوشي' => '#c026d3', 'بني' => '#92400e', 'بيج' => '#c8a97e', 'رمادي' => '#6b7280',
        'رصاصي' => '#6b7280', 'فضي' => '#9ca3af', 'اسود' => 'dark', 'سودا' => 'dark', 'سوداء' => 'dark', 'سوده' => 'dark',
        'غامق' => 'dark', 'غامقه' => 'dark', 'دارك' => 'dark', 'dark' => 'dark', 'ابيض' => 'light', 'بيضا' => 'light',
        'بيضاء' => 'light', 'بيضه' => 'light', 'فاتح' => 'light', 'فاتحه' => 'light', 'light' => 'light',
    ];

    // وضع ليلي/نهاري كامل — مش لون واحد (خلفية سودا مع كروت فاتحة شكلها بايظ).
    private const THEMES = [
        'dark' => ['background' => '#0b0f19', 'surface' => '#111827', 'text' => '#f1f5f9', 'muted' => '#94a3b8'],
        'light' => ['background' => '#ffffff', 'surface' => '#f3f4f6', 'text' => '#111827', 'muted' => '#4b5563'],
    ];

    // وصف الخط (من اللي بين القوسين في أسماء TemplateVariant::FONTS) ← كلمات فؤاد.
    private const FONT_STYLES = [
        'كلاسيكي' => ['كلاسيك', 'كلاسيكي', 'قديم', 'تقليدي'],
        'عريض' => ['عريض', 'تقيل', 'تخين'],
        'خط يد' => ['خط يد', 'رقعه', 'مكتوب باليد'],
        'دائري' => ['دائري', 'مدور', 'كيوت'],
        'عصري' => ['عصري', 'مودرن', 'حديث'],
        'زخرفي' => ['زخرفي', 'مزخرف'],
    ];

    /**
     * @return array<string, mixed>|null قرار بنفس شكل رد النموذج، أو null = محتاج النموذج
     */
    public static function detect(string $message, bool $hasImage, Project $project, GeneratedSite $site): ?array
    {
        [$words, $joined] = CategoryGuesser::words($message);
        $has = fn (string ...$options) => self::hasAny($words, $joined, $options);

        $change = $has('غير', 'غيري', 'بدل', 'بدلي', 'عايز', 'عاوز', 'عايزه', 'جرب', 'هات', 'اختار', 'خلي', 'خليه', 'خليها', 'خليلي', 'حط', 'تاني', 'مختلف', 'جديد', 'اعمل', 'عدل');

        // 1. رجوع للأصل — بيمسح كل تعديل، فلازم "كل"/"التعديلات"/"الموقع" صريحة ("رجّع العنوان
        //    زي ما كان" أو "امسح كل الخدمات" مش رجوع كامل — دول للنموذج).
        if (($has('رجع', 'ارجع', 'رجعي', 'الغي', 'الغى', 'تراجع') && $has('كل', 'كله', 'كلها', 'التعديلات', 'تعديلاتي', 'الموقع', 'موقع'))
            || ($has('امسح', 'شيل') && $has('التعديلات', 'تعديلاتي'))) {
            return ['action' => 'reset_to_default'];
        }

        $project->loadMissing('template.slots');
        $imageSlots = $project->template->slots->where('slot_type', 'image')->values();

        // 2. صورة مرفقة: الغلاف / صورة معرض برقمها / صورة جديدة.
        if ($hasImage) {
            if ($has('غلاف', 'هيرو', 'رئيسيه', 'الرئيسيه', 'كبيره', 'بانر', 'cover', 'hero') && ($slot = self::heroImageSlot($project->template))) {
                return ['action' => 'update_image', 'slot_key' => $slot];
            }
            if ($has('معرض', 'جاليري', 'gallery') && ($index = self::ordinal($words, $joined))) {
                $gallery = $imageSlots->reject(fn ($s) => $s->key === self::heroImageSlot($project->template))->values();
                if ($slot = $gallery->get($index - 1)) {
                    return ['action' => 'update_image', 'slot_key' => $slot->key];
                }
            }
            if ($has('ضيف', 'ضيفي', 'زود', 'زودي', 'اضافه', 'جديده', 'جديد')) {
                return ['action' => 'add_custom_block', 'block_type' => 'image', 'label' => 'صورة مضافة'];
            }

            return null;
        }

        // 3. مسح عنصر مضاف بالاسم.
        if ($has('امسح', 'شيل', 'احذف') && ! empty($site->custom_blocks_json)) {
            foreach ($site->custom_blocks_json as $block) {
                $label = PastedText::normalizeArabic((string) ($block['label'] ?? ''));
                if ($label !== '' && str_contains(PastedText::normalizeArabic($message), $label)) {
                    return ['action' => 'remove_custom_block', 'label' => $block['label']];
                }
            }
        }

        // 4. قالب — كلمة "قالب/تصميم/تيمبلت" صريحة، أو "شكل" مع فعل تغيير.
        if ($has('قالب', 'القالب', 'تيمبلت', 'template', 'layout', 'تصميم', 'ستايل') || ($has('شكل', 'شكله', 'شكلها') && $change)) {
            $decision = ['action' => 'change_template', 'style_hint' => $message];
            $categories = Template::where('is_active', true)->where('kind', 'landing')->distinct()->pluck('category');
            $guess = CategoryGuesser::guess($message, $categories);
            if ($guess && $guess['category'] !== $project->template->category) {
                $decision['category'] = $guess['category'];
            }

            return $decision;
        }

        // 5. حجم الخط ("كبّر الخط"، "الكلام أصغر").
        if ($has('خط', 'الخط', 'الكلام', 'النص', 'الكتابه') && ($dir = self::sizeDirection($words))) {
            $current = (float) ($site->font_size_scale_override ?: 1.0);

            return ['action' => 'update_font_size', 'scale' => round(max(0.8, min(1.4, $current + 0.1 * $dir)), 2)];
        }

        // 6. الخط باسمه أو بوصفه.
        if ($has('خط', 'الخط', 'فونت', 'font')) {
            if ($font = self::fontFrom($joined, $words)) {
                return ['action' => 'update_font', 'font' => $font];
            }
        }

        // 7. الألوان.
        if ($color = self::colorFrom($joined)) {
            $target = $has('خلفيه', 'الخلفيه', 'background') ? 'background'
                : ($has('لون الكلام', 'لون النص', 'لون الكتابه') ? 'text' : 'primary');

            if (isset(self::THEMES[$color])) {
                return ['action' => 'update_colors', 'colors' => self::THEMES[$color]];
            }

            return ['action' => 'update_colors', 'colors' => [$target => $color]];
        }

        // 8. "اكتب المحتوى من جديد" — النموذج يكتب كل النصوص تاني. "من جديد/تاني/كله" لازمة:
        //    "اكتب في المحتوى إننا بنقفل الجمعة" تعديل معيّن، مش إعادة كتابة كل حاجة.
        if ($has('اكتب', 'اعد', 'اعيد', 'جدد', 'غير') && $has('المحتوي', 'محتوي', 'الكلام', 'النصوص') && $has('جديد', 'تاني', 'كله', 'كلها', 'من الاول')) {
            return ['action' => 'rewrite_content'];
        }

        return null;
    }

    /**
     * @param  array<string, true>  $words
     * @param  list<string>  $options
     */
    private static function hasAny(array $words, string $joined, array $options): bool
    {
        foreach ($options as $option) {
            $option = PastedText::normalizeArabic($option);
            if (str_contains($option, ' ') ? str_contains($joined, ' '.$option.' ') : isset($words[$option])) {
                return true;
            }
        }

        return false;
    }

    public static function heroImageSlot(Template $template): ?string
    {
        $template->loadMissing('slots');

        return $template->slots->where('slot_type', 'image')->firstWhere('section_key', 'hero')?->key;
    }

    /**
     * @param  array<string, true>  $words
     */
    private static function ordinal(array $words, string $joined): ?int
    {
        $map = [
            1 => ['1', 'الاولي', 'اولي', 'الاول', 'اول', 'واحد'],
            2 => ['2', 'التانيه', 'تانيه', 'الثانيه', 'ثانيه', 'التاني', 'اتنين'],
            3 => ['3', 'التالته', 'تالته', 'الثالثه', 'ثالثه', 'التالت', 'تلاته'],
            4 => ['4', 'الرابعه', 'رابعه', 'اربعه'],
        ];

        foreach ($map as $n => $options) {
            if (self::hasAny($words, $joined, $options)) {
                return $n;
            }
        }

        return null;
    }

    /**
     * @param  array<string, true>  $words
     */
    private static function sizeDirection(array $words): int
    {
        // صيغة "أكبر/كبّر" بس — "الخط كبير" شكوى ممكن تبقى عايزه أصغر، فمتروكة للنموذج.
        foreach (['اكبر', 'كبر', 'كبري', 'كبره'] as $w) {
            if (isset($words[$w])) {
                return 1;
            }
        }
        foreach (['اصغر', 'صغر', 'صغري', 'صغره'] as $w) {
            if (isset($words[$w])) {
                return -1;
            }
        }

        return 0;
    }

    /**
     * @param  array<string, true>  $words
     */
    private static function fontFrom(string $joined, array $words): ?string
    {
        foreach (TemplateVariant::FONTS as $key => $label) {
            $name = PastedText::normalizeArabic(trim(preg_replace('/\s*\(.*\)\s*/u', '', $label) ?? $label));
            $latin = str_replace('-', ' ', $key);
            if (($name !== '' && str_contains($joined, ' '.$name.' ')) || str_contains($joined, ' '.$latin.' ')) {
                return $key;
            }
        }

        foreach (self::FONT_STYLES as $descriptor => $variants) {
            if (self::hasAny($words, $joined, $variants)) {
                foreach (TemplateVariant::FONTS as $key => $label) {
                    if (str_contains($label, '('.$descriptor) || str_contains($label, '، '.$descriptor)) {
                        return $key;
                    }
                }
            }
        }

        return null;
    }

    private static function colorFrom(string $joined): ?string
    {
        foreach (self::COLORS as $name => $value) {
            if (str_contains($joined, ' '.$name.' ')) {
                return $value;
            }
        }

        return null;
    }
}
