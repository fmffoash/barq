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
                    @if ($template->kind === 'landing')
                        {{-- معاينة حقيقية 100% (2026-09-21) — iframe بيرندر نفس شِل الموقع
                        الحقيقي (templates.preview) بمحتوى القالب الافتراضي. المحاولة الأولى
                        بـCSS بحت (container query units + loading="lazy" الأصلي) طلعت
                        الكروت فاضية عند فؤاد حياً — على الأرجح تفاعل الـtransform مع الـlazy
                        الأصلي للمتصفح. بدّلتها بـJS صريح (IntersectionObserver بيحمّل
                        data-src بس لما الكارت يقرب من الشاشة فعلاً، وResizeObserver بيحسب
                        نسبة التصغير من عرض الكارت الفعلي) — أبطأ شوية بس قابل للتشخيص
                        ومضمون شغله في كل المتصفحات. --}}
                        <div class="template-preview-frame relative h-56 overflow-hidden bg-slate-950">
                            <iframe
                                data-src="{{ route('templates.preview', $template) }}"
                                tabindex="-1"
                                title="معاينة {{ $template->name }}"
                                style="position: absolute; top: 0; left: 0; width: 1280px; height: 800px; transform-origin: top left; border: 0; pointer-events: none;"
                            ></iframe>
                        </div>
                    @else
                        {{-- قوالب ووردبريس مالهاش رندر لاندنج بيدج حقيقي نعرضه هنا — نفس
                        المعاينة التقريبية (صورة/لون) القديمة. --}}
                        <div
                            class="flex h-56 items-center justify-center bg-cover bg-center"
                            style="{{ $fallbackStyle }}"
                        >
                            @unless ($heroImage || $colors)
                                <span class="text-3xl opacity-30">🖼️</span>
                            @endunless
                        </div>
                    @endif

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

@push('scripts')
    <script>
        // معاينات القوالب المصغّرة (2026-09-21) — تحميل + تصغير بـJS صريح بدل CSS بحت
        // (container queries + loading="lazy" الأصلي طلعوا فاضيين حياً). IntersectionObserver
        // بيحمّل الـiframe (يحط src من data-src) بس لما الكارت يقرب من الشاشة (rootMargin
        // 400px مسبق)، وResizeObserver بيحسب نسبة التصغير الفعلية من عرض الكارت الحقيقي في
        // كل مرة يتغيّر (فتح/قفل نافذة، تغيير حجم الشاشة) بدل قيمة CSS ثابتة مبنية على افتراض.
        (function () {
            var frames = document.querySelectorAll('.template-preview-frame');
            if (!frames.length) return;

            function applyScale(wrapper) {
                var iframe = wrapper.querySelector('iframe');
                if (!iframe) return;
                var scale = wrapper.clientWidth / 1280;
                iframe.style.transform = 'scale(' + scale + ')';
            }

            var resizeObserver = new ResizeObserver(function (entries) {
                entries.forEach(function (entry) {
                    applyScale(entry.target);
                });
            });

            var loadObserver = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var wrapper = entry.target;
                    var iframe = wrapper.querySelector('iframe');
                    if (iframe && !iframe.src) {
                        iframe.src = iframe.dataset.src;
                        applyScale(wrapper);
                    }
                    obs.unobserve(wrapper);
                });
            }, {rootMargin: '400px 0px'});

            frames.forEach(function (wrapper) {
                resizeObserver.observe(wrapper);
                loadObserver.observe(wrapper);
            });
        })();
    </script>
@endpush
