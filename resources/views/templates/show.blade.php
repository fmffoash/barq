@extends('layouts.app')

@section('title', $template->name . ' — برق')

@section('content')
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('templates.index') }}" class="text-sm text-slate-400 transition hover:text-amber-400">
            &rarr; القوالب
        </a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-800 bg-red-950/50 px-4 py-3 text-sm text-red-300">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- بيانات القالب --}}
    <div class="mb-8 flex items-start justify-between gap-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
        <div>
            <div class="mb-1 flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-100">{{ $template->name }}</h1>
                @unless ($template->is_active)
                    <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400">معطّل</span>
                @endunless
            </div>
            <p class="text-sm text-slate-500">
                {{ $template->category ?: 'بدون تصنيف' }} &middot;
                {{ $template->kind === 'wordpress' ? 'ووردبريس' : 'صفحة هبوط' }}
                @if ($template->kind === 'landing')
                    &middot;
                    تصميم {{ \App\Models\Template::layoutLabel($template->layout) }}
                @endif
            </p>
            @if ($template->license_note)
                <p class="mt-2 text-sm text-slate-400">{{ $template->license_note }}</p>
            @endif
        </div>

        <div class="flex shrink-0 gap-2">
            <a
                href="{{ route('templates.edit', $template) }}"
                class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 transition hover:border-amber-400 hover:text-amber-400"
            >
                تعديل
            </a>

            <form
                method="POST"
                action="{{ route('templates.destroy', $template) }}"
                onsubmit="return confirm('حذف القالب دا هيمسح كل نسخه وخاناته كمان. متأكد؟');"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-900 px-3 py-1.5 text-sm text-red-400 transition hover:bg-red-950/40">
                    حذف
                </button>
            </form>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
        {{-- النسخ --}}
        <section>
            <h2 class="mb-4 text-lg font-semibold text-slate-100">نسخ القالب</h2>

            <div class="space-y-3">
                @forelse ($template->variants as $variant)
                    <details class="rounded-xl border border-slate-800 bg-slate-900/60 open:border-amber-400/40">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3">
                            <span class="flex items-center gap-2 text-sm font-medium text-slate-200">
                                {{ $variant->name }}
                                @if ($variant->is_default)
                                    <span class="rounded-full bg-amber-400/10 px-2 py-0.5 text-xs text-amber-400">افتراضية</span>
                                @endif
                            </span>
                            <span class="text-xs text-slate-500">تعديل</span>
                        </summary>

                        <div class="border-t border-slate-800 px-4 py-4">
                            <form method="POST" action="{{ route('template-variants.update', $variant) }}" class="space-y-4">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-slate-300">اسم النسخة</label>
                                    <input
                                        type="text"
                                        name="name"
                                        value="{{ $variant->name }}"
                                        required
                                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                                    >
                                </div>

                                @include('templates.partials.color-picker', ['colors' => $variant->colors_json])

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-slate-300">الأقسام (JSON)</label>
                                    <textarea
                                        name="sections_json"
                                        rows="4"
                                        dir="ltr"
                                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 font-mono text-xs text-slate-100 outline-none focus:border-amber-400"
                                    >{{ $variant->sections_json ? json_encode($variant->sections_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' }}</textarea>
                                </div>

                                <label class="flex items-center gap-2 text-sm text-slate-300">
                                    <input
                                        type="checkbox"
                                        name="is_default"
                                        value="1"
                                        @checked($variant->is_default)
                                        class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-400 focus:ring-amber-400"
                                    >
                                    اجعلها النسخة الافتراضية
                                </label>

                                <div class="flex items-center justify-between">
                                    <button type="submit" class="rounded-lg bg-amber-400 px-4 py-1.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                                        حفظ
                                    </button>
                                </div>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('template-variants.destroy', $variant) }}"
                                onsubmit="return confirm('حذف النسخة دي؟');"
                                class="mt-3"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-400 transition hover:text-red-300">
                                    حذف النسخة
                                </button>
                            </form>
                        </div>
                    </details>
                @empty
                    <p class="text-sm text-slate-500">لسه مفيش نسخ. ضيف أول نسخة تحت.</p>
                @endforelse

                <details class="rounded-xl border border-dashed border-slate-700 bg-slate-900/30">
                    <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-amber-400">
                        + إضافة نسخة جديدة
                    </summary>

                    <form method="POST" action="{{ route('templates.variants.store', $template) }}" class="space-y-4 border-t border-slate-800 px-4 py-4">
                        @csrf

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">اسم النسخة</label>
                            <input
                                type="text"
                                name="name"
                                required
                                placeholder="الأساسية، الموسّعة..."
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                            >
                        </div>

                        @include('templates.partials.color-picker', ['colors' => null])

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">الأقسام (JSON، اختياري)</label>
                            <textarea
                                name="sections_json"
                                rows="4"
                                dir="ltr"
                                placeholder='["hero", "services", "contact"]'
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 font-mono text-xs text-slate-100 outline-none focus:border-amber-400"
                            ></textarea>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <input
                                type="checkbox"
                                name="is_default"
                                value="1"
                                class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-400 focus:ring-amber-400"
                            >
                            اجعلها النسخة الافتراضية
                        </label>

                        <button type="submit" class="rounded-lg bg-amber-400 px-4 py-1.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                            إضافة
                        </button>
                    </form>
                </details>
            </div>
        </section>

        {{-- خانات المحتوى --}}
        <section>
            <h2 class="mb-4 text-lg font-semibold text-slate-100">خانات المحتوى</h2>

            <div class="space-y-6">
                @forelse ($slotsBySection as $sectionKey => $slots)
                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500" dir="ltr">
                            {{ $sectionKey }}
                        </h3>

                        <div class="space-y-2">
                            @foreach ($slots->sortBy('sort_order') as $slot)
                                <details class="rounded-xl border border-slate-800 bg-slate-900/60 open:border-amber-400/40">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3">
                                        <span class="flex items-center gap-2 text-sm font-medium text-slate-200">
                                            {{ $slot->label() }}
                                            @if ($slot->is_required)
                                                <span class="text-xs text-red-400">*</span>
                                            @endif
                                            <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400" dir="ltr">
                                                {{ $slot->slot_type }}
                                            </span>
                                        </span>
                                        <span class="text-xs text-slate-500">تعديل</span>
                                    </summary>

                                    <div class="border-t border-slate-800 px-4 py-4">
                                        <form method="POST" action="{{ route('template-slots.update', $slot) }}" class="space-y-4">
                                            @csrf
                                            @method('PUT')

                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-slate-300">القسم</label>
                                                    <input
                                                        type="text"
                                                        name="section_key"
                                                        value="{{ $slot->section_key }}"
                                                        required
                                                        dir="ltr"
                                                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 font-mono text-xs text-slate-100 outline-none focus:border-amber-400"
                                                    >
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-slate-300">المفتاح</label>
                                                    <input
                                                        type="text"
                                                        name="key"
                                                        value="{{ $slot->key }}"
                                                        required
                                                        dir="ltr"
                                                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 font-mono text-xs text-slate-100 outline-none focus:border-amber-400"
                                                    >
                                                </div>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-slate-300">التسمية (عربي)</label>
                                                    <input
                                                        type="text"
                                                        name="label_ar"
                                                        value="{{ $slot->label_ar }}"
                                                        required
                                                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                                                    >
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-slate-300">التسمية (إنجليزي)</label>
                                                    <input
                                                        type="text"
                                                        name="label_en"
                                                        value="{{ $slot->label_en }}"
                                                        dir="ltr"
                                                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                                                    >
                                                </div>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-slate-300">نوع الخانة</label>
                                                    <select
                                                        name="slot_type"
                                                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                                                    >
                                                        @foreach (['text' => 'نص قصير', 'textarea' => 'نص طويل', 'image' => 'صورة', 'list' => 'قايمة', 'link' => 'رابط'] as $value => $label)
                                                            <option value="{{ $value }}" @selected($slot->slot_type === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-slate-300">الترتيب</label>
                                                    <input
                                                        type="number"
                                                        name="sort_order"
                                                        value="{{ $slot->sort_order }}"
                                                        min="0"
                                                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                                                    >
                                                </div>
                                            </div>

                                            <label class="flex items-center gap-2 text-sm text-slate-300">
                                                <input
                                                    type="checkbox"
                                                    name="is_required"
                                                    value="1"
                                                    @checked($slot->is_required)
                                                    class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-400 focus:ring-amber-400"
                                                >
                                                خانة إجبارية
                                            </label>

                                            <button type="submit" class="rounded-lg bg-amber-400 px-4 py-1.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                                                حفظ
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('template-slots.destroy', $slot) }}"
                                            onsubmit="return confirm('حذف الخانة دي؟');"
                                            class="mt-3"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-400 transition hover:text-red-300">
                                                حذف الخانة
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">لسه مفيش خانات محتوى. ضيف أول خانة تحت.</p>
                @endforelse

                <details class="rounded-xl border border-dashed border-slate-700 bg-slate-900/30">
                    <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-amber-400">
                        + إضافة خانة محتوى جديدة
                    </summary>

                    <form method="POST" action="{{ route('templates.slots.store', $template) }}" class="space-y-4 border-t border-slate-800 px-4 py-4">
                        @csrf

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-300">القسم</label>
                                <input
                                    type="text"
                                    name="section_key"
                                    required
                                    dir="ltr"
                                    placeholder="hero"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 font-mono text-xs text-slate-100 outline-none focus:border-amber-400"
                                >
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-300">المفتاح</label>
                                <input
                                    type="text"
                                    name="key"
                                    required
                                    dir="ltr"
                                    placeholder="hero_title"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 font-mono text-xs text-slate-100 outline-none focus:border-amber-400"
                                >
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-300">التسمية (عربي)</label>
                                <input
                                    type="text"
                                    name="label_ar"
                                    required
                                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                                >
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-300">التسمية (إنجليزي)</label>
                                <input
                                    type="text"
                                    name="label_en"
                                    dir="ltr"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                                >
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-300">نوع الخانة</label>
                                <select
                                    name="slot_type"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                                >
                                    <option value="text">نص قصير</option>
                                    <option value="textarea">نص طويل</option>
                                    <option value="image">صورة</option>
                                    <option value="list">قايمة</option>
                                    <option value="link">رابط</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-300">الترتيب</label>
                                <input
                                    type="number"
                                    name="sort_order"
                                    value="0"
                                    min="0"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                                >
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <input
                                type="checkbox"
                                name="is_required"
                                value="1"
                                class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-400 focus:ring-amber-400"
                            >
                            خانة إجبارية
                        </label>

                        <button type="submit" class="rounded-lg bg-amber-400 px-4 py-1.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                            إضافة
                        </button>
                    </form>
                </details>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        // منتقي الألوان (templates/partials/color-picker.blade.php) — event delegation واحدة
        // بتخدم أي عدد مجموعات ألوان في الصفحة، من غير تكرار سكريبت لكل نسخة/فورم.
        document.addEventListener('input', function (event) {
            if (!event.target.matches('[data-colors-sync]')) {
                return;
            }

            const group = event.target.closest('[data-colors-group]');
            if (!group) {
                return;
            }

            const key = event.target.dataset.colorKey;
            group.querySelectorAll(`[data-color-key="${key}"]`).forEach((el) => {
                if (el !== event.target) {
                    el.value = event.target.value;
                }
            });

            const colors = {};
            group.querySelectorAll('input[type="color"][data-colors-sync]').forEach((el) => {
                colors[el.dataset.colorKey] = el.value;
            });

            group.querySelector('[data-colors-hidden]').value = JSON.stringify(colors);
        });
    </script>
@endpush
