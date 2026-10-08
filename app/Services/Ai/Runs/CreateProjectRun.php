<?php

namespace App\Services\Ai\Runs;

use App\Models\AiRun;
use App\Services\Ai\AiResult;
use App\Services\AiProjectAssistantService;
use Illuminate\Http\Request;

// "أنشئ مشروع بالذكاء الاصطناعي" (صفحة /ai) — نفس AiChatController::store() بس بالعدّاد.
class CreateProjectRun implements RunHandler
{
    public function __construct(private readonly AiProjectAssistantService $assistant) {}

    public function prepare(Request $request): array
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:30000'],
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font' => ['nullable', 'string'],
        ]);

        $prepared = $this->assistant->prepareCreate(
            $data['message'],
            $data['template_id'] ?? null,
            $data['color'] ?? null,
            $data['font'] ?? null,
        );

        return $prepared + ['redirect' => null];
    }

    public function complete(AiRun $run, AiResult $result): array
    {
        $outcome = $this->assistant->completeCreate($run->context_json ?? [], $result);

        return [
            'ok' => $outcome['ok'],
            'reply' => $outcome['reply'],
            'redirect' => $outcome['ok'] ? route('ai-chat.show', $outcome['project']) : null,
        ];
    }
}
