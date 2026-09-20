@extends('layouts.app')

@section('title', 'تعبئة محتوى ' . $project->name . '')

@section('content')
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('projects.show', $project) }}" class="text-sm text-slate-400 transition hover:text-amber-400">
            &rarr; {{ $project->name }}
        </a>
    </div>

    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-slate-100">تعبئة محتوى الموقع</h1>

        @if ($site)
            <a href="{{ $site->previewUrl() }}" target="_blank" rel="noopener" dir="ltr" class="text-sm text-amber-400 hover:underline">
                معاينة الموقع &larr;
            </a>
        @endif
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

    @if ($slotsBySection->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-900/40 p-10 text-center text-slate-500">
            القالب دا لسه مفيهوش أي خانة محتوى. ضيف خانات من صفحة القالب الأول.
        </div>
    @else
        <form method="POST" action="{{ route('projects.site.suggest', $project) }}" class="mb-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            @csrf
            <h2 class="mb-1.5 text-sm font-semibold text-slate-200">✨ اقترح محتوى بالذكاء الاصطناعي</h2>
            <p class="mb-3 text-xs text-slate-500">
                اكتب وصف قصير للنشاط، والنظام هيقترح محتوى للخانات الفاضية بس — مش هيدعس على أي حاجة مكتوبة بالفعل.
            </p>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input
                    type="text"
                    name="business_description"
                    required
                    maxlength="500"
                    placeholder="مثال: مطعم فطاير في المهندسين"
                    value="{{ old('business_description') }}"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                >
                <button type="submit" class="shrink-0 rounded-lg border border-amber-400/60 bg-transparent px-5 py-2.5 text-sm font-semibold text-amber-400 transition hover:bg-amber-400 hover:text-slate-950">
                    اقترح محتوى
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('projects.site.update', $project) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @foreach ($slotsBySection as $sectionKey => $slots)
                <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                    <h2 class="mb-4 text-xs font-semibold uppercase tracking-wide text-slate-500" dir="ltr">
                        {{ $sectionKey }}
                    </h2>

                    <div class="space-y-5">
                        @foreach ($slots->sortBy('sort_order') as $slot)
                            @php $current = $site?->content($slot->key); @endphp

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-300">
                                    {{ $slot->label() }}
                                    @if ($slot->is_required)
                                        <span class="text-red-400">*</span>
                                    @endif
                                </label>

                                @switch($slot->slot_type)
                                    @case('textarea')
                                        <textarea
                                            name="content[{{ $slot->key }}]"
                                            rows="4"
                                            @required($slot->is_required)
                                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                                        >{{ old("content.{$slot->key}", $current) }}</textarea>
                                        @break

                                    @case('list')
                                        <textarea
                                            name="content[{{ $slot->key }}]"
                                            rows="4"
                                            placeholder="سطر لكل عنصر"
                                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                                        >{{ old("content.{$slot->key}", is_array($current) ? implode("\n", $current) : $current) }}</textarea>
                                        <p class="mt-1 text-xs text-slate-500">اكتب كل عنصر في سطر لوحده.</p>
                                        @break

                                    @case('image')
                                        @if ($current)
                                            <div class="mb-2">
                                                <img src="{{ $current }}" alt="" class="h-24 w-auto rounded-lg border border-slate-800 object-cover">
                                            </div>
                                        @endif
                                        <input
                                            type="file"
                                            name="content_files[{{ $slot->key }}]"
                                            accept="image/*"
                                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-300 outline-none file:mr-3 file:rounded-md file:border-0 file:bg-amber-400 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-950 focus:border-amber-400"
                                        >
                                        @break

                                    @case('link')
                                        <input
                                            type="url"
                                            name="content[{{ $slot->key }}]"
                                            dir="ltr"
                                            value="{{ old("content.{$slot->key}", $current) }}"
                                            @required($slot->is_required)
                                            placeholder="https://"
                                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                                        >
                                        @break

                                    @default
                                        <input
                                            type="text"
                                            name="content[{{ $slot->key }}]"
                                            value="{{ old("content.{$slot->key}", $current) }}"
                                            @required($slot->is_required)
                                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                                        >
                                @endswitch

                                @if (in_array($slot->slot_type, ['text', 'textarea', 'list']))
                                    @php $override = $site?->styleFor($slot->key) ?? ['color' => null, 'font' => null]; @endphp
                                    <details class="mt-2" @if ($override['color'] || $override['font']) open @endif>
                                        <summary class="cursor-pointer text-xs text-amber-400/80 transition hover:text-amber-400">
                                            تخصيص لون/خط الخانة دي بس
                                        </summary>
                                        <div class="mt-2 flex flex-wrap items-center gap-4 rounded-lg border border-slate-800 bg-slate-950/50 p-3">
                                            <label class="flex items-center gap-2 text-xs text-slate-400">
                                                <input
                                                    type="checkbox"
                                                    data-style-color-toggle
                                                    data-target="style-color-{{ $slot->key }}"
                                                    @checked($override['color'])
                                                    class="h-3.5 w-3.5 rounded border-slate-700 bg-slate-950 text-amber-400 focus:ring-amber-400"
                                                >
                                                لون مخصص
                                                <input
                                                    type="color"
                                                    id="style-color-{{ $slot->key }}"
                                                    name="style[{{ $slot->key }}][color]"
                                                    value="{{ old("style.{$slot->key}.color", $override['color'] ?: '#f1f5f9') }}"
                                                    @disabled(! $override['color'])
                                                    class="h-7 w-7 cursor-pointer rounded border-0 bg-transparent p-0"
                                                >
                                            </label>

                                            <label class="flex items-center gap-2 text-xs text-slate-400">
                                                الخط
                                                <select
                                                    name="style[{{ $slot->key }}][font]"
                                                    class="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1 text-xs text-slate-100 outline-none focus:border-amber-400"
                                                >
                                                    <option value="default" @selected(old("style.{$slot->key}.font", $override['font']) === null)>— الخط العام —</option>
                                                    @foreach (\App\Models\TemplateVariant::FONTS as $fontKey => $fontLabel)
                                                        <option value="{{ $fontKey }}" @selected(old("style.{$slot->key}.font", $override['font']) === $fontKey)>{{ $fontLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                    حفظ المحتوى
                </button>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        // "لون مخصص" — الـ checkbox بيفعّل/يوقف مربع اللون؛ مربع لون disabled ملوش قيمة في
        // الفورم خالص وقت الإرسال، فده اللي بيخلي "مفيش تخصيص" يترسل فعلاً بدل ما يبعت لون
        // افتراضي عن طريق الغلط.
        document.addEventListener('change', function (event) {
            if (!event.target.matches('[data-style-color-toggle]')) {
                return;
            }

            const target = document.getElementById(event.target.dataset.target);
            if (target) {
                target.disabled = !event.target.checked;
            }
        });
    </script>
@endpush
