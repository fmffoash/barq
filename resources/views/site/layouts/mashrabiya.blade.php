{{--
    "مشربية" (2026-10-11) — فخامة شرقية: نقشة نجمة ثمانية هندسية (SVG مدمج) خفيفة ورا الهيرو
    والتواصل، الصور جوّه أقواس مدبّبة (قوس بصلي — clip-path) بإطار مزدوج، فواصل زخرفية (خط + معيّن)
    بين العناوين، والخدمات بلاطات بنجمة ثمانية. مختلف عن أقواس "سيجنتشر" النص دايرية.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img> (الشكل clip-path على الحاوية).
--}}
@php
    // قوس مدبّب: الحاوية مقصوصة بـpolygon، والإطار طبقة بنفس الشكل أكبر شوية وراها.
    $archClip = 'clip-path: polygon(50% 0%, 62% 3%, 74% 9%, 84% 18%, 92% 30%, 97% 43%, 100% 56%, 100% 100%, 0% 100%, 0% 56%, 3% 43%, 8% 30%, 16% 18%, 26% 9%, 38% 3%);';
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $outlineButton = 'border-color: var(--site-primary); color: var(--site-primary);';
    $star = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-full w-full"><path d="M12 1.5l2.6 4.2 4.8-1.1-1.1 4.8 4.2 2.6-4.2 2.6 1.1 4.8-4.8-1.1L12 22.5l-2.6-4.2-4.8 1.1 1.1-4.8L1.5 12l4.2-2.6-1.1-4.8 4.8 1.1z"/></svg>';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
@endphp

@php
    $pattern = function (string $id, string $opacity) {
        return '<svg class="pointer-events-none absolute inset-0 h-full w-full" aria-hidden="true" style="color: var(--site-primary); opacity: '.$opacity.';"><defs><pattern id="'.$id.'" width="56" height="56" patternUnits="userSpaceOnUse"><path d="M28 4l6.5 10.5 12-2.5-2.5 12L54.5 28 44 34.5l2.5 12-12-2.5L28 52.5 21.5 44l-12 2.5 2.5-12L1.5 28 12 21.5 9.5 9.5l12 2.5z" fill="none" stroke="currentColor" stroke-width="1"/><circle cx="28" cy="28" r="5" fill="none" stroke="currentColor" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(#'.$id.')"/></svg>';
    };
    $divider = '<span class="flex items-center gap-3" style="color: var(--site-primary);" aria-hidden="true"><span class="h-px w-12" style="background-color: currentColor;"></span><span class="h-2.5 w-2.5 rotate-45" style="background-color: currentColor;"></span><span class="h-px w-12" style="background-color: currentColor;"></span></span>';
@endphp

