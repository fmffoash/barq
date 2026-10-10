<?php

namespace App\Services;

use App\Console\Commands\SeedTemplateLibrary;
use App\Models\Project;
use App\Models\Template;
use App\Support\CategoryGuesser;
use App\Support\PastedText;
use Illuminate\Support\Collection;

/**
 * اختيار القالب جوّه الفئة (2026-10-08). قبل كده: اسم القالب فيه كلمة الطابع حرفياً، وإلا
 * عشوائي — فدكتور أسنان كان بياخد "عيادة نفسية" بالصدفة، ونفس القالب بيتكرر. دلوقتي كل قالب
 * بياخد نقط:
 *
 * 1. التخصص (الأهم): كلمات اسم القالب قبل "—" (عيادة أسنان، مطعم مشويات، باربر شوب) اللي موجودة
 *    في كلام فؤاد / نوع النشاط على جوجل — كل كلمة بوزن ندرتها في الفئة: "أسنان" (قالب واحد) أهم
 *    بكتير من "عيادة" (نص القوالب). وفيه مرادفات للكلام الشائع (Dentist، تقويم، كباب...).
 * 2. الطابع: كلمات الطابع (فاخر، بسيط، شبابي...) في نص اسم القالب بعد "—" أو في شخصية تصميمه.
 * 3. التنوع: قالب اتستخدم في مشاريع قريبة بيخسر نقط، وكمان التصميم (layout) اللي لسه مستخدم.
 * 4. فرق عشوائي صغير بين المتساويين — عشان متطلعش نفس النتيجة كل مرة.
 */
class TemplatePicker
{
    // كلمة في أسماء القوالب ← طرق تانية بيتقال بيها نفس التخصص (بعد التوحيد: ة←ه، أ←ا، ى←ي).
    private const SYNONYMS = [
        'اسنان' => ['dentist', 'dental', 'تقويم', 'زراعه', 'تبييض', 'ضروس', 'سنان'],
        'اطفال' => ['pediatrician', 'pediatric', 'kids', 'children', 'طفل', 'الاطفال'],
        'جلديه' => ['dermatologist', 'dermatology', 'skin', 'بشره', 'جلد'],
        'عيون' => ['ophthalmologist', 'eye', 'optometrist', 'رمد', 'نظارات'],
        'نسائيه' => ['gynecologist', 'obstetrician', 'نساء', 'توليد', 'حوامل'],
        'نفسيه' => ['psychiatrist', 'psychologist', 'نفسي', 'نفساني', 'اكتئاب'],
        'طبيعي' => ['physiotherapist', 'physiotherapy', 'تاهيل'],
        'اشعه' => ['radiology', 'سونار', 'رنين', 'xray'],
        'تحاليل' => ['laboratory', 'lab', 'معمل', 'معامل'],
        'تخسيس' => ['nutritionist', 'dietitian', 'diet', 'تغذيه', 'رجيم'],
        'طوارئ' => ['emergency', 'اسعاف'],
        'مستشفي' => ['hospital'],
        'بيتزا' => ['pizza', 'برجر', 'burger', 'فاست', 'سندوتشات'],
        'بحري' => ['seafood', 'fish', 'سمك', 'اسماك', 'جمبري'],
        'مشويات' => ['grill', 'barbecue', 'bbq', 'kebab', 'كباب', 'كفته', 'مشاوي'],
        'كافيه' => ['cafe', 'coffee', 'قهوه', 'كوفي'],
        'شعبي' => ['فول', 'طعميه', 'كشري', 'كبده', 'حواوشي'],
        'رجالي' => ['حلاق', 'barber', 'men', 'رجاله'],
        'باربر' => ['حلاق', 'barber', 'barbershop'],
        'عرايس' => ['bridal', 'عروسه', 'زفاف', 'فرح'],
        'اظافر' => ['nails', 'nail', 'مانيكير', 'باديكير'],
        'ليزر' => ['laser', 'ازاله شعر'],
        'سبا' => ['spa', 'مساج', 'massage'],
        'يوجا' => ['yoga'],
        'كروسفيت' => ['crossfit', 'كروس'],
        'ملاكمه' => ['boxing', 'كيك بوكس', 'mma'],
        'سباحه' => ['swimming', 'pool', 'حمام سباحه'],
        'بيلاتس' => ['pilates'],
        'نسائي' => ['ladies', 'women', 'سيدات', 'بنات', 'حريمي'],
        'قطط' => ['cat', 'cats'],
        'كلاب' => ['dog', 'dogs'],
        'بيطريه' => ['veterinarian', 'vet', 'بيطري'],
        'حلويات' => ['sweets', 'dessert', 'كنافه', 'بسبوسه'],
        'كيك' => ['cake', 'تورته', 'تورت'],
        'شوكولاته' => ['chocolate'],
        'جيلاتو' => ['ice cream', 'ايس كريم'],
        'محاماه' => ['lawyer', 'law', 'محامي', 'attorney'],
        'ضريبيه' => ['tax', 'ضرايب', 'ضرائب'],
        'تشطيبات' => ['finishing', 'تشطيب'],
        'ديكور' => ['interior', 'decor'],
        'اطارات' => ['tires', 'tyres', 'كاوتش'],
        'سمكره' => ['body shop', 'دوكو'],
        'عطور' => ['perfume', 'fragrance', 'برفان'],
        'مكياج' => ['makeup', 'ميكب', 'ميك اب'],
        'لغات' => ['language', 'english', 'انجليزي'],
        'برمجه' => ['coding', 'programming', 'developer'],
    ];

