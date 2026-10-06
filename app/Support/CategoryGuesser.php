<?php

namespace App\Support;

use Illuminate\Support\Collection;

// تخمين نوع النشاط من كلام فؤاد نفسه من غير ذكاء اصطناعي (2026-10-06). قبل كده لو الذكاء
// الاصطناعي ماردّش، البرنامج كان بياخد **أول فئة أبجدياً** ("أزياء وملابس") — فعيادة أسنان طلعت
// متجر هدوم. دلوقتي ده أول خط دفاع، وبيستخدم كمان لتأكيد اختيار النموذج.
//
// الكلمات مكتوبة بعد التوحيد (PastedText::normalizeArabic: أ/إ/آ ← ا، ة ← ه، ى ← ي). وزن 3 =
// كلمة بتحدد النشاط لوحدها (طبيب، مطعم)، وزن 1 = كلمة بتساعد بس (اطفال، تجميل).
class CategoryGuesser
{
    private const KEYWORDS = [
        'مطاعم وكافيهات' => [
            3 => ['مطعم', 'مطاعم', 'كافيه', 'كافي', 'كوفي', 'مشويات', 'بيتزا', 'برجر', 'فطاطري', 'فطير', 'شاورما', 'كشري', 'مشاوي', 'مأكولات', 'ماكولات', 'restaurant', 'cafe', 'café', 'coffee', 'pizza', 'burger', 'grill', 'diner', 'bistro', 'shawarma'],
            1 => ['اكل', 'اكلات', 'سندوتش', 'وجبات', 'قهوه', 'مطبخ', 'سمك', 'دليفري', 'food', 'menu', 'منيو'],
        ],
        'عيادات وخدمات طبية' => [
            3 => ['عياده', 'عيادات', 'طبيب', 'دكتور', 'اسنان', 'جلديه', 'باطنه', 'عظام', 'نساء وتوليد', 'مستشفي', 'مركز طبي', 'تحاليل', 'معمل', 'اشعه', 'علاج طبيعي', 'صيدليه', 'رمد', 'عيون', 'انف واذن', 'تقويم', 'clinic', 'doctor', 'dental', 'dentist', 'hospital', 'medical', 'pharmacy', 'physiotherapy', 'dr'],
            1 => ['كشف', 'علاج', 'مريض', 'صحه'],
        ],
        'صالونات وتجميل' => [
            3 => ['صالون', 'كوافير', 'حلاق', 'باربر', 'بيوتي', 'سبا', 'مانيكير', 'باديكير', 'بديكير', 'salon', 'barber', 'beauty center', 'spa', 'nails', 'hair'],
            1 => ['شعر', 'ميك اب', 'ميكب', 'ليزر', 'تجميل', 'عرايس'],
        ],
        'جيم ولياقة بدنية' => [
            3 => ['جيم', 'جيمنازيوم', 'لياقه', 'فتنس', 'كروس فيت', 'كروسفيت', 'يوجا', 'ملاكمه', 'كاراتيه', 'كونغ فو', 'gym', 'fitness', 'crossfit', 'yoga', 'boxing', 'pilates'],
            1 => ['رياضه', 'تمارين', 'تخسيس', 'كمال اجسام', 'سباحه', 'مدرب'],
        ],
        'عقارات' => [
            3 => ['عقارات', 'عقاري', 'شقق', 'كمبوند', 'تمليك', 'سمسار', 'وحدات سكنيه', 'real estate', 'apartments', 'property', 'villas'],
            1 => ['شقه', 'فيلا', 'ايجار', 'اراضي', 'مقدم', 'تقسيط', 'وحده'],
        ],
        'متاجر إلكترونية' => [
            3 => ['متجر الكتروني', 'متجر اونلاين', 'اونلاين ستور', 'online store', 'ecommerce', 'e-commerce'],
            1 => ['شحن', 'توصيل لكل المحافظات', 'اطلب اونلاين', 'منتجات', 'ستور', 'store', 'shop'],
        ],
        'تعليم ودورات تدريبية' => [
            3 => ['اكاديميه', 'كورسات', 'كورس', 'دورات', 'دوره تدريبيه', 'سنتر', 'مدرسه', 'academy', 'courses', 'school', 'training center', 'tutoring'],
            1 => ['تعليم', 'دروس', 'مدرس', 'لغات', 'تدريب', 'طلاب', 'شهاده'],
        ],
        'استشارات ومحاماة' => [
            3 => ['محامي', 'محاماه', 'مكتب محاماه', 'قانوني', 'محاسب قانوني', 'استشارات قانونيه', 'lawyer', 'law firm', 'attorney', 'legal'],
            1 => ['استشارات', 'استشاره', 'قضايا', 'عقود', 'محاسبه', 'ضرائب', 'consulting', 'accounting'],
        ],
        'مقاولات وديكور' => [
            3 => ['مقاولات', 'مقاول', 'تشطيب', 'تشطيبات', 'ديكور', 'نقاشه', 'الوميتال', 'جبس بورد', 'contracting', 'construction', 'interior design', 'finishing'],
            1 => ['دهانات', 'سباكه', 'كهرباء', 'اثاث', 'مطابخ', 'سيراميك', 'ترميم', 'decor'],
        ],
        'صيانة وخدمات سيارات' => [
            3 => ['مركز صيانه', 'ورشه', 'ميكانيكي', 'سمكره', 'دوكو', 'كاوتش', 'غسيل سيارات', 'غسيل عربيات', 'car service', 'auto repair', 'garage', 'car wash', 'tires'],
            1 => ['سيارات', 'عربيات', 'عربيه', 'سياره', 'زيوت', 'فرامل', 'تكييف سيارات'],
        ],
        'تنظيم فعاليات ومناسبات' => [
            3 => ['تنظيم فعاليات', 'تنظيم مناسبات', 'تنظيم حفلات', 'قاعه افراح', 'قاعات افراح', 'ويدينج', 'wedding planner', 'event planning', 'events', 'venue'],
            1 => ['فعاليات', 'مناسبات', 'افراح', 'حفلات', 'زفاف', 'خطوبه', 'كوشه', 'party'],
        ],
        'سفر وسياحة' => [
            3 => ['سياحه', 'شركه سفر', 'رحلات', 'حج', 'عمره', 'تذاكر طيران', 'حجز فنادق', 'تاشيرات', 'travel', 'tourism', 'tours', 'travel agency'],
            1 => ['سفر', 'طيران', 'فنادق', 'رحله', 'visa', 'hotel'],
        ],
        'برمجيات وستارت أب' => [
            3 => ['برمجيات', 'برمجه', 'سوفت وير', 'سوفتوير', 'تطبيقات موبايل', 'ستارت اب', 'شركه ناشئه', 'software', 'startup', 'saas', 'app development', 'web development'],
            1 => ['تطبيق', 'تطبيقات', 'منصه', 'تقنيه', 'مواقع', 'تكنولوجيا', 'tech', 'app'],
        ],
        'بورتفوليو ومستقلين' => [
            3 => ['فريلانسر', 'مستقل', 'بورتفوليو', 'سيره ذاتيه', 'portfolio', 'freelancer', 'freelance', 'resume'],
            1 => ['مصمم جرافيك', 'مترجم', 'كاتب محتوي', 'اعمالي', 'designer'],
        ],
        'أزياء وملابس' => [
            3 => ['ملابس', 'ازياء', 'فساتين', 'عبايات', 'بوتيك', 'محل هدوم', 'fashion', 'clothing', 'boutique', 'apparel'],
            1 => ['حجاب', 'احذيه', 'شنط', 'موضه', 'تيشرت', 'بناطيل', 'هدوم'],
        ],
        'مخابز وحلويات' => [
            3 => ['مخبز', 'مخابز', 'حلويات', 'حلواني', 'بسبوسه', 'جاتوه', 'تورته', 'بيتي فور', 'كحك', 'معجنات', 'bakery', 'pastry', 'patisserie', 'cake', 'sweets', 'desserts'],
            1 => ['كيك', 'عيش', 'خبز', 'شوكولاته', 'كنافه'],
        ],
        'حضانات وروضات أطفال' => [
            3 => ['حضانه', 'روضه', 'kindergarten', 'nursery', 'preschool', 'daycare', 'kids club'],
            1 => ['اطفال', 'الاطفال', 'تعليم مبكر', 'مونتيسوري', 'kids'],
        ],
        'تصوير وإنتاج فيديو' => [
            3 => ['تصوير', 'مصور', 'فوتوغرافي', 'استوديو تصوير', 'انتاج فيديو', 'مونتاج', 'موشن جرافيك', 'photography', 'photographer', 'video production', 'studio'],
            1 => ['فيديو', 'استوديو', 'سيشن', 'فوتو'],
        ],
        'عطور ومستحضرات تجميل' => [
            3 => ['عطور', 'برفان', 'بارفان', 'مستحضرات تجميل', 'سكين كير', 'perfume', 'fragrance', 'cosmetics', 'skincare'],
            1 => ['عطر', 'كريمات', 'ميك اب', 'بودي سبلاش', 'makeup'],
        ],
        'عيادات بيطرية وخدمات حيوانات أليفة' => [
            3 => ['بيطري', 'بيطريه', 'حيوانات اليفه', 'pet shop', 'vet', 'veterinary', 'pet'],
            1 => ['قطط', 'كلاب', 'حيوانات', 'grooming'],
        ],
    ];

