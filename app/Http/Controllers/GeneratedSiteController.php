<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// تعبئة محتوى الموقع الناتج من مشروع معيّن يدوياً — خانة بخانة حسب تعريفها في القالب —
// ونشر/إلغاء نشر الموقع لما المحتوى يخلص.
class GeneratedSiteController extends Controller
{
    public function edit(Project $project): View
    {
        $project->loadMissing(['template.slots', 'site']);

        $slotsBySection = $project->template->slots->groupBy('section_key');

        return view('projects.site-edit', [
            'project' => $project,
            'site' => $project->site,
            'slotsBySection' => $slotsBySection,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->loadMissing('template.slots');

        $site = $project->site()->firstOrFail();
        $content = $site->content_json ?? [];

        foreach ($project->template->slots as $slot) {
            $key = $slot->key;

            if ($slot->slot_type === 'image') {
                if ($request->hasFile("content_files.{$key}")) {
                    $path = $request->file("content_files.{$key}")->store('site-images', 'public');
                    $content[$key] = '/storage/'.$path;
                }

                continue;
            }

            if (! $request->has("content.{$key}")) {
                continue;
            }

            $value = $request->input("content.{$key}");

            if ($slot->slot_type === 'list') {
                $content[$key] = collect(explode("\n", (string) $value))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->values()
                    ->all();

                continue;
            }

            $content[$key] = $value;
        }

        $site->update(['content_json' => $content]);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'تم حفظ محتوى الموقع.');
    }

    public function publish(Project $project): RedirectResponse
    {
        $site = $project->site()->firstOrFail();

        $site->update([
            'status' => 'published',
            'last_generated_at' => now(),
        ]);

        if ($project->status === 'draft') {
            $project->update(['status' => 'generated']);
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'الموقع دلوقتي منشور ومتاح للمعاينة.');
    }

    public function unpublish(Project $project): RedirectResponse
    {
        $project->site()->firstOrFail()->update(['status' => 'archived']);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'تم إلغاء نشر الموقع.');
    }
}
