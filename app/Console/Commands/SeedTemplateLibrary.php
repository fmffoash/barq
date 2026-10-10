<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Template;
use App\Models\TemplateSlot;
use App\Models\TemplateVariant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// بيولّد/يحدّث مكتبة القوالب الأصلية — 20 فئة × 15 قالب (البيانات كلها في
// database/data/template-library.php). من 2026-10-10 كل قالب في الفئة مختلف فعلاً عن إخواته:
// عنوان ووصف هيرو خاصين بيه، نصوص باقي الصفحة حسب طابع تصميمه (LAYOUT_TONE)، صورة غلاف
// وترتيب معرض مختلفين، خط متن + خط عناوين حسب التصميم، ولون من عيلة لون مش متكررة في الفئة.
//
// الأمر آمن يتعاد في أي وقت (على السيرفر كمان، فيه مشاريع فؤاد الحقيقية):
// - الـslug ثابت في البيانات، فتغيير اسم قالب بيحدّث نفس الصف بدل ما يكرره.
// - قبل ما يغيّر أي حاجة مشاريع قايمة بتعتمد عليها (ألوان/خط النسخة، ترتيب الأقسام، القيمة
//   الافتراضية لخانة) بيثبّت القيمة الحالية جوّه موقع كل مشروع (freezeExistingSites) — فشكل
//   المواقع الموجودة مبيتغيرش. وتصميم (layout) أي قالب عليه مشاريع مبيتغيرش خالص.
// - مبيكتبش حاجة لو مفيش فرق، ولو اتشغّل بـ--if-outdated بيتخطّى نفسه لو المكتبة على آخر نسخة.
#[Signature('barq:seed-template-library
    {--if-outdated : Skip when the library in the database is already at the current LIBRARY_VERSION}')]
#[Description('توليد/تحديث مكتبة قوالب أصلية تغطي فئات نشاط شائعة (15 قالب لكل فئة)')]
class SeedTemplateLibrary extends Command
{
    // زوّدها مع أي تعديل في البيانات أو التوزيع — local/update وbarq:local-setup بيعيدوا التوليد
    // لما تختلف عن المتخزّنة في جدول settings، وعلى السيرفر خطوة في الديبلوي (شوف CLAUDE.md).
    public const LIBRARY_VERSION = '2026-10-10.1';

    public const VERSION_SETTING = 'template_library_version';

    public const LICENSE_NOTE = 'تصميم ومحتوى أصلي — صفر اعتماد على قالب خارجي.';

    // slugs قوالب كانت في المكتبة واتشالت من البيانات — بتتقفل (is_active=false) لو مفيش عليها
    // مشاريع. أي قالب يتشال من database/data/template-library.php يتضاف هنا.
    public const RETIRED_SLUGS = [];

    public const SECTIONS = ['hero', 'about', 'services', 'gallery', 'testimonials', 'contact'];

    // كل كلمة هنا بتربط شخصية تصميم (Template::LAYOUTS) بكلمات في الجزء اللي بعد "—" من اسم
    // القالب. التوزيع (assignLayouts) بيدّي كل قالب التصميم اللي اسمه بيوصفه. public عشان
    // AiProjectAssistantService وTemplatePicker بيستخدموها لما فؤاد يطلب طابع بالشات ("فاخر").
    /** @var array<string, list<string>> */
    public const LAYOUT_KEYWORDS = [
        'classic' => ['دافئ', 'دفء', 'أصيل', 'تراث', 'تقليد', 'ثقة وأمان', 'موثوق', 'أمان', 'طازة', 'نكهة أصيلة', 'واجهة دافئة', 'بداية آمنة', 'قيم من الصغر', 'ثقة من زمان', 'بنيان متين', 'تنفيذ موثوق', 'أساسيات موثوقة', 'مالك بأمان', 'سفر مسؤول', 'يومك بثقة', 'رائحة تدوم', 'وصفات من الجدة'],
        'modern' => ['عصري', 'عصرية', 'حديث', 'حديثة', 'إطلالة عصرية'],
        'gallery' => ['معرض', 'جولة', 'تصوير', 'بورتريه', 'صور', 'أعمال', 'عرض جذاب', 'تصميم يفرح العين', 'براءة موثقة', 'لكل مناسبة', 'اكتشف بلدك', 'إطلالة مميزة', 'إطلالة كاملة', 'واجهة جاذبة'],
        'split' => ['وسيط', 'بساطة وثقة', 'مرونة', 'حلول سريعة', 'توصلك', 'إجراءات سهلة', 'تجربة سلسة', 'حلول مخصصة', 'جمع بين', 'خبرة عند الطلب', 'لغات بلا حواجز', 'جودة بسعر مناسب', 'سعر مناسب', 'مرونة للعاملين', 'إطلالة عملية'],
        'magazine' => ['هدوء وإلهام', 'كاتب', 'كلمات مؤثرة', 'تفاصيل ساحرة', 'تصميم حسب الطلب', 'بيئة عمل ملهمة', 'لمسة أخيرة', 'حكاية', 'حكايتك', 'دليلك'],
        'bento' => ['شامل', 'متكامل', 'منصة', 'مميزات', 'رعاية شاملة', 'خدمة شاملة', 'تعلم باللعب', 'بيئة محفزة', 'تدريب ورعاية', 'تفاعلي', 'تعلم وترفيه', 'متعة منظمة', 'نتائج قابلة للقياس', 'تنظيم أفضل', 'محتوى يبيع', 'حلول للشركات'],
        'minimal' => ['بساطة', 'هدوء', 'نظافة', 'نقاء', 'واضح', 'بسيط', 'خصوصية', 'دعم بهدوء', 'اهتمام شخصي', 'اهتمام خاص', 'روتين مثالي', 'جمال طبيعي', 'كود نظيف', 'تصميم شخصي', 'بديل ألذ وأخف', 'إطلالة نظيفة', 'أناقة مسؤولة', 'صحة ولمعان'],
        'bold' => ['طاقة', 'قوة', 'جريء', 'جريئة', 'تحدي', 'قتالية', 'إثارة', 'مغامرة', 'نار', 'حضور قوي', 'ألوان جريئة', 'ستايل واثق', 'فرح بلا حدود', 'طاقة وحركة'],
        'glass' => ['فخامة', 'رقي', 'راقي', 'أنيق', 'أناقة', 'حصري', 'حصرية', 'استثنائي', 'جودة استثنائية', 'تجربة راقية', 'أنوثة راقية', 'تركيبات نادرة', 'متعة فاخرة', 'رقي في كل قضمة', 'بريق خاص', 'بريق دايم', 'عناية استثنائية', 'رعاية استثنائية', 'انطلاقة عالمية', 'رعاية متكاملة'],
        'timeline' => ['رحلة', 'مراحل', 'تدريجي', 'تطور', 'خطوة', 'انطلاقة', 'مستقبل', 'نمو آمن', 'لحظات لا تُنسى', 'قصص متحركة', 'استقلالية مبكرة', 'استعداد للمدرسة', 'سلوك أفضل', 'فرصتك التالية', 'نمو شخصي', 'يوم لا يُنسى', 'بداية لا تُنسى', 'ذكريات مشتركة', 'رحلة روحانية', 'نكهة الشهر الكريم', 'لحظة مميزة', 'مهارات المستقبل', 'فكرة تتحول لواقع'],
        'stack' => ['مساحة', 'مساحات', 'مريح', 'بيتك', 'عائلي', 'عائلية', 'دفء البيت', 'راحة', 'راحة بال', 'طعم البيت', 'قهوة وحلا مع بعض', 'مساحتك المثالية', 'دفء ومساحة', 'مساحات مدروسة', 'رعاية بمحبة', 'راحة في غيابك', 'بيت جديد وحب', 'راحة وأناقة'],
        'diagonal' => ['حركة', 'حيوية', 'ديناميك', 'رياضي', 'سرعة', 'أداء', 'استجابة سريعة', 'انتعاش', 'وقتك محسوب', 'سرعة وثقة', 'منظور مختلف', 'استجابة فورية', 'طبيعة قريبة', 'أسرع الطرق', 'سرعة واستقرار', 'طاقة قتالية', 'مستقبل أخضر'],
        'framed' => ['إطار', 'توثيق', 'دقة', 'رسمي', 'احترافي', 'احترافية عالية', 'رفيع', 'تفاصيل دقيقة', 'تشخيص دقيق', 'إصلاح احترافي', 'تنظيم احترافي', 'انطباع احترافي', 'انطباع أول قوي', 'عناية دقيقة', 'تحرير احترافي', 'حماية دائمة', 'التزام', 'امتثال'],
        'neon' => ['ليلي', 'ليل', 'شبابي', 'تريند', 'نادي', 'أجواء عصرية', 'ألوان مرحة'],
        'duotone' => ['فني', 'فنون', 'تصميم مبتكر', 'رقمي', 'سيبراني', 'تقنية', 'مبتكر', 'هوية بصرية', 'إبداع بلا حدود', 'إبداع بلا قيود', 'حركة وإبداع', 'خبرة نادرة', 'تقنية متطورة', 'تقنية تخدم صحتك', 'حماية رقمية', 'طبيعة وإبداع'],
        'signature' => ['فاخر', 'فاخرة', 'توقيع', 'توقيعك', 'نخبة', 'رفاهية', 'رفاهية كاملة', 'رفاهية بلا حدود'],
    ];

