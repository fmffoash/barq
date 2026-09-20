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
    public const LAYOUTS = [
        'classic', 'modern', 'gallery',
        'split', 'magazine', 'bento', 'minimal', 'bold',
        'glass', 'timeline', 'stack', 'diagonal', 'framed',
        'neon', 'duotone', 'signature',
    ];

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

    // الاسم العربي المعروض لتصميم بصري معيّن — مستخدم في صفحات الإدارة (index/show) عشان
    // مايتكررش نفس الـ match في أكتر من فيو.
    public static function layoutLabel(string $layout): string
    {
        return match ($layout) {
            'modern' => 'مودرن', 'gallery' => 'جاليري', 'split' => 'سبليت',
            'magazine' => 'مجلة', 'bento' => 'بينتو', 'minimal' => 'مينيمال',
            'bold' => 'بولد', 'glass' => 'جلاس', 'timeline' => 'تايم لاين',
            'stack' => 'ستاك', 'diagonal' => 'دياجونال', 'framed' => 'فريمد',
            'neon' => 'نيون', 'duotone' => 'دوتون', 'signature' => 'سيجنتشر',
            default => 'كلاسيك',
        };
    }

    // النسخة الافتراضية اللي بتتحدد تلقائي وقت اختيار القالب في فورم "مشروع جديد".
    public function defaultVariant(): ?TemplateVariant
    {
        return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
    }
}
