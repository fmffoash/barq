<?php

namespace App\Support;

/**
 * بيانات المكان من كوبي جوجل مابس (2026-10-08) — من غير ذكاء اصطناعي: الاسم، التقييم، نوع
 * النشاط على جوجل، التليفون، العنوان، المواعيد، الموقع الإلكتروني، لينك الخريطة، Plus Code.
 *
 * ليه مش الذكاء الاصطناعي؟ النموذج الصغير على الجهاز ممكن يغلط رقم في التليفون أو يقلب
 * المواعيد، والقواعد دي بتطلّعهم بالحرف زي ما هما — وبيتحطوا في الموقع مباشرة (زرار التواصل،
 * ملاحظة المواعيد/العنوان، سطر التقييم) وبيتبعتوا للنموذج كبيانات مؤكدة يكتب حواليها.
 *
 * النص لازم يكون اتنضّف بـPastedText::clean الأول (الأيقونات والأرقام الهندي).
 *
 * @phpstan-type Place array{name?: string, rating?: float, reviews?: int, kind?: string, phones?: list<string>, address?: string, hours?: list<string>, website?: string, maps_url?: string, plus_code?: string}
 */
class PlaceParser
{
    // كلام واجهة جوجل مابس (عربي + إنجليزي) — عمره ما يبقى اسم أو نوع نشاط أو عنوان.
    private const UI_WORDS = [
        'نظرة عامة', 'آراء', 'اراء', 'مراجعات', 'المراجعات', 'لمحة', 'حول', 'لمحة عن', 'الاتجاهات', 'حفظ',
        'الأماكن المجاورة', 'الاماكن المجاوره', 'إرسال إلى هاتفك', 'ارسال الي هاتفك', 'مشاركة', 'مشاركه',
        'المطالبة بهذا النشاط التجاري', 'اقتراح تعديل', 'إضافة صور', 'إضافة صورة', 'كتابة مراجعة',
        'الصور', 'الصور والفيديوهات', 'القائمة', 'حجز', 'حجز موعد', 'الموقع الإلكتروني', 'اتصال', 'مرشد محلي',
        'Overview', 'Reviews', 'About', 'Directions', 'Save', 'Nearby', 'Send to phone', 'Share',
        'Claim this business', 'Suggest an edit', 'Add photos', 'Write a review', 'Photos', 'Menu',
        'Website', 'Call', 'Book online', 'Local Guide', 'Updates', 'Q&A',
    ];

    private const HOURS_WORDS = '/(مفتوح|مغلق|يفتح|يغلق|يُغلق|يُفتح|على مدار ٢٤|على مدار 24|24 ساعة|٢٤ ساعة|\bopen\b|\bclosed\b|\bcloses\b|\bopens\b|open 24 hours)/iu';

    private const DAYS = '/^(السبت|الأحد|الاحد|الإثنين|الاثنين|الثلاثاء|الأربعاء|الاربعاء|الخميس|الجمعة|الجمعه|Saturday|Sunday|Monday|Tuesday|Wednesday|Thursday|Friday)\b/iu';

    private const ADDRESS_HINTS = '/(شارع|ش\.|طريق|ميدان|محافظة|محافظه|مدينة|حي |الحي|عمارة|برج|مول|كمبوند|المنطقة|Governorate|\bSt\b|Street|\bRd\b|Road|Square|\bAve\b|Avenue|Building|Tower|Mall|Compound|District)/iu';

