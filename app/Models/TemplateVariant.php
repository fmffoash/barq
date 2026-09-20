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

    // الخطوط المتاحة للموقع كله — كل مفتاح هنا لازم يقابله `--font-{key}` معرّف في
    // resources/css/app.css (@theme). نفس المفاتيح دي مستخدمة كمان في تخصيص خط خانة واحدة بس
    // (GeneratedSite::styleFor()، Phase 8). اتوسّعت لأكبر تشكيلة ممكنة (32 خط) في 2026-09-20 —
    // عربي (كلاسيكي/كوفي/خط يد/دائري/عصري) ولاتيني (سانس/سيريف/عريض)، كلها مستضافة محلياً
    // (@fontsource) بصفر اعتماد على CDN خارجي وقت التصفح.
    public const FONTS = [
        // عربي
        'cairo' => 'كايرو (افتراضي)',
        'tajawal' => 'تجوال',
        'almarai' => 'المراعي',
        'ibm-plex-arabic' => 'آي بي إم بلكس عربي',
        'noto-kufi-arabic' => 'نوتو كوفي عربي',
        'amiri' => 'أميري (كلاسيكي)',
        'lalezar' => 'لاليزار (عريض)',
        'rakkas' => 'رقّاص (زخرفي)',
        'aref-ruqaa' => 'عارف رقعة (خط يد)',
        'changa' => 'تشانجا',
        'el-messiri' => 'المسيري',
        'markazi-text' => 'مركزي تكست',
        'reem-kufi' => 'ريم كوفي',
        'jomhuria' => 'جمهورية (طويل وعريض)',
        'mada' => 'مدى',
        'harmattan' => 'هارماتان',
        'baloo-bhaijaan-2' => 'بالو بهيجان (دائري)',
        'lemonada' => 'ليمونادا (دائري)',
        'alexandria' => 'الإسكندرية (عصري)',
        'readex-pro' => 'ريدكس برو (عصري)',
        // لاتيني
        'poppins' => 'بوبينز (لاتيني)',
        'inter' => 'إنتر (لاتيني)',
        'roboto' => 'روبوتو (لاتيني)',
        'montserrat' => 'مونتسرات (لاتيني)',
        'lato' => 'لاتو (لاتيني)',
        'open-sans' => 'أوبن سانس (لاتيني)',
        'nunito' => 'نونيتو (لاتيني)',
        'raleway' => 'رالوي (لاتيني)',
        'playfair-display' => 'بلايفير ديسبلاي (لاتيني، كلاسيكي)',
        'merriweather' => 'ميريويذر (لاتيني، للقراءة)',
        'oswald' => 'أوزوالد (لاتيني، عريض)',
        'bebas-neue' => 'بيباس نيو (لاتيني، عريض)',
    ];

    protected $fillable = [
        'template_id',
        'name',
        'slug',
        'colors_json',
        'font',
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
