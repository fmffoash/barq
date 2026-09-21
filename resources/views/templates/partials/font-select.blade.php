{{--
    اختيار الخط العام للموقع كله (Phase 8، بقى custom dropdown في Phase 16 — 2026-09-21).
    $font: المفتاح الحالي أو null للافتراضي. $fieldName (اختياري): اسم الحقل — افتراضياً
    "font"، بيتغيّر لـ "font_override" لما الجزئية دي تتستخدم لتخصيص موقع واحد بس (site-edit).
    $withInherit (اختياري): لو true بيضيف خيار "— الخط العام —" في الأول (قيمته فاضية) بدل ما
    "cairo" يبقى هو افتراضي مفروض.

    ⚠️ ليه custom dropdown مش <select> عادي: جرّبنا الأول style="font-family" على كل <option>
    مباشرة — فؤاد أكّد حياً إنها مش بتظهر عنده (قايمة الـ<option> المفتوحة بتترندر بواجهة
    نظام التشغيل نفسها في كتير من المتصفحات، مش بواجهة الصفحة، فأي CSS بنحطه عليها ممكن
    يتجاهل بالكامل). الحل الموثوق: عناصر <div> عادية إحنا بنرندرها ونتحكم فيها بالكامل
    (أكيد هتحترم أي CSS، مفيش اعتماد على widget النظام)، بـinput مخفي بياخد القيمة الفعلية.

    كل الكلاسات هنا inline styles عمداً مش Tailwind — الجزئية دي بتتستخدم جوّه
    site/partials/live-editor.blade.php (اللي بيتجنّب Tailwind عمداً عشان يشتغل من غير
    npm run build) وكمان صفحات تانية Tailwind، فـinline styles بتشتغل صح في الحالتين.
--}}
@php
    $fonts = \App\Models\TemplateVariant::FONTS;
    $currentValue = ($withInherit ?? false) ? ($font ?: '') : ($font ?: 'cairo');
    $currentLabel = $currentValue === '' ? '— الخط العام (بتاع القالب) —' : ($fonts[$currentValue] ?? $currentValue);
@endphp
<div>
    <label style="display:block; margin-bottom:6px; font-size:14px; font-weight:500; color:#cbd5e1;">الخط</label>

    <div class="bq-font-picker" style="position:relative;">
        <input type="hidden" name="{{ $fieldName ?? 'font' }}" value="{{ $currentValue }}" class="bq-font-picker-value">

        <button
            type="button"
            class="bq-font-picker-toggle"
            style="display:flex; width:100%; align-items:center; justify-content:space-between; gap:8px; border-radius:8px; border:1px solid #334155; background:#020617; padding:10px 14px; font-size:14px; color:#f1f5f9; cursor:pointer; text-align:right;"
        >
            <span class="bq-font-picker-label" style="font-family: var(--font-{{ $currentValue ?: 'cairo' }});">{{ $currentLabel }}</span>
            <span style="color:#64748b; flex-shrink:0;">▾</span>
        </button>

        <div
            class="bq-font-picker-list"
            hidden
            style="position:absolute; z-index:40; margin-top:4px; max-height:260px; width:100%; overflow-y:auto; border-radius:8px; border:1px solid #334155; background:#0f172a; box-shadow:0 10px 25px rgba(0,0,0,.5);"
        >
            @if ($withInherit ?? false)
                <div
                    class="bq-font-picker-option"
                    data-value=""
                    data-label="— الخط العام (بتاع القالب) —"
                    style="padding:9px 14px; font-size:14px; color:#e2e8f0; cursor:pointer;"
                >— الخط العام (بتاع القالب) —</div>
            @endif
            @foreach ($fonts as $key => $label)
                <div
                    class="bq-font-picker-option"
                    data-value="{{ $key }}"
                    data-label="{{ $label }}"
                    style="padding:9px 14px; font-size:16px; color:#e2e8f0; cursor:pointer; font-family: var(--font-{{ $key }});"
                >{{ $label }}</div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // منتقي خط مخصّص (Phase 16، 2026-09-21) — بديل عن option اللي مبتظهرش بخطها
        // الحقيقي في متصفحات كتير. صفر تكرار-حماية هنا عمداً (تركيبة معيّنة من Blade
        // directives سبّبت خطأ compile لما الجزئية اتضمّت مرتين في نفس الصفحة — زي
        // templates/show.blade.php) — الكود idempotent أصلاً (querySelectorAll +
        // addEventListener)، فتكرار الوسم لو الجزئية اتضمت أكتر من مرة مش مشكلة، أسوأ
        // حالة الحدث بيتسجّل مرتين بدل مرة.
        document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.bq-font-picker').forEach(function (picker) {
                    var toggle = picker.querySelector('.bq-font-picker-toggle');
                    var list = picker.querySelector('.bq-font-picker-list');
                    var label = picker.querySelector('.bq-font-picker-label');
                    var hiddenInput = picker.querySelector('.bq-font-picker-value');
                    if (!toggle || !list || !hiddenInput) return;

                    toggle.addEventListener('click', function (event) {
                        event.preventDefault();
                        var isOpen = !list.hidden;
                        document.querySelectorAll('.bq-font-picker-list').forEach(function (l) { l.hidden = true; });
                        list.hidden = isOpen;
                    });

                    list.querySelectorAll('.bq-font-picker-option').forEach(function (option) {
                        option.addEventListener('click', function () {
                            hiddenInput.value = option.dataset.value;
                            label.textContent = option.dataset.label;
                            label.style.fontFamily = option.dataset.value
                                ? 'var(--font-' + option.dataset.value + ')'
                                : 'var(--font-cairo)';
                            list.hidden = true;
                            hiddenInput.dispatchEvent(new Event('change', {bubbles: true}));
                        });
                    });
                });

            document.addEventListener('click', function (event) {
                if (event.target.closest('.bq-font-picker')) return;
                document.querySelectorAll('.bq-font-picker-list').forEach(function (l) { l.hidden = true; });
            });
        });
    </script>
@endpush
