<?php

namespace App\Services\Ai\Runs;

use App\Models\AiRun;
use App\Models\Project;
use App\Services\Ai\AiResult;
use App\Services\AiProjectAssistantService;
use Illuminate\Http\Request;

// رسالة على مشروع موجود (شات التعديل) — نفس AiChatController::message() بس بالعدّاد.
// redirect = null: المتصفح بيعيد تحميل نفس الصفحة اللي فؤاد بعت منها (صفحة المشروع أو الشات).
class FollowUpRun implements RunHandler
{
    public function __construct(private readonly AiProjectAssistantService $assistant) {}

    public function prepare(Request $request): array
    {
        // نفس قيود AiChatController::message() بالحرف (نوع/حجم الصورة، svg مستبعد عمداً).
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'message' => ['required', 'string', 'max:30000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:8192'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $prepared = $this->assistant->prepareFollowUp($project, $data['message'], $request->file('image'));

        return $prepared + ['project_id' => $project->id, 'redirect' => null];
    }

    public function complete(AiRun $run, AiResult $result): array
    {
        $project = $run->project()->firstOrFail();
        $reply = $this->assistant->completeFollowUp($project, $run->context_json ?? [], $result);

        // reload: الرد (حتى لو فشل) اتسجّل في الشات، فالصفحة لازم تتحدّث عشان يبان.
        return ['ok' => $result->ok, 'reply' => $reply, 'redirect' => null, 'reload' => true];
    }
}