    /**
     * @return array<string, mixed>
     */
    public static function parse(string $text): array
    {
        $place = [];
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($l) => $l !== ''));

        if ($lines === []) {
            return [];
        }

        if ($url = self::firstMatch('#https?://(?:maps\.app\.goo\.gl|goo\.gl/maps|(?:www\.)?google\.[a-z.]+/maps)\S*#i', $text)) {
            $place['maps_url'] = rtrim($url, '.,)');
        }

        // التقييم: "4.8" في سطر لوحده وبعده "(127)"، أو "4.8(127)"، أو "4.8 ★★★★★ 127 مراجعة".
        foreach (array_slice($lines, 0, 12) as $i => $line) {
            if (preg_match('/^([1-5][.,]\d)\s*(?:★+|☆+)?\s*(?:\(([\d,.]+)\))?/u', $line, $m)) {
                $place['rating'] = (float) str_replace(',', '.', $m[1]);
                $count = $m[2] ?? null;
                if ($count === null && isset($lines[$i + 1]) && preg_match('/^\(?([\d,.]+)\)?(?:\s*(?:مراجعة|مراجعات|تقييم|تقييمات|reviews?))?$/iu', $lines[$i + 1], $c)) {
                    $count = $c[1];
                }
                if ($count !== null) {
                    $place['reviews'] = (int) preg_replace('/\D/', '', $count);
                }
                break;
            }
        }

        // الاسم بس لو الكلام شكله فعلاً كوبي من جوجل مابس (تقييم، أو كلام الواجهة) — في رسالة عادية
        // زي "عيادة أسنان في المنصورة اسمها..." أول سطر هو الوصف كله مش الاسم.
        $uiHits = count(array_filter($lines, fn ($line) => self::isNoise($line) && mb_strlen(trim($line)) >= 2));
        $mapsLike = isset($place['rating']) || isset($place['maps_url']) || $uiHits >= 2;

        foreach ($mapsLike ? $lines : [] as $line) {
            if (self::isNoise($line) || preg_match('#^https?://#i', $line) || preg_match('/^[\d\s().,★☆+-]+$/u', $line)) {
                continue;
            }
            $place['name'] = mb_substr($line, 0, 80);
            break;
        }

        // نوع النشاط على جوجل: السطر اللي بعد التقييم وعدد المراجعات (قصير ومش كلام واجهة).
        if (isset($place['rating'])) {
            $start = null;
            foreach ($lines as $i => $line) {
                if (preg_match('/^[1-5][.,]\d/u', $line)) {
                    $start = $i + 1;
                    break;
                }
            }
            for ($i = $start; $start !== null && $i < min(count($lines), $start + 3); $i++) {
                $line = trim($lines[$i], " \t·⋅");
                if (preg_match('/^\(?[\d,.]+\)?$/u', $line) || self::isNoise($line)) {
                    continue;
                }
                // "طبيب أسنان · $$" أو "Dentist" — بناخد أول جزء بس.
                $kind = trim(preg_split('/\s*[·⋅]\s*/u', $line)[0]);
                if ($kind !== '' && mb_strlen($kind) <= 40 && ! preg_match('/\d{3,}/', $kind)) {
                    $place['kind'] = $kind;
                }
                break;
            }
        }

        $phones = self::phones($text);
        if ($phones !== []) {
            $place['phones'] = $phones;
        }

        foreach ($lines as $line) {
            if (self::isNoise($line) || self::phones($line) !== [] || preg_match(self::HOURS_WORDS, $line)) {
                continue;
            }
            $postalWithCommas = preg_match('/\b\d{5}\b/', $line) && (str_contains($line, '،') || str_contains($line, ','));
            if (preg_match(self::ADDRESS_HINTS, $line) || $postalWithCommas) {
                $place['address'] = mb_substr($line, 0, 160);
                break;
            }
        }

        $hours = [];
        foreach ($lines as $line) {
            if (mb_strlen($line) > 80) {
                continue;
            }
            if (preg_match(self::DAYS, $line) || preg_match(self::HOURS_WORDS, $line)) {
                $hours[] = preg_replace('/\s*[·⋅]\s*/u', ' — ', $line);
            }
            if (count($hours) >= 8) {
                break;
            }
        }
        if ($hours !== []) {
            $place['hours'] = array_values(array_unique($hours));
        }

        if ($code = self::firstMatch('/\b[23456789CFGHJMPQRVWX]{4}\+[23456789CFGHJMPQRVWX]{2,3}\b/', $text)) {
            $place['plus_code'] = $code;
        }

        foreach ($lines as $line) {
            if (preg_match('#^(?:https?://)?(?:www\.)?([a-z0-9-]+(?:\.[a-z0-9-]+)*\.(?:com|net|org|eg|sa|ae|info|biz|co|io|me|store|shop|online|site|clinic|com\.eg|net\.eg))(?:/\S*)?$#i', $line, $m)
                && ! preg_match('/(google|goo\.gl|facebook\.com\/sharer)/i', $line)) {
                $place['website'] = preg_match('#^https?://#i', $line) ? $line : 'https://'.$line;
                break;
            }
        }

        return $place;
    }

    /**
     * أرقام التليفون بالترتيب اللي ظهرت بيه، بعد التوحيد (أرقام إنجليزي من غير مسافات).
     *
     * @return list<string>
     */
    public static function phones(string $text): array
    {
        $text = PastedText::asciiDigits($text);
        $found = [];

        // دولي (+966 / +20 / ...) — أو موبايل مصري 01x، أو أرضي مصري 0x.
        preg_match_all('/(?<![\d+])(?:\+\d{1,3}[\s-]?\d[\d\s-]{6,13}\d|01[0125][\s-]?\d{3,4}[\s-]?\d{4}|0\d{1,2}[\s-]?\d{3,4}[\s-]?\d{4})(?!\d)/u', $text, $matches);

        // خط ساخن (16xxx/19xxx) بس لو في سطر لوحده أو جنب كلمة "الخط الساخن" — غير كده ممكن يبقى سعر.
        preg_match_all('/(?:^|الخط الساخن|خط ساخن|hotline)[\s:]*(1[69]\d{3})\s*$/imu', $text, $hotlines);
        $raws = array_merge($matches[0], $hotlines[1]);

        foreach ($raws as $raw) {
            $digits = preg_replace('/[^\d+]/', '', $raw);
            $plain = ltrim($digits, '+');

            // +20 1x... → 01x... عشان يبقى زي ما الناس بتكتبه في مصر.
            if (str_starts_with($digits, '+20') || (str_starts_with($plain, '20') && strlen($plain) === 12)) {
                $digits = '0'.substr($plain, 2);
            }

            $length = strlen(ltrim($digits, '+'));
            if (($length >= 9 && $length <= 15) || preg_match('/^1[69]\d{3}$/', $digits)) {
                $found[$digits] = true;
            }
        }

        // array_keys بيحوّل "19019" لرقم — لازم يرجع نص زي باقي الأرقام.
        return array_map('strval', array_keys($found));
    }

    /**
     * زرار التواصل: واتساب لموبايل مصري، اتصال لأي رقم تاني، والموقع الإلكتروني لو مفيش رقم.
     *
     * @param  array<string, mixed>  $place
     */
    public static function contactLink(array $place): ?string
    {
        foreach ($place['phones'] ?? [] as $phone) {
            if (preg_match('/^01[0125]\d{8}$/', $phone)) {
                return 'https://wa.me/20'.substr($phone, 1);
            }
        }

        if (! empty($place['phones'])) {
            return 'tel:'.$place['phones'][0];
        }

        return $place['website'] ?? null;
    }

    /**
     * سطر "ملاحظة التواصل": المواعيد + العنوان زي ما هما في جوجل.
     *
     * @param  array<string, mixed>  $place
     */
    public static function contactNote(array $place): ?string
    {
        $parts = [];

        if (! empty($place['hours'])) {
            $parts[] = count($place['hours']) === 1 ? $place['hours'][0] : implode('، ', array_slice($place['hours'], 0, 7));
        }
        if (! empty($place['address'])) {
            $parts[] = $place['address'];
        }

        return $parts === [] ? null : implode(' — ', $parts);
    }

    /**
     * @param  array<string, mixed>  $place
     */
    public static function ratingLine(array $place): ?string
    {
        if (empty($place['rating'])) {
            return null;
        }

        $rating = rtrim(rtrim(number_format((float) $place['rating'], 1, '.', ''), '0'), '.');

        return ! empty($place['reviews'])
            ? "تقييمنا {$rating} ★ على جوجل من {$place['reviews']} تقييم"
            : "تقييمنا {$rating} ★ على جوجل";
    }

    /**
     * البيانات دي كسطور جاهزة للبرومبت — "مؤكدة" عشان النموذج يستخدمها ومايخترعش غيرها.
     *
     * @param  array<string, mixed>  $place
     */
    public static function promptBlock(array $place): string
    {
        $lines = [];
        $labels = ['name' => 'الاسم', 'kind' => 'نوع النشاط على جوجل', 'address' => 'العنوان', 'website' => 'الموقع'];

        foreach ($labels as $key => $label) {
            if (! empty($place[$key])) {
                $lines[] = "- {$label}: {$place[$key]}";
            }
        }
        if (! empty($place['phones'])) {
            $lines[] = '- التليفون: '.implode(' / ', $place['phones']);
        }
        if (! empty($place['hours'])) {
            $lines[] = '- المواعيد: '.implode('، ', $place['hours']);
        }
        if ($rating = self::ratingLine($place)) {
            $lines[] = '- '.$rating;
        }

        return $lines === [] ? '' : "بيانات مؤكدة اتعرفت من الكلام:\n".implode("\n", $lines);
    }

    private static function isNoise(string $line): bool
    {
        $line = trim($line, " \t·⋅•");

        if ($line === '' || mb_strlen($line) < 2) {
            return true;
        }

        foreach (self::UI_WORDS as $word) {
            if (mb_strtolower($line) === mb_strtolower($word)) {
                return true;
            }
        }

        return false;
    }

    private static function firstMatch(string $pattern, string $text): ?string
    {
        return preg_match($pattern, $text, $m) ? $m[0] : null;
    }
}