    // طابع كل تصميم — بيحدد نسخة النصوص (tones في ملف البيانات) والألوان والخطوط المفضّلة ليه.
    /** @var array<string, string> */
    public const LAYOUT_TONE = [
        'classic' => 'warm', 'stack' => 'warm',
        'glass' => 'luxury', 'signature' => 'luxury', 'framed' => 'luxury',
        'bold' => 'bold', 'diagonal' => 'bold', 'neon' => 'bold',
        'minimal' => 'calm', 'magazine' => 'calm', 'split' => 'calm',
        'modern' => 'modern', 'bento' => 'modern', 'timeline' => 'modern', 'gallery' => 'modern', 'duotone' => 'modern',
    ];

    // كلمات بتدل على طابع الاسم — بتكسر التعادل في التوزيع لصالح تصميم من نفس الطابع.
    private const TONE_KEYWORDS = [
        'warm' => ['دافئ', 'دفء', 'عائلي', 'عائلية', 'بيت', 'أصيل', 'محبة', 'زمان', 'راحة', 'دافئة'],
        'luxury' => ['فاخر', 'فاخرة', 'راقي', 'راقية', 'رقي', 'أنيق', 'أناقة', 'حصري', 'حصرية', 'نخبة', 'رفاهية', 'رفيع', 'استثنائي'],
        'bold' => ['طاقة', 'قوة', 'قوي', 'جريء', 'جريئة', 'تحدي', 'شبابي', 'شبابية', 'سرعة', 'سريع', 'نار', 'إثارة', 'مغامر'],
        'calm' => ['هدوء', 'هادئ', 'بساطة', 'بسيط', 'بسيطة', 'واضح', 'واضحة', 'خصوصية', 'نقاء', 'سهلة', 'سلسة'],
        'modern' => ['عصري', 'عصرية', 'حديث', 'حديثة', 'تقنية', 'رقمي', 'رقمية', 'مبتكر', 'ذكي', 'متطورة', 'منصة'],
    ];

