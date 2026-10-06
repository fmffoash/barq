<?php

namespace App\Support;

// تنضيف أي نص ملزوق قبل ما يتخزن أو يروح للذكاء الاصطناعي (2026-10-06). الكوبي من جوجل مابس
// بيجيب معاه رموز أيقونات خط جوجل (Private Use Area — كانت بتظهر لفؤاد مربعات في الشات)،
// وعلامات اتجاه مخفية حوالين الأرقام، ومسافات غريبة، وأرقام هندي.
class PastedText
{
    public static function clean(string $text): string
    {
        // أيقونات خط جوجل (U+E000–U+F8FF) + علامات الاتجاه المخفية + BOM + zero-width.
        $text = preg_replace('/[\x{E000}-\x{F8FF}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u', '', $text) ?? $text;
        // مسافات غير عادية (NBSP، المسافة الضيقة قبل AM/PM) ← مسافة عادية.
        $text = preg_replace('/[\x{00A0}\x{2007}\x{202F}\x{2009}\x{200A}]/u', ' ', $text) ?? $text;
        $text = self::asciiDigits($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $lines = array_map(fn ($line) => trim(preg_replace('/[ \t]+/u', ' ', $line) ?? $line), explode("\n", $text));

        // سطور فاضية متتالية ← سطر فاضي واحد.
        $text = preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)) ?? '';

        return trim($text);
    }

    public static function asciiDigits(string $text): string
    {
        return strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.', '٬' => ',',
        ]);
    }

    // للمقارنة بس (مش للعرض): من غير تشكيل/تطويل، والألف والتاء المربوطة والياء موحّدين.
    public static function normalizeArabic(string $text): string
    {
        $text = mb_strtolower(self::asciiDigits($text));
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text) ?? $text;

        return strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي']);
    }
}
