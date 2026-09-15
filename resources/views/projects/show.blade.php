@extends('layouts.app')

@section('title', $project->name . ' — برق')

@php
    $statusLabels = ['draft' => 'مسودة', 'generated' => 'الموقع اتولّد', 'delivered' => 'اتسلّم للعميل'];
    $siteStatusLabels = ['draft' => 'مسودة', 'published' => 'منشور', 'archived' => 'متوقف'];
    $siteStatusColors = [
        'draft' => 'bg-slate-800 text-slate-400',
        'published' => 'bg-emerald-900/40 text-emerald-300',
        'archived' => 'bg-red-950/50 text-red-300',
    ];
@endphp

@section('content')
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('projects.index') }}" class="text-sm text-slate-400 transition hover:text-amber-400">
            &rarr; المشاريع
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

    {{-- بيانات المشروع --}}
    <div class="mb-8 flex items-start justify-between gap-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
        <div>
            <div class="mb-1 flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-100">{{ $project->name }}</h1>
                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400">
                    {{ $statusLabels[$project->status] ?? $project->status }}
                </span>
            </div>

            <p class="text-sm text-slate-500">
                {{ $project->template?->name ?? 'بدون قالب' }}
                @if ($project->variant)
                    &middot; {{ $project->variant->name }}
                @endif
            </p>

            @if ($project->contact_name || $project->contact_phone || $project->contact_email)
                <p class="mt-2 text-sm text-slate-400">
                    {{ $project->contact_name }}
                    @if ($project->contact_phone)
                        &middot; <span dir="ltr">{{ $project->contact_phone }}</span>
                    @endif
                    @if ($project->contact_email)
                        &middot; <span dir="ltr">{{ $project->contact_email }}</span>
                    @endif
                </p>
            @endif
        </div>

        <div class="flex shrink-0 gap-2">
            @if ($project->status !== 'delivered')
                <form method="POST" action="{{ route('projects.deliver', $project) }}" onsubmit="return confirm('تعليم المشروع كمُسلَّم للعميل؟');">
                    @csrf
                    <button type="submit" class="rounded-lg border border-emerald-800 px-3 py-1.5 text-sm text-emerald-300 transition hover:bg-emerald-950/40">
                        تعليم كمُسلَّم
                    </button>
                </form>
            @endif

            <a
                href="{{ route('projects.edit', $project) }}"
                class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 transition hover:border-amber-400 hover:text-amber-400"
            >
                تعديل
            </a>

            <form
                method="POST"
                action="{{ route('projects.destroy', $project) }}"
                onsubmit="return confirm('حذف المشروع دا هيمسح موقعه الناتج كمان. متأكد؟');"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-900 px-3 py-1.5 text-sm text-red-400 transition hover:bg-red-950/40">
                    حذف
                </button>
            </form>
        </div>
    </div>

    {{-- الموقع الناتج --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-slate-100">الموقع الناتج</h2>
                @if ($project->site)
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $siteStatusColors[$project->site->status] ?? 'bg-slate-800 text-slate-400' }}">
                        {{ $siteStatusLabels[$project->site->status] ?? $project->site->status }}
                    </span>
                @endif
            </div>

            @if ($project->site)
                <div class="flex shrink-0 gap-2">
                    <a
                        href="{{ route('projects.site.edit', $project) }}"
                        class="rounded-lg bg-amber-400 px-4 py-1.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300"
                    >
                        تعبئة المحتوى
                    </a>

                    @if ($project->site->status === 'published')
                        <form method="POST" action="{{ route('projects.site.unpublish', $project) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 transition hover:border-red-800 hover:text-red-400">
                                إلغاء النشر
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('projects.site.publish', $project) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-emerald-800 px-3 py-1.5 text-sm text-emerald-300 transition hover:bg-emerald-950/40">
                                نشر الموقع
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        @if ($project->site)
            <a href="{{ $project->site->previewUrl() }}" target="_blank" rel="noopener" dir="ltr" class="text-sm text-amber-400 hover:underline">
                {{ $project->site->previewUrl() }}
            </a>

            @if ($project->site->last_generated_at)
                <p class="mt-2 text-xs text-slate-500">
                    آخر تحديث للمحتوى: {{ $project->site->last_generated_at->diffForHumans() }}
                </p>
            @endif
        @else
            <p class="text-sm text-slate-500">مفيش موقع مربوط بالمشروع دا.</p>
        @endif
    </div>

    {{-- حفظ كقالب جديد --}}
    @if ($project->template)
        <div class="mt-8 rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            <h2 class="mb-1 text-lg font-semibold text-slate-100">احفظ كقالب جديد</h2>
            <p class="mb-4 text-sm text-slate-500">
                بيولّد قالب مستقل بنفس خانات "{{ $project->template->name }}" — تعديله بعد كده متأثرش على القالب الأصلي.
            </p>

            <form method="POST" action="{{ route('templates.store-from-project', $project) }}" class="space-y-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="template_name" class="mb-1.5 block text-sm font-medium text-slate-300">اسم القالب الجديد</label>
                        <input
                            id="template_name"
                            type="text"
                            name="name"
                            value="{{ old('name', $project->name) }}"
                            required
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                        >
                    </div>

                    <div>
                        <label for="template_category" class="mb-1.5 block text-sm font-medium text-slate-300">التصنيف (اختياري)</label>
                        <input
                            id="template_category"
                            type="text"
                            name="category"
                            value="{{ old('category', $project->template->category) }}"
                            placeholder="{{ $project->template->category ?: 'مفيش تصنيف' }}"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                        >
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="with_content" value="1" checked class="rounded border-slate-700 bg-slate-950 text-amber-400 focus:ring-amber-400">
                    احفظ بمحتوى الموقع الحالي كقيم افتراضية (بدل ما القالب الجديد يبدأ فاضي)
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg border border-amber-400 px-4 py-2 text-sm font-semibold text-amber-400 transition hover:bg-amber-400/10">
                        احفظ كقالب جديد
                    </button>
                </div>
            </form>
        </div>
    @endif
@endsection
