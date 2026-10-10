<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// طلب ذكاء اصطناعي واحد بين "ابدأ" و"طبّق" (شوف migration create_ai_runs_table و
// AiRunController). status: pending (مستني الرد) → applying (بيتطبّق دلوقتي) → done/failed/cancelled.
class AiRun extends Model
{
    use HasUlids;

    public const PENDING = 'pending';

    public const APPLYING = 'applying';

    public const DONE = 'done';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'kind',
        'project_id',
        'status',
        'task',
        'context_json',
        'body_json',
        'prompt_chars',
        'result_json',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'context_json' => 'array',
            'body_json' => 'array',
            'result_json' => 'array',
            'finished_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // النموذج اللي الطلب اتجهّز بيه فعلاً (ممكن يكون اتغيّر من الإعدادات وهو شغال).
    public function model(): string
    {
        return (string) ($this->body_json['model'] ?? '');
    }
}