    // لوحات الألوان. "family" = عيلة اللون (مفيش عيلة بتتكرر جوّه الفئة)، و"mode" = نوع الخلفية:
    // dark (شبه سودا) / light (شبه بيضا) / mid-dark و mid-light (خلفية ملوّنة فعلاً). لون muted
    // في كل لوحة متظبط بحيث تباينه ≥ 4.5:1 مع الخلفية والـsurface الاتنين (WCAG AA) — التست
    // TemplateLibrarySeedTest بيحسبه. أي لوحة جديدة لازم تعدّي نفس الفحص.
    /** @var array<string, array{family: string, mode: string, colors: array{primary: string, background: string, surface: string, text: string, muted: string}}> */
    public const PALETTES = [
        'amber' => ['family' => 'amber', 'mode' => 'dark', 'colors' => ['primary' => '#f59e0b', 'background' => '#0b1220', 'surface' => '#111a2e', 'text' => '#f1f5f9', 'muted' => '#94a3b8']],
        'amber-light' => ['family' => 'amber', 'mode' => 'light', 'colors' => ['primary' => '#b45309', 'background' => '#fffbeb', 'surface' => '#fef3c7', 'text' => '#292116', 'muted' => '#88682d']],
        'emerald' => ['family' => 'emerald', 'mode' => 'dark', 'colors' => ['primary' => '#10b981', 'background' => '#0a1612', 'surface' => '#0f2019', 'text' => '#ecfdf5', 'muted' => '#8fae9f']],
        'emerald-light' => ['family' => 'emerald', 'mode' => 'light', 'colors' => ['primary' => '#047857', 'background' => '#ecfdf5', 'surface' => '#d1fae5', 'text' => '#0f2419', 'muted' => '#467561']],
        'sky' => ['family' => 'sky', 'mode' => 'dark', 'colors' => ['primary' => '#38bdf8', 'background' => '#0b1524', 'surface' => '#101c30', 'text' => '#eff6ff', 'muted' => '#93a8c4']],
        'sky-light' => ['family' => 'sky', 'mode' => 'light', 'colors' => ['primary' => '#0369a1', 'background' => '#f0f9ff', 'surface' => '#e0f2fe', 'text' => '#0c2233', 'muted' => '#457189']],
        'rose' => ['family' => 'rose', 'mode' => 'dark', 'colors' => ['primary' => '#fb7185', 'background' => '#180d12', 'surface' => '#24141b', 'text' => '#fdf2f4', 'muted' => '#c7a3ab']],
        'rose-light' => ['family' => 'rose', 'mode' => 'light', 'colors' => ['primary' => '#be123c', 'background' => '#fff1f2', 'surface' => '#ffe4e6', 'text' => '#2a1116', 'muted' => '#945561']],
        'ruby' => ['family' => 'ruby', 'mode' => 'dark', 'colors' => ['primary' => '#ef4444', 'background' => '#1a0e0e', 'surface' => '#271414', 'text' => '#fef2f2', 'muted' => '#c9a3a3']],
        'ruby-light' => ['family' => 'ruby', 'mode' => 'light', 'colors' => ['primary' => '#b91c1c', 'background' => '#fef2f2', 'surface' => '#fee2e2', 'text' => '#2a1414', 'muted' => '#8e5757']],
        'teal' => ['family' => 'teal', 'mode' => 'dark', 'colors' => ['primary' => '#14b8a6', 'background' => '#08181a', 'surface' => '#0f2426', 'text' => '#ecfeff', 'muted' => '#8fbabd']],
        'teal-light' => ['family' => 'teal', 'mode' => 'light', 'colors' => ['primary' => '#0f766e', 'background' => '#f0fdfa', 'surface' => '#ccfbf1', 'text' => '#0b2320', 'muted' => '#46746f']],
        'indigo' => ['family' => 'indigo', 'mode' => 'dark', 'colors' => ['primary' => '#818cf8', 'background' => '#0d0e1a', 'surface' => '#14152a', 'text' => '#eef2ff', 'muted' => '#9ca3c9']],
        'indigo-light' => ['family' => 'indigo', 'mode' => 'light', 'colors' => ['primary' => '#4338ca', 'background' => '#eef2ff', 'surface' => '#e0e7ff', 'text' => '#1a1a3a', 'muted' => '#616190']],
        'gold' => ['family' => 'gold', 'mode' => 'dark', 'colors' => ['primary' => '#facc15', 'background' => '#1a1708', 'surface' => '#262008', 'text' => '#fefce8', 'muted' => '#cbbf94']],
        'gold-light' => ['family' => 'gold', 'mode' => 'light', 'colors' => ['primary' => '#a16207', 'background' => '#fefce8', 'surface' => '#fef9c3', 'text' => '#292408', 'muted' => '#7c6f32']],
        'lime' => ['family' => 'lime', 'mode' => 'dark', 'colors' => ['primary' => '#a3e635', 'background' => '#10140a', 'surface' => '#1a2210', 'text' => '#f7fee7', 'muted' => '#adc494']],
        'lime-light' => ['family' => 'lime', 'mode' => 'light', 'colors' => ['primary' => '#4d7c0f', 'background' => '#f7fee7', 'surface' => '#ecfccb', 'text' => '#1c2410', 'muted' => '#657449']],
        'cyan' => ['family' => 'cyan', 'mode' => 'dark', 'colors' => ['primary' => '#22d3ee', 'background' => '#071b1e', 'surface' => '#0c2a2e', 'text' => '#ecfeff', 'muted' => '#8fb8bd']],
        'cyan-light' => ['family' => 'cyan', 'mode' => 'light', 'colors' => ['primary' => '#0e7490', 'background' => '#ecfeff', 'surface' => '#cffafe', 'text' => '#0b2226', 'muted' => '#44737c']],
        'blue' => ['family' => 'blue', 'mode' => 'dark', 'colors' => ['primary' => '#3b82f6', 'background' => '#0a1020', 'surface' => '#10192e', 'text' => '#eff4ff', 'muted' => '#94a3c9']],
        'blue-light' => ['family' => 'blue', 'mode' => 'light', 'colors' => ['primary' => '#1d4ed8', 'background' => '#eff6ff', 'surface' => '#dbeafe', 'text' => '#101a33', 'muted' => '#4d6699']],
        'violet' => ['family' => 'violet', 'mode' => 'dark', 'colors' => ['primary' => '#a78bfa', 'background' => '#130f1e', 'surface' => '#1d1830', 'text' => '#f5f3ff', 'muted' => '#b3a8cf']],
        'violet-light' => ['family' => 'violet', 'mode' => 'light', 'colors' => ['primary' => '#6d28d9', 'background' => '#f5f3ff', 'surface' => '#ede9fe', 'text' => '#211a33', 'muted' => '#71608f']],
        'fuchsia' => ['family' => 'fuchsia', 'mode' => 'dark', 'colors' => ['primary' => '#e879f9', 'background' => '#1a0d1c', 'surface' => '#28142a', 'text' => '#fdf4ff', 'muted' => '#c9a3ca']],
        'fuchsia-light' => ['family' => 'fuchsia', 'mode' => 'light', 'colors' => ['primary' => '#a21caf', 'background' => '#fdf4ff', 'surface' => '#fae8ff', 'text' => '#2a1a2c', 'muted' => '#835c85']],
        'pink' => ['family' => 'pink', 'mode' => 'dark', 'colors' => ['primary' => '#f472b6', 'background' => '#1a0d14', 'surface' => '#27141e', 'text' => '#fdf2f8', 'muted' => '#c9a3b6']],
        'pink-light' => ['family' => 'pink', 'mode' => 'light', 'colors' => ['primary' => '#be185d', 'background' => '#fdf2f8', 'surface' => '#fce7f3', 'text' => '#2a141e', 'muted' => '#875d71']],
        'slate' => ['family' => 'slate', 'mode' => 'dark', 'colors' => ['primary' => '#94a3b8', 'background' => '#0d1117', 'surface' => '#161b22', 'text' => '#f1f5f9', 'muted' => '#8b96a5']],
        'slate-light' => ['family' => 'slate', 'mode' => 'light', 'colors' => ['primary' => '#334155', 'background' => '#f8fafc', 'surface' => '#f1f5f9', 'text' => '#0f172a', 'muted' => '#5f6e85']],
        // خلفيات ملوّنة (2026-10-10) — مش كل الكروت سودا أو بيضا.
        'sand' => ['family' => 'sand', 'mode' => 'mid-light', 'colors' => ['primary' => '#9a3412', 'background' => '#f3e5d0', 'surface' => '#e9d5b7', 'text' => '#2b1a10', 'muted' => '#6b4a35']],
        'sage' => ['family' => 'sage', 'mode' => 'mid-light', 'colors' => ['primary' => '#2f5d3a', 'background' => '#dfe9dc', 'surface' => '#cddcc8', 'text' => '#14241a', 'muted' => '#405a46']],
        'burgundy' => ['family' => 'burgundy', 'mode' => 'mid-light', 'colors' => ['primary' => '#7f1d2d', 'background' => '#f5ece0', 'surface' => '#eadbc7', 'text' => '#2a1216', 'muted' => '#6a4147']],
        'olive' => ['family' => 'olive', 'mode' => 'mid-light', 'colors' => ['primary' => '#556218', 'background' => '#efedd8', 'surface' => '#e2dfbf', 'text' => '#23260f', 'muted' => '#555936']],
        'denim' => ['family' => 'denim', 'mode' => 'mid-light', 'colors' => ['primary' => '#1e4f7a', 'background' => '#dde8f2', 'surface' => '#cbdbea', 'text' => '#0f2033', 'muted' => '#3f566e']],
        'navy' => ['family' => 'navy', 'mode' => 'mid-dark', 'colors' => ['primary' => '#e3b65a', 'background' => '#14233f', 'surface' => '#1c3052', 'text' => '#f7f2e6', 'muted' => '#b7c1d4']],
        'forest' => ['family' => 'forest', 'mode' => 'mid-dark', 'colors' => ['primary' => '#e9c46a', 'background' => '#16302a', 'surface' => '#1f3f36', 'text' => '#f1f5ec', 'muted' => '#b4c8bc']],
        'plum' => ['family' => 'plum', 'mode' => 'mid-dark', 'colors' => ['primary' => '#f6ad8f', 'background' => '#3a1d36', 'surface' => '#4a2745', 'text' => '#fcefe9', 'muted' => '#dcbccf']],
        'coffee' => ['family' => 'coffee', 'mode' => 'mid-dark', 'colors' => ['primary' => '#e0a765', 'background' => '#2e2019', 'surface' => '#3d2b21', 'text' => '#f8efe4', 'muted' => '#d0b9a3']],
        'ocean' => ['family' => 'ocean', 'mode' => 'mid-dark', 'colors' => ['primary' => '#ffd166', 'background' => '#0f3b4c', 'surface' => '#164a5e', 'text' => '#eef8fb', 'muted' => '#b3d3dd']],
    ];

