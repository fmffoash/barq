{{--
    "دوتون" — بوستر إعلاني جريء: الصور بتأثير ثنائي اللون (أبيض وأسود + طبقة باللون الأساسي
    بـ mix-blend-mode) ومعاها كتل لونية صلبة وطباعة ضخمة.
    (2026-10-10) اتعاد تصميمه: الهيرو بقى "بوستر" نصّين (كتلة لونية بالعنوان الضخم جنب صورة
    دوتون بطول الشاشة) بدل عنوان صغير فوق صورة باهتة، والخدمات كتل ألوان بأحجام مختلفة، المعرض
    ألواح طولية بتاخد لونها الحقيقي لما الماوس يقف عليها، والآراء حزام بلون أساسي. النص فوق اللون
    الأساسي دايماً --site-on-primary (كان لون الخلفية — فاتح على فاتح في بعض اللوحات).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img> (التأثير filter، مش transform).
--}}
@php
    $onPrimaryBlock = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $inkBlock = 'background-color: var(--site-text); color: var(--site-background);';
    $surfaceBlock = 'background-color: var(--site-surface); color: var(--site-text);';
    $invertButton = 'background-color: var(--site-on-primary); color: var(--site-primary);';
    $primaryButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    // جوّه حزام بلون أساسي: أي حاجة بتستخدم --site-primary/--site-muted (البارشيالز المشتركة)
    // بتاخد لون النص المقروء فوق الأساسي بدل ما تختفي.
    $onPrimaryScope = '--site-primary: var(--site-on-primary); --site-muted: color-mix(in srgb, var(--site-on-primary) 82%, transparent); --site-surface: color-mix(in srgb, var(--site-on-primary) 12%, transparent);';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
    $blockFills = [$onPrimaryBlock, $inkBlock, $surfaceBlock];
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="relative z-30" style="{{ $inkBlock }}">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-10">
            <a href="#top" class="min-w-0 max-w-[55vw] truncate text-xl font-black sm:max-w-sm">{{ $project->name }}</a>
            <ul class="hidden items-center gap-7 text-sm font-bold md:flex">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="px-4 py-2 text-sm font-black transition hover:opacity-85" style="{{ $primaryButton }}">تواصل معنا</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md', 'sharp' => true, 'panelStyle' => $inkBlock]])
            </div>
        </div>
    </nav>
@endif

