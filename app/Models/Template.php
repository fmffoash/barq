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

    // التصميمات البصرية المتاحة للموقع المنشور — كل واحد منها Blade partial مستقل في
    // resources/views/site/layouts/، وكلهم بيشتغلوا بنفس بيانات الخانات (TemplateSlot) بالظبط،
    // فالتبديل بينهم متعمّد يكون رندر بس، صفر تأثير على الإدارة أو اقتراح المحتوى بالذكاء الاصطناعي.
    public const LAYOUTS = ['classic', 'modern', 'gallery'];

    protected $fillable = [
        'name',
        'slug',
        'category',
        'kind',
        'layout',
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

    // بنرتّب بـ sort_order أولاً، وبـ id كفاصل ثانوي — من غير الفاصل الثانوي ده، خانتين
    // من قسمين مختلفين بنفس sort_order بيرجعوا بترتيب غير مضمون من الداتابيز، وده كان بيبوّظ
    // ترتيب الأقسام نفسه (SiteRenderer بيحدد "أول قسم = هيرو" على أساس الترتيب ده بالظبط).
    public function slots(): HasMany
    {
        return $this->hasMany(TemplateSlot::class)->orderBy('sort_order')->orderBy('id');
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
