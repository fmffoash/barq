@extends('layouts.app')

@section('title', 'مشروع جديد')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('projects.index') }}" class="text-sm text-slate-400 transition hover:text-amber-400">
                &rarr; المشاريع
            </a>
        </div>

        @if (! $template)
            {{-- الخطوة الأولى: اختيار القالب --}}
            <h1 class="mb-6 text-2xl font-bold text-slate-100">مشروع جديد — اختار القالب</h1>

            {{-- بحث وفلترة --}}
            <form method="GET" action="{{ route('projects.create') }}" class="mb-6 flex flex-wrap gap-3">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="بحث بالاسم..."
                    class="min-w-[160px] flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                >

                <select
                    name="category"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
                >
                    <option value="">كل التصنيفات</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>

                <button type="submit" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 transition hover:border-amber-400 hover:text-amber-400">
                    فلترة
                </button>

                @if (request('q') || request('category'))
                    <a href="{{ route('projects.create') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 transition hover:text-slate-300">
                        إلغاء الفلترة
                    </a>
                @endif
            </form>

            @if ($templates->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-900/40 p-10 text-center text-slate-500">
                    @if (request('q') || request('category'))
                        مفيش قوالب مطابقة للفلترة دي.
                    @else
                        لسه مفيش أي قالب متاح.
                        <a href="{{ route('templates.create') }}" class="text-amber-400 hover:underline">ضيف قالب الأول</a>.
                    @endif
                </div>
            @else
                {{-- صورة شكل كل قالب الحقيقي + "معاينة" بتفتح الشكل كامل في تاب جديد (2026-10-06)
                — عشان فؤاد يشوف القالب قبل ما يختاره. --}}
                <div class="grid grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-4">
                    @foreach ($templates as $t)
                        <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 transition hover:border-amber-400/60">
                            @include('templates.partials.preview-image', ['template' => $t, 'thumbnail' => $thumbnails[$t->id] ?? null])

                            <div class="flex flex-1 flex-col p-4">
                                <h2 class="mb-1 font-semibold text-slate-100">{{ $t->name }}</h2>
                                <p class="mb-3 text-sm text-slate-500">
                                    {{ $t->category ?: 'بدون تصنيف' }} &middot;
                                    {{ $t->kind === 'wordpress' ? 'ووردبريس' : 'صفحة هبوط' }}
                                </p>
                                <div class="mt-auto flex gap-2">
                                    @if ($t->kind === 'landing')
                                        <a href="{{ route('templates.preview', $t) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 transition hover:border-amber-400 hover:text-amber-400">👁 معاينة</a>
                                    @endif
                                    <a href="{{ route('projects.create', ['template' => $t->id]) }}" class="rounded-lg bg-amber-400 px-3 py-1.5 text-xs font-semibold text-slate-950 transition hover:bg-amber-300">اختار القالب ده</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            {{-- الخطوة الثانية: بيانات المشروع --}}
            <h1 class="mb-1 text-2xl font-bold text-slate-100">مشروع جديد</h1>
            <p class="mb-6 text-sm text-slate-500">
                القالب: <span class="text-slate-300">{{ $template->name }}</span> &middot;
                <a href="{{ route('projects.create') }}" class="text-amber-400 hover:underline">غيّر القالب</a>
            </p>

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-800 bg-red-950/50 px-4 py-3 text-sm text-red-300">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('projects.store') }}" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                @csrf
                <input type="hidden" name="template_id" value="{{ $template->id }}">

                <div class="space-y-5">
                    @if ($template->variants->isNotEmpty())
                        <div>
                            <label for="template_variant_id" class="mb-1.5 block text-sm font-medium text-slate-300">نسخة القالب</label>
                            <select
                                id="template_variant_id"
                                name="template_variant_id"
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                            >
                                @php $defaultVariant = old('template_variant_id', $template->defaultVariant()?->id); @endphp
                                @foreach ($template->variants as $variant)
                                    <option value="{{ $variant->id }}" @selected((string) $defaultVariant === (string) $variant->id)>
                                        {{ $variant->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('template_variant_id')
                                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    @include('projects._fields')
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                        إنشاء المشروع
                    </button>
                </div>
            </form>
        @endif
    </div>
@endsection
