<?php

namespace App\Models;

use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// القالب — تصميم موقع جاهز وثابت، ليه نسخ (ألوان/أقسام مختلفة) وخانات محتوى قابلة للتعبئة.
// المشروع الواحد بيختار قالب واحد ونسخة واحدة منه وقت إنشائه.
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'kind',
        'license_note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(TemplateVariant::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(TemplateSlot::class)->orderBy('sort_order');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    // النسخة الافتراضية اللي بتتحدد تلقائي وقت اختيار القالب في فورم "مشروع جديد".
    public function defaultVariant(): ?TemplateVariant
    {
        return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
    }
}
