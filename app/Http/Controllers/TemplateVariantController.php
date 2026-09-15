<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\TemplateVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// إدارة نسخ القالب (الألوان والأقسام). القراءة والعرض بتتم من صفحة القالب نفسها
// (TemplateController::show)، والكونترولر ده مسؤول بس عن الحفظ/التعديل/الحذف.
class TemplateVariantController extends Controller
{
    public function store(Request $request, Template $template): RedirectResponse
    {
        $data = $this->validated($request);

        $data['slug'] = $this->uniqueSlug($template, $data['name']);

        $variant = new TemplateVariant($data);
        $variant->template()->associate($template);

        if ($variant->is_default) {
            $template->variants()->update(['is_default' => false]);
        }

        $variant->save();

        return redirect()
            ->route('templates.show', $template)
            ->with('status', 'تم إضافة النسخة.');
    }

    public function update(Request $request, TemplateVariant $variant): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['is_default'] && ! $variant->is_default) {
            $variant->template->variants()
                ->where('id', '!=', $variant->id)
                ->update(['is_default' => false]);
        }

        $variant->update($data);

        return redirect()
            ->route('templates.show', $variant->template)
            ->with('status', 'تم تحديث النسخة.');
    }

    public function destroy(TemplateVariant $variant): RedirectResponse
    {
        $template = $variant->template;

        $variant->delete();

        return redirect()
            ->route('templates.show', $template)
            ->with('status', 'تم حذف النسخة.');
    }

    // بيتحقق من بيانات الفورم ويحوّل حقلي الـ JSON (لو موجودين) من نص خام لمصفوفة PHP
    // — عشان الـ cast بتاع الموديل (array) ميعملش double-encode للنص الخام.
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'colors_json' => ['nullable', 'json'],
            'sections_json' => ['nullable', 'json'],
        ]);

        foreach (['colors_json', 'sections_json'] as $field) {
            $raw = $request->input($field);
            $data[$field] = filled($raw) ? json_decode($raw, true) : null;
        }

        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }

    // بيولّد slug فريد داخل نفس القالب بس — نفس الاسم ينفع يتكرر في قالب تاني.
    private function uniqueSlug(Template $template, string $name): string
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
