<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// (2026-10-19) مرة مشروع خد فيها قالب — from فاضي = أول قالب، غير كده تبديل من from لـ to.
// بيتسجّل تلقائي من Project::booted (أي مكان بيغيّر template_id: الشات، المحرر، الإنشاء).
class TemplateEvent extends Model
{
    protected $fillable = ['project_id', 'from_template_id', 'to_template_id', 'user_id'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
