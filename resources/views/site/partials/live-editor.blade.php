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
    <button type="button" id="bq-free-position-toggle" class="bq-bar-toggle">📐 ترتيب حر</button>
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
        @if ($sameCategoryTemplates->isNotEmpty())
            {{-- تغيير القالب (2026-09-21) — قوالب تانية بس من نفس فئة القالب الحالي، عشان
            المحتوى (نفس الـ17 مفتاح) ينتقل صح للقالب الجديد. الاختيار الافتراضي "نفس القالب
            الحالي" عشان الحفظ العادي (ألوان/خط) ميغيّرش القالب من غير قصد. --}}
            <div class="bq-drawer-section">
                <label class="bq-checkbox-label" style="display: block; margin-bottom: 6px;">🔄 غيّر القالب</label>
                <select name="template_id" style="width: 100%; border-radius: 8px; border: 1px solid #334155; background: #020617; padding: 10px 14px; font-size: 14px; color: #f1f5f9;">
                    <option value="">— نفس القالب الحالي —</option>
                    @foreach ($sameCategoryTemplates as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
                <p class="bq-hint">المحتوى الحالي بيتنقل زي ما هو للقالب الجديد — التغيير هيبان بعد ما تحفظ وتعمل تحديث للصفحة.</p>
            </div>
        @endif

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

        {{-- تخين/مَيَلان/حجم الخط العام (Phase 16، 2026-09-21) — نفس مبدأ font_override:
        قيمة فاضية = "زي القالب". تخين الخط ده بيأثر بس على النصوص اللي مالهاش وزن خط ثابت
        من التصميم نفسه (زي العناوين الكبيرة اللي أصلاً bold/black بتصميمها) — العناوين
        هتفضل بارزة عمداً حتى لو اخترت "عادي" هنا، عشان التباين البصري بين العنوان والنص
        العادي جزء من هوية كل تصميم. --}}
        <div class="bq-drawer-section">
            <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#cbd5e1;">تخين الخط (للنص العادي)</label>
            <select name="font_weight_override" style="width:100%; border-radius:8px; border:1px solid #334155; background:#020617; padding:10px 14px; font-size:14px; color:#f1f5f9;">
                <option value="">— زي القالب —</option>
                <option value="400" @selected($site->font_weight_override === '400')>عادي</option>
                <option value="500" @selected($site->font_weight_override === '500')>متوسط</option>
                <option value="600" @selected($site->font_weight_override === '600')>نص سميك</option>
                <option value="700" @selected($site->font_weight_override === '700')>سميك</option>
                <option value="800" @selected($site->font_weight_override === '800')>سميك جداً</option>
            </select>
        </div>

        <div class="bq-drawer-section">
            <label class="bq-checkbox-label">
                <input type="checkbox" name="font_style_override" value="italic" @checked($site->font_style_override === 'italic')>
                خط مائل (Italic) لكل النصوص
            </label>
            <p class="bq-hint">مش كل خط عنده تصميم مايل حقيقي — لو مفيش، المتصفح بيميّل النص صناعياً (شكل مقبول بس مش نفس دقة خط مايل أصلي).</p>
        </div>

        <div class="bq-drawer-section">
            <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#cbd5e1;">حجم النصوص</label>
            <select name="font_size_scale_override" style="width:100%; border-radius:8px; border:1px solid #334155; background:#020617; padding:10px 14px; font-size:14px; color:#f1f5f9;">
                <option value="">— زي القالب —</option>
                <option value="0.85" @selected((float) ($site->font_size_scale_override ?? 0) === 0.85)>صغير</option>
                <option value="1" @selected((float) ($site->font_size_scale_override ?? 0) === 1.0)>عادي</option>
                <option value="1.15" @selected((float) ($site->font_size_scale_override ?? 0) === 1.15)>كبير</option>
                <option value="1.3" @selected((float) ($site->font_size_scale_override ?? 0) === 1.3)>كبير جداً</option>
            </select>
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
