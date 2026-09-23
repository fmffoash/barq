{{--
    "سيجنتشر" — تصميم فاخر بطابع مطاعم/ضيافة راقٍ: هيرو بصورة خلفية كاملة وتراكب متدرّج،
    عناوين أقسام بخط فاصل رفيع من الجهتين، قسم القوائم (list) بيترندر كصفوف "منيو" حقيقية
    (اسم — خط منقّط — سعر لو النص فيه "—")، وقسم آراء العملاء بيترندر ككروت اقتباس بعلامة
    تنصيص كبيرة بدل نفس شكل صفوف المنيو (بنميّزهم بس بالاسم section_key، زي باقي التصميمات
    اللي بتفرّق شكل القسم بمعرفة نوعه من موقعه/خاناته مش باسمه الحرفي — هنا استثناء واحد بسيط
    لتحسين شكل "آراء العملاء" تحديداً، وبيرجع لشكل صفوف المنيو العادي لو مفيش كلمة "testimonial"
    في مفتاح القسم). مناسب لأي نشاط عايز طابع "راقي/فاخر" مش بس مطاعم — نفس بنية الخانات
    العامة (Template::LAYOUTS) بيشتغل معاها.
--}}
<style>
    .signature-dropcap::first-letter {
        float: right;
        margin-left: 0.5rem;
        line-height: 0.8;
        font-size: 3.4em;
        font-weight: 800;
        color: var(--site-primary);
    }
</style>

@php
    $navSections = $sections->skip(1);
    $navLabel = fn (string $key) => match ($key) {
        'hero' => 'الرئيسية', 'about' => 'من نحن', 'services', 'menu' => 'قائمتنا',
        'gallery' => 'معرض الصور', 'testimonials' => 'آراء العملاء', 'pricing' => 'الأسعار',
        'faq' => 'الأسئلة الشائعة', 'contact', 'cta' => 'تواصل معنا',
        default => \Illuminate\Support\Str::of($key)->replace(['_', '-'], ' ')->trim()->title()->toString(),
    };
@endphp

@if ($navSections->isNotEmpty())
    <div class="sticky top-0 z-30 border-b backdrop-blur-md" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent); background-color: color-mix(in srgb, var(--site-background) 75%, transparent);">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5 sm:px-12">
            <a href="#{{ $sections->first()['key'] }}" class="text-xl font-black tracking-wide">{{ $project->name }}</a>
            <ul class="hidden flex-wrap items-center gap-8 text-sm font-semibold uppercase tracking-widest sm:flex">
                @foreach ($navSections as $s)
                    <li><a href="#{{ $s['key'] }}" class="transition hover:opacity-70">{{ $navLabel($s['key']) }}</a></li>
                @endforeach
            </ul>
        </nav>
    </div>
@endif

