{{-- اختيار الخط العام للموقع كله (Phase 8) — $font: المفتاح الحالي أو null للافتراضي. --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-300">الخط</label>
    <select
        name="font"
        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
    >
        @foreach (\App\Models\TemplateVariant::FONTS as $key => $label)
            <option value="{{ $key }}" @selected(($font ?: 'cairo') === $key)>{{ $label }}</option>
        @endforeach
    </select>
</div>
