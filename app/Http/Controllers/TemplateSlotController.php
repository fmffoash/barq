<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\TemplateSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// إدارة خانات المحتوى (slots) بتاعة القالب — كل خانة هي حقل واحد قابل للتعبئة (عنوان،
// وصف، صورة...) هيظهر في فورم إنشاء المشروع لاحقاً. العرض بيتم من صفحة القالب نفسها.
class TemplateSlotController extends Controller
{
    public function store(Request $request, Template $template): RedirectResponse
    {
        $data = $this->validated($request, $template);

        $slot = new TemplateSlot($data);
        $slot->template()->associate($template);
        $slot->save();

        return redirect()
            ->route('templates.show', $template)
            ->with('status', 'تم إضافة خانة المحتوى.');
    }

    public function update(Request $request, TemplateSlot $slot): RedirectResponse
    {
        $data = $this->validated($request, $slot->template, $slot);

        $slot->update($data);

        return redirect()
            ->route('templates.show', $slot->template)
            ->with('status', 'تم تحديث خانة المحتوى.');
    }

    public function destroy(TemplateSlot $slot): RedirectResponse
    {
        $template = $slot->template;

        $slot->delete();

        return redirect()
            ->route('templates.show', $template)
            ->with('status', 'تم حذف خانة المحتوى.');
    }

    // مفتاح الخانة (key) لازم يكون فريد داخل نفس القالب — الـ Rule::unique هنا بيستثني
    // الخانة الحالية نفسها وقت التعديل (ignore) عشان الحفظ من غير تغيير المفتاح ميترفضش.
    private function validated(Request $request, Template $template, ?TemplateSlot $ignore = null): array
    {
        $data = $request->validate([
            'section_key' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/'],
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('template_slots', 'key')
                    ->where('template_id', $template->id)
                    ->ignore($ignore?->id),
            ],
            'label_ar' => ['required', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'slot_type' => ['required', 'in:text,textarea,image,list,link'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_required'] = $request->boolean('is_required');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
