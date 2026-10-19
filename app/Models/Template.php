<?php

namespace App\Models;

use App\Console\Commands\SeedTemplateLibrary;
use App\Support\SceneLibrary;
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
        'motion',
        'classic', 'modern', 'gallery',
        'split', 'magazine', 'bento', 'minimal', 'bold',
        'glass', 'timeline', 'stack', 'diagonal', 'framed',
        'neon', 'duotone', 'signature',
        // الدفعة التانية (2026-10-11) — 10 تصميمات أحدث، كل واحد بهيرو مختلف عن الـ16 اللي قبله.
        'aurora', 'bauhaus', 'organic', 'swiss', 'cinematic',
        'mashrabiya', 'showcase', 'story', 'retro', 'terminal',
        // الدفعة 3 (2026-10-11) — تصميمات 3D، كل واحد مجسمه على مقاس فئة (site/partials/depth).
        'brew', 'helix', 'gloss', 'forge', 'skyline',
        'parcel', 'scholar', 'verdict', 'blocks', 'torque',
        'festive', 'globe', 'cubic', 'canvas', 'atelier',
        'bakery', 'nursery', 'shutter', 'essence', 'pawprint',
        'dish', 'beanpot', 'pizza', 'skewer', 'juice', 'tooth', 'stetho', 'joint',
        'eye', 'teddy', 'capsule', 'tubes', 'drop', 'barber', 'polish', 'stones',
        'lipstick', 'yoga', 'boxing', 'mat', 'swim', 'villa', 'chalet', 'storefront',
        'key', 'dairy', 'spice', 'cart', 'phone', 'speech', 'laptop', 'crayons',
        'calc', 'chart', 'stamp', 'sofa', 'kitchen', 'paint', 'saw', 'tire',
        'battery', 'oil', 'wash', 'car', 'rings', 'partyhat', 'mic', 'spotlight',
        'jet', 'kaaba', 'beach', 'tent', 'apps', 'servers', 'padlock', 'neural',
        'pencils', 'brackets', 'quill', 'suit', 'heels', 'abaya', 'kidswear', 'bread',
        'kunafa', 'donut', 'icecream', 'rattle', 'abc', 'clapper', 'drone', 'polaroid',
        'incense', 'soap', 'cat', 'dog', 'bird', 'petbowl',
    ];

    protected $fillable = [
        'name',
        'slug',
        'category',
        'extra_categories',
        'kind',
        'layout',
        'motion_profile',
        'license_note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'extra_categories' => 'array',
            'motion_profile' => 'array',
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
    // تصميم 3D (site/partials/depth) = مفتاح رسمة في SceneLibrary.
    public static function isDepthLayout(?string $layout): bool
    {
        return $layout !== null && SceneLibrary::exists($layout);
    }

    // (2026-10-19) قالب 3D = تصميمات الرسومات (الـ98) أو «موشن 3D» (الـ98). TemplatePicker بيدّيهم
    // الأولوية لما الإعداد ده شغال (افتراضي شغال، بيتقفل من صفحة إحصائيات القوالب).
    public const PREFER_3D_SETTING = 'prefer_3d_templates';

    public static function is3dLayout(?string $layout): bool
    {
        return $layout === 'motion' || self::isDepthLayout($layout);
    }

    public static function prefers3d(): bool
    {
        return (bool) Setting::get(self::PREFER_3D_SETTING, true);
    }

    // حذف نهائي — مرفوض لو عليه مشاريع (هيكسر مواقعها). قالب مكتبة (عادي أو موشن) بيتسجّل إنه
    // اتمسح عشان أوامر تحديث المكتبة مايرجّعوش تاني.
    public function deletePermanently(): bool
    {
        if ($this->projects()->exists()) {
            return false;
        }

        if ($this->license_note === SeedTemplateLibrary::LICENSE_NOTE || $this->layout === 'motion') {
            Setting::put(SeedTemplateLibrary::DELETED_SETTING, array_values(array_unique([...SeedTemplateLibrary::deletedSlugs(), $this->slug])));
        }

        return (bool) $this->delete();
    }

    public static function layoutLabel(string $layout): string
    {
        return match ($layout) {
            'motion' => 'موشن 3D',
            'modern' => 'مودرن', 'gallery' => 'جاليري', 'split' => 'سبليت',
            'magazine' => 'مجلة', 'bento' => 'بينتو', 'minimal' => 'مينيمال',
            'bold' => 'بولد', 'glass' => 'جلاس', 'timeline' => 'تايم لاين',
            'stack' => 'ستاك', 'diagonal' => 'دياجونال', 'framed' => 'فريمد',
            'neon' => 'نيون', 'duotone' => 'دوتون', 'signature' => 'سيجنتشر',
            'aurora' => 'أورورا', 'bauhaus' => 'باوهاوس', 'organic' => 'أورجانيك',
            'swiss' => 'سويسري', 'cinematic' => 'سينمائي', 'mashrabiya' => 'مشربية',
            'showcase' => 'شوكيس', 'story' => 'حكاية', 'retro' => 'ريترو', 'terminal' => 'تيرمينال',
            'brew' => 'قهوة 3D', 'helix' => 'حلزون 3D', 'gloss' => 'لمعة 3D', 'forge' => 'حديد 3D', 'skyline' => 'أبراج 3D',
            'parcel' => 'طرد 3D', 'scholar' => 'كتاب 3D', 'verdict' => 'ميزان 3D', 'blocks' => 'طوب 3D', 'torque' => 'عجلة 3D',
            'festive' => 'احتفال 3D', 'globe' => 'كرة أرضية 3D', 'cubic' => 'مكعب 3D', 'canvas' => 'لوحات 3D', 'atelier' => 'شماعة 3D',
            'bakery' => 'تورتة 3D', 'nursery' => 'مكعبات لعب 3D', 'shutter' => 'كاميرا 3D', 'essence' => 'عطر 3D', 'pawprint' => 'أثر 3D',
            'dish' => 'طبق وشوكة 3D', 'beanpot' => 'قدرة فول 3D', 'pizza' => 'بيتزا 3D', 'skewer' => 'سيخ مشويات 3D',
            'juice' => 'كوباية عصير 3D', 'tooth' => 'سنة 3D', 'stetho' => 'قلب وسماعة 3D', 'joint' => 'عضمة ومفصل 3D',
            'eye' => 'عين 3D', 'teddy' => 'دبدوب 3D', 'capsule' => 'كبسولات دوا 3D', 'tubes' => 'أنابيب تحاليل 3D',
            'drop' => 'قطرة لامعة 3D', 'barber' => 'عمود الحلاق ومقص 3D', 'polish' => 'زجاجة مانيكير 3D', 'stones' => 'أحجار سبا وشمعة 3D',
            'lipstick' => 'روج 3D', 'yoga' => 'كورة يوجا ودمبل 3D', 'boxing' => 'جوانتي وكيس ملاكمة 3D', 'mat' => 'مات يوجا 3D',
            'swim' => 'عوامة وموج 3D', 'villa' => 'فيلا بحمام سباحة 3D', 'chalet' => 'شاليه ع البحر 3D', 'storefront' => 'محل تجاري 3D',
            'key' => 'مفتاح بيت 3D', 'dairy' => 'كرتونة لبن وجبنة 3D', 'spice' => 'برطمانات عطارة 3D', 'cart' => 'عربية تسوق 3D',
            'phone' => 'موبايل 3D', 'speech' => 'فقاعات كلام (لغات) 3D', 'laptop' => 'لابتوب 3D', 'crayons' => 'ألوان شمع 3D',
            'calc' => 'آلة حاسبة وعملات 3D', 'chart' => 'رسم بياني طالع 3D', 'stamp' => 'ورقة وختم 3D', 'sofa' => 'كنبة 3D',
            'kitchen' => 'مطبخ 3D', 'paint' => 'جردل ورولة دهان 3D', 'saw' => 'منشار وخشب 3D', 'tire' => 'كاوتش 3D',
            'battery' => 'بطارية 3D', 'oil' => 'جركن زيت 3D', 'wash' => 'إسفنجة وفقاعات 3D', 'car' => 'عربية 3D',
            'rings' => 'خاتمين 3D', 'partyhat' => 'طرطور وبلالين 3D', 'mic' => 'مايك ومنصة 3D', 'spotlight' => 'كشافات وسماعة 3D',
            'jet' => 'طيارة 3D', 'kaaba' => 'الكعبة 3D', 'beach' => 'شمسية وشاطئ 3D', 'tent' => 'خيمة ونار 3D',
            'apps' => 'موبايل وأيقونات 3D', 'servers' => 'سيرفرات وسحابة 3D', 'padlock' => 'قفل أمان 3D', 'neural' => 'شبكة ذكاء اصطناعي 3D',
            'pencils' => 'أقلام وباليتة ألوان 3D', 'brackets' => 'أقواس كود 3D', 'quill' => 'ريشة وحبر 3D', 'suit' => 'بدلة وكرافتة 3D',
            'heels' => 'كعب وشنطة 3D', 'abaya' => 'عباية 3D', 'kidswear' => 'تيشيرت أطفال 3D', 'bread' => 'عيش وفينو 3D',
            'kunafa' => 'صينية كنافة 3D', 'donut' => 'دونتس 3D', 'icecream' => 'آيس كريم 3D', 'rattle' => 'رضّاعة وخشخيشة 3D',
            'abc' => 'حروف وأرقام 3D', 'clapper' => 'كلاكيت تصوير 3D', 'drone' => 'درون 3D', 'polaroid' => 'صور مطبوعة 3D',
            'incense' => 'مبخرة ودخان 3D', 'soap' => 'صابون طبيعي 3D', 'cat' => 'قطة 3D', 'dog' => 'كلب 3D',
            'bird' => 'عصفورة 3D', 'petbowl' => 'طبق أكل حيوانات 3D',
            default => 'كلاسيك',
        };
    }

    // النسخة الافتراضية اللي بتتحدد تلقائي وقت اختيار القالب في فورم "مشروع جديد".
    public function defaultVariant(): ?TemplateVariant
    {
        return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
    }
}
