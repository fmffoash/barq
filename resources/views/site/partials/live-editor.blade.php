{{--
    واجهة المحرر البصري المباشر (WYSIWYG click-to-edit) — شريط علوي + زرار "تصميم الموقع"
    عائم بيفتح درج فيه نفس منتقيات الألوان/الخط/ترتيب الأقسام الموجودة أصلاً (Phase 8/9)،
    وبيانات إعداد لـ public/js/live-editor.js. صفر منطق تعديل جوّه الملف ده — كل التفاعل في
    الـ JS، الملف ده بس بيبني الـ DOM والبيانات اللي محتاجها.

    مفيش أي كلاس Tailwind هنا عمداً — public/css/live-editor.css ملف عادي مش معتمد على
    build خطوة Tailwind، عشان واجهة المحرر تشتغل صح من غير ما نحتاج npm run build بعد أي
    تعديل عليها (درس Phase 8 الموثّق في CLAUDE.md). الأجزاء المعاد استخدامها (منتقي الألوان/
    الخط/ترتيب الأقسام) هي الوحيدة اللي فيها كلاسات Tailwind، وهي أصلاً متبنية جوّه CSS
    لوحة التحكم من زمان.
--}}
@php
    $slotTypes = [];
    foreach ($sections as $section) {
        foreach ($section['items'] as $item) {
            $slotTypes[$item['slot']->key] = $item['slot']->slot_type;
        }
    }
@endphp

<script id="live-editor-config" type="application/json">
{!! json_encode([
    'saveUrl' => route('projects.site.update', $project),
    'backUrl' => route('projects.show', $project),
    'csrfToken' => csrf_token(),
    'slotTypes' => $slotTypes,
    'styleOverrides' => $site->style_overrides_json ?? [],
    'fonts' => \App\Models\TemplateVariant::FONTS,
]) !!}
</script>

<div class="bq-bar">
    <span class="bq-bar-label">🖊️ وضع التعديل المباشر — دوس على أي نص عشان تعدّله في مكانه</span>
    <a href="{{ route('projects.show', $project) }}" class="bq-bar-back">رجوع للمشروع</a>
</div>

<div id="bq-toast-container" class="bq-toast-container" aria-live="polite"></div>

<button type="button" id="bq-design-fab" class="bq-fab">🎨 تصميم الموقع</button>

<div id="bq-design-drawer" class="bq-drawer" aria-hidden="true">
    <div class="bq-drawer-header">
        <strong>تصميم الموقع</strong>
        <button type="button" id="bq-design-drawer-close" class="bq-drawer-close" aria-label="قفل">✕</button>
    </div>

    <form id="bq-design-form" class="bq-drawer-body">
        <div class="bq-drawer-section">
            <label class="bq-checkbox-label">
                <input type="checkbox" name="use_custom_colors" value="1" @checked($site->colors_override_json)>
                ألوان مختلفة عن القالب الأصلي لهذا الموقع بس
            </label>
            @include('templates.partials.color-picker', [
                'colors' => $site->colors_override_json ?: $variant?->colors_json,
                'fieldName' => 'colors_override',
            ])
        </div>

        <div class="bq-drawer-section">
            @include('templates.partials.font-select', [
                'font' => $site->font_override,
                'fieldName' => 'font_override',
                'withInherit' => true,
            ])
        </div>

        <div class="bq-drawer-section">
            <label class="bq-checkbox-label">
                <input type="checkbox" name="use_custom_sections" value="1" @checked($site->sections_override_json)>
                ترتيب/إظهار أقسام مختلف عن القالب الأصلي لهذا الموقع بس
            </label>
            @include('partials.section-order-picker', [
                'sectionKeys' => $slotsBySection->keys(),
                'currentOrder' => $site->sections_override_json ?: $variant?->sections_json,
                'fieldName' => 'sections_override',
            ])
            <p class="bq-hint">ترتيب الأقسام بيتحدّث بعد ما تحفظ وتعمل تحديث للصفحة.</p>
        </div>

        <button type="submit" class="bq-drawer-save">حفظ التصميم</button>
    </form>
</div>
