<?php

namespace App\Models;

use Database\Factories\GeneratedSiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// الموقع الناتج فعلياً من مشروع معيّن — ده اللي middleware اكتشاف الموقع (DetectSite) بيدوّر
// عليه بالـ slug عشان يعرف يعرض المحتوى الصح على subdomain المعاينة بتاعه.
class GeneratedSite extends Model
{
    /** @use HasFactory<GeneratedSiteFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'slug',
        'content_json',
        'status',
        'exported_at',
        'last_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'exported_at' => 'datetime',
            'last_generated_at' => 'datetime',
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

    // الرابط الكامل لمعاينة الموقع ده على الدومين الفرعي الخاص بيه.
    public function previewUrl(): string
    {
        $scheme = request()?->isSecure() ? 'https' : (app()->environment('production') ? 'https' : 'http');
        $baseDomain = config('barq.base_domain');

        return "{$scheme}://{$this->slug}.{$baseDomain}";
    }
}
