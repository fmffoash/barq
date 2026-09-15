<?php

namespace App\Http\Controllers;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// إدارة المشاريع — كل مشروع طلب عميل واحد لموقع، بيختار قالب ونسخة منه، وبمجرد الإنشاء
// بيتولّد له موقع فعلي فاضي (generated_sites) جاهز لتعبئة محتواه يدوياً من GeneratedSiteController.
class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::with(['template', 'variant', 'site'])
            ->latest()
            ->get();

        return view('projects.index', compact('projects'));
    }

    // خطوتين في صفحة واحدة: من غير ?template بيعرض قايمة القوالب لاختيار واحد، وبيه بيعرض
    // الفورم الكامل. الفصل ده عشان دروب داون النسخ يفضل مقصور على نسخ القالب المختار بس،
    // من غير أي جافاسكريبت.
    public function create(Request $request): View
    {
        if ($request->filled('template')) {
            $template = Template::with('variants')
                ->where('is_active', true)
                ->find($request->integer('template'));

            if ($template) {
                return view('projects.create', [
                    'template' => $template,
                    'templates' => null,
                ]);
            }
        }

        $templates = Template::where('is_active', true)
            ->withCount('variants')
            ->orderBy('name')
            ->get();

        return view('projects.create', [
            'template' => null,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $template = Template::where('is_active', true)->findOrFail($request->integer('template_id'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_variant_id' => ['nullable', Rule::exists('template_variants', 'id')->where('template_id', $template->id)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        $project = DB::transaction(function () use ($template, $data) {
            $project = Project::create([
                'template_id' => $template->id,
                'template_variant_id' => $data['template_variant_id'] ?? null,
                'name' => $data['name'],
                'slug' => $this->uniqueProjectSlug($data['name']),
                'contact_name' => $data['contact_name'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'status' => 'draft',
            ]);

            GeneratedSite::create([
                'project_id' => $project->id,
                'slug' => $this->uniqueSiteSlug($data['name']),
                'content_json' => [],
                'status' => 'draft',
            ]);

            return $project;
        });

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'تم إنشاء المشروع. دلوقتي املا محتوى الموقع.');
    }

    public function show(Project $project): View
    {
        $project->load(['template', 'variant', 'site', 'creator']);

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        $template = $project->template()->with('variants')->first();

        return view('projects.edit', compact('project', 'template'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_variant_id' => ['nullable', Rule::exists('template_variants', 'id')->where('template_id', $project->template_id)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:draft,generated,delivered'],
        ]);

        $project->update($data);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'تم تحديث بيانات المشروع.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('status', 'تم حذف المشروع وموقعه.');
    }

    // تعليم المشروع كمُسلَّم فعلاً للعميل — خطوة إدارية بحتة، صفر تأثير على حالة الموقع نفسه.
    public function deliver(Project $project): RedirectResponse
    {
        $project->update(['status' => 'delivered']);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'تم تعليم المشروع كمُسلَّم للعميل.');
    }

    // بيولّد slug فريد لمشروع من اسمه (مفيش constraint فريد على العمود ده في الداتابيز،
    // بس بنحافظ على تفرّده تطبيقياً زي ما موضّح في تعليق الـ migration).
    private function uniqueProjectSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $suffix = 2;

        while (Project::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    // بيولّد slug فريد على مستوى المنصة كلها للموقع الناتج — ده اللي فعلياً بيتستخدم
    // كـ subdomain معاينة، فلازم يكون فريد بين كل المشاريع (مش بس مشروع واحد).
    private function uniqueSiteSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'site';
        $slug = $base;
        $suffix = 2;

        while (GeneratedSite::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
