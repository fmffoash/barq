{{--
    "أورجانيك" (2026-10-11) — دافي وطبيعي: الصور جوّه أشكال "بقعة" عضوية (border-radius غير منتظم)
    ووراها بقعة تانية باللون الأساسي، الأقسام بتتبادل خلفيتها مع فواصل متموّجة (SVG)، الخدمات كروت
    ناعمة بورقة شجر صغيرة، والمعرض صور بأشكال بقع مختلفة. مفيش ولا زاوية حادة في الصفحة.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img> (الشكل border-radius على حاوية overflow-hidden).
--}}
@php
    $blobs = [
        '62% 38% 46% 54% / 55% 46% 54% 45%',
        '42% 58% 63% 37% / 45% 55% 45% 55%',
        '55% 45% 38% 62% / 38% 52% 48% 62%',
        '48% 52% 58% 42% / 60% 40% 60% 40%',
        '36% 64% 50% 50% / 50% 38% 62% 50%',
        '58% 42% 44% 56% / 44% 60% 40% 56%',
    ];
    $softButton = 'background-color: var(--site-primary); color: var(--site-on-primary); box-shadow: 0 12px 28px -14px var(--site-primary);';
    $tintBand = 'background-color: color-mix(in srgb, var(--site-surface) 85%, var(--site-background));';
    $leaf = '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.5 3.5S11 3 6.7 7.3C3 11 4.1 16 5 17.6L3.3 19.3l1.4 1.4L6.4 19c1.6.9 6.6 2 10.3-1.7C21 13 20.5 3.5 20.5 3.5zM8.8 15.2l6.6-6.6"/></svg>';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
    $bandIndex = 0;
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="relative z-30 px-5 pt-5 sm:px-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
            <a href="#top" class="flex min-w-0 items-center gap-2 text-xl font-extrabold">
                <span class="shrink-0" style="color: var(--site-primary);">{!! $leaf !!}</span>
                <span class="max-w-[50vw] truncate sm:max-w-sm">{{ $project->name }}</span>
            </a>
            <ul class="hidden items-center gap-2 rounded-full px-2 py-1.5 text-sm font-semibold md:flex" style="{{ $tintBand }}">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="block rounded-full px-4 py-1.5 transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="rounded-full px-5 py-2.5 text-sm font-bold transition hover:opacity-90" style="{{ $softButton }}">تواصل معنا</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md']])
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

        // الأقسام اللي بعد الهيرو بتتبادل: خلفية ملوّنة خفيفة بفاصل متموّج فوق وتحت، وعادي.
        $tinted = $section['kind'] !== 'hero' && $section['kind'] !== 'cta' && $bandIndex++ % 2 === 0;
    @endphp

    @if ($tinted)
        <svg class="block h-10 w-full sm:h-16" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true" style="color: color-mix(in srgb, var(--site-surface) 85%, var(--site-background));"><path fill="currentColor" d="M0 48c160-32 320-40 480-16s320 48 480 24 320-56 480-32v56H0z"/></svg>
    @endif

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-14 sm:px-10 sm:py-20">
            <div @class(['mx-auto grid max-w-6xl items-center gap-12', 'lg:grid-cols-[1.05fr_1fr]' => $heroImage])>
                <div @class(['flex flex-col gap-7', 'items-start' => $heroImage, 'items-center text-center' => ! $heroImage])>
                    <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold" style="background-color: color-mix(in srgb, var(--site-primary) 14%, transparent); color: var(--site-primary);">
                        {!! $leaf !!} {{ $project->name }}
                    </span>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.6rem,5.8vw,4.8rem)] font-extrabold leading-[1.2]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-[2] sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-9 py-4 text-base font-bold transition hover:opacity-90', 'style' => $softButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-9 py-4 text-base font-bold transition hover:opacity-90', 'style' => $softButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    <div class="relative mx-auto aspect-square w-full max-w-lg">
                        <div class="pointer-events-none absolute inset-[-4%] translate-x-[5%] translate-y-[4%]" style="border-radius: {{ $blobs[1] }}; background-color: color-mix(in srgb, var(--site-primary) 30%, transparent);" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute -start-4 top-6 h-20 w-20 sm:h-24 sm:w-24" style="border-radius: {{ $blobs[3] }}; background-color: var(--site-primary);" aria-hidden="true"></div>
                        <div class="relative h-full w-full overflow-hidden" style="border-radius: {{ $blobs[0] }}; background-color: var(--site-surface);">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'gallery')
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20" style="{{ $tinted ? $tintBand : '' }}">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-10">
                    @foreach ($imageItems as $i => $item)
                        <div class="relative aspect-square overflow-hidden {{ $i % 3 === 1 ? 'w-[calc(50%-0.75rem)] sm:w-[calc(33.333%-1.7rem)] sm:mt-14' : 'w-[calc(50%-0.75rem)] sm:w-[calc(33.333%-1.7rem)]' }}" style="border-radius: {{ $blobs[$i % count($blobs)] }}; background-color: var(--site-surface);">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20" style="{{ $tinted ? $tintBand : '' }}">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                @foreach ($listItems as $item)
                    @php $entries = (array) $item['value']; @endphp
                    <div class="flex flex-wrap justify-center gap-6">
                        @foreach ($entries as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                            <div data-slot="{{ $item['slot']->key }}" class="flex flex-col gap-4 rounded-[2.5rem] p-7 sm:p-8 {{ \App\Services\SiteRenderer::cardWidth(count($entries)) }}" style="background-color: {{ $tinted ? 'var(--site-background)' : 'var(--site-surface)' }};">
                                <span class="flex h-14 w-14 items-center justify-center" style="border-radius: {{ $blobs[$i % count($blobs)] }}; background-color: color-mix(in srgb, var(--site-primary) 18%, transparent); color: var(--site-primary);">{!! $leaf !!}</span>
                                <span class="text-xl font-bold leading-snug">{{ $e['title'] }}</span>
                                @if ($e['desc'])
                                    <span class="leading-relaxed" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $softButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20" style="{{ $tinted ? $tintBand : '' }}">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $i => $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                    'style' => 'border-radius: 2.5rem 2.5rem 2.5rem '.($i % 2 === 0 ? '0.5rem' : '2.5rem').';'.($tinted ? ' background-color: var(--site-background);' : ''),
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="relative mx-auto max-w-5xl overflow-hidden px-6 py-16 text-center sm:px-16 sm:py-20" style="border-radius: 3.5rem 3.5rem 3.5rem 1rem; background-color: var(--site-primary); color: var(--site-on-primary);">
                <div class="pointer-events-none absolute -end-16 -top-16 h-56 w-56" style="border-radius: {{ $blobs[2] }}; background-color: color-mix(in srgb, var(--site-on-primary) 14%, transparent);" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-20 -start-10 h-48 w-48" style="border-radius: {{ $blobs[4] }}; background-color: color-mix(in srgb, var(--site-on-primary) 10%, transparent);" aria-hidden="true"></div>
                <div class="relative mx-auto flex max-w-2xl flex-col items-center gap-6">
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.2rem,5vw,3.6rem)] font-extrabold leading-[1.25]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose sm:text-xl" style="opacity: .9;">{!! $item['value'] !!}</p>
                    @endforeach
                    @if ($linkItems->isNotEmpty() || $contactAction)
                        <div class="mt-2 flex flex-wrap justify-center gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-9 py-4 text-lg font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary-deep);']])
                            @endforeach
                            @if ($linkItems->isEmpty() && $contactAction)
                                @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-full px-9 py-4 text-lg font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary-deep);']])
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>

    @else
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20" style="{{ $tinted ? $tintBand : '' }}">
            <div data-reveal class="mx-auto flex max-w-3xl flex-col items-center gap-6 text-center">
                <span style="color: var(--site-primary);">{!! $leaf !!}</span>
                @if (filled($section['eyebrow'] ?? null))
                    <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                @endif
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-[2.1] sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $softButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($tinted)
        <svg class="block h-10 w-full sm:h-16" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true" style="color: color-mix(in srgb, var(--site-surface) 85%, var(--site-background));"><path fill="currentColor" d="M0 0h1440v28c-160 32-320 40-480 16S640-4 480 20 160 76 0 52z"/></svg>
    @endif
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    @include('site.partials.footer', ['footerTheme' => 'rounded'])
@endif
