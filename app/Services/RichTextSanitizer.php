<?php

namespace App\Services;

use App\Models\TemplateVariant;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

// تطهير HTML الجزئي الجاي من تنسيق النص الحر (Bold/Italic/Underline/لون/تظليل/خط/حجم —
// docs/rich-text-and-image-editing-plan.md، المرحلة 1) قبل ما يتخزن في content_json. استثناء
// ضيّق ومتعمّد من قاعدة "صفر {!! !!}" في CLAUDE.md الجذر: القيمة المطهّرة هنا بس هي اللي
// بترندر بـ {!! !!} في الـ16 layout. DOMDocument بيمشي على شجرة الـHTML عقدة بعقدة ويعيد بناء
// output من الصفر (whitelist)، مش regex/strip_tags — أصعب بكتير على أي مدخل خبيث الالتفاف عليه.
class RichTextSanitizer
{
    private const ALLOWED_TAGS = ['b', 'strong', 'i', 'em', 'u', 'span', 'br'];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8"?><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $dom->getElementsByTagName('body')->item(0);

        return $body ? self::renderChildren($body) : '';
    }

    private static function renderChildren(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= self::renderNode($child);
        }

        return $out;
    }

    private static function renderNode(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->data, ENT_QUOTES, 'UTF-8');
        }

        if (! $node instanceof DOMElement) {
            // تعليقات/CDATA/إلخ — تتجاهل بالكامل، صفر معنى لها هنا.
            return '';
        }

        $tag = strtolower($node->tagName);

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            // Tag مش مسموح (script/img/a/iframe/svg/...) — نصه بس (escaped) بيفضل، مش الـtag
            // نفسه ولا أي attribute بتاعه. محتوى المستخدم النصي متتفقدش، الخطر بس اللي بيتشال.
            return self::renderChildren($node);
        }

        $inner = self::renderChildren($node);

        if ($tag === 'br') {
            return '<br>';
        }

        if ($tag === 'span') {
            $style = self::cleanSpanStyle((string) $node->getAttribute('style'));

            return $style !== ''
                ? '<span style="'.htmlspecialchars($style, ENT_QUOTES, 'UTF-8').'">'.$inner.'</span>'
                : '<span>'.$inner.'</span>';
        }

        // أي attribute تاني (class/id/on*/...) بيتشال بالكامل — إحنا بنعيد بناء الـtag من
        // الصفر من غير ما ننسخ أي attribute أصلاً، الوحيد اللي بيتعامل معاه صراحة هو style
        // على span وبس.
        return '<'.$tag.'>'.$inner.'</'.$tag.'>';
    }

    private static function cleanSpanStyle(string $style): string
    {
        $declarations = [];

        foreach (explode(';', $style) as $part) {
            $part = trim($part);
            if ($part === '' || ! str_contains($part, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $part, 2));
            $cleaned = self::cleanDeclaration(strtolower($property), $value);
            if ($cleaned !== null) {
                $declarations[] = $cleaned;
            }
        }

        return implode('; ', $declarations);
    }

    // نفس قيود CLAUDE.md/GeneratedSiteController لتخصيص لون/خط الخانة الكاملة — بالظبط نفس
    // regex الألوان (`/^#[0-9a-fA-F]{6}$/`) ونفس فحص TemplateVariant::FONTS. أي خاصية CSS
    // تانية (background-image/position/behavior/...) أو قيمة فيها url(/expression(/javascript:
    // بترفض بالكامل — مش تنضيف جزئي، رفض تام للقيمة كلها لو مش مطابقة تماماً.
    private static function cleanDeclaration(string $property, string $value): ?string
    {
        // ألوان جوّه heading معناه bold/italic بالفعل (Tailwind font-bold/italic على الـh1/h2
        // في أغلب الـ16 layout) — execCommand('foreColor'/'hiliteColor') بيطبّق تاني على
        // Selection متلوّنة بالفعل ساعات بيطلّع rgb(r, g, b) بدل الـhex الأصلي (لاحظنا ده
        // فعلياً وقت اختبار المتصفح الحقيقي)، فلازم نقبل الشكلين مش hex بس.
        if (in_array($property, ['color', 'background-color'], true)) {
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                return "{$property}: {$value}";
            }

            if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $value, $m)
                && (int) $m[1] <= 255 && (int) $m[2] <= 255 && (int) $m[3] <= 255) {
                return "{$property}: {$value}";
            }

            return null;
        }

        if ($property === 'font-family') {
            if (preg_match('/^var\(--font-([a-z0-9-]+)\)$/', $value, $m) && array_key_exists($m[1], TemplateVariant::FONTS)) {
                return "{$property}: {$value}";
            }

            return null;
        }

        if ($property === 'font-size') {
            if (preg_match('/^(\d+(?:\.\d+)?)px$/', $value, $m) && (float) $m[1] >= 10 && (float) $m[1] <= 72) {
                return "{$property}: {$value}";
            }

            if (preg_match('/^(\d+(?:\.\d+)?)rem$/', $value, $m) && (float) $m[1] >= 0.6 && (float) $m[1] <= 4) {
                return "{$property}: {$value}";
            }

            return null;
        }

        // font-weight/font-style/text-decoration: مش من الخانات الأساسية المطلوبة في الخطة،
        // بس execCommand بيولّدهم فعلياً لما المستخدم يعمل Bold/Italic/Underline على جزء من
        // نص هو أصلاً bold/italic بالكلاس (زي عنوان hero_title اللي عنده font-bold ثابت) —
        // المتصفح مش عنده طريقة "يشيل" bold موروث من كلاس غير إنه يحط override صريح بالـinline
        // style. القيم دي شكلية بحتة (صفر خطر أمان)، فمسموحة بقيمها المحدودة بس.
        if ($property === 'font-weight') {
            return in_array($value, ['normal', 'bold'], true) || preg_match('/^[1-9]00$/', $value)
                ? "{$property}: {$value}"
                : null;
        }

        if ($property === 'font-style') {
            return in_array($value, ['normal', 'italic'], true) ? "{$property}: {$value}" : null;
        }

        if ($property === 'text-decoration' || $property === 'text-decoration-line') {
            return in_array($value, ['none', 'underline'], true) ? "text-decoration: {$value}" : null;
        }

        return null;
    }
}
