{{-- فورم مشترك لبيانات القالب الأساسية — مستخدم في صفحتي الإضافة والتعديل. --}}
<div class="space-y-5">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-300">اسم القالب</label>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $template->name ?? '') }}"
            required
            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
        >
        @error('name')
            <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="category" class="mb-1.5 block text-sm font-medium text-slate-300">التصنيف</label>
            <input
                type="text"
                id="category"
                name="category"
                value="{{ old('category', $template->category ?? '') }}"
                placeholder="مطاعم، عيادات، متاجر..."
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
            >
            @error('category')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="kind" class="mb-1.5 block text-sm font-medium text-slate-300">نوع القالب</label>
            <select
                id="kind"
                name="kind"
                required
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
            >
                @php $kind = old('kind', $template->kind ?? 'landing'); @endphp
                <option value="landing" @selected($kind === 'landing')>صفحة هبوط (Landing)</option>
                <option value="wordpress" @selected($kind === 'wordpress')>ووردبريس</option>
            </select>
            @error('kind')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="license_note" class="mb-1.5 block text-sm font-medium text-slate-300">ملاحظة الترخيص (اختياري)</label>
        <textarea
            id="license_note"
            name="license_note"
            rows="2"
            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
        >{{ old('license_note', $template->license_note ?? '') }}</textarea>
        @error('license_note')
            <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-300">
        <input
            type="checkbox"
            name="is_active"
            value="1"
            @checked(old('is_active', $template->is_active ?? true))
            class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-400 focus:ring-amber-400"
        >
        القالب متاح للاستخدام
    </label>
</div>