@if ($navLinks->isNotEmpty())
    <div class="sticky top-0 z-30 border-b backdrop-blur-md" style="border-color: color-mix(in srgb, var(--site-primary) 30%, transparent); background-color: color-mix(in srgb, var(--site-background) 85%, transparent);">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-10">
            <a href="#top" class="flex min-w-0 items-center gap-2.5 text-xl font-bold">
                <span class="h-6 w-6 shrink-0" style="color: var(--site-primary);">{!! $star !!}</span>
                <span class="max-w-[50vw] truncate sm:max-w-sm">{{ $project->name }}</span>
            </a>
            <ul class="hidden items-center gap-8 text-sm font-semibold md:flex">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="border px-4 py-2 text-sm font-bold transition hover:opacity-75" style="{{ $outlineButton }}">احجز الآن</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md', 'sharp' => true]])
            </div>
        </nav>
    </div>
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
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-16 sm:px-10 sm:py-24">
            {!! $pattern('bq-mashrabiya-hero', '.13') !!}
            <div @class(['relative mx-auto grid max-w-6xl items-center gap-14', 'lg:grid-cols-[1.1fr_0.9fr]' => $heroImage])>
                <div @class(['flex flex-col gap-7', 'items-start' => $heroImage, 'items-center text-center' => ! $heroImage])>
                    {!! $divider !!}
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.6rem,5.8vw,4.8rem)] font-bold leading-[1.25]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton, 'icon' => false]])
                        @endif
                        @if ($navLinks->isNotEmpty())
                            <a href="#{{ $navLinks->first()['key'] }}" class="border px-9 py-4 text-base font-bold transition hover:opacity-75" style="{{ $outlineButton }}">{{ $navLinks->first()['label'] }}</a>
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    {{-- قوس مزدوج: طبقة بلون أساسي (الإطار) + فراغ بلون الخلفية + الصورة. --}}
                    <div class="relative mx-auto aspect-[3/4] w-full max-w-sm sm:max-w-md">
                        <div class="pointer-events-none absolute inset-0" style="{{ $archClip }} background-color: var(--site-primary);" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute inset-[6px]" style="{{ $archClip }} background-color: var(--site-background);" aria-hidden="true"></div>
                        <div class="absolute inset-[14px] overflow-hidden" style="{{ $archClip }} background-color: var(--site-surface);">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                        <span class="pointer-events-none absolute -bottom-5 left-1/2 h-10 w-10 -translate-x-1/2 p-1" style="color: var(--site-primary); background-color: var(--site-background);">{!! $star !!}</span>
                    </div>
                @endif
            </div>
        </section>

    @else
        @php
            $isCta = $section['kind'] === 'cta';
            $band = in_array($section['kind'], ['list', 'testimonials'], true) ? 'background-color: var(--site-surface);' : '';
        @endphp
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-20 sm:px-10 sm:py-28" style="{{ $band }}">
            @if ($isCta)
                {!! $pattern('bq-mashrabiya-'.$section['key'], '.16') !!}
            @endif
            <div data-reveal @class(['relative mx-auto flex flex-col items-center gap-14', 'max-w-6xl' => ! $isCta, 'max-w-3xl' => $isCta])>
                <div class="mx-auto flex max-w-2xl flex-col items-center gap-5 text-center">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="{{ $isCta ? 'text-[clamp(2.4rem,5.4vw,4.2rem)]' : 'text-[clamp(2rem,4.2vw,3.3rem)]' }} font-bold leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                    {!! $divider !!}
                    @if (! empty($section['rating']))
                        @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating']]])
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                </div>

                @if ($section['kind'] === 'gallery')
                    <div class="flex w-full flex-wrap items-end justify-center gap-6 sm:gap-8">
                        @foreach ($imageItems as $item)
                            <div class="relative aspect-[3/4] w-[calc(50%-0.75rem)] sm:w-[calc(33.333%-1.34rem)]">
                                <div class="pointer-events-none absolute inset-0" style="{{ $archClip }} background-color: color-mix(in srgb, var(--site-primary) 55%, transparent);" aria-hidden="true"></div>
                                <div class="absolute inset-[4px] overflow-hidden" style="{{ $archClip }} background-color: var(--site-surface);">
                                    <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                                </div>
                            </div>
                        @endforeach
                    </div>

                @elseif ($section['kind'] === 'list')
                    @foreach ($listItems as $item)
                        @php $entries = (array) $item['value']; @endphp
                        <div class="flex w-full flex-wrap justify-center gap-5">
                            @foreach ($entries as $entry)
                                @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                <div data-slot="{{ $item['slot']->key }}" class="flex flex-col items-center gap-4 border p-8 text-center {{ \App\Services\SiteRenderer::cardWidth(count($entries)) }}" style="border-color: color-mix(in srgb, var(--site-primary) 35%, transparent); background-color: var(--site-background);">
                                    <span class="h-10 w-10" style="color: var(--site-primary);">{!! $star !!}</span>
                                    <span class="text-xl font-bold leading-snug">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="leading-relaxed" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                @elseif ($section['kind'] === 'testimonials')
                    @foreach ($listItems as $item)
                        @if (isset($item['testimonials']))
                            <div class="flex w-full flex-wrap justify-center gap-6">
                                @foreach ($item['testimonials'] as $t)
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'theme' => 'sharp',
                                        'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                        'style' => 'background-color: var(--site-background); border-color: color-mix(in srgb, var(--site-primary) 35%, transparent);',
                                    ]])
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                @endif

                @if ($linkItems->isNotEmpty() || ($isCta && $contactAction))
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                        @endforeach
                        @if ($isCta && $linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                        @endif
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
