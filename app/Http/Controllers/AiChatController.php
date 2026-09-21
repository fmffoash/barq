<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Template;
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
    // خيارات "حدد بنفسك" الاختيارية (Phase 14، 2026-09-21) — فؤاد طلب تحكّم يدوي مباشر
    // (فئة → قالب يخص الفئة دي بس → لون → خط) بدل ما يفضل يعتمد على تخمين الذكاء الاصطناعي
    // بس، خصوصاً بعد باج "غيّر القالب" اللي كان بيدور بين نفس القالبين. $templates بيانات
    // خفيفة (id/name/category بس، مفيش slots/variants) عشان الفلترة بالفئة تحصل فوراً في
    // المتصفح (JS) من غير أي نداء تاني للسيرفر.
    public function create(): View
    {
        $categories = Template::where('is_active', true)->where('kind', 'landing')
            ->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        $templates = Template::where('is_active', true)->where('kind', 'landing')
            ->orderBy('name')->get(['id', 'name', 'category']);

        return view('ai-chat.create', [
            'categories' => $categories,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request, AiProjectAssistantService $assistant): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font' => ['nullable', 'string'],
        ]);

        $result = $assistant->createFromMessage(
            $data['message'],
            $data['template_id'] ?? null,
            $data['color'] ?? null,
            $data['font'] ?? null,
        );

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