    // عيلات ألوان قريبة من بعض في العين — كل ما يتاخد من نفس المجموعة في الفئة، الباقي بيبعد.
    private const PALETTE_GROUPS = [
        ['blue', 'indigo', 'denim', 'navy', 'sky'],
        ['cyan', 'sky', 'teal', 'ocean'],
        ['emerald', 'teal', 'forest', 'sage', 'lime', 'olive'],
        ['amber', 'gold', 'sand', 'coffee'],
        ['ruby', 'rose', 'burgundy', 'pink'],
        ['violet', 'indigo', 'fuchsia', 'plum', 'pink'],
    ];

    // العيلات اللي تناسب كل طابع (الأول أنسب).
    private const TONE_HUES = [
        'luxury' => ['gold', 'navy', 'burgundy', 'plum', 'coffee', 'slate', 'violet', 'forest', 'emerald', 'rose', 'indigo', 'ocean'],
        'bold' => ['ruby', 'amber', 'fuchsia', 'lime', 'blue', 'pink', 'cyan', 'violet', 'ocean', 'rose', 'sky', 'indigo'],
        'calm' => ['sage', 'sky', 'teal', 'denim', 'slate', 'olive', 'emerald', 'sand', 'indigo', 'cyan', 'blue', 'rose'],
        'warm' => ['sand', 'amber', 'coffee', 'olive', 'burgundy', 'rose', 'forest', 'gold', 'ruby', 'emerald', 'pink', 'teal'],
        'modern' => ['indigo', 'cyan', 'blue', 'violet', 'teal', 'sky', 'emerald', 'ocean', 'pink', 'lime', 'fuchsia', 'denim'],
    ];

    private const TONE_MODES = [
        'luxury' => ['dark', 'mid-dark'],
        'bold' => ['dark', 'light', 'mid-dark'],
        'calm' => ['light', 'mid-light'],
        'warm' => ['light', 'mid-light'],
        'modern' => ['dark', 'light', 'mid-dark', 'mid-light'],
    ];

    // "نيون" توهّجه مصمم لخلفية شبه سودا بس (على فاتح بيطلع هالة باهتة)، و"سيجنتشر" صورة الهيرو
    // بتتلاشى في لون الخلفية — على خلفية فاتحة كانت بتبان زي الشبورة.
    private const LAYOUT_MODES = [
        'neon' => ['dark'],
        'signature' => ['dark', 'mid-dark'],
    ];

    // خطوط كل تصميم: [خط المتن، خط العناوين] — كله عربي مستضاف محلياً (resources/css/app.css)
    // وفيه وزن 700 على الأقل. خطوط العرض اللي بوزن واحد (lalezar/rakkas/jomhuria) أو الزخرفية
    // (aref-ruqaa) مش هنا عن قصد: الخط العام بيتطبّق على المتن كمان، والعناوين بتتكتب بـbold.
    /** @var array<string, list<array{0: string, 1: string}>> */
    public const LAYOUT_FONTS = [
        'classic' => [['almarai', 'el-messiri'], ['cairo', 'amiri'], ['tajawal', 'markazi-text']],
        'modern' => [['tajawal', 'cairo'], ['readex-pro', 'alexandria'], ['cairo', 'readex-pro']],
        'gallery' => [['tajawal', 'amiri'], ['almarai', 'el-messiri'], ['readex-pro', 'markazi-text']],
        'split' => [['ibm-plex-arabic', 'alexandria'], ['tajawal', 'ibm-plex-arabic'], ['mada', 'readex-pro']],
        'magazine' => [['ibm-plex-arabic', 'amiri'], ['markazi-text', 'el-messiri'], ['mada', 'markazi-text']],
        'bento' => [['readex-pro', 'alexandria'], ['tajawal', 'cairo'], ['cairo', 'changa']],
        'minimal' => [['ibm-plex-arabic', 'ibm-plex-arabic'], ['readex-pro', 'readex-pro'], ['mada', 'mada']],
        'bold' => [['cairo', 'changa'], ['tajawal', 'noto-kufi-arabic'], ['almarai', 'alexandria']],
        'glass' => [['readex-pro', 'el-messiri'], ['tajawal', 'el-messiri'], ['ibm-plex-arabic', 'reem-kufi']],
        'timeline' => [['almarai', 'cairo'], ['cairo', 'alexandria'], ['tajawal', 'changa']],
        'stack' => [['almarai', 'el-messiri'], ['cairo', 'markazi-text'], ['tajawal', 'amiri']],
        'diagonal' => [['tajawal', 'changa'], ['cairo', 'alexandria'], ['readex-pro', 'noto-kufi-arabic']],
        'framed' => [['ibm-plex-arabic', 'amiri'], ['mada', 'el-messiri'], ['almarai', 'markazi-text']],
        'neon' => [['changa', 'reem-kufi'], ['tajawal', 'reem-kufi'], ['alexandria', 'changa']],
        'duotone' => [['readex-pro', 'reem-kufi'], ['alexandria', 'changa'], ['mada', 'reem-kufi']],
        'signature' => [['tajawal', 'amiri'], ['ibm-plex-arabic', 'el-messiri'], ['markazi-text', 'amiri']],
    ];

    private bool $hasHeadingFont = false;