@forelse ($sections as $section)
    @php
        $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
        $listItems = $section['items']->where('slot.slot_type', 'list');
        $imageItems = $section['items']->where('slot.slot_type', 'image');
        $linkItems = $section['items']->where('slot.slot_type', 'link');

        // قسم زي "تواصل معنا" بيبقى فيه خانتين نوعهم "text" (العنوان + ملاحظة قصيرة) —
        // لو الاتنين اترندروا كـ h2 هيبانوا عنوانين ضخمين فوق بعض. أول خانة نصية بس بتاخد
        // شكل العنوان، وأي خانة تانية (نصية كانت أو textarea) بترندر كنص مساند أصغر.
        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    @endphp

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section
            id="{{ $section['key'] }}"
            class="relative flex min-h-[88vh] items-center justify-center overflow-hidden px-6 py-24 text-center sm:px-12"
        >
            @if ($heroImage)
                {{-- صورة حقيقية (<img data-slot>) بدل background-image مباشر على الـsection —
                عشان تكبير/تحريك الصورة (المرحلة 2، transform:scale/object-position على
                [data-slot]) يشتغل، ونفس آلية overflow-hidden الموجودة على الـsection أصلاً
                بتمنعها تكسر الحواف. --}}
                {{-- صفر z-index سالب هنا عمداً (عكس فرع الـ@else الديكوري تحت) — عنصر بـ
                z-index سالب بيترندر وراء الخلفية الخاصة بأقرب جد له عنده stacking context
                (الـsection هنا relative)، يعني أي دوس فاضي على الـsection هيوصل للـsection
                نفسه مش للصورة (اتأكدنا منها فعلياً وقت اختبار المحرر البصري). ترتيبها في
                الـDOM (قبل محتوى النص) كافي وحده إنها تفضل وراه بصرياً. --}}
                <div class="absolute inset-0">
                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="" class="h-full w-full object-cover" aria-hidden="true">
                    <div class="pointer-events-none absolute inset-0" style="background-image: linear-gradient(180deg, color-mix(in srgb, var(--site-background) 35%, transparent) 0%, var(--site-background) 94%);"></div>
                </div>
            @else
                <div class="pointer-events-none absolute inset-0 -z-10">
                    <div class="absolute left-1/2 top-1/2 h-[36rem] w-[36rem] -translate-x-1/2 -translate-y-1/2 rounded-full opacity-20 blur-3xl" style="background-color: var(--site-primary);"></div>
                </div>
            @endif
            {{-- pointer-events-none على الحاوية + pointer-events-auto على كل عنصر قابل
            للتعديل فعلياً (المرحلة 2) — من غيرها مساحات الفراغ حوالين النص بتمنع الدوس على
            صورة الهيرو تحتها (الشارة الزخرفية بتورّث pointer-events:none زي ما هي، مفيهاش
            data-slot أصلاً). --}}
            <div class="mx-auto flex max-w-3xl flex-col items-center gap-6" style="pointer-events: none;">
                <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-[0.35em]" style="color: var(--site-primary);">
                    <span class="h-px w-10" style="background-color: var(--site-primary);"></span>
                    {{ $project->name }}
                    <span class="h-px w-10" style="background-color: var(--site-primary);"></span>
                </div>
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-5xl font-black leading-tight sm:text-7xl" style="pointer-events: auto;">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg" style="color: var(--site-muted); pointer-events: auto;">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a data-slot="{{ $item['slot']->key }}" href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 border-2 px-10 py-4 text-base font-bold uppercase tracking-widest transition hover:opacity-80" style="border-color: var(--site-primary); color: var(--site-primary); pointer-events: auto;">{{ $item['slot']->label() }}</a>
                @endforeach
            </div>
        </section>
    @elseif ($section['kind'] === 'gallery')
        <section id="{{ $section['key'] }}" class="relative px-6 py-24 sm:px-12">
            <div class="mx-auto max-w-6xl">
                @if ($textItems->isNotEmpty())
                    <div class="mb-14 flex flex-col items-center gap-4 text-center">
                        <span class="h-px w-16" style="background-color: var(--site-primary);"></span>
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-black">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($imageItems as $item)
                        <div class="aspect-[4/3] w-full overflow-hidden">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" loading="lazy" class="h-full w-full object-cover">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @elseif ($section['kind'] === 'list')
        @php $isTestimonials = str_contains($section['key'], 'testimonial'); @endphp
        <section id="{{ $section['key'] }}" class="relative px-6 py-24 sm:px-12" style="background-color: color-mix(in srgb, var(--site-surface) 55%, transparent);">
            <div class="mx-auto max-w-5xl">
                @if ($textItems->isNotEmpty())
                    <div class="mb-14 flex flex-col items-center gap-4 text-center">
                        <span class="h-px w-16" style="background-color: var(--site-primary);"></span>
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-black">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="max-w-lg" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif

                @foreach ($listItems as $item)
                    @if ($isTestimonials)
                        <div class="grid gap-6 sm:grid-cols-2">
                            @foreach ((array) $item['value'] as $listItem)
                                <div data-slot="{{ $item['slot']->key }}" class="rounded-sm border p-8" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
                                    <div class="text-5xl leading-none" style="color: var(--site-primary);">”</div>
                                    <p class="mt-3 text-lg italic leading-relaxed">{{ $listItem }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="grid gap-x-16 sm:grid-cols-2">
                            @foreach ((array) $item['value'] as $listItem)
                                @php $parts = collect(explode('—', $listItem, 2))->map(fn ($p) => trim($p)); @endphp
                                <div data-slot="{{ $item['slot']->key }}" class="flex items-baseline gap-3 border-b py-4" style="border-color: color-mix(in srgb, var(--site-text) 12%, transparent);">
                                    <span class="text-lg font-bold">{{ $parts->get(0) }}</span>
                                    <span class="mb-1 flex-1 border-b border-dotted" style="border-color: color-mix(in srgb, var(--site-muted) 45%, transparent);"></span>
                                    @if ($parts->count() > 1)
                                        <span class="whitespace-nowrap text-lg font-bold" style="color: var(--site-primary);">{{ $parts->get(1) }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative border-y px-6 py-20 text-center sm:px-12" style="border-color: color-mix(in srgb, var(--site-primary) 40%, transparent); background-color: color-mix(in srgb, var(--site-primary) 8%, transparent);">
            <div class="mx-auto flex max-w-2xl flex-col items-center gap-5">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-black">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a data-slot="{{ $item['slot']->key }}" href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block px-10 py-4 text-base font-bold uppercase tracking-widest transition hover:opacity-90" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                @endforeach
            </div>
        </section>
    @else
        <section id="{{ $section['key'] }}" class="relative px-6 py-24 sm:px-12">
            <div class="mx-auto grid max-w-5xl gap-10 sm:grid-cols-[0.8fr_1.2fr] sm:items-start">
                <div class="flex flex-col gap-4">
                    <span class="h-px w-16" style="background-color: var(--site-primary);"></span>
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-black leading-tight">{!! $heading['value'] !!}</h2>
                    @endif
                </div>
                <div class="flex flex-col gap-4">
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="signature-dropcap text-lg leading-relaxed" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        <a data-slot="{{ $item['slot']->key }}" href="{{ $item['value'] }}" target="_blank" rel="noopener" class="inline-block w-fit px-8 py-3 text-sm font-bold uppercase tracking-widest" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    <footer class="border-t px-6 py-10 text-center" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
        <p class="text-lg font-black">{{ $project->name }}</p>
        <p class="mt-2 text-sm" style="color: var(--site-muted);">© {{ now()->year }} — جميع الحقوق محفوظة</p>
    </footer>
@endif