@forelse ($sections as $section)
    @php
        $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
        $listItems = $section['items']->where('slot.slot_type', 'list');
        $imageItems = $section['items']->where('slot.slot_type', 'image')->values();
        $linkItems = $section['items']->where('slot.slot_type', 'link');

        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
        $alt = trim(strip_tags((string) ($heading['value'] ?? ''))) ?: $project->name;
    @endphp

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" @class(['relative grid min-h-[82vh]', 'lg:grid-cols-2' => $heroImage])>
            <div class="flex flex-col justify-center gap-7 px-6 py-16 sm:px-12 lg:px-16" style="{{ $onPrimaryBlock }}">
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.8rem,6.4vw,5.6rem)] font-black leading-[1.1]">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-xl font-medium leading-loose" style="opacity: .9;">{!! $item['value'] !!}</p>
                @endforeach
                <div class="flex flex-wrap gap-3">
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-10 py-4 text-lg font-black transition hover:opacity-90', 'style' => $invertButton]])
                    @endforeach
                    @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                        @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'px-10 py-4 text-lg font-black transition hover:opacity-90', 'style' => $invertButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
                    @endif
                </div>
            </div>
            @if ($heroImage)
                {{-- الصورة دوتون: أبيض وأسود (filter) + طبقة لون أساسي multiply — الطبقة
                pointer-events-none عشان الدوس يوصل للصورة في المحرر المباشر. موبايل: فوق الكلام. --}}
                <div class="relative order-first h-[46vh] overflow-hidden lg:order-none lg:h-auto">
                    <div class="absolute inset-0">
                        <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover grayscale contrast-125" loading="eager" fetchpriority="high">
                    </div>
                    <div class="pointer-events-none absolute inset-0 mix-blend-multiply" style="background-color: var(--site-primary);"></div>
                    <div class="pointer-events-none absolute inset-0 mix-blend-screen" style="background-color: color-mix(in srgb, var(--site-primary) 18%, transparent);"></div>
                </div>
            @endif
        </section>

    @elseif ($section['kind'] === 'gallery')
        {{-- ألواح طولية دوتون جنب بعض (بتاخد لونها الحقيقي لما الماوس يقف عليها). موبايل عمودين. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-7xl flex-col gap-10">
                <div class="flex flex-col items-start gap-3">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="px-3 py-1 text-sm font-black" style="{{ $onPrimaryBlock }}">{{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.2rem,5vw,4rem)] font-black leading-[1.15]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:h-[28rem] sm:gap-2">
                    @foreach ($imageItems as $item)
                        <div @class(['group relative aspect-[3/4] overflow-hidden sm:aspect-auto sm:h-full sm:flex-1', 'col-span-2' => $loop->last && $imageItems->count() % 2 === 1])>
                            <div class="absolute inset-0">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover grayscale contrast-125 transition duration-500 group-hover:grayscale-0" loading="lazy">
                            </div>
                            <div class="pointer-events-none absolute inset-0 mix-blend-multiply transition duration-500 group-hover:opacity-0" style="background-color: var(--site-primary);"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        {{-- كتل ألوان صلبة بأحجام مختلفة (أنصاص وأتلات) بتملّي كل صف بالظبط. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-7xl flex-col gap-10">
                <div class="flex flex-col items-start gap-3">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="px-3 py-1 text-sm font-black" style="{{ $inkBlock }}">{{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.2rem,5vw,4rem)] font-black leading-[1.15]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                </div>
                @foreach ($listItems as $item)
                    @php $entries = (array) $item['value']; $spans = \App\Services\SiteRenderer::rowSpans(count($entries)); @endphp
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-6">
                        @foreach ($entries as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                            <div data-slot="{{ $item['slot']->key }}" class="flex min-h-40 flex-col justify-between gap-6 p-6 sm:min-h-52 sm:p-8 {{ $spans[$i] }}" style="{{ $blockFills[$i % count($blockFills)] }}">
                                <span class="text-5xl font-black leading-none" style="opacity: .35;">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="flex flex-col gap-2">
                                    <span class="text-xl font-black leading-snug sm:text-2xl">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="text-base font-medium" style="opacity: .85;">{{ $e['desc'] }}</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-8 py-3.5 text-base font-black transition hover:opacity-90', 'style' => $primaryButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24" style="{{ $onPrimaryBlock }}">
            <div data-reveal class="mx-auto flex max-w-7xl flex-col gap-12" style="{{ $onPrimaryScope }}">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null, 'align' => 'start', 'h2Class' => 'text-[clamp(2.2rem,5vw,4rem)] font-black leading-[1.15]']])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-x-10 gap-y-12">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'theme' => 'editorial',
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-5 py-20 sm:px-10 sm:py-28" style="{{ $inkBlock }}">
            <div data-reveal class="mx-auto flex max-w-7xl flex-col items-start gap-6">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-4xl text-[clamp(2.6rem,6.4vw,5.4rem)] font-black leading-[1.1]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-xl leading-loose" style="opacity: .85;">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty() || $contactAction)
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-10 py-4 text-lg font-black transition hover:opacity-90', 'style' => $primaryButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'px-10 py-4 text-lg font-black transition hover:opacity-90', 'style' => $primaryButton]])
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @else
        {{-- نص عادي ("من نحن" وأي قسم مضاف): "جملة بوستر" — الفقرة نفسها بخط كبير وتخين. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-5xl flex-col items-start gap-6">
                @if (filled($section['eyebrow'] ?? null))
                    <span class="px-3 py-1 text-sm font-black" style="{{ $onPrimaryBlock }}">{{ $section['eyebrow'] }}</span>
                @endif
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4.4vw,3.4rem)] font-black leading-[1.2]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-2xl font-bold leading-[1.9] sm:text-3xl sm:leading-[1.8]">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-8 py-3.5 text-base font-black transition hover:opacity-90', 'style' => $primaryButton]])
                        @endforeach
                    </div>
                @endif
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
    @include('site.partials.footer', ['footerTheme' => 'sharp'])
@endif
