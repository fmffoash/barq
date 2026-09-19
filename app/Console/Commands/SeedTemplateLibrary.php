<?php

namespace App\Console\Commands;

use App\Models\Template;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// بيولّد مكتبة قوالب أصلية (Phase 7) — عدد من الفئات التجارية الشائعة، كل فئة ليها 3 قوالب
// بتصميمات بصرية وألوان مختلفة (classic/modern/gallery) لكن بنفس بنية الخانات الموحّدة (هيرو،
// من نحن، خدمات، جاليري، آراء العملاء، تواصل). المحتوى الافتراضي كله نصوص عربية أصلية اتكتبت
// خصيصاً للمكتبة دي — مفيش قالب خارجي أو تصميم منسوخ من مصدر تاني، عشان صفر مخاطرة ترخيص.
// الأمر idempotent بالكامل (updateOrCreate بكل مستوى) — تشغيله تاني آمن ومحدّش هيتكرر.
#[Signature('barq:seed-template-library')]
#[Description('توليد/تحديث مكتبة قوالب أصلية تغطي فئات نشاط شائعة (3 قوالب لكل فئة)')]
class SeedTemplateLibrary extends Command
{
    /** @var array<string, array{primary: string, background: string, surface: string, text: string, muted: string}> */
    private array $palettes = [
        'amber' => ['primary' => '#f59e0b', 'background' => '#0b1220', 'surface' => '#111a2e', 'text' => '#f1f5f9', 'muted' => '#94a3b8'],
        'emerald' => ['primary' => '#10b981', 'background' => '#0a1612', 'surface' => '#0f2019', 'text' => '#ecfdf5', 'muted' => '#8fae9f'],
        'sky' => ['primary' => '#38bdf8', 'background' => '#0b1524', 'surface' => '#101c30', 'text' => '#eff6ff', 'muted' => '#93a8c4'],
        'rose' => ['primary' => '#fb7185', 'background' => '#180d12', 'surface' => '#24141b', 'text' => '#fdf2f4', 'muted' => '#c7a3ab'],
        'ruby' => ['primary' => '#ef4444', 'background' => '#1a0e0e', 'surface' => '#271414', 'text' => '#fef2f2', 'muted' => '#c9a3a3'],
        'teal' => ['primary' => '#14b8a6', 'background' => '#08181a', 'surface' => '#0f2426', 'text' => '#ecfeff', 'muted' => '#8fbabd'],
        'indigo' => ['primary' => '#818cf8', 'background' => '#0d0e1a', 'surface' => '#14152a', 'text' => '#eef2ff', 'muted' => '#9ca3c9'],
    ];

    // الترتيب اللي كل فئة بتوزّع بيه 3 قوالبها على التصميمات — نفس التوزيع لكل الفئات
    // عشان يبقى متوقّع: الأول modern، التاني gallery، التالت classic.
    private array $layoutRotation = ['modern', 'gallery', 'classic'];