    // كلمات الطابع اللي فؤاد/النموذج بيقولها ← كلمات بتوصفه في أسماء القوالب وشخصيات التصميمات.
    private const STYLE_WORDS = [
        'فاخر' => ['فاخر', 'فخم', 'افخم', 'راقي', 'ارقي', 'شيك', 'luxury', 'فخامه', 'رقي', 'انيق'],
        'بسيط' => ['بسيط', 'ابسط', 'هادي', 'اهدي', 'هادئ', 'نظيف', 'minimal', 'بساطه', 'هدوء'],
        'عصري' => ['عصري', 'مودرن', 'modern', 'حديث', 'احدث', 'عصريه'],
        'شبابي' => ['شبابي', 'جريء', 'جرئ', 'ملون', 'مبهج', 'حيوي', 'طاقه', 'جريئه'],
        'دافئ' => ['دافئ', 'دافي', 'عائلي', 'تقليدي', 'اصيل', 'دفء'],
        'ليلي' => ['ليلي', 'غامق', 'dark', 'نيون', 'neon'],
    ];

    public function __construct(private readonly bool $jitter = true) {}

    /**
     * @param  string  $context  كلام فؤاد + نوع النشاط على جوجل + الاسم (اللي يوصف التخصص)
     * @param  string  $styleHint  الطابع (من النموذج أو من رسالة "عايز شكل أفخم")
     * @return array{template: Template, reason: string|null}|null
     */
    public function pick(string $category, string $context, string $styleHint = '', ?int $exceptId = null, ?string $avoidLayout = null): ?array
    {
        $templates = Template::query()
            ->where('is_active', true)
            ->where('kind', 'landing')
            ->where('category', $category)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->with('variants')
            ->get();

        if ($templates->isEmpty()) {
            return null;
        }

        $scored = $this->score($templates, $context, $styleHint, $avoidLayout);
        $best = $scored->sortByDesc('score')->first();

        return ['template' => $best['template'], 'reason' => $best['reason']];
    }

