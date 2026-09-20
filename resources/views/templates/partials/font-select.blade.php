{{--
    اختيار الخط العام للموقع كله (Phase 8) — $font: المفتاح الحالي أو null للافتراضي.
    $fieldName (اختياري): اسم الحقل — افتراضياً "font"، بيتغيّر لـ "font_override" لما
    الجزئية دي تتستخدم لتخصيص موقع واحد بس (site-edit). $withInherit (اختياري): لو true
    بيضيف خيار "— الخط العام —" في الأول (قيمته فاضية) بدل ما "cairo" يبقى هو افتراضي مفروض.
--}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-300">الخط</label>
    <select
        name="{{ $fieldName ?? 'font' }}"
        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
    >
        @if ($withInherit ?? false)
            <option value="" @selected(blank($font))>— الخط العام (بتاع القالب) —</option>
        @endif
        @foreach (\App\Models\TemplateVariant::FONTS as $key => $label)
            <option value="{{ $key }}" @selected((($withInherit ?? false) ? $font : ($font ?: 'cairo')) === $key)>{{ $label }}</option>
        @endforeach
    </select>
</div>