    /**
     * أنسب فئة من الفئات الموجودة فعلاً، أو null لو الكلام مفيهوش أي دليل كفاية.
     *
     * @param  Collection<int, string>|array<int, string>  $available
     * @return array{category: string, score: int}|null
     */
    public static function guess(string $text, Collection|array $available, int $minScore = 3): ?array
    {
        [$words, $joined] = self::tokens($text);
        $scores = [];

        foreach (collect($available) as $category) {
            $score = 0;

            foreach (self::KEYWORDS[$category] ?? [] as $weight => $keywords) {
                foreach ($keywords as $keyword) {
                    $keyword = PastedText::normalizeArabic($keyword);
                    // كلمة واحدة لازم تطابق كلمة كاملة (عشان "سبا" متطلعش من "سباكه" و"حج" من
                    // "حجز")، والعبارة لازم تيجي كلمات كاملة متتالية.
                    $hit = str_contains($keyword, ' ')
                        ? str_contains($joined, ' '.$keyword.' ')
                        : isset($words[$keyword]);

                    if ($hit) {
                        $score += $weight;
                    }
                }
            }

            if ($score > 0) {
                $scores[$category] = $score;
            }
        }

        if ($scores === []) {
            return null;
        }

        arsort($scores);
        $category = array_key_first($scores);

        return $scores[$category] >= $minScore ? ['category' => $category, 'score' => $scores[$category]] : null;
    }

    /**
     * كلمات النص بعد التوحيد، كل كلمة بنسختها من غير "ال"/"و"/"ب"... في أولها.
     *
     * @return array{0: array<string, true>, 1: string}
     */
    private static function tokens(string $text): array
    {
        $normalized = PastedText::normalizeArabic($text);
        $raw = preg_split('/[^\p{L}\p{N}]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $words = [];
        $stripped = [];
        foreach ($raw as $token) {
            $words[$token] = true;
            $base = preg_replace('/^(وال|بال|فال|كال|لل|ال|و|ب|ف|ل)(?=\p{L}{3,})/u', '', $token) ?? $token;
            $words[$base] = true;
            $stripped[] = $base;
        }

        return [$words, ' '.implode(' ', $raw).' '.implode(' ', $stripped).' '];
    }

    /**
     * @return array<int, string>
     */
    public static function knownCategories(): array
    {
        return array_keys(self::KEYWORDS);
    }
}