    /**
     * @param  Collection<int, Template>  $templates
     * @return Collection<int, array{template: Template, score: float, reason: string|null}>
     */
    public function score(Collection $templates, string $context, string $styleHint = '', ?string $avoidLayout = null): Collection
    {
        [$words, $joined] = CategoryGuesser::words($context);
        $words = $this->withSynonyms($words, $joined);
        $styles = $this->styleKeys($styleHint);

        // ندرة كل كلمة تخصص جوّه الفئة: كلمة في قالب واحد بس = وزن عالي.
        $specialties = $templates->mapWithKeys(fn (Template $t) => [$t->id => $this->specialtyTokens($t->name)]);
        $df = [];
        foreach ($specialties as $tokens) {
            foreach (array_unique($tokens) as $token) {
                $df[$token] = ($df[$token] ?? 0) + 1;
            }
        }
        $n = max($templates->count(), 1);

        [$recentTemplates, $recentLayouts] = $this->recentUse();

        return $templates->map(function (Template $template) use ($specialties, $df, $n, $words, $styles, $avoidLayout, $recentTemplates, $recentLayouts) {
            $specialty = 0.0;
            foreach (array_unique($specialties[$template->id]) as $token) {
                if (isset($words[$token])) {
                    $specialty += log(($n + 1) / $df[$token]);
                }
            }

            $style = 0.0;
            if ($styles !== []) {
                $mood = PastedText::normalizeArabic(trim(explode('—', $template->name, 2)[1] ?? ''));
                $layoutWords = PastedText::normalizeArabic(implode(' ', SeedTemplateLibrary::LAYOUT_KEYWORDS[$template->layout] ?? []));
                foreach ($styles as $styleKey) {
                    foreach (self::STYLE_WORDS[$styleKey] as $variant) {
                        $variant = PastedText::normalizeArabic($variant);
                        if (str_contains($mood, $variant) || str_contains($layoutWords, $variant)) {
                            $style = 1.2;
                            break 2;
                        }
                    }
                }
            }

            $score = 2.0 * $specialty + $style
                - 0.8 * min($recentTemplates[$template->id] ?? 0, 3)
                - 0.4 * ($recentLayouts[$template->layout] ?? 0)
                - ($avoidLayout !== null && $template->layout === $avoidLayout ? 1.0 : 0.0)
                + ($this->jitter ? mt_rand(0, 300) / 1000 : 0.0);

            $label = trim(explode('—', $template->name, 2)[0]);

            return [
                'template' => $template,
                'score' => $score,
                'reason' => $specialty >= 1.0 ? "مخصوص لـ«{$label}»" : null,
            ];
        })->values();
    }

    /**
     * كلمات التخصص في اسم القالب (قبل "—")، موحّدة.
     *
     * @return list<string>
     */
    private function specialtyTokens(string $name): array
    {
        $part = PastedText::normalizeArabic(trim(explode('—', $name, 2)[0]));
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $part, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_map(
            fn ($t) => preg_replace('/^(وال|بال|ال|و|ب|ل)(?=\p{L}{3,})/u', '', $t) ?? $t,
            $tokens,
        ));
    }

    /**
     * @param  array<string, true>  $words
     * @return array<string, true>
     */
    private function withSynonyms(array $words, string $joined): array
    {
        foreach (self::SYNONYMS as $canonical => $variants) {
            foreach ($variants as $variant) {
                $variant = PastedText::normalizeArabic($variant);
                $hit = str_contains($variant, ' ') ? str_contains($joined, ' '.$variant.' ') : isset($words[$variant]);
                if ($hit) {
                    $words[PastedText::normalizeArabic($canonical)] = true;
                    break;
                }
            }
        }

        return $words;
    }

    /**
     * @return list<string> مفاتيح STYLE_WORDS اللي موجودة في الكلام
     */
    public function styleKeys(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        [$words, $joined] = CategoryGuesser::words($text);
        $keys = [];

        foreach (self::STYLE_WORDS as $key => $variants) {
            foreach ($variants as $variant) {
                $variant = PastedText::normalizeArabic($variant);
                if (isset($words[$variant]) || str_contains($joined, ' '.$variant.' ')) {
                    $keys[] = $key;
                    break;
                }
            }
        }

        return $keys;
    }

    /**
     * عدد مرات استخدام كل قالب في آخر 20 مشروع، وكل تصميم في آخر 3.
     *
     * @return array{0: array<int, int>, 1: array<string, int>}
     */
    private function recentUse(): array
    {
        $recent = Project::query()
            ->latest('id')
            ->limit(20)
            ->with('template:id,layout')
            ->get(['id', 'template_id']);

        $templates = $recent->countBy('template_id')->all();
        $layouts = $recent->take(3)->map(fn ($p) => $p->template?->layout)->filter()->countBy()->all();

        return [$templates, $layouts];
    }
}
