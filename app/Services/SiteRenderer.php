<?php

namespace App\Services;

use App\Models\GeneratedSite;

// بيبني بيانات رندر الموقع (الأقسام مرتّبة ومفلترة بمحتواها الفعلي + الألوان النهائية) من
// موقع ناتج معيّن — نفس المنطق مستخدم في المعاينة الحية (SiteController) وفي التصدير كملفات
// ثابتة (SiteExportService)، عشان الاتنين يعرضوا بالظبط نفس المحتوى من غير تكرار المنطق.
class SiteRenderer
{
    public function render(GeneratedSite $site): array
    {
        $project = $site->project;
        $template = $project->template;
        $variant = $project->variant;

        $slotsBySection = $template->slots->groupBy('section_key');

        // ترتيب الأقسام: بنستخدم اللي متحدد في النسخة صراحة، وإلا بنرجع لترتيب أول ظهور
        // للأقسام جوّه خانات القالب نفسه (اللي أصلاً مرتّبة بـ sort_order).
        $sectionOrder = filled($variant?->sections_json)
            ? $variant->sections_json
            : $slotsBySection->keys()->all();

        $sections = collect($sectionOrder)
            ->filter(fn ($key) => $slotsBySection->has($key))
            ->map(function ($key) use ($slotsBySection, $site) {
                $items = $slotsBySection->get($key)
                    ->sortBy('sort_order')
                    ->map(fn ($slot) => [
                        'slot' => $slot,
                        'value' => $site->content($slot->key),
                        // تخصيص لون/خط الخانة دي بس (Phase 8) — ['color' => ?, 'font' => ?]،
                        // فاضي (null/null) لو الخانة من غير أي تخصيص، فبترجع للعام تلقائي.
                        'style' => $site->styleFor($slot->key),
                    ])
                    ->filter(fn (array $item) => filled($item['value']))
                    ->values();

                return ['key' => $key, 'items' => $items];
            })
            // قسم من غير أي قيمة متعبّاة فيه لسه (المشروع لسه بيتظبط) بنسيبه من غير ما يترندر
            // فاضي وسط الصفحة.
            ->filter(fn (array $section) => $section['items']->isNotEmpty())
            ->values();

        // بنحسب "نوع" كل قسم (hero/gallery/list/cta/text) بناءً على موقعه وأنواع خاناته —
        // مش من اسم القسم نفسه، عشان يشتغل مع أي قالب (بتاعنا أو أي قالب الأدمن يعمله يدوي)
        // من غير ما نفرض تسميات أقسام معينة. التصميمات البصرية (modern/gallery) بتستخدم النوع
        // ده عشان تقرر شكل العرض، بينما "classic" بيتجاهله تماماً (نفس السلوك القديم بالحرف).
        $sectionCount = $sections->count();

        $sections = $sections->values()->map(
            fn (array $section, int $index) => $section + [
                'kind' => $this->classifySection($section, $index === 0, $index === $sectionCount - 1),
            ]
        );

        $colors = array_merge([
            'primary' => '#f59e0b',
            'background' => '#0b1220',
            'surface' => '#111a2e',
            'text' => '#f1f5f9',
            'muted' => '#94a3b8',
        ], $variant?->colors_json ?? []);

        return [
            'project' => $project,
            'sections' => $sections,
            'colors' => $colors,
            'font' => $variant?->font ?: 'cairo',
            'layout' => $template->layout ?: 'classic',
        ];
    }

    /**
     * @param  array{key: string, items: \Illuminate\Support\Collection<int, array{slot: \App\Models\TemplateSlot, value: mixed}>}  $section
     */
    private function classifySection(array $section, bool $isFirst, bool $isLast): string
    {
        if ($isFirst) {
            return 'hero';
        }

        $types = $section['items']->pluck('slot.slot_type');

        if ($types->contains('image')) {
            return 'gallery';
        }

        if ($isLast && $types->contains('link')) {
            return 'cta';
        }

        if ($types->filter(fn ($type) => $type === 'list')->isNotEmpty()) {
            return 'list';
        }

        return 'text';
    }
}
