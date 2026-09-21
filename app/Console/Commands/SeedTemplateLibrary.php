<?php

namespace App\Console\Commands;

use App\Models\Template;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// بيولّد مكتبة قوالب أصلية (Phase 7، اتوسّعت لاحقاً) — 20 فئة تجارية شائعة، كل فئة ليها 15
// قالب. كل قالب في الفئة بياخد تصميم بصري مختلف تماماً (من الـ 16 تصميم في
// Template::LAYOUTS، بما فيهم "سيجنتشر" الفاخر) ولوحة ألوان مختلفة تماماً (من 30 لوحة —
// 15 درجة لون × نسخة داكنة وفاتحة لكل واحدة، 2026-09-20 — فمزيج فاتح/داكن حقيقي جوّه كل فئة،
// مش كله داكن زي قبل كده) — فمفيش قالبين في نفس الفئة شكلهم أو ألوانهم واحدة. المحتوى
// الافتراضي (نص الهيرو، الخدمات، آراء العملاء...) واحد لكل فئة (زي ما الفئة نفسها بتمثّله)،
// والاختلاف بين الـ 15 قالب جوّه الفئة في الاسم/الشكل/اللون — الأدمن بيعدّل النص الفعلي وقت ما
// يعمل مشروع حقيقي منه. كل النصوص عربية أصلية اتكتبت خصيصاً للمكتبة دي — مفيش قالب خارجي أو
// تصميم منسوخ من مصدر تاني، عشان صفر مخاطرة ترخيص. الأمر idempotent بالكامل (updateOrCreate
// بكل مستوى) — تشغيله تاني آمن ومحدّش هيتكرر.
#[Signature('barq:seed-template-library')]
#[Description('توليد/تحديث مكتبة قوالب أصلية تغطي فئات نشاط شائعة (15 قالب لكل فئة)')]
class SeedTemplateLibrary extends Command
{
    // كل كلمة هنا مأخوذة فعلياً من نصوص أسماء القوالب التحتانية (بعد "—") — بتربط شخصية كل
    // تصميم (Template::LAYOUTS) بالكلمات اللي بتوحي بيها. الهدف إن اسم القالب ("طاقة
    // وحيوية" مثلاً) يبقى فعلاً معبّر عن شكله، مش مجرد نص تسويقي منفصل عن التصميم الحقيقي
    // (2026-09-20 — كان فيه قالب اسمه "طاقة وحيوية" واقع على layout="minimal"، أبسط تصميم
    // في المكتبة عن قصد، بسبب توزيع دوراني عشوائي مالوش علاقة بمعنى الاسم).
    /** @var array<string, list<string>> */
    // public (مش private) عشان AiProjectAssistantService::pickTemplateInCategory() يعيد
    // استخدامها لما فؤاد يطلب طابع بالشات (زي "فاخر") — بدل ما تتكرر نفس القايمة في مكانين.
    public const LAYOUT_KEYWORDS = [
        'classic' => ['دافئ', 'دفء', 'أصيل', 'تراث', 'تقليد', 'ثقة وأمان', 'موثوق', 'أمان', 'طازة', 'نكهة أصيلة', 'واجهة دافئة', 'بداية آمنة', 'قيم من الصغر', 'ثقة من زمان', 'بنيان متين', 'تنفيذ موثوق', 'أساسيات موثوقة', 'مالك بأمان', 'سفر مسؤول', 'يومك بثقة', 'رائحة تدوم', 'وصفات من الجدة'],
        'modern' => ['عصري', 'عصرية', 'حديث', 'حديثة', 'إطلالة عصرية'],
        'gallery' => ['معرض', 'جولة', 'تصوير', 'بورتريه', 'صور', 'أعمال', 'عرض جذاب', 'تصميم يفرح العين', 'براءة موثقة', 'لكل مناسبة', 'اكتشف بلدك', 'إطلالة مميزة', 'إطلالة كاملة', 'واجهة جاذبة'],
        'split' => ['وسيط', 'بساطة وثقة', 'مرونة', 'حلول سريعة', 'توصلك', 'إجراءات سهلة', 'تجربة سلسة', 'حلول مخصصة', 'جمع بين', 'خبرة عند الطلب', 'لغات بلا حواجز', 'جودة بسعر مناسب', 'مرونة للعاملين', 'إطلالة عملية'],
        'magazine' => ['هدوء وإلهام', 'كاتب', 'كلمات مؤثرة', 'تفاصيل ساحرة', 'تصميم حسب الطلب', 'بيئة عمل ملهمة', 'لمسة أخيرة'],
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
        // "سيجنتشر" (Phase 11، اتضاف 2026-09-20 من فرع تاني) — تصميم فاخر بطابع ضيافة راقٍ،
        // منافس مباشر لـ"glass"/"framed" على نفس عائلة كلمات الفخامة، فبيوزّع القوالب
        // الفاخرة على 3 تصميمات مختلفة بدل 2 بس.
        'signature' => ['فاخر', 'فاخرة', 'توقيع', 'توقيعك', 'نخبة', 'رفاهية', 'رفاهية كاملة', 'رفاهية بلا حدود'],
    ];

