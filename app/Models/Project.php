<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

// المشروع — طلب عميل واحد لموقع. بيختار قالب ونسخة، وبمجرد ما يتملى بالبيانات بيتولّد منه
// موقع فعلي واحد (generated_sites) قابل للمعاينة على subdomain مستقل.
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'template_id',
        'template_variant_id',
        'name',
        'slug',
        'contact_name',
        'contact_phone',
        'contact_email',
        'place_json',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            // بيانات المكان من كوبي جوجل مابس (PlaceParser) — تليفون/عنوان/مواعيد/تقييم...
            'place_json' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(TemplateVariant::class, 'template_variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * (2026-10-11) المشاريع اللي اليوزر يشوفها: الأدمن الكل، وأي يوزر تاني مشاريعه هو بس (created_by).
     * كل رابط فيه {project} بيعدّي على ده مركزياً (AppServiceProvider)، ومشروع مش بتاعه = 404.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $user->is_admin ? $query : $query->where('created_by', $user->id);
    }

    public function site(): HasOne
    {
        return $this->hasOne(GeneratedSite::class);
    }

    public function aiChatMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AiChatMessage::class)->orderBy('created_at');
    }
}