    private int $frozenSites = 0;

    public function handle(): int
    {
        $categories = self::libraryData();
        $this->hasHeadingFont = Schema::hasColumn('template_variants', 'heading_font');

        if ($this->option('if-outdated') && $this->isUpToDate($categories)) {
            $this->line('Template library is up to date (version '.self::LIBRARY_VERSION.') — skipped.');

            return self::SUCCESS;
        }

        $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
        $produced = [];

        foreach ($categories as $categoryIndex => $category) {
            foreach ($this->planCategory($categoryIndex, $category) as $plan) {
                $counts[$this->upsertTemplate($category, $plan)]++;
                $produced[] = $plan['slug'];
            }

            $this->info("✓ {$category['category']} — ".count($category['templates']).' قالب');
        }

        $retired = $this->retireOldTemplates($produced);

        Setting::put(self::VERSION_SETTING, self::LIBRARY_VERSION);

        $this->newLine();
        $this->info('خلصت (نسخة المكتبة '.self::LIBRARY_VERSION."): {$counts['created']} قالب جديد، {$counts['updated']} محدّث، {$counts['unchanged']} من غير تغيير.");
        if ($this->frozenSites > 0) {
            $this->line("اتثبّت الشكل الحالي لـ{$this->frozenSites} موقع قايم (عشان تحديث المكتبة ميغيّرش شكلهم).");
        }
        if ($retired > 0) {
            $this->line("اتقفل {$retired} قالب قديم مالوش مشاريع ومش موجود في المكتبة الجديدة.");
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function libraryData(): array
    {
        return require database_path('data/template-library.php');
    }

    /**
     * خطة كل قوالب الفئة (من غير ما تلمس الداتابيز إلا لمعرفة التصميمات المتثبتة):
     * slug/name/layout/tone/palette/fonts/الصور/المحتوى الكامل.
     *
     * @param  array<string, mixed>  $category
     * @param  array<int, string>|null  $pinned  فهرس القالب => التصميم الثابت (null = من الداتابيز)
     * @return list<array<string, mixed>>
     */
    public function planCategory(int $categoryIndex, array $category, ?array $pinned = null): array
    {
        $templates = $category['templates'];
        $pinned ??= $this->pinnedLayouts($templates);
        $layouts = self::assignLayouts(array_column($templates, 'name'), $pinned);
        $palettes = self::paletteKeysFor($categoryIndex, $category['hues'] ?? [], $layouts, $category['max_dark'] ?? 7);
        $fonts = self::fontsFor($categoryIndex, $category, $layouts);

        $plans = [];
        foreach ($templates as $templateIndex => $entry) {
            $layout = $layouts[$templateIndex];
            $tone = self::LAYOUT_TONE[$layout];

            $plans[] = [
                'slug' => $entry['slug'],
                'name' => $entry['name'],
                'layout' => $layout,
                'tone' => $tone,
                'palette' => $palettes[$templateIndex],
                'font' => $fonts[$templateIndex]['font'],
                'heading_font' => $fonts[$templateIndex]['heading_font'],
                'content' => array_merge(
                    $category['tones'][$tone],
                    ['hero_title' => $entry['hero_title'], 'hero_subtitle' => $entry['hero_subtitle']],
                    self::photosFor($categoryIndex, $category, $templateIndex),
                ),
            ];
        }

        return $plans;
    }

    /**
     * توزيع أمثل للتصميمات على أسماء الفئة: كل تصميم مرة واحدة بالكتير، ومجموع تطابق كلمات
     * الأسماء مع LAYOUT_KEYWORDS أعلى ما يمكن (Hungarian algorithm — مش greedy، الـgreedy القديم
     * كان بيدّي الاسم الأول أحسن تصميم حتى لو ده بيحرم اسم تاني من التصميم الوحيد اللي يناسبه).
     * التعادل بيتكسر لصالح تصميم من نفس طابع الاسم (TONE_KEYWORDS).
     *
     * @param  list<string>  $names
     * @param  array<int, string>  $pinned  أسماء ليها تصميم ثابت (قوالب عليها مشاريع قايمة)
     * @return array<int, string>
     */
    public static function assignLayouts(array $names, array $pinned = []): array
    {
        $result = $pinned;
        $freeNames = array_values(array_diff(array_keys($names), array_keys($pinned)));
        $freeLayouts = array_values(array_diff(Template::LAYOUTS, $pinned));

        if ($freeNames === []) {
            ksort($result);

            return $result;
        }

        $size = max(count($freeNames), count($freeLayouts));
        $weights = [];
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                $nameIndex = $freeNames[$row] ?? null;
                $layout = $freeLayouts[$col] ?? null;
                $weights[$row][$col] = ($nameIndex === null || $layout === null)
                    ? 0
                    : self::keywordScore($names[$nameIndex], $layout) * 100
                        + (self::nameTone($names[$nameIndex]) === self::LAYOUT_TONE[$layout] ? 10 : 0);
            }
        }

        foreach (self::hungarianMax($weights) as $row => $col) {
            if (isset($freeNames[$row], $freeLayouts[$col])) {
                $result[$freeNames[$row]] = $freeLayouts[$col];
            }
        }

        ksort($result);

        return $result;
    }

    public static function keywordScore(string $name, string $layout): int
    {
        $score = 0;
        foreach (self::LAYOUT_KEYWORDS[$layout] as $keyword) {
            if (mb_stripos($name, $keyword) !== false) {
                $score++;
            }
        }

        return $score;
    }

    private static function nameTone(string $name): ?string
    {
        $scores = [];
        foreach (self::TONE_KEYWORDS as $tone => $keywords) {
            $scores[$tone] = count(array_filter($keywords, fn (string $keyword) => mb_stripos($name, $keyword) !== false));
        }
        arsort($scores);
        $top = array_slice($scores, 0, 2, true);
        [$first, $second] = array_values($top);

        return $first > 0 && $first > $second ? array_key_first($top) : null;
    }