    // كل لوحة داكنة (dark) متبوعة فوراً بنسختها الفاتحة (light) بنفس درجة اللون الأساسي —
    // الترتيب ده (تبادل داكن/فاتح) مقصود: صيغة توزيع اللوحات على القوالب تحت
    // (offset دايماً زوجي) بتحافظ على "زوجيّة" الموقع، فكل فئة نشاط (15 قالب) بتاخد مزيج
    // حقيقي فاتح+داكن تلقائياً بدل ما تطلع كل قوالبها داكنة زي قبل 2026-09-20.
    /** @var array<string, array{primary: string, background: string, surface: string, text: string, muted: string}> */
    private array $palettes = [
        'amber' => ['primary' => '#f59e0b', 'background' => '#0b1220', 'surface' => '#111a2e', 'text' => '#f1f5f9', 'muted' => '#94a3b8'],
        'amber-light' => ['primary' => '#b45309', 'background' => '#fffbeb', 'surface' => '#fef3c7', 'text' => '#292116', 'muted' => '#92702f'],
        'emerald' => ['primary' => '#10b981', 'background' => '#0a1612', 'surface' => '#0f2019', 'text' => '#ecfdf5', 'muted' => '#8fae9f'],
        'emerald-light' => ['primary' => '#047857', 'background' => '#ecfdf5', 'surface' => '#d1fae5', 'text' => '#0f2419', 'muted' => '#4b7c67'],
        'sky' => ['primary' => '#38bdf8', 'background' => '#0b1524', 'surface' => '#101c30', 'text' => '#eff6ff', 'muted' => '#93a8c4'],
        'sky-light' => ['primary' => '#0369a1', 'background' => '#f0f9ff', 'surface' => '#e0f2fe', 'text' => '#0c2233', 'muted' => '#4a7891'],
        'rose' => ['primary' => '#fb7185', 'background' => '#180d12', 'surface' => '#24141b', 'text' => '#fdf2f4', 'muted' => '#c7a3ab'],
        'rose-light' => ['primary' => '#be123c', 'background' => '#fff1f2', 'surface' => '#ffe4e6', 'text' => '#2a1116', 'muted' => '#9d5b68'],
        'ruby' => ['primary' => '#ef4444', 'background' => '#1a0e0e', 'surface' => '#271414', 'text' => '#fef2f2', 'muted' => '#c9a3a3'],
        'ruby-light' => ['primary' => '#b91c1c', 'background' => '#fef2f2', 'surface' => '#fee2e2', 'text' => '#2a1414', 'muted' => '#9c6060'],
        'teal' => ['primary' => '#14b8a6', 'background' => '#08181a', 'surface' => '#0f2426', 'text' => '#ecfeff', 'muted' => '#8fbabd'],
        'teal-light' => ['primary' => '#0f766e', 'background' => '#f0fdfa', 'surface' => '#ccfbf1', 'text' => '#0b2320', 'muted' => '#4d7d78'],
        'indigo' => ['primary' => '#818cf8', 'background' => '#0d0e1a', 'surface' => '#14152a', 'text' => '#eef2ff', 'muted' => '#9ca3c9'],
        'indigo-light' => ['primary' => '#4338ca', 'background' => '#eef2ff', 'surface' => '#e0e7ff', 'text' => '#1a1a3a', 'muted' => '#6b6b9c'],
        'gold' => ['primary' => '#facc15', 'background' => '#1a1708', 'surface' => '#262008', 'text' => '#fefce8', 'muted' => '#cbbf94'],
        'gold-light' => ['primary' => '#a16207', 'background' => '#fefce8', 'surface' => '#fef9c3', 'text' => '#292408', 'muted' => '#8c7d3a'],
        'lime' => ['primary' => '#a3e635', 'background' => '#10140a', 'surface' => '#1a2210', 'text' => '#f7fee7', 'muted' => '#adc494'],
        'lime-light' => ['primary' => '#4d7c0f', 'background' => '#f7fee7', 'surface' => '#ecfccb', 'text' => '#1c2410', 'muted' => '#6d7d4f'],
        'cyan' => ['primary' => '#22d3ee', 'background' => '#071b1e', 'surface' => '#0c2a2e', 'text' => '#ecfeff', 'muted' => '#8fb8bd'],
        'cyan-light' => ['primary' => '#0e7490', 'background' => '#ecfeff', 'surface' => '#cffafe', 'text' => '#0b2226', 'muted' => '#4a7c85'],
        'blue' => ['primary' => '#3b82f6', 'background' => '#0a1020', 'surface' => '#10192e', 'text' => '#eff4ff', 'muted' => '#94a3c9'],
        'blue-light' => ['primary' => '#1d4ed8', 'background' => '#eff6ff', 'surface' => '#dbeafe', 'text' => '#101a33', 'muted' => '#4d6699'],
        'violet' => ['primary' => '#a78bfa', 'background' => '#130f1e', 'surface' => '#1d1830', 'text' => '#f5f3ff', 'muted' => '#b3a8cf'],
        'violet-light' => ['primary' => '#6d28d9', 'background' => '#f5f3ff', 'surface' => '#ede9fe', 'text' => '#211a33', 'muted' => '#7c6a9c'],
        'fuchsia' => ['primary' => '#e879f9', 'background' => '#1a0d1c', 'surface' => '#28142a', 'text' => '#fdf4ff', 'muted' => '#c9a3ca'],
        'fuchsia-light' => ['primary' => '#a21caf', 'background' => '#fdf4ff', 'surface' => '#fae8ff', 'text' => '#2a1a2c', 'muted' => '#966b99'],
        'pink' => ['primary' => '#f472b6', 'background' => '#1a0d14', 'surface' => '#27141e', 'text' => '#fdf2f8', 'muted' => '#c9a3b6'],
        'pink-light' => ['primary' => '#be185d', 'background' => '#fdf2f8', 'surface' => '#fce7f3', 'text' => '#2a141e', 'muted' => '#996b81'],
        'slate' => ['primary' => '#94a3b8', 'background' => '#0d1117', 'surface' => '#161b22', 'text' => '#f1f5f9', 'muted' => '#8b96a5'],
        'slate-light' => ['primary' => '#334155', 'background' => '#f8fafc', 'surface' => '#f1f5f9', 'text' => '#0f172a', 'muted' => '#64748b'],
    ];

    public function handle(): int
    {
        $paletteKeys = array_keys($this->palettes);
        $paletteCount = count($paletteKeys);
        // "نيون" (توهّج بـ text-shadow/box-shadow) شكله بيتصمم أصلاً عشان يتقرا فوق خلفية
        // داكنة بس — جرّبناه حي فوق لوحة فاتحة وطلع التوهّج شبه عيب رندر (هالة باهتة) مش
        // تأثير "نيون" حقيقي، فمن غير الطبقة دي بيفضل مربوط بلوحات داكنة بس، حتى بعد ما
        // ضفنا لوحات فاتحة لكل التصميمات التانية (2026-09-20).
        $darkOnlyPaletteKeys = array_values(array_filter($paletteKeys, fn (string $key) => ! str_ends_with($key, '-light')));
        $darkOnlyLayouts = ['neon'];
        $allLayouts = Template::LAYOUTS;
        $created = 0;
        $updated = 0;

        foreach ($this->categories() as $categoryIndex => $category) {
            $templateCount = count($category['names']);
            $layoutForIndex = $this->matchLayoutsToNames($category['names'], $allLayouts);

            foreach ($category['names'] as $templateIndex => $name) {
                // التصميم بيتاخد حسب قرب معنى اسم القالب من شخصية كل تصميم (راجع
                // matchLayoutsToNames())، واللون لسه بيتوزّع بإزاحة دورانية زي الأول —
                // فيما عدا "نيون" اللي لازم يفضل مربوط بلوحة داكنة بس (راجع $darkOnlyLayouts).
                $layout = $layoutForIndex[$templateIndex];

                $paletteKey = in_array($layout, $darkOnlyLayouts, true)
                    ? $darkOnlyPaletteKeys[($templateIndex + $categoryIndex * 2) % count($darkOnlyPaletteKeys)]
                    : $paletteKeys[($templateIndex + $categoryIndex * 2) % $paletteCount];
                $palette = $this->palettes[$paletteKey];

                [, $wasRecentlyCreated] = $this->upsertTemplate($category, $name, $layout, $palette);

                $wasRecentlyCreated ? $created++ : $updated++;
            }

            $this->info("✓ {$category['category']} — {$templateCount} قالب");
        }

        $this->newLine();
        $this->info("خلصت: {$created} قالب جديد، {$updated} قالب محدّث. شوفهم في /templates.");

        return self::SUCCESS;
    }

