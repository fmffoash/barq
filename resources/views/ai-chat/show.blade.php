@extends('layouts.app')

@section('title', $project->name . ' — شات الذكاء الاصطناعي')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('ai-chat.create') }}" class="text-sm text-slate-400 transition hover:text-amber-400">
                &rarr; مشروع جديد بالشات
            </a>
            <h1 class="mt-1 text-2xl font-bold text-slate-100">{{ $project->name }}</h1>
            <p class="text-sm text-slate-500">
                {{ $project->template->name }} — {{ $project->template->category }}
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('projects.site.edit', $project) }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 transition hover:border-amber-400 hover:text-amber-400">
                عدّل يدوي
            </a>
            @if ($project->site)
                <a href="{{ $project->site->previewUrl() }}" target="_blank" rel="noopener" dir="ltr" class="rounded-lg border border-amber-400/60 px-3 py-1.5 text-sm font-semibold text-amber-400 transition hover:bg-amber-400 hover:text-slate-950">
                    معاينة الموقع ←
                </a>
            @endif
        </div>
    </div>

    @include('ai-chat._panel', ['project' => $project])
@endsection
