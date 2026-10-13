<?php

namespace App\Support;

use App\Services\SiteRenderer;
use Illuminate\Support\Collection;

// (2026-10-13) محتوى الموقع لتصميمات Claude Design (site/layouts/{رسمة}.blade.php المتحوّلة من HTML جاهز):
// كل تصميم ثابت الشكل وبيطلب الخانة بالاسم بدل ما يلف على الأقسام زي التصميمات القديمة.
final class DesignedPage
{
    /** @var array<string, array<string, mixed>> */
    private array $items = [];

    /** @var array<string, array<string, mixed>> قسم بمفتاحه — الأقسام الفاضية مش موجودة (SiteRenderer بيشيلها) */
    private array $sections = [];

    /**
     * @param  Collection<int, array<string, mixed>>  $sections  من SiteRenderer::render()
     * @param  array<string, string>|null  $contactAction
     */
    public function __construct(Collection $sections, private readonly ?array $contactAction = null)
    {
        foreach ($sections as $section) {
            $this->sections[$section['key']] = $section;
            foreach ($section['items'] as $item) {
                $this->items[$item['slot']->key] = $item;
            }
        }
    }

    // القسم فيه محتوى؟ (التصميم بيخفي القسم الفاضي زي التصميمات القديمة)
    public function has(string $sectionKey): bool
    {
        return isset($this->sections[$sectionKey]);
    }

    // تقييم جوجل من عنوان قسم الآراء («تقييمنا 4.7 ★ على جوجل…») — لـ site.partials.rating-badge.
    public function rating(): mixed
    {
        return $this->sections['testimonials']['rating'] ?? null;
    }

    /**
     * صور معرض متعبّية مش ليها مكان في شكل التصميم (التصميم فيه 3 مثلاً واليوزر رفع 6) — بتتعرض تحت المعرض.
     *
     * @param  list<string>  $used
     * @return array<string, string> مفتاح الخانة => الرابط
     */
    public function extraGallery(array $used): array
    {
        $extra = [];
        foreach (($this->sections['gallery']['items'] ?? collect()) as $item) {
            $key = $item['slot']->key;
            $value = trim((string) $item['value']);
            if ($item['slot']->slot_type === 'image' && $value !== '' && ! in_array($key, $used, true)) {
                $extra[$key] = $value;
            }
        }

        return $extra;
    }

    // نص الخانة (HTML متعقّم من SiteRenderer — نفس اللي التصميمات القديمة بتطبعه بـ{!! !!}).
    public function text(string $key): string
    {
        return (string) ($this->items[$key]['value'] ?? '');
    }

    // صورة بالمفتاح، ولو فاضية صورة تانية من صور الموقع بالدور (عشان خانة المعرض في التصميم ماتبانش فاضية).
    public function image(string $key): string
    {
        $value = trim((string) ($this->items[$key]['value'] ?? ''));
        if ($value !== '') {
            return $value;
        }

        $pool = collect(['gallery_image_1', 'gallery_image_2', 'gallery_image_3', 'gallery_image_4', 'gallery_image_5', 'gallery_image_6', 'hero_image'])
            ->map(fn (string $k) => trim((string) ($this->items[$k]['value'] ?? '')))
            ->filter()->values();
        if ($pool->isEmpty()) {
            return '';
        }
        $n = (int) preg_replace('/\D/', '', $key) ?: 1;

        return $pool[($n - 1) % $pool->count()];
    }

    /** @return list<array{title: string, desc: string|null}> */
    public function services(): array
    {
        return array_map(fn ($entry) => SiteRenderer::splitEntry($entry), array_values(array_filter((array) ($this->items['services_list']['value'] ?? []), fn ($e) => trim(strip_tags((string) $e)) !== '')));
    }

    /** @return list<array<string, mixed>> */
    public function testimonials(): array
    {
        return array_values($this->items['testimonials_list']['testimonials'] ?? []);
    }

    // رابط الزرار: الخانة نفسها لو متعبّية، وإلا رابط التواصل (واتساب/تليفون)، وإلا قسم التواصل.
    public function href(string $key): string
    {
        $value = trim((string) ($this->items[$key]['value'] ?? ''));

        return $value !== '' ? $value : ($this->contactAction['href'] ?? '#contact');
    }

    public function external(string $key): bool
    {
        return SiteRenderer::isExternal($this->href($key));
    }
}
