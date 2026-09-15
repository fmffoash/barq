<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// خانة محتوى واحدة قابلة للتعبئة جوه قالب معيّن (عنوان، وصف، صورة...) — القيمة الفعلية
// بتتخزن في generated_sites.content_json تحت مفتاح يساوي عمود "key" هنا.
class TemplateSlot extends Model
{
    protected $fillable = [
        'template_id',
        'section_key',
        'key',
        'label_ar',
        'label_en',
        'slot_type',
        'is_required',
        'sort_order',
        'default_value',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'default_value' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    // التسمية المناسبة للعرض حسب اللغة الحالية للتطبيق (بيرجع الإنجليزي لو موجود ومطلوب،
    // وإلا العربي دايماً كخيار احتياطي).
    public function label(): string
    {
        if (app()->getLocale() === 'en' && filled($this->label_en)) {
            return $this->label_en;
        }

        return $this->label_ar;
    }
}
