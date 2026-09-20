@extends('layouts.app')

@section('title', 'الرئيسية')

@php
    $statusLabels = ['draft' => 'مسودة', 'generated' => 'الموقع اتولّد', 'delivered' => 'اتسلّم للعميل'];
    $statusColors = [
        'draft' => 'bg-slate-800 text-slate-400',
        'generated' => 'bg-amber-400/10 text-amber-400',
        'delivered' => 'bg-emerald-900/40 text-emerald-300',
    ];
@endphp

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-white">أهلاً بيك 👋</h1>

    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('templates.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 transition hover:border-amber-400/60">
            <p class="text-sm text-slate-500">القوالب</p>
            <p class="mt-1 text-3xl font-bold text-slate-100">{{ $templatesCount }}</p>
        </a>

        <a href="{{ route('projects.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 transition hover:border-amber-400/60">
            <p class="text-sm text-slate-500">المشاريع</p>
            <p class="mt-1 text-3xl font-bold text-slate-100">{{ $projectsCount }}</p>
        </a>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            <p class="text-sm text-slate-500">مواقع منشورة</p>
            <p class="mt-1 text-3xl font-bold text-slate-100">{{ $publishedSitesCount }}</p>
        </div>
    </div>

    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-100">آخر المشاريع</h2>

        <div class="flex gap-2">
            <a href="{{ route('templates.create') }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 transition hover:border-amber-400 hover:text-amber-400">
                + قالب جديد
            </a>
            <a href="{{ route('projects.create') }}" class="rounded-lg bg-amber-400 px-3 py-1.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                + مشروع جديد
            </a>
        </div>
    </div>

    @if ($recentProjects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-900/40 p-10 text-center text-slate-500">
            <p>لسه مفيش أي مشروع.</p>
            <p class="mt-1 text-sm text-slate-600">
                @if ($templatesCount === 0)
                    ابدأ بإضافة أول قالب، وبعدين اعمل مشروع منه.
                @else
                    ابدأ بإضافة أول مشروع.
                @endif
            </p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($recentProjects as $project)
                <a
                    href="{{ route('projects.show', $project) }}"
                    class="block rounded-2xl border border-slate-800 bg-slate-900/60 p-5 transition hover:border-amber-400/60"
                >
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-slate-100">{{ $project->name }}</h3>

                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs {{ $statusColors[$project->status] ?? 'bg-slate-800 text-slate-400' }}">
                            {{ $statusLabels[$project->status] ?? $project->status }}
                        </span>
                    </div>

                    <p class="text-sm text-slate-500">
                        {{ $project->template?->name ?? 'بدون قالب' }}
                        @if ($project->variant)
                            &middot; {{ $project->variant->name }}
                        @endif
                    </p>
                </a>
            @endforeach
        </div>
    @endif
@endsection
