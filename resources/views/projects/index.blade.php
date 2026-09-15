@extends('layouts.app')

@section('title', 'المشاريع — برق')

@php
    $statusLabels = ['draft' => 'مسودة', 'generated' => 'الموقع اتولّد', 'delivered' => 'اتسلّم للعميل'];
    $statusColors = [
        'draft' => 'bg-slate-800 text-slate-400',
        'generated' => 'bg-amber-400/10 text-amber-400',
        'delivered' => 'bg-emerald-900/40 text-emerald-300',
    ];
@endphp

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-100">المشاريع</h1>

        <a
            href="{{ route('projects.create') }}"
            class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-300"
        >
            + مشروع جديد
        </a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($projects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-900/40 p-10 text-center text-slate-500">
            لسه مفيش أي مشروع. ابدأ بإضافة أول مشروع.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
                <a
                    href="{{ route('projects.show', $project) }}"
                    class="block rounded-2xl border border-slate-800 bg-slate-900/60 p-5 transition hover:border-amber-400/60"
                >
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <h2 class="font-semibold text-slate-100">{{ $project->name }}</h2>

                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs {{ $statusColors[$project->status] ?? 'bg-slate-800 text-slate-400' }}">
                            {{ $statusLabels[$project->status] ?? $project->status }}
                        </span>
                    </div>

                    <p class="mb-4 text-sm text-slate-500">
                        {{ $project->template?->name ?? 'بدون قالب' }}
                        @if ($project->variant)
                            &middot; {{ $project->variant->name }}
                        @endif
                    </p>

                    <div class="flex gap-4 text-xs text-slate-500">
                        @if ($project->contact_name)
                            <span>{{ $project->contact_name }}</span>
                        @endif
                        @if ($project->site)
                            <span dir="ltr">{{ $project->site->slug }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
