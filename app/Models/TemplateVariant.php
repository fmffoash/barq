<?php

namespace App\Models;

use Database\Factories\TemplateVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// نسخة من نسخ القالب — بتحدد الألوان والأقسام المتضمّنة لمجموعة معيّنة من المشاريع اللي
// اختارت نفس القالب لكن شكل مختلف شوية (زي "الأساسية" مقابل "الموسّعة").
class TemplateVariant extends Model
{
    /** @use HasFactory<TemplateVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'template_id',
        'name',
        'slug',
        'colors_json',
        'sections_json',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'colors_json' => 'array',
            'sections_json' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
