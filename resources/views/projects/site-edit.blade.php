@extends('layouts.app')

@section('title', 'تعبئة محتوى ' . $project->name . ' — برق')

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
