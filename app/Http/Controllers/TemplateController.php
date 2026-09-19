<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// إدارة القوالب — إضافة/تعديل/حذف بيانات القالب الأساسية بس (الاسم/التصنيف/النوع...).
// إدارة نسخه وخانات المحتوى بتاعته بتتم من صفحة العرض (show) عن طريق
// TemplateVariantController و TemplateSlotController.
class TemplateController extends Controller
{
    // بحث بالاسم + فلترة بالتصنيف (التصنيفات مبنية ديناميكياً من القيم الموجودة فعلاً في
    // الداتابيز — العمود ده نص حر مش قايمة ثابتة).
    public function index(Request $request): View
    {
        $categories = Template::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $templates = Template::withCount(['variants', 'slots'])
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->orderBy('name')
            ->get();

        return view('templates.index', compact('templates', 'categories'));
    }

    public function create(): View
    {
        return view('templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', 'in:landing,wordpress'],
            'layout' => ['required', Rule::in(Template::LAYOUTS)],
            'license_note' => ['nullable', 'string'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        $template = Template::create($data);

        return redirect()
            ->route('templates.show', $template)
            ->with('status', 'تم إنشاء القالب. دلوقتي ضيفله نسخة وخانات محتوى.');
    }

    public function show(Template $template): View
    {
        $template->load(['variants', 'slots']);

        $slotsBySection = $template->slots->groupBy('section_key');

        return view('templates.show', compact('template', 'slotsBySection'));
    }

    public function edit(Template $template): View
    {
        return view('templates.edit', compact('template'));
    }

    public function update(Request $request, Template $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', 'in:landing,wordpress'],
            'layout' => ['required', Rule::in(Template::LAYOUTS)],
            'license_note' => ['nullable', 'string'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $template->update($data);

        return redirect()
            ->route('templates.show', $template)
            ->with('status', 'تم تحديث بيانات القالب.');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $template->delete();

        return redirect()
            ->route('templates.index')
            ->with('status', 'تم حذف القالب بكل نسخه وخاناته.');
    }

    // بيحفظ مشروع منجز كقالب جديد ومستقل تماماً (خاناته منسوخة من قالبه الأصلي، ونسخته
    // الافتراضية منسوخة من النسخة اللي المشروع مستخدمها لو موجودة) — عشان مكتبة القوالب
    // تكبر من غير ما نبدأ من الصفر كل مرة، زي ما موضّح في الروادماب (Phase 3).
    public function storeFromProject(Request $request, Project $project): RedirectResponse
    {
        $sourceTemplate = $project->template()->with('slots')->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        // لو الأدمن اختار "بمحتواه" بنستخدم قيم الموقع الحالي كقيمة افتراضية لكل خانة،
        // وإلا بنسيب خانات القالب الجديد فاضية (أو بنورّث الافتراضي القديم لو كان موجود أصلاً).
        $withContent = $request->boolean('with_content');
        $siteContent = $withContent ? ($project->site?->content_json ?? []) : [];

        $newTemplate = DB::transaction(function () use ($sourceTemplate, $project, $data, $siteContent) {
            $newTemplate = Template::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'category' => ($data['category'] ?? null) ?: $sourceTemplate->category,
                'kind' => $sourceTemplate->kind,
                'layout' => $sourceTemplate->layout,
                'license_note' => $sourceTemplate->license_note,
                'is_active' => true,
            ]);

            foreach ($sourceTemplate->slots as $slot) {
                $defaultValue = $siteContent === [] ? $slot->default_value : data_get($siteContent, $slot->key);

                $newTemplate->slots()->create([
                    'section_key' => $slot->section_key,
                    'key' => $slot->key,
                    'label_ar' => $slot->label_ar,
                    'label_en' => $slot->label_en,
                    'slot_type' => $slot->slot_type,
                    'is_required' => $slot->is_required,
                    'sort_order' => $slot->sort_order,
                    'default_value' => $defaultValue,
                ]);
            }

            if ($project->variant) {
                $newTemplate->variants()->create([
                    'name' => $project->variant->name,
                    'slug' => $this->uniqueVariantSlug($newTemplate, $project->variant->name),
                    'colors_json' => $project->variant->colors_json,
                    'sections_json' => $project->variant->sections_json,
                    'is_default' => true,
                ]);
            }

            return $newTemplate;
        });

        return redirect()
            ->route('templates.show', $newTemplate)
            ->with('status', 'تم حفظ المشروع كقالب جديد.');
    }

    // بيولّد slug فريد من اسم القالب — لو الاسم اتكرر بيضيف رقم في الآخر (template-2...).
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'template';
        $slug = $base;
        $suffix = 2;

        while (Template::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    // بيولّد slug فريد داخل نسخ القالب الجديد بس (نفس نمط TemplateVariantController::uniqueSlug).
    private function uniqueVariantSlug(Template $template, string $name): string
    {
        $base = Str::slug($name) ?: 'variant';
        $slug = $base;
        $suffix = 2;

        while ($template->variants()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