    /**
     * بيوزّع الـ15 تصميم على الـ15 اسم في الفئة بحيث كل اسم ياخد التصميم اللي كلماته أقرب
     * لمعناه (عدد كلمات LAYOUT_KEYWORDS الموجودة فعلياً في الاسم)، وكل تصميم بيتستخدم مرة
     * واحدة بالظبط في الفئة (زي القديم بالظبط). خوارزمية greedy بسيطة: بنرتب كل التوليفات
     * (اسم، تصميم) تنازلياً حسب قوة التطابق، وبناخد الأقوى الأول لسه مالوش تصميم واسمه
     * مالوش اسم، وهكذا. لو مفيش أي تطابق كلمات خالص بيرجع لترتيب تسلسلي بسيط (زي القديم).
     *
     * @param  list<string>  $names
     * @param  list<string>  $layouts
     * @return array<int, string> فهرس الاسم فى المصفوفة => التصميم
     */
    private function matchLayoutsToNames(array $names, array $layouts): array
    {
        $pairs = [];

        foreach ($names as $ni => $name) {
            foreach ($layouts as $li => $layout) {
                $score = 0;
                foreach (self::LAYOUT_KEYWORDS[$layout] as $keyword) {
                    if (mb_stripos($name, $keyword) !== false) {
                        $score++;
                    }
                }
                $pairs[] = ['ni' => $ni, 'li' => $li, 'score' => $score];
            }
        }

        // usort ثابت (stable) في PHP 8 — عند تساوي الدرجة (زي كل التوليفات صفر) بيحافظ على
        // ترتيب الإدراج الأصلي (اسم0/تصميم0، اسم0/تصميم1...)، فبيرجع سلوك تسلسلي منطقي
        // بدل عشوائي لما مفيش تطابق كلمات.
        usort($pairs, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $result = [];
        $usedLayouts = [];

        foreach ($pairs as $pair) {
            if (isset($result[$pair['ni']]) || isset($usedLayouts[$pair['li']])) {
                continue;
            }

            $result[$pair['ni']] = $layouts[$pair['li']];
            $usedLayouts[$pair['li']] = true;
        }

        ksort($result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $category
     * @param  array<string, string>  $palette
     * @return array{0: Template, 1: bool}
     */
    private function upsertTemplate(array $category, string $name, string $layout, array $palette): array
    {
        $slug = Str::slug($category['category'].' '.$name);

        return DB::transaction(function () use ($category, $name, $layout, $palette, $slug) {
            $existed = Template::where('slug', $slug)->exists();

            $template = Template::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'category' => $category['category'],
                    'kind' => 'landing',
                    'layout' => $layout,
                    'license_note' => 'تصميم ومحتوى أصلي — صفر اعتماد على قالب خارجي.',
                    'is_active' => true,
                ]
            );

            foreach ($this->slotDefinitions($category) as $definition) {
                $template->slots()->updateOrCreate(
                    ['key' => $definition['key']],
                    $definition
                );
            }

            $template->variants()->updateOrCreate(
                ['slug' => 'default'],
                [
                    'name' => 'الأساسية',
                    'colors_json' => $palette,
                    'sections_json' => ['hero', 'about', 'services', 'gallery', 'testimonials', 'contact'],
                    'is_default' => true,
                ]
            );

            return [$template, ! $existed];
        });
    }

    /**
     * بنية الخانات الموحّدة لكل قوالب المكتبة — 6 أقسام ثابتة، لكن التسميات والمحتوى الافتراضي
     * بيجيلها من بيانات الفئة نفسها. رابط الـ CTA وباقي خانات الروابط من غير default_value
     * عمداً — أي رابط وهمي هيكون مضلّل، الأدمن لازم يحط رابط حقيقي.
     *
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    private function slotDefinitions(array $c): array
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

            ['section_key' => 'testimonials', 'key' => 'testimonials_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['testimonials_title']],
            ['section_key' => 'testimonials', 'key' => 'testimonials_list', 'label_ar' => 'آراء العملاء', 'slot_type' => 'list', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['testimonials_list']],

            ['section_key' => 'contact', 'key' => 'contact_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['contact_title']],
            ['section_key' => 'contact', 'key' => 'contact_note', 'label_ar' => 'ملاحظة تواصل', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['contact_note']],
            ['section_key' => 'contact', 'key' => 'contact_link', 'label_ar' => 'رابط التواصل (واتساب/اتصال)', 'slot_type' => 'link', 'is_required' => false, 'sort_order' => 3, 'default_value' => null],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categories(): array
    {
        return [
            [
                'category' => 'مطاعم وكافيهات',
                'names' => [
                    'مطعم — واجهة دافئة', 'كافيه — إطلالة عصرية', 'مطعم فاخر — طابع راقي',
                    'مطعم شعبي — طابع أصيل', 'كافيه اختصاصي — قهوة وهدوء', 'مطعم بحري — إطلالة منعشة',
                    'بيتزا وفاست فود — طاقة وحيوية', 'مطعم عائلي — دفء ومساحة', 'كافيه ليلي — أجواء عصرية',
                    'مطعم مشويات — نار وطعم', 'كافيه كتب — هدوء وإلهام', 'مطعم فيوجن — تصميم جريء',
                    'كافيه صحي — نظافة وبساطة', 'مطعم فاخر ليلي — طابع أنيق', 'كافيه شارع — طابع شبابي',
                ],
                'cta_label' => 'اطلب دلوقتي',
                'hero_title' => 'طعم أصيل يجمعكم على السفرة',
                'hero_subtitle' => 'أكلات طازة بتتحضّر كل يوم، وخدمة توصيل سريعة لحد باب البيت.',
                'about_title' => 'قصتنا',
                'about_body' => 'بدأنا بحلم بسيط: إن كل وجبة تحس فيها بطعم البيت. دلوقتي بقينا وجهة تفضيل كتير من العيلات في المنطقة.',
                'services_title' => 'قائمة الأكلات المميزة',
                'services_label' => 'أطباقنا',
                'services_list' => ['مشويات طازة يومياً', 'أطباق شرقية وغربية', 'حلويات منزلية', 'عصائر طبيعية', 'توصيل خلال 30 دقيقة'],
                'gallery_title' => 'لقطات من مطبخنا وصالتنا',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['أحسن أكل جربته من زمان — أحمد س.', 'الخدمة سريعة والطعم ثابت كل مرة — منى ع.'],
                'contact_title' => 'احجز طاولتك',
                'contact_note' => 'متواجدين يومياً من 12 ظهراً لـ 1 بعد منتصف الليل.',
                'hero_image' => '/images/template-library/restaurants/hero.jpg',
                'gallery_image_1' => '/images/template-library/restaurants/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/restaurants/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/restaurants/gallery-3.jpg',
            ],
            [
                'category' => 'عيادات وخدمات طبية',
                'names' => [
                    'عيادة — ثقة وأمان', 'مركز طبي — رعاية شاملة', 'عيادة تخصصية — طابع هادئ',
                    'عيادة أسنان — ابتسامة واثقة', 'مركز أشعة وتحاليل — دقة وسرعة', 'عيادة أطفال — أجواء مريحة',
                    'مركز علاج طبيعي — تعافي تدريجي', 'عيادة جلدية — عناية متخصصة', 'مستشفى صغير — رعاية متكاملة',
                    'مركز طبي فاخر — راحة تامة', 'عيادة نسائية — خصوصية وثقة', 'مركز تخسيس وتغذية — نتائج حقيقية',
                    'عيادة عيون — رؤية أوضح', 'مركز طوارئ — استجابة سريعة', 'عيادة نفسية — دعم بهدوء',
                ],
                'cta_label' => 'احجز موعدك',
                'hero_title' => 'صحتك في إيد أمينة',
                'hero_subtitle' => 'فريق طبي متخصص وأحدث الأجهزة، في بيئة مريحة ليك وللعيلة.',
                'about_title' => 'ليه تختارنا',
                'about_body' => 'بنؤمن إن الرعاية الطبية الحقيقية بتبدأ بالاستماع لمريضنا كويس، وبعدين تشخيص دقيق وخطة علاج واضحة.',
                'services_title' => 'خدماتنا الطبية',
                'services_label' => 'التخصصات',
                'services_list' => ['كشف وتشخيص دقيق', 'متابعة دورية للحالات المزمنة', 'أشعة وتحاليل فورية', 'استشارات أونلاين', 'حجز مواعيد مرن'],
                'gallery_title' => 'جولة داخل العيادة',
                'testimonials_title' => 'تجارب مرضانا',
                'testimonials_list' => ['تعامل محترم ودقة في التشخيص — سارة م.', 'الدكتور شرحلي كل حاجة بهدوء — كريم ط.'],
                'contact_title' => 'احجز الكشف',
                'contact_note' => 'مواعيد العمل من السبت للخميس، 10 صباحاً لـ 9 مساءً.',
                'hero_image' => '/images/template-library/clinics/hero.jpg',
                'gallery_image_1' => '/images/template-library/clinics/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/clinics/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/clinics/gallery-3.jpg',
            ],
            [
                'category' => 'صالونات وتجميل',
                'names' => [
                    'صالون — لمسة أنوثة', 'مركز تجميل — فخامة عصرية', 'صالون رجالي — ستايل مميز',
                    'صالون عرايس — إطلالة الحلم', 'مركز ليزر — نتائج مضمونة', 'صالون شعر — تألق يومي',
                    'مركز سبا — استرخاء كامل', 'صالون أظافر — تفاصيل دقيقة', 'باربر شوب — قصة كلاسيك',
                    'مركز تجميل فاخر — رقي عصري', 'صالون مكياج — إطلالة احترافية', 'مركز عناية بالبشرة — نضارة طبيعية',
                    'صالون شبابي — ألوان جريئة', 'مركز تجميل طبي — علم وجمال', 'صالون منزلي — راحة بيتك',
                ],
                'cta_label' => 'احجزي دلوقتي',
                'hero_title' => 'جمالك يستاهل عناية حقيقية',
                'hero_subtitle' => 'خدمات تجميل وعناية بأحدث التقنيات، من إيد متخصصين محترفين.',
                'about_title' => 'خبرتنا معاكِ',
                'about_body' => 'فريقنا من أفضل الخبيرات في التجميل والعناية بالبشرة والشعر، بنستخدم منتجات أصلية وأدوات معقّمة بالكامل.',
                'services_title' => 'خدماتنا',
                'services_label' => 'الخدمات',
                'services_list' => ['قص وتصفيف شعر', 'مكياج مناسبات', 'عناية بالبشرة', 'باديكير ومانيكير', 'باقات عرايس'],
                'gallery_title' => 'من أعمالنا',
                'testimonials_title' => 'آراء عميلاتنا',
                'testimonials_list' => ['النتيجة فاقت توقعاتي — ياسمين ح.', 'مكان نظيف والخدمة راقية — دينا ف.'],
                'contact_title' => 'احجزي ميعادك',
                'contact_note' => 'الحجز أونلاين متاح على مدار الأسبوع.',
                'hero_image' => '/images/template-library/salons/hero.jpg',
                'gallery_image_1' => '/images/template-library/salons/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/salons/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/salons/gallery-3.jpg',
            ],
            [
                'category' => 'جيم ولياقة بدنية',
                'names' => [
                    'جيم — طاقة وقوة', 'نادي لياقة — مساحة مفتوحة', 'استوديو تمارين — تركيز شخصي',
                    'جيم نسائي — مساحة خاصة', 'استوديو يوجا — هدوء وتوازن', 'نادي كروسفيت — تحدي يومي',
                    'جيم 24 ساعة — مرونة كاملة', 'استوديو بيلاتس — قوة داخلية', 'نادي ملاكمة — طاقة قتالية',
                    'جيم عائلي — لياقة للجميع', 'استوديو رقص رياضي — حركة وحيوية', 'نادي سباحة — لياقة مائية',
                    'جيم احترافي — تجهيزات متكاملة', 'استوديو تدريب شخصي — نتائج مركّزة', 'نادي شبابي — طاقة جيل جديد',
                ],
                'cta_label' => 'اشترك دلوقتي',
                'hero_title' => 'جسمك الأفضل يبدأ من هنا',
                'hero_subtitle' => 'أجهزة حديثة ومدربين معتمدين يساعدوك توصل لهدفك بأمان.',
                'about_title' => 'مين إحنا',
                'about_body' => 'نادي رياضي متكامل بمساحات واسعة وأجهزة متنوعة، وبرامج تدريب مخصصة لكل المستويات من مبتدئ لمحترف.',
                'services_title' => 'برامجنا',
                'services_label' => 'البرامج',
                'services_list' => ['تدريب شخصي واحد لواحد', 'حصص جروب (كروسفيت/يوجا)', 'برامج تخسيس', 'استشارات تغذية', 'ساونا وسبا'],
                'gallery_title' => 'جولة داخل النادي',
                'testimonials_title' => 'قصص نجاح أعضائنا',
                'testimonials_list' => ['خسرت 10 كيلو في شهرين — محمود ي.', 'المدربين بيتابعوا معاك أول بأول — نور س.'],
                'contact_title' => 'ابدأ رحلتك',
                'contact_note' => 'باقات اشتراك شهرية وسنوية بأسعار مرنة.',
                'hero_image' => '/images/template-library/gyms/hero.jpg',
                'gallery_image_1' => '/images/template-library/gyms/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/gyms/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/gyms/gallery-3.jpg',
            ],
            [
                'category' => 'عقارات',
                'names' => [
                    'عقارات — واجهة احترافية', 'شركة تطوير عقاري — إطلالة فاخرة', 'وسيط عقاري — بساطة وثقة',
                    'كمبوند سكني — حياة متكاملة', 'مكتب تسويق عقاري — عروض حصرية', 'شركة تطوير ساحلي — إطلالة بحرية',
                    'وحدات تجارية — استثمار ذكي', 'شاليهات وفلل — راحة الإجازة', 'مكتب عقارات فاخر — طابع رفيع',
                    'شركة إدارة عقارات — خدمة متكاملة', 'مشروع عقاري جديد — انطلاقة مبكرة', 'وسيط إيجارات — حلول سريعة',
                    'شركة تمويل عقاري — تملّك أسهل', 'مكتب عقاري شبابي — بساطة عصرية', 'معرض عقاري — تجربة تفاعلية',
                ],
                'cta_label' => 'اطلب معاينة',
                'hero_title' => 'بيت أحلامك أقرب مما تتخيل',
                'hero_subtitle' => 'وحدات سكنية وتجارية مختارة بعناية، في أفضل المواقع وبأسعار تنافسية.',
                'about_title' => 'خبرتنا في السوق',
                'about_body' => 'بنساعدك تلاقي العقار المناسب لاحتياجك وميزانيتك، من خلال فريق استشاري بيعرف السوق كويس ومتابعة لحد التسليم.',
                'services_title' => 'وحداتنا المميزة',
                'services_label' => 'أنواع الوحدات',
                'services_list' => ['شقق سكنية', 'فيلات وتاون هاوس', 'محلات ومكاتب تجارية', 'أراضي استثمارية', 'خدمة تقسيط مرنة'],
                'gallery_title' => 'معرض الوحدات',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['ساعدوني ألاقي الشقة المناسبة بسرعة — إيهاب ن.', 'متابعة ممتازة من أول يوم لحد الاستلام — هبة ك.'],
                'contact_title' => 'اطلب استشارة مجانية',
                'contact_note' => 'فريقنا جاهز يرد عليك خلال ساعات العمل.',
                'hero_image' => '/images/template-library/real-estate/hero.jpg',
                'gallery_image_1' => '/images/template-library/real-estate/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/real-estate/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/real-estate/gallery-3.jpg',
            ],
            [
                'category' => 'متاجر إلكترونية',
                'names' => [
                    'متجر — تسوق سهل', 'بوتيك أونلاين — طابع أنيق', 'متجر منتجات — عرض واسع',
                    'متجر إلكترونيات — تقنية موثوقة', 'متجر مستلزمات منزلية — كل بيت محتاجه', 'متجر أطفال — منتجات آمنة',
                    'متجر رياضي — أداء أفضل', 'متجر هدايا — لحظات مميزة', 'متجر عطور — رائحة تدوم',
                    'متجر إكسسوارات — تفاصيل أنيقة', 'متجر كتب — عالم من المعرفة', 'متجر أدوات منزلية — عملية وذكية',
                    'متجر موضة سريعة — تريند دايماً', 'متجر فاخر — تجربة تسوق راقية', 'متجر محلي — دعم صناعة بلدك',
                ],
                'cta_label' => 'تسوق الآن',
                'hero_title' => 'كل اللي تحتاجه في مكان واحد',
                'hero_subtitle' => 'منتجات أصلية، توصيل سريع لكل المحافظات، واستبدال سهل لو محتاج.',
                'about_title' => 'عن متجرنا',
                'about_body' => 'بنختار منتجاتنا بعناية من موردين موثوقين، وهدفنا إن تجربة الشراء تبقى بسيطة وسريعة من الطلب لحد التوصيل.',
                'services_title' => 'ليه تشتري منا',
                'services_label' => 'مميزاتنا',
                'services_list' => ['منتجات أصلية 100%', 'توصيل خلال 48 ساعة', 'الدفع عند الاستلام', 'استبدال خلال 14 يوم', 'عروض أسبوعية'],
                'gallery_title' => 'منتجات مختارة',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['التوصيل كان أسرع مما توقعت — رنا ع.', 'خدمة عملاء بترد بسرعة — طارق م.'],
                'contact_title' => 'تحتاج مساعدة؟',
                'contact_note' => 'خدمة العملاء متاحة يومياً من 9 صباحاً لـ 10 مساءً.',
                'hero_image' => '/images/template-library/ecommerce/hero.jpg',
                'gallery_image_1' => '/images/template-library/ecommerce/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/ecommerce/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/ecommerce/gallery-3.jpg',
            ],
            [
                'category' => 'تعليم ودورات تدريبية',
                'names' => [
                    'أكاديمية — تصميم واضح', 'منصة دورات — طابع تفاعلي', 'مركز تدريب — إطلالة عملية',
                    'أكاديمية لغات — تواصل بثقة', 'مركز دعم دراسي — تفوق مستمر', 'منصة تعليم أطفال — تعلم باللعب',
                    'معهد مهني — مهارة تطعمك', 'أكاديمية برمجة — مستقبل تقني', 'مركز تدريب شركات — كفاءة أعلى',
                    'منصة كورسات أونلاين — تعلّم من بيتك', 'أكاديمية فنون — إبداع بلا حدود', 'مركز تقوية — نتيجة أقوى',
                    'معهد قيادة وسواقة — أمان أولاً', 'أكاديمية تجارة — استثمار في نفسك', 'مركز تدريب رياضي — احتراف تدريجي',
                ],
                'cta_label' => 'سجّل الآن',
                'hero_title' => 'مهارة جديدة تفتحلك أبواب جديدة',
                'hero_subtitle' => 'دورات تدريبية عملية بيقدّمها متخصصين، أونلاين أو حضورياً.',
                'about_title' => 'رؤيتنا',
                'about_body' => 'بنؤمن إن التعلم المستمر هو أقوى استثمار في نفسك. مناهجنا مصمّمة تجمع بين النظري والتطبيق العملي الحقيقي.',
                'services_title' => 'دوراتنا',
                'services_label' => 'المجالات',
                'services_list' => ['برمجة وتطوير مواقع', 'تسويق رقمي', 'تصميم جرافيك', 'لغات أجنبية', 'شهادات معتمدة'],
                'gallery_title' => 'من قاعات التدريب',
                'testimonials_title' => 'آراء المتدربين',
                'testimonials_list' => ['غيّرت مسار شغلي بعد الدورة دي — عمر ف.', 'المدرب بيشرح ببساطة وصبر — ملك ر.'],
                'contact_title' => 'ابدأ رحلتك التعليمية',
                'contact_note' => 'دفعات جديدة بتبدأ كل شهر.',
                'hero_image' => '/images/template-library/education/hero.jpg',
                'gallery_image_1' => '/images/template-library/education/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/education/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/education/gallery-3.jpg',
            ],
            [
                'category' => 'استشارات ومحاماة',
                'names' => [
                    'مكتب محاماة — طابع رسمي', 'استشارات قانونية — ثقة ومصداقية', 'مكتب استشارات أعمال — احترافية',
                    'مكتب محاماة دولي — خبرة عالمية', 'استشارات ضريبية — التزام بلا قلق', 'مكتب توثيق عقود — دقة قانونية',
                    'استشارات موارد بشرية — فرق أقوى', 'مكتب تحكيم — حلول عادلة', 'استشارات مالية — قرارات مدروسة',
                    'مكتب محاماة أسرية — دعم إنساني', 'استشارات استثمار — نمو آمن', 'مكتب قانوني شبابي — وضوح وبساطة',
                    'استشارات امتثال — حماية شركتك', 'مكتب محاماة جنائي — دفاع قوي', 'استشارات ريادة أعمال — انطلاقة صحيحة',
                ],
                'cta_label' => 'اطلب استشارة',
                'hero_title' => 'حقك محفوظ معانا',
                'hero_subtitle' => 'فريق قانوني متخصص بيقدملك استشارة دقيقة ومتابعة كاملة لقضيتك.',
                'about_title' => 'خبرتنا',
                'about_body' => 'بخبرة سنوات في مختلف فروع القانون، بنقدم حلول قانونية واضحة وعملية تحمي حقوقك وتوفّر وقتك ومجهودك.',
                'services_title' => 'مجالات عملنا',
                'services_label' => 'التخصصات',
                'services_list' => ['قضايا مدنية وتجارية', 'عقود واستشارات شركات', 'قضايا أسرة', 'تحكيم وتسوية منازعات', 'استشارة أونلاين'],
                'gallery_title' => 'مكتبنا',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['متابعة دقيقة وشرح واضح لكل خطوة — سامح ج.', 'حلولهم عملية ومدروسة — نادية ب.'],
                'contact_title' => 'احجز استشارتك',
                'contact_note' => 'سرية تامة لكل الاستشارات.',
                'hero_image' => '/images/template-library/legal/hero.jpg',
                'gallery_image_1' => '/images/template-library/legal/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/legal/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/legal/gallery-3.jpg',
            ],
            [
                'category' => 'مقاولات وديكور',
                'names' => [
                    'شركة مقاولات — طابع صناعي', 'استوديو ديكور — إطلالة أنيقة', 'شركة تشطيبات — عرض أعمال',
                    'شركة مقاولات فاخرة — جودة استثنائية', 'استوديو تصميم داخلي — مساحات ملهمة', 'شركة عزل ومقاولات — حماية دائمة',
                    'ديكور مكاتب — بيئة عمل ملهمة', 'شركة تشطيب فاخر — تفاصيل دقيقة', 'مقاول عام — تنفيذ موثوق',
                    'استوديو عمارة — تصميم مبتكر', 'شركة حدائق ولاندسكيب — طبيعة قريبة', 'ديكور محلات — واجهة جاذبة',
                    'شركة إنشاءات — بنيان متين', 'استوديو ديكور شبابي — ألوان جريئة', 'مقاولات ترميم — إحياء المساحات',
                ],
                'cta_label' => 'اطلب معاينة موقع',
                'hero_title' => 'من التصميم لحد التسليم',
                'hero_subtitle' => 'تنفيذ وتشطيب بأعلى جودة، بإشراف فريق هندسي متكامل.',
                'about_title' => 'خبرتنا في التنفيذ',
                'about_body' => 'نفّذنا عشرات المشاريع السكنية والتجارية بالتزام كامل بالمواعيد والميزانية المتفق عليها، مع ضمان على كل شغلنا.',
                'services_title' => 'خدماتنا',
                'services_label' => 'الخدمات',
                'services_list' => ['تصميم ديكور داخلي', 'تشطيبات كاملة', 'أعمال كهرباء وسباكة', 'تجديد وترميم', 'إشراف هندسي'],
                'gallery_title' => 'من مشاريعنا',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['التزموا بالميعاد والميزانية بالظبط — وائل س.', 'الديكور طلع أحلى من المتوقع — أميرة ت.'],
                'contact_title' => 'ابدأ مشروعك',
                'contact_note' => 'معاينة أولية مجانية في القاهرة والجيزة.',
                'hero_image' => '/images/template-library/contracting/hero.jpg',
                'gallery_image_1' => '/images/template-library/contracting/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/contracting/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/contracting/gallery-3.jpg',
            ],
            [
                'category' => 'صيانة وخدمات سيارات',
                'names' => [
                    'مركز صيانة — طابع تقني', 'ورشة سيارات — عرض خدمات', 'مركز عناية بالسيارات — إطلالة عصرية',
                    'مركز صيانة سريعة — وقتك محسوب', 'ورشة متنقلة — الخدمة توصلك', 'مركز إطارات وزيوت — أساسيات موثوقة',
                    'مركز تلميع وتنظيف — بريق دايم', 'ورشة كهرباء سيارات — تشخيص دقيق', 'مركز فحص فني — أمان قبل السفر',
                    'ورشة سمكرة ودهان — إصلاح احترافي', 'مركز صيانة فارهة — عناية استثنائية', 'ورشة دراجات نارية — سرعة وثقة',
                    'مركز قطع غيار — أصلي ومضمون', 'ورشة صيانة أسطول — حلول للشركات', 'مركز عناية شامل — عربيتك زي الجديدة',
                ],
                'cta_label' => 'احجز صيانة',
                'hero_title' => 'عربيتك في أيدٍ أمينة',
                'hero_subtitle' => 'فريق فني متخصص وقطع غيار أصلية، وضمان على كل خدمة.',
                'about_title' => 'ليه تثق فينا',
                'about_body' => 'بنستخدم أجهزة تشخيص حديثة وفريق فني مدرّب على أحدث الموديلات، عشان عربيتك ترجعلك بأمان وأداء ممتاز.',
                'services_title' => 'خدماتنا',
                'services_label' => 'الخدمات',
                'services_list' => ['صيانة دورية', 'كهرباء وميكانيكا', 'تكييف السيارات', 'بودي ودهانات', 'خدمة الطريق 24 ساعة'],
                'gallery_title' => 'داخل المركز',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['أسعار عادلة وخدمة سريعة — كريم ح.', 'شرحوا المشكلة بوضوح قبل ما يبدأوا — منال ز.'],
                'contact_title' => 'احجز موعدك',
                'contact_note' => 'خدمة سحب مجانية داخل المدينة.',
                'hero_image' => '/images/template-library/auto-service/hero.jpg',
                'gallery_image_1' => '/images/template-library/auto-service/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/auto-service/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/auto-service/gallery-3.jpg',
            ],
            [
                'category' => 'تنظيم فعاليات ومناسبات',
                'names' => [
                    'منظّم أفراح — طابع فخم', 'شركة فعاليات — إطلالة احتفالية', 'استوديو مناسبات — تصميم أنيق',
                    'منظّم حفلات خطوبة — بداية جميلة', 'شركة مؤتمرات — تنظيم احترافي', 'استوديو تصوير مناسبات — ذكرى موثقة',
                    'منظّم حفلات أطفال — فرح بلا حدود', 'شركة تأجير قاعات — مساحتك المثالية', 'منظّم فعاليات شركات — انطباع أول قوي',
                    'استوديو ديكور مناسبات — تفاصيل ساحرة', 'منظّم رحلات ترفيهية جماعية — متعة منظمة', 'شركة إضاءة وصوتيات — تجربة حسية كاملة',
                    'منظّم أعياد ميلاد — احتفال مميز', 'استوديو مناسبات فاخر — رفاهية كاملة', 'منظّم فعاليات خارجية — طبيعة واحتفال',
                ],
                'cta_label' => 'احجز استشارة',
                'hero_title' => 'مناسبتك تستاهل تفاصيل مثالية',
                'hero_subtitle' => 'من التخطيط للتنفيذ، بنحوّل مناسبتك لذكرى ما تتنسيش.',
                'about_title' => 'شغفنا',
                'about_body' => 'فريقنا بيهتم بأدق التفاصيل، من اختيار المكان لحد الديكور والضيافة، عشان يومك يبقى مظبوط زي ما حلمت بيه بالظبط.',
                'services_title' => 'خدماتنا',
                'services_label' => 'الخدمات',
                'services_list' => ['تنظيم حفلات زفاف', 'مناسبات شركات', 'ديكور وإضاءة', 'تصوير وتوثيق', 'ضيافة وكاترينج'],
                'gallery_title' => 'من مناسباتنا',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['يوم زفافنا كان أحلى من الخيال — ريم و أحمد.', 'تنظيم محترف من غير أي توتر — شركة نور.'],
                'contact_title' => 'خلّينا نخطط مناسبتك',
                'contact_note' => 'استشارة أولى مجانية لمناقشة رؤيتك.',
                'hero_image' => '/images/template-library/events/hero.jpg',
                'gallery_image_1' => '/images/template-library/events/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/events/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/events/gallery-3.jpg',
            ],
            [
                'category' => 'سفر وسياحة',
                'names' => [
                    'وكالة سياحة — إطلالة استكشافية', 'شركة رحلات — طابع مغامرة', 'منظّم رحلات — بساطة وثقة',
                    'وكالة سفر فاخرة — رفاهية بلا حدود', 'شركة رحلات عائلية — ذكريات مشتركة', 'منظّم رحلات دينية — رحلة روحانية',
                    'وكالة تأشيرات — إجراءات سهلة', 'شركة رحلات مغامرة — إثارة حقيقية', 'منظّم شهر عسل — بداية لا تُنسى',
                    'وكالة حجوزات فنادق — راحة مضمونة', 'شركة سياحة داخلية — اكتشف بلدك', 'منظّم رحلات طلابية — تعلم وترفيه',
                    'وكالة طيران — أسرع الطرق للسفر', 'شركة سياحة بيئية — سفر مسؤول', 'منظّم مؤتمرات سياحية — جمع بين العمل والسفر',
                ],
                'cta_label' => 'احجز رحلتك',
                'hero_title' => 'دنيا واسعة تستاهل تكتشفها',
                'hero_subtitle' => 'باقات سياحية مصممة ليك، بأسعار مناسبة وتنظيم كامل من الألف للياء.',
                'about_title' => 'ليه تسافر معانا',
                'about_body' => 'بنقدم رحلات داخلية وخارجية مختارة بعناية، مع حجوزات طيران وفنادق موثوقة، ودعم على مدار رحلتك بالكامل.',
                'services_title' => 'وجهاتنا وباقاتنا',
                'services_label' => 'الباقات',
                'services_list' => ['رحلات داخلية', 'باقات سفر خارجي', 'حجز طيران وفنادق', 'برامج سياحية جماعية', 'رحلات شهر عسل'],
                'gallery_title' => 'من رحلاتنا',
                'testimonials_title' => 'آراء المسافرين',
                'testimonials_list' => ['تنظيم ممتاز من أول لحظة — ياسر ع.', 'الباقة كانت تستاهل كل جنيه فيها — لمياء ص.'],
                'contact_title' => 'خطط رحلتك الجاية',
                'contact_note' => 'باقات مخصصة حسب ميزانيتك ورغبتك.',
                'hero_image' => '/images/template-library/travel/hero.jpg',
                'gallery_image_1' => '/images/template-library/travel/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/travel/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/travel/gallery-3.jpg',
            ],
            [
                'category' => 'برمجيات وستارت أب',
                'names' => [
                    'ستارت أب — إطلالة تقنية', 'منتج SaaS — عرض مميزات', 'شركة برمجيات — طابع احترافي',
                    'تطبيق موبايل — تجربة سلسة', 'منصة تجارة إلكترونية — بيع بلا حدود', 'ستارت أب تقني ناشئ — فكرة تتحول لواقع',
                    'شركة تطوير تطبيقات — حلول مخصصة', 'منصة إدارة مشاريع — تنظيم أفضل', 'ستارت أب فينتك — مالك بأمان',
                    'شركة أمن سيبراني — حماية رقمية', 'منصة تعليم تقني — مهارات المستقبل', 'ستارت أب صحي — تقنية تخدم صحتك',
                    'شركة استضافة مواقع — سرعة واستقرار', 'منصة توظيف رقمي — فرصتك التالية', 'ستارت أب طاقة متجددة — مستقبل أخضر',
                ],
                'cta_label' => 'جرّب مجاناً',
                'hero_title' => 'حل ذكي يوفّرلك وقتك ومجهودك',
                'hero_subtitle' => 'منصة سهلة الاستخدام بتساعد فريقك يشتغل أسرع وأكتر تنظيماً.',
                'about_title' => 'مهمتنا',
                'about_body' => 'بنبني أدوات بسيطة وقوية تحل مشاكل حقيقية للشركات، بفريق تقني شغوف ودعم فني سريع الاستجابة.',
                'services_title' => 'مميزات المنتج',
                'services_label' => 'المميزات',
                'services_list' => ['لوحة تحكم بسيطة', 'تكامل مع أدواتك الحالية', 'تقارير لحظية', 'دعم فني على مدار الساعة', 'أمان بيانات عالي'],
                'gallery_title' => 'لقطات من المنصة',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['وفّر علينا ساعات شغل كل أسبوع — يوسف ط.', 'دعم فني سريع فعلاً — هدى م.'],
                'contact_title' => 'جاهز تبدأ؟',
                'contact_note' => 'تجربة مجانية 14 يوم من غير أي بطاقة ائتمان.',
                'hero_image' => '/images/template-library/software/hero.jpg',
                'gallery_image_1' => '/images/template-library/software/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/software/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/software/gallery-3.jpg',
            ],
            [
                'category' => 'بورتفوليو ومستقلين',
                'names' => [
                    'بورتفوليو — تصميم شخصي', 'معرض أعمال — إطلالة إبداعية', 'صفحة فريلانسر — بساطة احترافية',
                    'بورتفوليو مصمم جرافيك — هوية بصرية', 'صفحة مطور مواقع — كود نظيف', 'بورتفوليو مصور — لحظات محفوظة',
                    'صفحة كاتب محتوى — كلمات مؤثرة', 'بورتفوليو معماري — مساحات مدروسة', 'صفحة مستشار مستقل — خبرة عند الطلب',
                    'بورتفوليو فنان — إبداع بلا قيود', 'صفحة مترجم — لغات بلا حواجز', 'بورتفوليو مونتير فيديو — قصص متحركة',
                    'صفحة مدرب مستقل — نمو شخصي', 'بورتفوليو مصمم داخلي — رؤية عملية', 'صفحة مسوق رقمي — نتائج قابلة للقياس',
                ],
                'cta_label' => 'تواصل معايا',
                'hero_title' => 'أحوّل أفكارك لشغل حقيقي',
                'hero_subtitle' => 'مستقل متخصص بشغف بيقدّم شغل بجودة عالية وفي الميعاد.',
                'about_title' => 'نبذة عني',
                'about_body' => 'بشتغل في المجال من سنين، وشغلي بيتكلم عني — كل مشروع بيبقى فرصة أثبت فيها إبداعي والتزامي بالمواعيد.',
                'services_title' => 'اللي بقدمه',
                'services_label' => 'الخدمات',
                'services_list' => ['تصميم وهوية بصرية', 'تطوير مواقع', 'كتابة محتوى', 'استشارات فنية', 'تعديلات غير محدودة على المشروع'],
                'gallery_title' => 'من أعمالي',
                'testimonials_title' => 'آراء عملائي',
                'testimonials_list' => ['شغل احترافي وتسليم في الميعاد — شركة ابتكار.', 'فاهم اللي محتاجه بالظبط من غير ما أشرح كتير — مريم ن.'],
                'contact_title' => 'نتكلم عن مشروعك؟',
                'contact_note' => 'بردّ على أي استفسار خلال يوم عمل.',
                'hero_image' => '/images/template-library/portfolio/hero.jpg',
                'gallery_image_1' => '/images/template-library/portfolio/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/portfolio/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/portfolio/gallery-3.jpg',
            ],
            [
                'category' => 'أزياء وملابس',
                'names' => [
                    'متجر أزياء — إطلالة عصرية', 'بوتيك نسائي — أناقة يومية', 'ماركة ملابس رجالي — ستايل واثق',
                    'متجر مقاسات كبيرة — راحة وأناقة', 'بوتيك فساتين سهرة — بريق خاص', 'متجر أزياء أطفال — ألوان مرحة',
                    'ماركة رياضية — حركة بلا قيود', 'بوتيك حجاب وملابس محتشمة — أناقة راقية', 'متجر ملابس عملية — يومك بثقة',
                    'ماركة فاخرة — حصرية وتميز', 'بوتيك موضة سريعة — تريند كل أسبوع', 'متجر إكسسوارات موضة — تفاصيل تكمّل الإطلالة',
                    'ماركة ملابس مستدامة — أناقة مسؤولة', 'بوتيك عرايس — يوم لا يُنسى', 'متجر أحذية وشنط — لمسة أخيرة للإطلالة',
                ],
                'cta_label' => 'تسوقي المجموعة',
                'hero_title' => 'ستايلك يبدأ من هنا',
                'hero_subtitle' => 'أحدث صيحات الموضة بخامات مختارة بعناية، وتوصيل لكل المحافظات.',
                'about_title' => 'هويتنا',
                'about_body' => 'بنؤمن إن الملابس مش بس قماش — دي طريقة تعبّر بيها عن نفسك. بنختار كل قطعة بعناية عشان تحسي بثقة في أي مكان.',
                'services_title' => 'مجموعاتنا',
                'services_label' => 'الفئات',
                'services_list' => ['ملابس كاجوال', 'ملابس رسمية ومناسبات', 'إكسسوارات', 'أحذية وشنط', 'مقاسات كبيرة'],
                'gallery_title' => 'من أحدث المجموعات',
                'testimonials_title' => 'آراء عميلاتنا',
                'testimonials_list' => ['الخامة والتفصيل فاق توقعاتي — سلمى ع.', 'التوصيل سريع والمقاسات مظبوطة — هالة ن.'],
                'contact_title' => 'اطلبي دلوقتي',
                'contact_note' => 'استبدال مجاني خلال 7 أيام.',
                'hero_image' => '/images/template-library/fashion/hero.jpg',
                'gallery_image_1' => '/images/template-library/fashion/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/fashion/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/fashion/gallery-3.jpg',
            ],
            [
                'category' => 'مخابز وحلويات',
                'names' => [
                    'مخبز — طازة كل يوم', 'محل حلويات شرقية — نكهة أصيلة', 'كيك شوب — تصميم يفرح العين',
                    'باتيسري فرنسي — رقي في كل قضمة', 'محل حلويات منزلية — طعم البيت', 'مخبز صحي — بديل ألذ وأخف',
                    'محل تورت مناسبات — تصميم حسب الطلب', 'كافيه حلويات — قهوة وحلا مع بعض', 'مخبز فرنسي — كرواسون طازة',
                    'محل شوكولاتة — متعة فاخرة', 'باتيسري عصري — إبداع في كل تفصيلة', 'محل حلويات رمضان — نكهة الشهر الكريم',
                    'مخبز عائلي — وصفات من الجدة', 'محل جيلاتو وآيس كريم — انتعاش في كل نكهة', 'باتيسري فاخر — تجربة استثنائية',
                ],
                'cta_label' => 'اطلب دلوقتي',
                'hero_title' => 'حلاوة يومك تبدأ من هنا',
                'hero_subtitle' => 'مخبوزات وحلويات طازة بتتحضّر يومياً بأجود المكونات.',
                'about_title' => 'شغفنا بالحلويات',
                'about_body' => 'كل قطعة بنعملها بحب واهتمام بالتفاصيل، من اختيار المكونات لحد اللمسة الأخيرة، عشان توصلك بنفس الطعم اللي بنفتخر بيه.',
                'services_title' => 'منتجاتنا',
                'services_label' => 'الأصناف',
                'services_list' => ['تورت مناسبات', 'حلويات شرقية', 'معجنات طازة يومياً', 'كيك ومخبوزات غربية', 'طلبات خاصة حسب الطلب'],
                'gallery_title' => 'من أفران وصالة عرضنا',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['التورت كان أحلى من الصورة — نهى س.', 'طعم أصيل زي أيام زمان — كريم ه.'],
                'contact_title' => 'اطلب حلوياتك',
                'contact_note' => 'طلبات المناسبات محتاجة يوم مقدماً على الأقل.',
                'hero_image' => '/images/template-library/bakery/hero.jpg',
                'gallery_image_1' => '/images/template-library/bakery/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/bakery/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/bakery/gallery-3.jpg',
            ],
            [
                'category' => 'حضانات وروضات أطفال',
                'names' => [
                    'حضانة — بداية آمنة', 'روضة أطفال — تعلم باللعب', 'حضانة نهارية — راحة بال الأهل',
                    'روضة دولية — لغات من الصغر', 'حضانة مونتيسوري — استقلالية مبكرة', 'روضة فنية — إبداع بلا حدود',
                    'حضانة صغيرة — اهتمام شخصي', 'روضة رياضية — طاقة وحركة', 'حضانة على مدار اليوم — مرونة للعاملين',
                    'روضة تحفيظ — قيم من الصغر', 'حضانة حديثة — بيئة محفزة', 'روضة تحضيرية — استعداد للمدرسة',
                    'حضانة عائلية — دفء البيت', 'روضة ثنائية اللغة — انطلاقة عالمية', 'حضانة فاخرة — رعاية متكاملة',
                ],
                'cta_label' => 'سجّل طفلك دلوقتي',
                'hero_title' => 'بداية آمنة ومليانة تعلم لطفلك',
                'hero_subtitle' => 'بيئة محفّزة ومربّيات متخصصات، عشان طفلك يكبر بثقة وسعادة.',
                'about_title' => 'فلسفتنا التربوية',
                'about_body' => 'بنؤمن إن كل طفل عنده طاقة إبداع لازم تتنمّى في بيئة آمنة ومحبة. برنامجنا بيجمع بين اللعب والتعلم بطريقة متوازنة.',
                'services_title' => 'برامجنا',
                'services_label' => 'المراحل العمرية',
                'services_list' => ['حضانة (من 6 شهور)', 'تمهيدي', 'روضة أولى وتانية', 'أنشطة ورحلات تعليمية', 'متابعة يومية مع الأهل'],
                'gallery_title' => 'جولة داخل الحضانة',
                'testimonials_title' => 'آراء أولياء الأمور',
                'testimonials_list' => ['بنتي بقت تحب تروح كل يوم — منى ط.', 'المربيات صبورين وبيتابعوا كل التفاصيل — أحمد ز.'],
                'contact_title' => 'احجز زيارة تعريفية',
                'contact_note' => 'أماكن محدودة كل فصل دراسي.',
                'hero_image' => '/images/template-library/nurseries/hero.jpg',
                'gallery_image_1' => '/images/template-library/nurseries/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/nurseries/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/nurseries/gallery-3.jpg',
            ],
            [
                'category' => 'تصوير وإنتاج فيديو',
                'names' => [
                    'استوديو تصوير — احترافية عالية', 'مصور أفراح — لحظات لا تُنسى', 'استوديو إنتاج فيديو — قصص متحركة',
                    'مصور منتجات — عرض جذاب', 'استوديو بورتريه — إطلالة مميزة', 'مصور مناسبات — توثيق شامل',
                    'استوديو إعلانات — محتوى يبيع', 'مصور خارجي — طبيعة وإبداع', 'استوديو تصوير أطفال — براءة موثقة',
                    'مصور فعاليات شركات — انطباع احترافي', 'استوديو موشن جرافيك — حركة وإبداع', 'مصور بالدرون — منظور مختلف',
                    'استوديو تصوير فاخر — تجربة راقية', 'مصور شخصي — قصتك بعدستي', 'استوديو مونتاج — تحرير احترافي',
                ],
                'cta_label' => 'احجز جلستك',
                'hero_title' => 'لحظاتك تستاهل توثيق احترافي',
                'hero_subtitle' => 'تصوير فوتوغرافي وفيديو بعين فنية وخبرة تقنية عالية.',
                'about_title' => 'رؤيتنا',
                'about_body' => 'بنشوف كل مناسبة كقصة تستاهل تتحكي صح. بنستخدم أحدث المعدات وخبرة سنين عشان نوثقلك أجمل اللحظات.',
                'services_title' => 'خدماتنا',
                'services_label' => 'التخصصات',
                'services_list' => ['تصوير أفراح ومناسبات', 'تصوير منتجات وإعلانات', 'فيديو تعريفي للشركات', 'تصوير بورتريه', 'مونتاج وإضافة مؤثرات'],
                'gallery_title' => 'من أعمالنا',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['وثقوا يوم زفافنا بشكل سينمائي — دينا وكريم.', 'الفيديو الإعلاني رفع مبيعاتنا فعلاً — شركة أفق.'],
                'contact_title' => 'احجز جلسة تصوير',
                'contact_note' => 'حجز مسبق مطلوب قبل المناسبة بأسبوعين على الأقل.',
                'hero_image' => '/images/template-library/photography/hero.jpg',
                'gallery_image_1' => '/images/template-library/photography/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/photography/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/photography/gallery-3.jpg',
            ],
            [
                'category' => 'عطور ومستحضرات تجميل',
                'names' => [
                    'متجر عطور — رائحة تدوم', 'بوتيك مستحضرات تجميل — جمال طبيعي', 'محل عطور فاخرة — تركيبات نادرة',
                    'متجر مكياج — إطلالة كاملة', 'بوتيك عناية بالبشرة — روتين مثالي', 'محل عطور رجالي — حضور قوي',
                    'متجر عطور نسائي — أنوثة راقية', 'بوتيك هدايا تجميل — لحظة مميزة', 'محل عطور عربية — تراث أصيل',
                    'متجر مستحضرات طبيعية — نقاء وأمان', 'بوتيك فاخر — تجربة حصرية', 'محل عطور مستوحاة — جودة بسعر مناسب',
                    'متجر عناية بالشعر — صحة ولمعان', 'بوتيك مكياج احترافي — لكل مناسبة', 'محل عطور مميزة — توقيعك الفريد',
                ],
                'cta_label' => 'اكتشف المجموعة',
                'hero_title' => 'رائحتك توقيعك الخاص',
                'hero_subtitle' => 'عطور ومستحضرات تجميل أصلية مختارة بعناية لكل الأذواق.',
                'about_title' => 'شغفنا بالعطور',
                'about_body' => 'بنختار كل عطر ومنتج بعناية فائقة، من مصادر موثوقة، عشان نضمنلك جودة أصلية وتجربة حسية مميزة.',
                'services_title' => 'منتجاتنا',
                'services_label' => 'الفئات',
                'services_list' => ['عطور رجالي ونسائي', 'مستحضرات عناية بالبشرة', 'مكياج أصلي', 'عطور فاخرة ونادرة', 'باقات هدايا'],
                'gallery_title' => 'من مجموعتنا',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['العطر ثابت طول اليوم — رنا ك.', 'منتجات أصلية 100% وتغليف فخم — عمرو ل.'],
                'contact_title' => 'اطلب دلوقتي',
                'contact_note' => 'توصيل لكل المحافظات خلال 3 أيام عمل.',
                'hero_image' => '/images/template-library/perfumes/hero.jpg',
                'gallery_image_1' => '/images/template-library/perfumes/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/perfumes/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/perfumes/gallery-3.jpg',
            ],
            [
                'category' => 'عيادات بيطرية وخدمات حيوانات أليفة',
                'names' => [
                    'عيادة بيطرية — رعاية بمحبة', 'مركز طبي للحيوانات — خدمة شاملة', 'عيادة قطط متخصصة — عناية دقيقة',
                    'مركز فندقة حيوانات — راحة في غيابك', 'عيادة كلاب — تدريب ورعاية', 'مركز تجميل حيوانات — إطلالة نظيفة',
                    'عيادة بيطرية طارئة — استجابة فورية', 'مركز بيطري فاخر — رعاية استثنائية', 'عيادة حيوانات أليفة صغيرة — اهتمام خاص',
                    'مركز تدريب حيوانات — سلوك أفضل', 'عيادة بيطرية متنقلة — الخدمة توصلك', 'مركز رعاية طيور وحيوانات غريبة — خبرة نادرة',
                    'عيادة بيطرية عائلية — ثقة من زمان', 'مركز تبني حيوانات — بيت جديد وحب', 'عيادة بيطرية حديثة — تقنية متطورة',
                ],
                'cta_label' => 'احجز موعد لحيوانك الأليف',
                'hero_title' => 'صحة صديقك الأليف أولويتنا',
                'hero_subtitle' => 'رعاية بيطرية شاملة بأيدي متخصصين بيحبوا الحيوانات زيك بالظبط.',
                'about_title' => 'ليه تثق فينا',
                'about_body' => 'فريقنا البيطري بيتعامل مع حيوانك بلطف واحترافية، بأحدث الأجهزة الطبية وخبرة سنين في كل الأنواع والحالات.',
                'services_title' => 'خدماتنا',
                'services_label' => 'الخدمات',
                'services_list' => ['كشف وتطعيمات', 'جراحات بيطرية', 'عناية وتجميل', 'فندقة حيوانات أليفة', 'استشارات تغذية'],
                'gallery_title' => 'جولة داخل العيادة',
                'testimonials_title' => 'آراء عملائنا',
                'testimonials_list' => ['اتعاملوا مع قطتي بلطف شديد — ياسمين ع.', 'فريق محترف وأسعار عادلة — محمد ف.'],
                'contact_title' => 'احجز الكشف',
                'contact_note' => 'خدمة طوارئ بيطرية متاحة على مدار الساعة.',
                'hero_image' => '/images/template-library/veterinary/hero.jpg',
                'gallery_image_1' => '/images/template-library/veterinary/gallery-1.jpg',
                'gallery_image_2' => '/images/template-library/veterinary/gallery-2.jpg',
                'gallery_image_3' => '/images/template-library/veterinary/gallery-3.jpg',
            ],
        ];
    }
}
