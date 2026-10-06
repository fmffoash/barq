<?php

namespace App\Services;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;

// معاينة شكل القالب الحقيقي قبل اختياره (2026-10-06، فؤاد: "عايز شكل القالب نفسه بدل الصورة
// علشان أتفرج عليه وأعاينه قبل ما أختاره"). جزئين:
//
// 1) معاينة كاملة حيّة لقالب واحد (templates.preview) — نفس رندر الموقع الحقيقي (SiteRenderer)
//    بمحتوى القالب الافتراضي، عن طريق Project/GeneratedSite مؤقتين بالذاكرة (صفر كتابة داتابيز).
//    صفحة واحدة بتترندر وقت ما فؤاد يدوس "معاينة" — مش iframe لكل كارت (التجربة دي اتلغت
//    2026-09-21 لأنها كانت بتحمّل عشرات الصفحات مرة واحدة، وعلى ويندوز محلياً السيرفر بيخدم
//    طلب واحد في نفس الوقت، فكانت هتبقى أبطأ كمان).
// 2) صورة حقيقية لشكل كل قالب على الكارت — لقطة شاشة جاهزة من نفس المعاينة دي
//    (public/images/template-previews/{slug}.jpg، بيولّدها `php artisan barq:template-thumbnails`).
//    الصورة بتتعرض بس لو "بصمة" القالب الحالية (التصميم/الألوان/الخط/المحتوى الافتراضي)
//    مطابقة للبصمة اللي الصورة اتاخدت عليها — لو فؤاد عدّل ألوان القالب مثلاً، الكارت بيرجع
//    للعرض القديم بدل ما يعرض صورة مش مطابقة للحقيقة.
class TemplatePreviewService
{
    public function __construct(private readonly SiteRenderer $renderer) {}

    /**
     * @return array<string, mixed> نفس بيانات SiteRenderer::render() لموقع حقيقي.
     */
    public function render(Template $template): array
    {
        return $this->renderer->render($this->siteFor($template));
    }

    // مشروع وموقع مؤقتين بالذاكرة بس (مش متسجّلين) — SiteRenderer بيرجع لـdefault_value بتاع
    // كل خانة لوحده لما content_json فاضي، فمفيش داعي ننسخ المحتوى الافتراضي هنا.
    public function siteFor(Template $template): GeneratedSite
    {
        $template->loadMissing(['slots', 'variants']);

        $project = new Project(['name' => $template->name]);
        $project->setRelation('template', $template);
        $project->setRelation('variant', $template->defaultVariant());

        $site = new GeneratedSite(['content_json' => []]);
        $site->setRelation('project', $project);

        return $site;
    }

    // كل حاجة في الداتا بتأثر على شكل المعاينة. لو أي حاجة منها اتغيّرت بعد اللقطة، اللقطة
    // مبقتش تمثّل القالب.
    public function fingerprint(Template $template): string
    {
        $template->loadMissing(['slots', 'variants']);
        $variant = $template->defaultVariant();

        return sha1(json_encode([
            'kind' => $template->kind,
            'layout' => $template->layout,
            'name' => $template->name,
            'variant' => $variant ? [
                'colors' => $this->sortedKeys($variant->colors_json),
                'font' => $variant->font,
                'sections' => $this->sortedKeys($variant->sections_json),
            ] : null,
            'slots' => $template->slots
                ->sortBy(fn ($slot) => [$slot->sort_order, $slot->id])
                ->map(fn ($slot) => [$slot->section_key, $slot->key, $slot->slot_type, $slot->sort_order, $slot->default_value])
                ->values()
                ->all(),
        ], JSON_UNESCAPED_UNICODE));
    }

    // ترتيب مفاتيح الـJSON مش جزء من الشكل — عمود JSON في MySQL بيعيد ترتيبها، فداتا متنقلة من
    // السيرفر (barq:import-data) كانت هتطلع ببصمة مختلفة لنفس الألوان بالظبط. القوايم بتفضل بترتيبها.
    private function sortedKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($item) => $this->sortedKeys($item), $value);
    }

    // قابل للتغيير من الكونفيج عشان التستات تشتغل على فولدر مؤقت مش public الحقيقي.
    public function thumbnailDirectory(): string
    {
        return config('barq.template_previews_dir') ?: public_path('images/template-previews');
    }

    public function thumbnailPath(Template $template): string
    {
        return $this->thumbnailDirectory().'/'.$this->safeSlug($template).'.jpg';
    }

    // رابط الصورة لو موجودة ومطابقة لشكل القالب الحالي، وإلا null (الكارت بيرجع للعرض القديم).
    public function thumbnailUrl(Template $template): ?string
    {
        if ($template->kind !== 'landing') {
            return null;
        }

        $expected = $this->manifest()[$this->safeSlug($template)] ?? null;
        $path = $this->thumbnailPath($template);

        if ($expected === null || $expected !== $this->fingerprint($template) || ! is_file($path)) {
            return null;
        }

        return asset('images/template-previews/'.$this->safeSlug($template).'.jpg').'?v='.substr($expected, 0, 10);
    }

    /**
     * الملف بيتقري مرة واحدة بس في الطلب (صفحة القوالب بتسأل عليه 300 مرة).
     *
     * @var array<string, array<string, string>>
     */
    private static array $manifests = [];

    /**
     * @return array<string, string> slug => fingerprint
     */
    public function manifest(): array
    {
        $file = $this->thumbnailDirectory().'/manifest.json';

        if (! array_key_exists($file, self::$manifests)) {
            $decoded = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
            self::$manifests[$file] = is_array($decoded) ? $decoded : [];
        }

        return self::$manifests[$file];
    }

    /**
     * @param  array<string, string>  $manifest
     */
    public function writeManifest(array $manifest): void
    {
        ksort($manifest);
        $file = $this->thumbnailDirectory().'/manifest.json';
        file_put_contents($file, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        self::$manifests[$file] = $manifest;
    }

    // الـslug بيدخل في مسار ملف — أي حرف غير [a-z0-9-] بيتشال عشان مفيش مسار يخرج برّه الفولدر.
    public function safeSlug(Template $template): string
    {
        return preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $template->slug)) ?: 'template-'.$template->id;
    }
}
