<?php

namespace App\Support;

// (2026-10-19) النصوص الثابتة اللي حوالين الهيرو في تصميم «موشن 3D» (layouts/motion): الملصق
// الدايري، وسطر آخر الهيرو، وجملة الترحيب، وزرار «اكتشف». كانت جملة عامة واحدة على الـ98 قالب
// («تفاصيل صغيرة إحساس مختلف» + «MADE FOR THE MOMENT» بالإنجليزي) — دلوقتي جملة لكل نوع نشاط
// حسب motion_profile.theme. زخرفة بس (مش خانات)، فمحتوى اليوزر نفسه في الخانات مابيتأثرش.
class MotionCopy
{
    private const THEMES = [
        'restaurants' => ['sticker' => ['طازة', 'كل يوم'], 'tagline' => 'طعم يفضل في البال', 'note' => 'أهلاً بيك في :name. المكان مستنيك والأكل سخن.', 'explore' => 'شوف المنيو'],
        'clinics' => ['sticker' => ['صحتك', 'أولويتنا'], 'tagline' => 'رعاية بضمير وخبرة', 'note' => 'في :name بنسمعك الأول، وبعدين نطمّنك.', 'explore' => 'خدماتنا الطبية'],
        'salons' => ['sticker' => ['اهتمي', 'بنفسك'], 'tagline' => 'لمسة جمال تفرق', 'note' => 'في :name كل زيارة لحظة دلع ليكي.', 'explore' => 'شوفي الخدمات'],
        'gyms' => ['sticker' => ['ابدأ', 'النهارده'], 'tagline' => 'كل تمرينة خطوة لقدّام', 'note' => 'في :name هنمشي معاك خطوة بخطوة لحد هدفك.', 'explore' => 'الاشتراكات والبرامج'],
        'real-estate' => ['sticker' => ['بيتك', 'مستنيك'], 'tagline' => 'مكانك الجاي يبدأ من هنا', 'note' => 'في :name بنساعدك تختار صح، من غير قلق.', 'explore' => 'شوف الوحدات'],
        'ecommerce' => ['sticker' => ['توصيل', 'لحد بابك'], 'tagline' => 'اختار براحتك واطلب بسهولة', 'note' => 'أهلاً بيك في :name. كل اللي محتاجه في مكان واحد.', 'explore' => 'تصفّح المنتجات'],
        'education' => ['sticker' => ['اتعلّم', 'صح'], 'tagline' => 'معرفة بتفتح أبواب', 'note' => 'في :name بنعلّم بطريقة تفضل معاك.', 'explore' => 'الكورسات'],
        'legal' => ['sticker' => ['ثقة', 'وخبرة'], 'tagline' => 'حقك في إيد أمينة', 'note' => 'في :name بنسمعك بسرية تامة وبنرد بوضوح.', 'explore' => 'مجالات عملنا'],
        'contracting' => ['sticker' => ['تنفيذ', 'بالمواعيد'], 'tagline' => 'شغل متين يعيش سنين', 'note' => 'في :name بنبني على أساس صح من أول يوم.', 'explore' => 'شوف أعمالنا'],
        'auto-service' => ['sticker' => ['عربيتك', 'في أمان'], 'tagline' => 'صيانة بضمير على الطريق', 'note' => 'في :name عربيتك بتتعامل كأنها عربيتنا.', 'explore' => 'خدمات الصيانة'],
        'events' => ['sticker' => ['ليلة', 'متتنسيش'], 'tagline' => 'كل مناسبة ليها حكاية', 'note' => 'في :name بنهتم بالتفاصيل وإنت تفرح.', 'explore' => 'باقات المناسبات'],
        'travel' => ['sticker' => ['جهّز', 'شنطتك'], 'tagline' => 'الرحلة تبدأ من هنا', 'note' => 'مع :name رحلتك متظبطة من الحجز للرجوع.', 'explore' => 'شوف الرحلات'],
        'technology' => ['sticker' => ['فكرتك', 'أونلاين'], 'tagline' => 'تقنية بتسهّل شغلك', 'note' => 'في :name بنحوّل فكرتك لحاجة شغالة فعلاً.', 'explore' => 'حلولنا'],
        'portfolio' => ['sticker' => ['شغل', 'بحب'], 'tagline' => 'كل مشروع حكاية', 'note' => 'أهلاً بيك. خد جولة في شغلي واحكيلي فكرتك.', 'explore' => 'شوف الأعمال'],
        'fashion' => ['sticker' => ['ستايلك', 'يفرق'], 'tagline' => 'إطلالة تشبهك', 'note' => 'في :name هتلاقي اللي يليق عليك.', 'explore' => 'شوف الكوليكشن'],
        'bakery' => ['sticker' => ['من الفرن', 'لإيدك'], 'tagline' => 'حلاوة كل يوم', 'note' => 'في :name بنخبز كل يوم بنفس الحب.', 'explore' => 'شوف الأصناف'],
        'nurseries' => ['sticker' => ['أمان', 'ولعب'], 'tagline' => 'بيت تاني لطفلك', 'note' => 'في :name طفلك في إيد أمينة وبيتعلم وهو مبسوط.', 'explore' => 'برنامج اليوم'],
        'photography' => ['sticker' => ['لحظتك', 'تعيش'], 'tagline' => 'كل كادر ذكرى', 'note' => 'في :name بنصوّر اللحظة زي ما حسيتها.', 'explore' => 'شوف أعمالنا'],
        'perfumes' => ['sticker' => ['ريحة', 'تفضل'], 'tagline' => 'أثرك يفضل بعد ما تمشي', 'note' => 'في :name هتلاقي الريحة اللي تشبهك.', 'explore' => 'شوف المنتجات'],
        'veterinary' => ['sticker' => ['صاحبك', 'في أمان'], 'tagline' => 'رعاية لأقرب صاحب', 'note' => 'في :name بنهتم بحيوانك الأليف كأنه من العيلة.', 'explore' => 'خدماتنا'],
    ];

    private const FALLBACK = ['sticker' => ['تفاصيل', 'بتفرق'], 'tagline' => 'معمول عشانك', 'note' => 'أهلاً بيك في :name. خد وقتك واكتشفنا.', 'explore' => 'اكتشف التفاصيل'];

    /** @return array{sticker: array{0: string, 1: string}, tagline: string, note: string, explore: string} */
    public static function for(?string $theme, string $name): array
    {
        $copy = self::THEMES[$theme ?? ''] ?? self::FALLBACK;
        $copy['note'] = str_replace(':name', $name, $copy['note']);

        return $copy;
    }

    public static function themes(): array
    {
        return array_keys(self::THEMES);
    }
}
