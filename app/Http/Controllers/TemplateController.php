<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

// إدارة القوالب — إضافة/تعديل/حذف بيانات القالب الأساسية بس (الاسم/التصنيف/النوع...).
// إدارة نسخه وخانات المحتوى بتاعته بتتم من صفحة العرض (show) عن طريق
// TemplateVariantController و TemplateSlotController.
class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = Template::withCount(['variants', 'slots'])
            ->orderBy('name')
            ->get();

        return view('templates.index', compact('templates'));
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
}
