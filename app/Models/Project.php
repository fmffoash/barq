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
        'status',
        'created_by',
    ];

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

    public function site(): HasOne
    {
        return $this->hasOne(GeneratedSite::class);
    }
}