    /**
     * Hungarian algorithm (Kuhn-Munkres) لمصفوفة مربعة — أعلى مجموع أوزان.
     *
     * @param  array<int, array<int, int>>  $weights
     * @return array<int, int> صف => عمود
     */
    private static function hungarianMax(array $weights): array
    {
        $n = count($weights);
        $max = max(array_map('max', $weights));
        $u = array_fill(0, $n + 1, 0);
        $v = array_fill(0, $n + 1, 0);
        $match = array_fill(0, $n + 1, 0);
        $way = array_fill(0, $n + 1, 0);

        for ($i = 1; $i <= $n; $i++) {
            $match[0] = $i;
            $col = 0;
            $minv = array_fill(0, $n + 1, PHP_INT_MAX);
            $used = array_fill(0, $n + 1, false);

            do {
                $used[$col] = true;
                $row = $match[$col];
                $delta = PHP_INT_MAX;
                $next = 0;

                for ($j = 1; $j <= $n; $j++) {
                    if ($used[$j]) {
                        continue;
                    }
                    $cost = ($max - $weights[$row - 1][$j - 1]) - $u[$row] - $v[$j];
                    if ($cost < $minv[$j]) {
                        $minv[$j] = $cost;
                        $way[$j] = $col;
                    }
                    if ($minv[$j] < $delta) {
                        $delta = $minv[$j];
                        $next = $j;
                    }
                }

                for ($j = 0; $j <= $n; $j++) {
                    if ($used[$j]) {
                        $u[$match[$j]] += $delta;
                        $v[$j] -= $delta;
                    } else {
                        $minv[$j] -= $delta;
                    }
                }

                $col = $next;
            } while ($match[$col] !== 0);

            do {
                $previous = $way[$col];
                $match[$col] = $match[$previous];
                $col = $previous;
            } while ($col !== 0);
        }

        $result = [];
        for ($j = 1; $j <= $n; $j++) {
            if ($match[$j] > 0) {
                $result[$match[$j] - 1] = $j - 1;
            }
        }
        ksort($result);

        return $result;
    }

    /**
     * لوحة لكل قالب: عيلة لون مختلفة لكل قالب في الفئة، مناسبة لطابع تصميمه وللفئة نفسها،
     * ومزيج من الخلفيات الداكنة/الفاتحة/الملوّنة (مش كله داكن ولا كله فاتح).
     *
     * @param  list<string>  $categoryHues  العيلات اللي تناسب الفئة (الأول أنسب)
     * @param  array<int, string>  $layouts
     * @param  int  $maxDark  أقصى عدد خلفيات داكنة قبل ما الاختيار يميل للفاتح (حضانات/عيادات أقل)
     * @return array<int, string> فهرس القالب => مفتاح اللوحة في PALETTES
     */
    public static function paletteKeysFor(int $categoryIndex, array $categoryHues, array $layouts, int $maxDark = 7): array
    {
        $maxLight = count($layouts) - $maxDark + 2;
        $familyOrder = array_flip(array_values(array_unique(array_column(self::PALETTES, 'family'))));
        $order = array_keys($layouts);
        // التصميمات اللي ليها قيود (نيون/سيجنتشر) بتختار الأول.
        usort($order, fn (int $a, int $b) => [isset(self::LAYOUT_MODES[$layouts[$a]]) ? 0 : 1, $a] <=> [isset(self::LAYOUT_MODES[$layouts[$b]]) ? 0 : 1, $b]);

        $used = [];
        $dark = 0;
        $light = 0;
        $result = [];

        foreach ($order as $templateIndex) {
            $layout = $layouts[$templateIndex];
            $tone = self::LAYOUT_TONE[$layout];
            $best = null;
            $bestScore = INF;

            foreach (self::PALETTES as $key => $palette) {
                $family = $palette['family'];
                if (isset($used[$family]) || ! in_array($palette['mode'], self::LAYOUT_MODES[$layout] ?? [$palette['mode']], true)) {
                    continue;
                }

                // كل عيلات الطابع مناسبة ليه، فترتيبها بيلف من فئة للتانية — عشان نفس التصميم
                // مياخدش نفس العيلة في كل الفئات (من غيره "ستاك" كان رملي في أغلب الـ20 فئة).
                $toneHues = self::TONE_HUES[$tone];
                $toneRank = array_search($family, $toneHues, true);
                $score = $toneRank === false
                    ? 9.0
                    : (($toneRank + count($toneHues) - ($categoryIndex * 5) % count($toneHues)) % count($toneHues)) * 0.6;

                $categoryRank = array_search($family, $categoryHues, true);
                if ($categoryRank !== false) {
                    $score -= 6 - $categoryRank * 0.5;
                }

                if (! in_array($palette['mode'], self::TONE_MODES[$tone], true)) {
                    $score += 4;
                }

                $isDark = str_contains($palette['mode'], 'dark');
                if (($isDark && $dark >= $maxDark) || (! $isDark && $light >= $maxLight)) {
                    $score += 6;
                }

                foreach (self::PALETTE_GROUPS as $group) {
                    if (in_array($family, $group, true)) {
                        $score += 1.5 * count(array_intersect($group, array_keys($used)));
                    }
                }

                // فرق صغير ثابت (مش عشوائي — نفس البيانات بتطلع نفس النتيجة دايماً) بيكسر التعادل.
                $score += (crc32($layout.'|'.$family.'|'.$categoryIndex) % 1000) / 1000 * 2;

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best = $key;
                }
            }

            $result[$templateIndex] = $best;
            $used[self::PALETTES[$best]['family']] = true;
            str_contains(self::PALETTES[$best]['mode'], 'dark') ? $dark++ : $light++;
        }