    public function handle(): int
    {
        $paletteKeys = array_keys($this->palettes);
        $created = 0;
        $updated = 0;

        foreach ($this->categories() as $categoryIndex => $category) {
            $palette = $this->palettes[$paletteKeys[$categoryIndex % count($paletteKeys)]];

            foreach ($category['names'] as $templateIndex => $name) {
                $layout = $this->layoutRotation[$templateIndex % count($this->layoutRotation)];

                [, $wasRecentlyCreated] = $this->upsertTemplate($category, $name, $layout, $palette);

                $wasRecentlyCreated ? $created++ : $updated++;
            }

            $this->info("✓ {$category['category']} — 3 قوالب");
        }

        $this->newLine();
        $this->info("خلصت: {$created} قالب جديد، {$updated} قالب محدّث. شوفهم في /templates.");

        return self::SUCCESS;
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
                    'license_note' => 'تصميم ومحتوى أصلي لمكتبة برق — صفر اعتماد على قالب خارجي.',
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
            ['section_key' => 'hero', 'key' => 'hero_image', 'label_ar' => 'صورة الغلاف', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 4, 'default_value' => null],

            ['section_key' => 'about', 'key' => 'about_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['about_title']],
            ['section_key' => 'about', 'key' => 'about_body', 'label_ar' => 'نبذة تعريفية', 'slot_type' => 'textarea', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['about_body']],

            ['section_key' => 'services', 'key' => 'services_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['services_title']],
            ['section_key' => 'services', 'key' => 'services_list', 'label_ar' => $c['services_label'], 'slot_type' => 'list', 'is_required' => false, 'sort_order' => 2, 'default_value' => $c['services_list']],

            ['section_key' => 'gallery', 'key' => 'gallery_title', 'label_ar' => 'عنوان القسم', 'slot_type' => 'text', 'is_required' => false, 'sort_order' => 1, 'default_value' => $c['gallery_title']],
            ['section_key' => 'gallery', 'key' => 'gallery_image_1', 'label_ar' => 'صورة 1', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 2, 'default_value' => null],
            ['section_key' => 'gallery', 'key' => 'gallery_image_2', 'label_ar' => 'صورة 2', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 3, 'default_value' => null],
            ['section_key' => 'gallery', 'key' => 'gallery_image_3', 'label_ar' => 'صورة 3', 'slot_type' => 'image', 'is_required' => false, 'sort_order' => 4, 'default_value' => null],

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
                'names' => ['مطعم — واجهة دافئة', 'كافيه — إطلالة عصرية', 'مطعم فاخر — طابع راقي'],
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
            ],
            [
                'category' => 'عيادات وخدمات طبية',
                'names' => ['عيادة — ثقة وأمان', 'مركز طبي — رعاية شاملة', 'عيادة تخصصية — طابع هادئ'],
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
            ],
            [
                'category' => 'صالونات وتجميل',
                'names' => ['صالون — لمسة أنوثة', 'مركز تجميل — فخامة عصرية', 'صالون رجالي — ستايل مميز'],
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
            ],
            [
                'category' => 'جيم ولياقة بدنية',
                'names' => ['جيم — طاقة وقوة', 'نادي لياقة — مساحة مفتوحة', 'استوديو تمارين — تركيز شخصي'],
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
            ],
            [
                'category' => 'عقارات',
                'names' => ['عقارات — واجهة احترافية', 'شركة تطوير عقاري — إطلالة فاخرة', 'وسيط عقاري — بساطة وثقة'],
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
            ],
            [
                'category' => 'متاجر إلكترونية',
                'names' => ['متجر — تسوق سهل', 'بوتيك أونلاين — طابع أنيق', 'متجر منتجات — عرض واسع'],
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
            ],
            [
                'category' => 'تعليم ودورات تدريبية',
                'names' => ['أكاديمية — تصميم واضح', 'منصة دورات — طابع تفاعلي', 'مركز تدريب — إطلالة عملية'],
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
            ],
            [
                'category' => 'استشارات ومحاماة',
                'names' => ['مكتب محاماة — طابع رسمي', 'استشارات قانونية — ثقة ومصداقية', 'مكتب استشارات أعمال — احترافية'],
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
            ],
            [
                'category' => 'مقاولات وديكور',
                'names' => ['شركة مقاولات — طابع صناعي', 'استوديو ديكور — إطلالة أنيقة', 'شركة تشطيبات — عرض أعمال'],
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
            ],
            [
                'category' => 'صيانة وخدمات سيارات',
                'names' => ['مركز صيانة — طابع تقني', 'ورشة سيارات — عرض خدمات', 'مركز عناية بالسيارات — إطلالة عصرية'],
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
            ],
            [
                'category' => 'تنظيم فعاليات ومناسبات',
                'names' => ['منظّم أفراح — طابع فخم', 'شركة فعاليات — إطلالة احتفالية', 'استوديو مناسبات — تصميم أنيق'],
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
            ],
            [
                'category' => 'سفر وسياحة',
                'names' => ['وكالة سياحة — إطلالة استكشافية', 'شركة رحلات — طابع مغامرة', 'منظّم رحلات — بساطة وثقة'],
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
            ],
            [
                'category' => 'برمجيات وستارت أب',
                'names' => ['ستارت أب — إطلالة تقنية', 'منتج SaaS — عرض مميزات', 'شركة برمجيات — طابع احترافي'],
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
            ],
            [
                'category' => 'بورتفوليو ومستقلين',
                'names' => ['بورتفوليو — تصميم شخصي', 'معرض أعمال — إطلالة إبداعية', 'صفحة فريلانسر — بساطة احترافية'],
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
            ],
        ];
    }
}
