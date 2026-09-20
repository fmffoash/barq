<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AiProjectAssistantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * واجهة "أنشئ/عدّل بالذكاء الاصطناعي" (Phase 10) — شات بدل الفورم التقليدي لاختيار قالب
 * وتعبئة محتوى: رسالة واحدة حرة (وصف + بيانات ملخبطة لو حابب)، والمساعد بيرجّع مشروع
 * جاهز. أي رسالة تانية على نفس المشروع بتتفسّر كطلب تعديل. شوف AiProjectAssistantService
 * للمنطق الفعلي — الكنترولر ده بس واجهة HTTP رفيعة فوقه.
 */
class AiChatController extends Controller
{
    public function create(): View
    {
        return view('ai-chat.create');
    }

    public function store(Request $request, AiProjectAssistantService $assistant): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $result = $assistant->createFromMessage($data['message']);

        if (! $result['ok']) {
            return redirect()
                ->route('ai-chat.create')
                ->withInput()
                ->with('status', $result['reply']);
        }

        return redirect()->route('ai-chat.show', $result['project']);
    }

    public function show(Project $project): View
    {
        $project->load(['template', 'variant', 'site', 'aiChatMessages']);

        return view('ai-chat.show', compact('project'));
    }

    public function message(Request $request, Project $project, AiProjectAssistantService $assistant): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $assistant->handleFollowUp($project, $data['message']);

        // بيرجع لنفس الصفحة اللي فؤاد بعت منها — ممكن تكون صفحة المشروع الرئيسية
        // (projects.show) أو صفحة الشات المستقلة (ai-chat.show)، الاتنين فيهم نفس
        // البارشيال دلوقتي (ai-chat._panel).
        return redirect()->back();
    }
}
