<?php

namespace App\Support;

/**
 * تنضيف رابط زرار من المحرر/الفورم قبل التخزين (2026-10-10). خانات الروابط كانت بتتخزن زي ما
 * هي بالحرف — ودي بتتحط في href على الموقع العام.
 *   - رقم تليفون لوحده (01xxxxxxxxx / +20... / 00966...) ← رابط واتساب wa.me (أشهر استخدام).
 *   - wa.me/... أو www.... أو دومين من غير http ← https:// قدامه.
 *   - http(s) / tel: / mailto: / #قسم / whatsapp: زي ما هم.
 *   - أي حاجة تانية (javascript:/data:/مسافات غريبة...) ← null = مرفوض.
 * نص فاضي = '' (مسح الزرار).
 */
final class LinkInput
{
    public static function normalize(?string $raw): ?string
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return '';
        }

        if (preg_match('/[\x00-\x1F\x7F<>"\'`\s]/u', $value)) {
            // مسافات جوّه رقم التليفون بس هي المقبولة ("010 1234 5678").
            if (! preg_match('/^\+?[\d\s\-().]+$/', $value)) {
                return null;
            }
        }

        if (preg_match('/^\+?[\d\s\-().]{7,}$/', $value)) {
            return self::whatsapp($value);
        }

        $lower = strtolower($value);

        foreach (['https://', 'http://', 'tel:', 'mailto:', 'whatsapp:'] as $scheme) {
            if (str_starts_with($lower, $scheme)) {
                return strlen($value) > strlen($scheme) ? $value : null;
            }
        }

        if (str_starts_with($value, '#')) {
            return preg_match('/^#[A-Za-z0-9_-]+$/', $value) ? $value : null;
        }

        // wa.me/2010... أو www.site.com أو site.com/page — من غير بروتوكول.
        if (preg_match('~^(?:[a-z0-9-]+\.)+[a-z]{2,}(?:[/?#]\S*)?$~i', $value)) {
            return 'https://'.$value;
        }

        return null;
    }

    /**
     * رقم ← https://wa.me/<كود الدولة + الرقم>. رقم مصري محلي (01xxxxxxxxx) بياخد 20 قدامه.
     */
    public static function whatsapp(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '01')) {
            $digits = '2'.$digits;
        }

        return strlen($digits) >= 8 && strlen($digits) <= 15 ? 'https://wa.me/'.$digits : null;
    }
}
