<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// طلب مش مدعوم — أي طلب من المستخدم للنموذج الذكي (مرحلة تانية) لم يقدر يصنّفه كـ"إنشاء
// تصميم جديد" أو "تعديل تصميم موجود" بيتسجّل هنا بدل ما يتم تجاهله أو تنفيذه بالغلط.
class UnsupportedRequest extends Model
{
    protected $fillable = [
        'generated_site_id',
        'request_type',
        'prompt_text',
        'ai_raw_response',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(GeneratedSite::class, 'generated_site_id');
    }

    public function markReviewed(): void
    {
        $this->update(['reviewed_at' => now()]);
    }
}
