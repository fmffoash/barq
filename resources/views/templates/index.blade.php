@extends('layouts.app')

@section('title', 'القوالب — برق')

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

    @if ($templates->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-900/40 p-10 text-center text-slate-500">
            لسه مفيش أي قالب. ابدأ بإضافة أول قالب.
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
