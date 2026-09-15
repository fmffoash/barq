{{-- فورم بيانات المشروع الأساسية (الاسم وبيانات التواصل) — مستخدم في الإضافة والتعديل، بدون
    أي حقل خاص بالقالب نفسه (ده بيتحدد في الصفحتين بشكل مختلف). --}}
<div class="space-y-5">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-300">اسم المشروع</label>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $project->name ?? '') }}"
            required
            placeholder="اسم العميل أو نشاطه"
            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
        >
        @error('name')
            <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="contact_name" class="mb-1.5 block text-sm font-medium text-slate-300">اسم المسؤول</label>
            <input
                type="text"
                id="contact_name"
                name="contact_name"
                value="{{ old('contact_name', $project->contact_name ?? '') }}"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
            >
            @error('contact_name')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="contact_phone" class="mb-1.5 block text-sm font-medium text-slate-300">التليفون</label>
            <input
                type="text"
                id="contact_phone"
                name="contact_phone"
                dir="ltr"
                value="{{ old('contact_phone', $project->contact_phone ?? '') }}"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
            >
            @error('contact_phone')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="contact_email" class="mb-1.5 block text-sm font-medium text-slate-300">الإيميل</label>
            <input
                type="email"
                id="contact_email"
                name="contact_email"
                dir="ltr"
                value="{{ old('contact_email', $project->contact_email ?? '') }}"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
            >
            @error('contact_email')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
