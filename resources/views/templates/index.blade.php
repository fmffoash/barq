@extends('layouts.app')

@section('title', 'القوالب')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-100">القوالب</h1>

        <a
            href="{{ route('templates.create') }}"
            class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-300"
        >
            + قالب جديد
        </a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    {{-- بحث وفلترة --}}
    <form method="GET" action="{{ route('templates.index') }}" class="mb-6 flex flex-wrap gap-3">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="بحث بالاسم..."
            class="min-w-[200px] flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-slate-100 outline-none focus:border-amber-400"
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
            <a href="{{ route('templates.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 transition hover:text-slate-300">
                إلغاء الفلترة
            </a>
        @endif
    </form>

    @if ($templates->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-900/40 p-10 text-center text-slate-500">
            @if (request('q') || request('category'))
                مفيش قوالب مطابقة للفلترة دي.
            @else
                لسه مفيش أي قالب. ابدأ بإضافة أول قالب.
            @endif
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($templates as $template)
                <a
                    href="{{ route('templates.show', $template) }}"
                    class="block rounded-2xl border border-slate-800 bg-slate-900/60 p-5 transition hover:border-amber-400/60"
                >
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <h2 class="font-semibold text-slate-100">{{ $template->name }}</h2>

                        @if (! $template->is_active)
                            <span class="shrink-0 rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400">معطّل</span>
                        @endif
                    </div>

                    <p class="mb-4 text-sm text-slate-500">
                        {{ $template->category ?: 'بدون تصنيف' }} &middot;
                        {{ $template->kind === 'wordpress' ? 'ووردبريس' : 'صفحة هبوط' }}
                        @if ($template->kind === 'landing')
                            &middot;
                            {{ \App\Models\Template::layoutLabel($template->layout) }}
                        @endif
                    </p>

                    <div class="flex gap-4 text-xs text-slate-500">
                        <span>{{ $template->variants_count }} نسخة</span>
                        <span>{{ $template->slots_count }} خانة محتوى</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
