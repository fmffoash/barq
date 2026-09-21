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
        {{-- شبكة مرنة حقيقية (auto-fill) بدل عدد أعمدة ثابت — عدد الكروت جنب بعض بيتحدد
        بعرض الشاشة الفعلي (كل كارت 240px على الأقل)، والباقي بينزل صف تحت تلقائي. كانت قبل
        كده sm:grid-cols-2 lg:grid-cols-3 (تتوقف عند 3 أعمدة مهما اتسعت الشاشة أكتر) —
        فؤاد لاحظ إن الصفحة بتفضل بنفس الشكل ومساحة فاضية على الشاشات الواسعة (2026-09-21). --}}
        <div class="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-5">
            @foreach ($templates as $template)
                @php
                    $colors = $template->defaultVariant()?->colors_json;
                    $heroImage = $template->slots->firstWhere('key', 'hero_image')?->default_value;

                    $fallbackStyle = match (true) {
                        (bool) $heroImage => "background-image: linear-gradient(to bottom, rgba(0,0,0,.15), rgba(0,0,0,.55)), url('{$heroImage}');",
                        (bool) $colors => "background-image: linear-gradient(135deg, {$colors['primary']}, {$colors['background']});",
                        default => '',
                    };
                @endphp

                <a
                    href="{{ route('templates.show', $template) }}"
                    class="block overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 transition hover:border-amber-400/60"
                >
                    {{-- رجعنا للمعاينة الخفيفة (صورة الفئة + تدرّج ألوان القالب، بدون أي
                    نداء سيرفر إضافي — 2026-09-21). كنا جرّبنا معاينة حقيقية 100% بـiframe
                    (templates.preview) لكل قالب، لكن فؤاد شاف السيرفر بيتقل واضح لحظة فتح
                    الصفحة — 300 قالب حتى مع lazy-loading يعني عشرات الـiframes بتتحمّل مرة
                    واحدة على pool صغير (max_children=2) وسيرفر 4 أنوية بس. مش نمط مناسب
                    للحجم ده، بغض النظر عن أي تحسين إضافي في الطريقة. الصورة/اللون بيدوا
                    إحساس حقيقي بمزاج القالب (نفس صور الفئة الحقيقية اللي اترفعت) من غير أي
                    تكلفة على السيرفر — واسم التصميم (badge) بيدي تلميح إضافي عن الشكل. --}}
                    <div
                        class="relative flex h-56 items-end bg-cover bg-center"
                        style="{{ $fallbackStyle }}"
                    >
                        @unless ($heroImage || $colors)
                            <span class="mx-auto mb-auto mt-auto text-3xl opacity-30">🖼️</span>
                        @endunless

                        @if ($template->kind === 'landing')
                            <span class="m-2 rounded-full bg-slate-950/70 px-2.5 py-1 text-xs text-slate-200 backdrop-blur">
                                {{ \App\Models\Template::layoutLabel($template->layout) }}
                            </span>
                        @endif
                    </div>

                    <div class="p-5">
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
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
