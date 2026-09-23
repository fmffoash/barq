<?php

namespace App\Models;

use Database\Factories\GeneratedSiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// الموقع الناتج فعلياً من مشروع معيّن — ده اللي middleware اكتشاف الموقع (DetectSite) بيدوّر
// عليه بالـ slug عشان يعرف يعرض المحتوى الصح على مسار المعاينة بتاعه (`/site/{slug}`).
class GeneratedSite extends Model
{
    /** @use HasFactory<GeneratedSiteFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'slug',
        'content_json',
        'style_overrides_json',
        'colors_override_json',
        'font_override',
        'font_weight_override',
        'font_style_override',
        'font_size_scale_override',
        'sections_override_json',
        'status',
        'exported_at',
        'last_generated_at',
        'wp_site_id',
        'wp_site_url',
        'wp_admin_url',
        'wp_provisioned_at',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'style_overrides_json' => 'array',
            'colors_override_json' => 'array',
            'sections_override_json' => 'array',
            'font_size_scale_override' => 'decimal:2',
            'exported_at' => 'datetime',
            'last_generated_at' => 'datetime',
            'wp_provisioned_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function unsupportedRequests(): HasMany
    {
        return $this->hasMany(UnsupportedRequest::class);
    }

    // قيمة خانة معيّنة من محتوى الموقع بمفتاحها (زي content('hero_title'))، أو قيمة افتراضية
    // لو الخانة دي لسه ما اتملتش.
    public function content(string $key, mixed $default = null): mixed
    {
        return data_get($this->content_json, $key, $default);
    }

    // تخصيص خانة واحدة بس (بدل الألوان/الخط العامة بتاعة الموقع كله، Phase 8) — بيرجع
    // ['color' => ?string, 'font' => ?string, 'zoom' => ?float, 'position' => ?string,
    // 'posX' => ?float, 'posY' => ?float, 'width' => ?float]، كل مفتاح null لو الخانة دي من
    // غير التخصيص ده. color/font لخانات text/textarea/list، zoom/position لخانات image بس
    // (المرحلة 2)، posX/posY/width لأي خانة (المرحلة 3 — ترتيب حر زي Canva/Wix، شوف
    // docs/rich-text-and-image-editing-plan.md) — نفس المفتاح style_overrides_json[slotKey]
    // بيحمل كل الأنواع مع بعض، مفيش تعارض لأن كل خانة بتستخدم اللي يخصها بس.
    public function styleFor(string $slotKey): array
    {
        $override = data_get($this->style_overrides_json, $slotKey, []);

        return [
            'color' => $override['color'] ?? null,
            'font' => $override['font'] ?? null,
            'zoom' => $override['zoom'] ?? null,
            'position' => $override['position'] ?? null,
            'posX' => $override['posX'] ?? null,
            'posY' => $override['posY'] ?? null,
            'width' => $override['width'] ?? null,
        ];
    }

    // اتعمل فعلاً site حقيقي على شبكة الـ WordPress ولا لسه (Phase 5). wp_site_id بيتملى
    // بس بعد ما WordPressService::provisionSite() ينجح.
    public function isWordPressProvisioned(): bool
    {
        return $this->wp_site_id !== null;
    }

    // الرابط الكامل لمعاينة الموقع ده. لو ده موقع ووردبريس اتعمل فعلاً على الشبكة، بنودّي
    // لرابطه الحقيقي هناك بدل مسار المعاينة بتاع برق (اللي عمره ما هيعرض ووردبريس فعلي).
    // مسار (`/site/{slug}`) تحت نفس دومين لوحة التحكم — مش سب دومين منفصل (2026-09-20).
    public function previewUrl(): string
    {
        if ($this->isWordPressProvisioned()) {
            return $this->wp_site_url;
        }

        return route('site.show', ['siteSlug' => $this->slug]);
    }
}
