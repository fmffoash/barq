<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// رسالة واحدة في محادثة "أنشئ/عدّل بالذكاء الاصطناعي" لمشروع معيّن (Phase 10) —
// role: 'user' (كلام الأدمن) أو 'assistant' (رد المساعد).
class AiChatMessage extends Model
{
    protected $fillable = ['project_id', 'role', 'content'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