        ksort($result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $category
     * @param  array<int, string>  $layouts
     * @return array<int, array{font: string, heading_font: string}>
     */
    public static function fontsFor(int $categoryIndex, array $category, array $layouts): array
    {
        $headingOverride = $category['heading_fonts'] ?? null;
        $result = [];
        $bodyUsage = [];

        foreach ($layouts as $templateIndex => $layout) {
            // من خيارات التصميم، خط المتن الأقل استخداماً في الفئة لحد دلوقتي (تنوع أكتر بين
            // القوالب الـ15)، والتعادل بيبدأ من خيار بيلف مع الفئة والقالب.
            $options = self::LAYOUT_FONTS[$layout];
            $start = ($categoryIndex + $templateIndex) % count($options);
            $pick = null;
            for ($offset = 0; $offset < count($options); $offset++) {
                $option = $options[($start + $offset) % count($options)];
                if ($pick === null || ($bodyUsage[$option[0]] ?? 0) < ($bodyUsage[$pick[0]] ?? 0)) {
                    $pick = $option;
                }
            }
            [$body, $heading] = $pick;
            $bodyUsage[$body] = ($bodyUsage[$body] ?? 0) + 1;

            if ($headingOverride) {
                $heading = $headingOverride[$templateIndex % count($headingOverride)];
            }

            $result[$templateIndex] = ['font' => $body, 'heading_font' => $heading];
        }

        return $result;
    }

    /**
     * صورة الغلاف بتلف على صور الفئة (كل صورة بتبقى غلاف لـ3-4 قوالب بس بدل الـ15)، والمعرض
     * بياخد باقي صور الفئة بترتيب مختلف لكل قالب.
     *
     * @param  array<string, mixed>  $category
     * @return array{hero_image: string, gallery_image_1: string, gallery_image_2: string, gallery_image_3: string}
     */
    public static function photosFor(int $categoryIndex, array $category, int $templateIndex): array
    {
        $dir = '/images/template-library/'.$category['photos'];
        $own = [$dir.'/hero.jpg', $dir.'/gallery-1.jpg', $dir.'/gallery-2.jpg', $dir.'/gallery-3.jpg'];

        $heroes = array_merge(
            array_map(fn (string $file) => $dir.'/'.$file, $category['hero_photos'] ?? ['hero.jpg', 'gallery-1.jpg', 'gallery-2.jpg', 'gallery-3.jpg']),
            $category['shared_photos'] ?? [],
        );
        $hero = $heroes[($templateIndex + $categoryIndex) % count($heroes)];

        $gallery = array_values(array_filter($own, fn (string $path) => $path !== $hero));
        $shift = $templateIndex % count($gallery);
        $gallery = array_merge(array_slice($gallery, $shift), array_slice($gallery, 0, $shift));

        return [
            'hero_image' => $hero,
            'gallery_image_1' => $gallery[0],
            'gallery_image_2' => $gallery[1],
            'gallery_image_3' => $gallery[2],
        ];
    }

    /**
     * بنية الخانات الموحّدة لكل قوالب المكتبة (20 خانة، نفس المفاتيح بالحرف في كل القوالب —
     * تبديل القالب بالشات بيعتمد على كده). خانات الروابط من غير default_value عمداً — أي رابط
     * وهمي هيكون مضلّل. صور المعرض 4-6 فاضية افتراضياً (مكان لصور فؤاد/جوجل ماب).
     *
     * @param  array<string, mixed>  $c  نصوص وصور القالب + cta_label/services_label بتوع الفئة
     * @return list<array<string, mixed>>
     */
    public static function slotDefinitions(array $c): array
    {
        return [
            ['section_key' => 'hero', 'key' => 'hero_title', 'label_ar' => 'العنوان الرئيسي', 'slot_type' => 'text', 'is_required' => true, 'sort_order' => 1, 'default_value' => $c['hero_title']],
            ['section_key' => 'hero', 'key' => 'hero_subtitle', 'label_ar' => 'الوصف المختصر', 'slot_type' => 'textarea', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['hero_subtitle']],
            ['section_key' => 'hero', 'key' => 'hero_cta', 'label_ar' => $c['cta_label'], 'slot_type' => 'link', 'is_required' => false, 'sort_order' => 3, 'default_value' => null],
            ['section_key' => 'hero', 'key' => 'hero_image', 'label_ar' => 'صورة الغلاف', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 4, 'default_value' => $c['hero_image'] ?? null],

            ['section_key' => 'about', 'key' => 'about_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['about_title']],
            ['section_key' => 'about', 'key' => 'about_body', 'label_ar' => 'نبذة تعريفية', 'slot_type' => 'textarea', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['about_body']],

            ['section_key' => 'services', 'key' => 'services_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['services_title']],
            ['section_key' => 'services', 'key' => 'services_list', 'label_ar' => $c['services_label'], 'slot_type' => 'list', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['services_list']],

            ['section_key' => 'gallery', 'key' => 'gallery_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['gallery_title']],
            ['section_key' => 'gallery', 'key' => 'gallery_image_1', 'label_ar' => 'صورة 1', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['gallery_image_1'] ?? null],
            ['section_key' => 'gallery', 'key' => 'gallery_image_2', 'label_ar' => 'صورة 2', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 3, 'default_value' => $c['gallery_image_2'] ?? null],
            ['section_key' => 'gallery', 'key' => 'gallery_image_3', 'label_ar' => 'صورة 3', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 4, 'default_value' => $c['gallery_image_3'] ?? null],
            ['section_key' => 'gallery', 'key' => 'gallery_image_4', 'label_ar' => 'صورة 4', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 5, 'default_value' => null],
            ['section_key' => 'gallery', 'key' => 'gallery_image_5', 'label_ar' => 'صورة 5', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 6, 'default_value' => null],
            ['section_key' => 'gallery', 'key' => 'gallery_image_6', 'label_ar' => 'صورة 6', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 7, 'default_value' => null],

            ['section_key' => 'testimonials', 'key' => 'testimonials_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['testimonials_title']],
            ['section_key' => 'testimonials', 'key' => 'testimonials_list', 'label_ar' => 'آراء العملاء', 'slot_type' => 'list', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['testimonials_list']],

            ['section_key' => 'contact', 'key' => 'contact_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['contact_title']],
            ['section_key' => 'contact', 'key' => 'contact_note', 'label_ar' => 'ملاحظة تواصل', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['contact_note']],
            // التسمية دي بتتكتب على الزرار في الموقع نفسه — فلازم تبقى نص زرار مش وصف للأدمن.
            ['section_key' => 'contact', 'key' => 'contact_link', 'label_ar' => 'تواصل معنا', 'slot_type' => 'link', 'is_required' => false, 'sort_order' => 3, 'default_value' => null],
        ];
    }

    /**
     * @param  array<string, mixed>  $category
     * @param  array<string, mixed>  $plan
     * @return 'created'|'updated'|'unchanged'
     */
    private function upsertTemplate(array $category, array $plan): string
    {
        $attributes = [
            'name' => $plan['name'],
            'category' => $category['category'],
            'kind' => 'landing',
            'layout' => $plan['layout'],
            'license_note' => self::LICENSE_NOTE,
            'is_active' => true,
        ];
        $definitions = self::slotDefinitions($plan['content'] + [
            'cta_label' => $category['cta_label'],
            'services_label' => $category['services_label'],
        ]);
        $variantAttributes = [
            'name' => 'الأساسية',
            'colors_json' => self::PALETTES[$plan['palette']]['colors'],
            'font' => $plan['font'],
            'sections_json' => self::SECTIONS,
            'is_default' => true,
        ];
        if ($this->hasHeadingFont) {
            $variantAttributes['heading_font'] = $plan['heading_font'];
        }

        return DB::transaction(function () use ($plan, $attributes, $definitions, $variantAttributes) {
            $template = Template::query()->where('slug', $plan['slug'])->first();

            if (! $template) {
                $template = Template::create(['slug' => $plan['slug']] + $attributes);
                $template->slots()->createMany($definitions);
                $template->variants()->create(['slug' => 'default'] + $variantAttributes);

                return 'created';
            }

            $slots = $template->slots()->get()->keyBy('key');
            $variant = $template->variants()->where('slug', 'default')->first();

            $this->freezeExistingSites($template, $slots, $variant, $definitions, $variantAttributes);

            $changed = $this->saveChanges($template, $attributes);

            foreach ($definitions as $definition) {
                $slot = $slots->get($definition['key']);
                $changed = ($slot ? $this->saveChanges($slot, $definition) : (bool) $template->slots()->create($definition)) || $changed;
            }

            $changed = ($variant
                ? $this->saveChanges($variant, $variantAttributes)
                : (bool) $template->variants()->create(['slug' => 'default'] + $variantAttributes)) || $changed;

            return $changed ? 'updated' : 'unchanged';
        });
    }

    /**
     * بيكتب بس الحقول اللي قيمتها اتغيرت فعلاً (مقارنة JSON من غير اعتبار لترتيب المفاتيح —
     * MySQL بيعيد ترتيبها) — تشغيل الأمر تاني على نفس البيانات مبيكتبش حاجة.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function saveChanges(Model $model, array $attributes): bool
    {
        $changes = array_filter(
            $attributes,
            fn ($value, string $key) => ! self::sameValue($model->getAttribute($key), $value),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($changes === []) {
            return false;
        }

        $model->forceFill($changes)->save();

        return true;
    }

    /**
     * تحديث المكتبة ممنوع يغيّر شكل موقع قايم. SiteRenderer بيقرا ألوان/خط/ترتيب أقسام نسخة القالب
     * المشتركة مباشرة، وأي خانة فاضية في content_json بترجع للـdefault_value — فقبل ما القيم دي
     * تتغيّر، القيمة الحالية بتتنسخ جوّه موقع كل مشروع على القالب:
     * - ألوان/خط/ترتيب أقسام النسخة → colors_override_json/font_override/sections_override_json
     *   (بس لمشاريع النسخة دي، وبس لو الموقع مالوش تخصيص كامل بالفعل).
     * - default_value اتغيّر لخانة → المفتاح بيتكتب في content_json بالقيمة القديمة (حتى لو null)
     *   لو المفتاح مش موجود أصلاً — يعني الموقع كان بيعرض الافتراضي. صور الغلاف/المعرض أهم حالة.
     * خط العناوين (heading_font) جديد: قبله العناوين كانت بخط المتن، فتغيّره بيثبّت font_override
     * بخط المتن القديم (الرندر بيمشّي العناوين على font_override لو متحدد).
     *
     * @param  Collection<string, TemplateSlot>  $slots
     * @param  list<array<string, mixed>>  $definitions
     * @param  array<string, mixed>  $newVariant
     */
    private function freezeExistingSites(Template $template, Collection $slots, ?TemplateVariant $variant, array $definitions, array $newVariant): void
    {
        $projects = $template->projects()->with('site')->get();
        if ($projects->isEmpty()) {
            return;
        }

        $oldDefaults = [];
        foreach ($definitions as $definition) {
            $old = $slots->get($definition['key'])?->default_value;
            if (! self::sameValue($old, $definition['default_value'])) {
                $oldDefaults[$definition['key']] = $old;
            }
        }

        $oldFont = $variant?->font ?: 'cairo';
        $colorsChanged = $variant && ! self::sameValue($variant->colors_json, $newVariant['colors_json']);
        $fontChanged = $variant && ($oldFont !== $newVariant['font']
            || ($this->hasHeadingFont && ($variant->heading_font ?: null) !== ($newVariant['heading_font'] ?? null)));
        $sectionsChanged = $variant && ! self::sameValue($variant->sections_json, $newVariant['sections_json']);
        $oldSections = filled($variant?->sections_json)
            ? $variant->sections_json
            : $slots->sortBy(fn ($slot) => [$slot->sort_order, $slot->id])->pluck('section_key')->unique()->values()->all();

        foreach ($projects as $project) {
            $site = $project->site;
            if (! $site) {
                continue;
            }

            $updates = [];

            $content = is_array($site->content_json) ? $site->content_json : [];
            $missing = array_diff_key($oldDefaults, $content);
            if ($missing !== []) {
                $updates['content_json'] = $content + $missing;
            }

            if ($variant && (int) $project->template_variant_id === (int) $variant->id) {
                $override = is_array($site->colors_override_json) ? $site->colors_override_json : [];
                $frozenColors = array_merge($variant->colors_json ?? [], $override);
                if ($colorsChanged && ! self::sameValue($frozenColors, $override)) {
                    $updates['colors_override_json'] = $frozenColors;
                }
                if ($fontChanged && blank($site->font_override)) {
                    $updates['font_override'] = $oldFont;
                }
                if ($sectionsChanged && blank($site->sections_override_json)) {
                    $updates['sections_override_json'] = $oldSections;
                }
            }

            if ($updates !== []) {
                $site->forceFill($updates)->save();
                $this->frozenSites++;
            }
        }
    }

    // قوالب عليها مشاريع: تصميمها مبيتغيرش (مفيش تخصيص layout على مستوى الموقع، فتغييره كان
    // هيغيّر شكل مواقع فؤاد بالكامل).
    /**
     * @param  list<array<string, mixed>>  $templates
     * @return array<int, string>
     */
    private function pinnedLayouts(array $templates): array
    {
        $existing = Template::query()
            ->whereIn('slug', array_column($templates, 'slug'))
            ->has('projects')
            ->pluck('layout', 'slug');

        $pinned = [];
        foreach ($templates as $index => $entry) {
            $layout = $existing[$entry['slug']] ?? null;
            if ($layout && in_array($layout, Template::LAYOUTS, true)) {
                $pinned[$index] = $layout;
            }
        }

        return $pinned;
    }

    // قوالب مكتبة قديمة (بتوقيع المكتبة: نفس ملاحظة الترخيص، وslug بصيغة المولّد القديم أو في
    // RETIRED_SLUGS) مش موجودة في البيانات الحالية ومالهاش مشاريع → بتتقفل بدل ما تتمسح.
    /**
     * @param  list<string>  $produced
     */
    private function retireOldTemplates(array $produced): int
    {
        return Template::query()
            ->where('kind', 'landing')
            ->where('license_note', self::LICENSE_NOTE)
            ->where('is_active', true)
            ->whereNotIn('slug', $produced)
            ->doesntHave('projects')
            ->get()
            ->filter(fn (Template $template) => in_array($template->slug, self::RETIRED_SLUGS, true)
                || $template->slug === Str::slug($template->category.' '.$template->name))
            ->each(fn (Template $template) => $template->update(['is_active' => false]))
            ->count();
    }

    // --if-outdated: النسخة المتسجلة لازم تطابق، والمكتبة نفسها لازم تبقى موجودة فعلاً (لو
    // الداتابيز اتبدّلت بـbarq:import-data بنسخة أقدم، النسخة المتسجلة لوحدها ممكن تكدب).
    /**
     * @param  list<array<string, mixed>>  $categories
     */
    private function isUpToDate(array $categories): bool
    {
        if (Setting::get(self::VERSION_SETTING) !== self::LIBRARY_VERSION) {
            return false;
        }

        $slugs = array_merge(...array_map(fn (array $category) => array_column($category['templates'], 'slug'), $categories));
        $library = Template::query()->whereIn('slug', $slugs);

        if ((clone $library)->count() !== count($slugs)) {
            return false;
        }

        return ! $this->hasHeadingFont || ! TemplateVariant::query()
            ->whereIn('template_id', (clone $library)->select('id'))
            ->whereNull('heading_font')
            ->exists();
    }

    // مقارنة قيم JSON/نصوص من غير اعتبار لترتيب مفاتيح الـobjects (القوايم بترتيبها).
    public static function sameValue(mixed $a, mixed $b): bool
    {
        return self::normalize($a) === self::normalize($b);
    }

    private static function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_bool($value) ? (int) $value : $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($item) => self::normalize($item), $value);
    }
}
