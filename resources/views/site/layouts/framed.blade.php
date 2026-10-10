{{--
    "فريمد" — طابع بروشور/دعوة رسمية: إطارات رفيعة بزوايا مميّزة باللون الأساسي، فواصل زخرفية
    (خط ◆ خط)، وصور بـ"باسبارتو" (هامش ورق حوالين الصورة) — لأي نشاط رسمي/فاخر (محاماة،
    عقارات، مناسبات).
    (2026-10-10) اتعاد تصميمه: كان نسخة من "مينيمال" بالحرف (عنوان في النص وتحته صورة عريضة)
    زيادة عليه إطار بس. دلوقتي الهيرو عمودين جوّه الإطار (الكلام جنب صورة طولية بإطار مزاح)،
    الخدمات "فهرس" مرقّم بعمودين، المعرض صور بهامش ورق، والتواصل إطار مزدوج.
    مفيش tracking (تباعد حروف) على أي نص عربي — بيقطّع وصل الحروف.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@php
    $line = 'border-color: color-mix(in srgb, var(--site-primary) 40%, transparent);';
    $hairline = 'border-color: color-mix(in srgb, var(--site-text) 12%, transparent);';
    $bodyColor = 'color: color-mix(in srgb, var(--site-text) 78%, var(--site-background));';
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $outlineButton = 'border-color: var(--site-primary); color: var(--site-primary);';
    $mat = 'background-color: var(--site-surface); border-color: color-mix(in srgb, var(--site-primary) 30%, transparent);';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
@endphp

@if ($navLinks->isNotEmpty())
    <header class="relative z-30 px-5 pt-6 sm:px-10 sm:pt-8">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 pb-5" style="border-bottom: 4px double color-mix(in srgb, var(--site-primary) 45%, transparent);">
            <a href="#top" class="min-w-0 max-w-[55vw] truncate text-xl font-bold sm:max-w-sm">{{ $project->name }}</a>
            <ul class="hidden items-center gap-8 text-sm font-semibold md:flex">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="border px-4 py-2 text-sm font-bold transition hover:opacity-70" style="{{ $outlineButton }}">تواصل معنا</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md', 'sharp' => true]])
            </div>
        </div>
    </header>
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
        <section id="{{ $section['key'] }}" class="relative px-5 py-10 sm:px-10 sm:py-14">
            <div class="relative mx-auto max-w-6xl border p-7 sm:p-12 lg:p-16" style="{{ $line }}">
                @include('site.partials.frame-corners', ['corner' => 'h-8 w-8'])
                <div @class(['grid items-center gap-12', 'lg:grid-cols-[1.15fr_0.85fr] lg:gap-16' => $heroImage])>
                    <div @class(['flex flex-col gap-6', 'items-start text-start' => $heroImage, 'items-center text-center' => ! $heroImage])>
                        <div class="flex items-center gap-3" style="color: var(--site-primary);" aria-hidden="true">
                            <span class="h-px w-10" style="background-color: currentColor;"></span>
                            <span class="h-2 w-2 rotate-45" style="background-color: currentColor;"></span>
                            <span class="h-px w-10" style="background-color: currentColor;"></span>
                        </div>
                        @if ($heading)
                            <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.4rem,5vw,4.2rem)] font-bold leading-[1.25]">{!! $heading['value'] !!}</h1>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                        @endforeach
                        <div class="mt-2 flex flex-wrap gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-8 py-3.5 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                            @endforeach
                            @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                                @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'px-8 py-3.5 text-base font-bold transition hover:opacity-85', 'style' => $solidButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-4 w-4']])
                            @endif
                            @if ($navLinks->isNotEmpty())
                                <a href="#{{ $navLinks->first()['key'] }}" class="border px-7 py-3.5 text-base font-bold transition hover:opacity-70" style="{{ $outlineButton }}">{{ $navLinks->first()['label'] }}</a>
                            @endif
                        </div>
                    </div>
                    @if ($heroImage)
                        {{-- صورة طولية بهامش ورق، وإطار رفيع مزاح وراها (زخرفة). --}}
                        <div class="relative mx-auto w-full max-w-sm lg:max-w-none">
                            <div class="pointer-events-none absolute inset-0 -translate-x-4 translate-y-4 border" style="border-color: var(--site-primary);" aria-hidden="true"></div>
                            <div class="relative border p-3" style="{{ $mat }}">
                                <div class="aspect-[3/4] overflow-hidden">
                                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'gallery')
        @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'h2Class' => 'text-[clamp(1.9rem,3.4vw,2.8rem)] font-bold leading-[1.3]']])
                <div class="{{ $mosaic['grid'] }} auto-rows-[9rem] gap-4 sm:auto-rows-[12rem] sm:gap-6 lg:auto-rows-[14rem]">
                    @foreach ($imageItems as $item)
                        <div class="border p-2 sm:p-2.5 {{ $mosaic['items'][$loop->index] }}" style="{{ $mat }}">
                            <div class="h-full w-full overflow-hidden">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        {{-- الخدمات "فهرس": إطار بزوايا، وجوّاه بنود مرقّمة بعمودين بخطوط رفيعة. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20">
            <div data-reveal class="relative mx-auto flex max-w-5xl flex-col gap-10 border p-7 sm:p-12 lg:p-14" style="{{ $line }}">
                @include('site.partials.frame-corners')
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'h2Class' => 'text-[clamp(1.9rem,3.4vw,2.8rem)] font-bold leading-[1.3]']])
                @foreach ($listItems as $item)
                    <ol class="grid gap-x-12 sm:grid-cols-2">
                        @foreach ((array) $item['value'] as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                            <li data-slot="{{ $item['slot']->key }}" class="flex items-baseline gap-4 border-b py-5" style="{{ $hairline }}">
                                <span class="w-7 shrink-0 text-sm font-bold tabular-nums" style="color: var(--site-primary);">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="flex flex-col gap-1">
                                    <span class="text-lg font-bold leading-snug">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="text-base leading-relaxed" style="{{ $bodyColor }}">{{ $e['desc'] }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'border px-7 py-3 text-base font-bold transition hover:opacity-70', 'style' => $outlineButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null, 'h2Class' => 'text-[clamp(1.9rem,3.4vw,2.8rem)] font-bold leading-[1.3]']])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        @php $width = \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])); @endphp
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $t)
                                <div class="relative {{ $width }}">
                                    @include('site.partials.frame-corners', ['corner' => 'h-4 w-4'])
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'theme' => 'sharp',
                                        'class' => 'h-full',
                                        'style' => $line,
                                    ]])
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20">
            <div data-reveal class="mx-auto max-w-5xl p-2" style="border: 4px double color-mix(in srgb, var(--site-primary) 55%, transparent);">
                <div class="flex flex-col items-center gap-6 px-6 py-14 text-center sm:px-12 sm:py-16" style="background-color: color-mix(in srgb, var(--site-primary) 7%, transparent);">
                    <div class="flex items-center gap-3" style="color: var(--site-primary);" aria-hidden="true">
                        <span class="h-px w-10" style="background-color: currentColor;"></span>
                        <span class="h-2 w-2 rotate-45" style="background-color: currentColor;"></span>
                        <span class="h-px w-10" style="background-color: currentColor;"></span>
                    </div>
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-3xl text-[clamp(2rem,4vw,3.2rem)] font-bold leading-[1.25]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                    @endforeach
                    @if ($linkItems->isNotEmpty() || $contactAction)
                        <div class="mt-2 flex flex-wrap justify-center gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-9 py-4 text-lg font-bold transition hover:opacity-85', 'style' => $solidButton]])
                            @endforeach
                            @if ($linkItems->isEmpty() && $contactAction)
                                @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'px-9 py-4 text-lg font-bold transition hover:opacity-85', 'style' => $solidButton]])
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>

    @else
        {{-- نص عادي ("من نحن" وأي قسم مضاف): من غير إطار — نص في النص بين فاصلين زخرفيين. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20">
            <div data-reveal class="mx-auto flex max-w-3xl flex-col items-center gap-6 text-center">
                @if (filled($section['eyebrow'] ?? null))
                    <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                @endif
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,3.6vw,3rem)] font-bold leading-[1.3]">{!! $heading['value'] !!}</h2>
                @endif
                <div class="flex items-center gap-3" style="color: var(--site-primary);" aria-hidden="true">
                    <span class="h-px w-16" style="background-color: currentColor;"></span>
                    <span class="h-2 w-2 rotate-45" style="background-color: currentColor;"></span>
                    <span class="h-px w-16" style="background-color: currentColor;"></span>
                </div>
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-xl leading-loose sm:leading-[2.1]" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'border px-7 py-3 text-base font-bold transition hover:opacity-70', 'style' => $outlineButton]])
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
