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
        // الدفعة التانية (2026-10-11) — 10 تصميمات أحدث، كل واحد بهيرو مختلف عن الـ16 اللي قبله.
        'aurora', 'bauhaus', 'organic', 'swiss', 'cinematic',
        'mashrabiya', 'showcase', 'story', 'retro', 'terminal',
        // الدفعة 3 (2026-10-11) — تصميمات 3D، كل واحد مجسمه على مقاس فئة (site/partials/depth).
        'brew', 'helix', 'gloss', 'forge', 'skyline',
    ];

    protected $fillable = [
        'name',
        'slug',
        'category',
        'extra_categories',
        'kind',
        'layout',
        'license_note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'extra_categories' => 'array',
        ];
    }

    // القالب بيظهر في فئته الأساسية + أي فئة إضافية ينفع لها (2026-10-11).
    public function scopeInCategory($query, ?string $category)
    {
        return $query->where(fn ($q) => $q->where('category', $category)->orWhereJsonContains('extra_categories', $category));
    }

    public function belongsToCategory(?string $category): bool
    {
        return $category !== null && ($this->category === $category || in_array($category, $this->extra_categories ?? [], true));
    }

    /** @return list<string> */
    public function allCategories(): array
    {
        return array_values(array_unique(array_filter([$this->category, ...($this->extra_categories ?? [])])));
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
            'aurora' => 'أورورا', 'bauhaus' => 'باوهاوس', 'organic' => 'أورجانيك',
            'swiss' => 'سويسري', 'cinematic' => 'سينمائي', 'mashrabiya' => 'مشربية',
            'showcase' => 'شوكيس', 'story' => 'حكاية', 'retro' => 'ريترو', 'terminal' => 'تيرمينال',
            'brew' => 'قهوة 3D', 'helix' => 'حلزون 3D', 'gloss' => 'لمعة 3D', 'forge' => 'حديد 3D', 'skyline' => 'أبراج 3D',
            default => 'كلاسيك',
        };
    }

    // النسخة الافتراضية اللي بتتحدد تلقائي وقت اختيار القالب في فورم "مشروع جديد".
    public function defaultVariant(): ?TemplateVariant
    {
        return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
    }
}
