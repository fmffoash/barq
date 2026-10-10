<?php

namespace App\Services;

use App\Models\Template;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * صور المشروع (2026-10-08) — فؤاد بيلزق كوبي جوجل مابس (فيه صور المكان) أو يسحب/يرفع صور
 * جاهزة مع رسالة الإنشاء. كل صورة بتتخزن في نفس مكان أي صورة مرفوعة (site-images على الـpublic
 * disk)، وبتتوزّع بالترتيب على خانات الصور (الغلاف الأول، وبعدين المعرض)، والباقي بيفضل في
 * "مخزن الصور" بتاع الموقع (generated_sites.photo_pool_json) يختار منه بعدين.
 *
 * تنزيل صور جوجل من السيرفر محصور جداً عمداً (مفيش أي رابط تاني ينفع): https بس، على سيرفرات
 * صور جوجل بس (googleusercontent/ggpht)، من غير تحويلات، صورة حقيقية (مش أي ملف)، 8 ميجا بالكتير.
 */
class PhotoPoolService
{
    public const MAX_PHOTOS = 12;

    private const MAX_BYTES = 8 * 1024 * 1024;

    private const ALLOWED_HOSTS = '/(^|\.)(googleusercontent\.com|ggpht\.com)$/i';

    private const TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];

    /**
     * @param  array<int, UploadedFile|null>  $files  (اتعمل لها validate قبل كده: image + mimes + max)
     * @return list<string> مسارات "/storage/site-images/..."
     */
    public function storeUploads(array $files): array
    {
        $paths = [];

        foreach (array_slice(array_filter($files), 0, self::MAX_PHOTOS) as $file) {
            $paths[] = '/storage/'.$file->store('site-images', 'public');
        }

        return $paths;
    }

    /**
     * @param  array<int, string|null>  $urls
     * @return list<string>
     */
    public function importUrls(array $urls): array
    {
        $paths = [];
        $seen = [];

        foreach ($urls as $url) {
            if (count($paths) >= self::MAX_PHOTOS) {
                break;
            }

            $url = is_string($url) ? $this->upgradeGoogleUrl(trim($url)) : null;
            if ($url === null || isset($seen[$url]) || ! self::isAllowedUrl($url)) {
                continue;
            }
            $seen[$url] = true;

            if ($path = $this->download($url)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public static function isAllowedUrl(string $url): bool
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? '') === 'https'
            && isset($parts['host'])
            && ! isset($parts['user']) && ! isset($parts['pass']) && ! isset($parts['port'])
            && preg_match(self::ALLOWED_HOSTS, $parts['host']) === 1;
    }

    // صور جوجل بتيجي مصغّرة في الكوبي (=w408-h306-k-no مثلاً) — نفس الصورة بمقاس أكبر بتغيير
    // آخر الرابط. أيقونات صغيرة (=s32, w24...) مالهاش لازمة في موقع.
    public function upgradeGoogleUrl(string $url): ?string
    {
        if ($url === '' || ! preg_match(self::ALLOWED_HOSTS, (string) parse_url($url, PHP_URL_HOST))) {
            return $url === '' ? null : $url;
        }

        if (preg_match('/=(?:s|w)(\d{1,3})(?:-|$)/', $url, $m) && (int) $m[1] < 120) {
            return null;
        }

        return preg_replace('/=[a-z0-9-]+$/i', '=w1600-h1200-k-no', $url) ?? $url;
    }

    private function download(string $url): ?string
    {
        try {
            $response = Http::timeout(20)->connectTimeout(5)
                ->withOptions(['allow_redirects' => false])
                ->get($url);
        } catch (Throwable $e) {
            Log::info('Photo import failed.', ['url' => $url, 'message' => $e->getMessage()]);

            return null;
        }

        $body = $response->body();

        if (! $response->successful() || $body === '' || strlen($body) > self::MAX_BYTES) {
            return null;
        }

        $info = @getimagesizefromstring($body);
        $extension = $info ? (self::TYPES[$info[2]] ?? null) : null;

        if (! $extension || $info[0] < 120 || $info[1] < 120) {
            return null;
        }

        $relative = 'site-images/'.Str::random(40).'.'.$extension;
        Storage::disk('public')->put($relative, $body);

        return '/storage/'.$relative;
    }

    /**
     * بيحط الصور بالترتيب في خانات الصور الفاضية في القالب (الغلاف الأول — أول خانة صورة في
     * الترتيب — وبعدين الباقي)، وبيرجّع المحتوى بعد التوزيع.
     *
     * @param  array<string, mixed>  $content
     * @param  list<string>  $photos
     * @return array<string, mixed>
     */
    public function assign(Template $template, array $content, array $photos): array
    {
        if ($photos === []) {
            return $content;
        }

        $template->loadMissing(['slots', 'variants']);

        // ترتيب خانات الصور بترتيب الأقسام في الصفحة (الهيرو الأول) مش بـsort_order لوحده —
        // sort_order بيتعدّ جوّه كل قسم، فصورة المعرض 1 (2) كانت هتسبق صورة الغلاف (4).
        $sections = array_flip(array_values($template->defaultVariant()?->sections_json ?? []));

        /** @var Collection<int, \App\Models\TemplateSlot> $imageSlots */
        $imageSlots = $template->slots->where('slot_type', 'image')
            ->sortBy(fn ($slot) => [$slot->section_key === 'hero' ? -1 : ($sections[$slot->section_key] ?? 99), $slot->sort_order, $slot->id])
            ->values();
        $queue = $photos;

        foreach ($imageSlots as $slot) {
            if ($queue === []) {
                break;
            }
            if (! empty($content[$slot->key])) {
                continue;
            }
            $content[$slot->key] = array_shift($queue);
        }

        return $content;
    }

    // صور اترفعت مع طلب اتلغى أو فشل قبل ما يتعمل مشروع — مالهاش مكان.
    public function discard(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && str_starts_with($path, '/storage/site-images/')) {
                Storage::disk('public')->delete(substr($path, strlen('/storage/')));
            }
        }
    }
}
