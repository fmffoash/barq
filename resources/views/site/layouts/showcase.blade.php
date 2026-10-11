{{--
    "شوكيس" (2026-10-11) — عرض منتج عصري (طابع مواقع الشركات التقنية): هيرو في النص بشارة صغيرة
    وعنوان كبير وزرارين، وتحته الصورة جوّه "نافذة متصفح" مايلة ميل خفيف بمنظور 3D وعليها شرائح
    عائمة. الخدمات شبكة مميزات بأيقونات، المعرض نوافذ صغيرة، الآراء "حيطة" أعمدة متفاوتة الطول
    (masonry)، والتواصل لوحة متدرّجة.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img> (الميل على إطار النافذة، والصورة جوّه حاوية overflow-hidden).
--}}
@php
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $ghostButton = 'background-color: var(--site-surface); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 12%, transparent);';
    $cardShadow = 'background-color: var(--site-surface); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 8%, transparent), 0 18px 40px -24px rgb(0 0 0 / .35);';
    $onPrimaryScope = '--site-primary: var(--site-on-primary); --site-muted: color-mix(in srgb, var(--site-on-primary) 82%, transparent); --site-surface: color-mix(in srgb, var(--site-on-primary) 14%, transparent); --site-text: var(--site-on-primary);';
    $featureIcons = ['sparkles', 'check', 'clock', 'star', 'arrow-up', 'check'];
    $rating = $sections->firstWhere('kind', 'testimonials')['rating'] ?? null;
    $windowBar = function (string $label) {
        return '<div class="flex items-center gap-2 border-b px-4 py-3" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);" aria-hidden="true">'
            .'<span class="h-3 w-3 rounded-full" style="background-color: #ff5f57;"></span>'
            .'<span class="h-3 w-3 rounded-full" style="background-color: #febc2e;"></span>'
            .'<span class="h-3 w-3 rounded-full" style="background-color: #28c840;"></span>'
            .'<span class="ms-3 truncate rounded-full px-3 py-0.5 text-xs" style="background-color: color-mix(in srgb, var(--site-text) 7%, transparent); color: var(--site-muted);">'.e($label).'</span>'
            .'</div>';
    };
@endphp

@include('site.partials.nav')

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
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 pb-16 pt-14 sm:px-10 sm:pb-24 sm:pt-20">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-[36rem]" style="background-image: radial-gradient(ellipse 60% 70% at 50% 0%, color-mix(in srgb, var(--site-primary) 22%, transparent), transparent 70%);" aria-hidden="true"></div>
            <div class="relative mx-auto flex max-w-6xl flex-col items-center gap-14">
                <div class="flex max-w-3xl flex-col items-center gap-7 text-center">
                    <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold" style="{{ $ghostButton }}">
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold" style="{{ $solidButton }}">جديد</span>
                        {{ $project->name }}
                    </span>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.6rem,6vw,5rem)] font-extrabold leading-[1.15]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-xl px-8 py-4 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-xl px-8 py-4 text-base font-bold transition hover:opacity-90', 'style' => $solidButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
                        @endif
                        @if (! empty($siteMeta['nav']))
                            <a href="#{{ $siteMeta['nav'][0]['key'] }}" class="rounded-xl px-8 py-4 text-base font-bold transition hover:opacity-80" style="{{ $ghostButton }}">{{ $siteMeta['nav'][0]['label'] }}</a>
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    <div class="relative w-full max-w-5xl" style="perspective: 1600px;">
                        <div class="bq-showcase-tilt overflow-hidden rounded-2xl" style="{{ $cardShadow }}">
                            {!! $windowBar($project->name) !!}
                            <div class="relative aspect-[16/9] overflow-hidden">
                                <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                            </div>
                        </div>
                        @if ($rating)
                            <div class="pointer-events-none absolute -bottom-6 start-4 hidden rounded-2xl p-1.5 sm:block sm:start-10" style="{{ $cardShadow }}">
                                @include('site.partials.rating-badge', ['badge' => ['rating' => $rating, 'style' => 'background-color: transparent;']])
                            </div>
                        @endif
                        @if ($contactAction)
                            <div class="pointer-events-none absolute -top-5 end-4 hidden items-center gap-2 rounded-2xl px-4 py-3 text-sm font-bold sm:flex sm:end-10" style="{{ $cardShadow }}">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg" style="{{ $solidButton }}">@include('site.partials.icon', ['name' => $contactAction['type'] === 'whatsapp' ? 'whatsapp' : 'phone', 'class' => 'h-4 w-4'])</span>
                                {{ $contactAction['label'] }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'gallery')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                <div class="flex flex-wrap justify-center gap-5">
                    @foreach ($imageItems as $item)
                        <div class="overflow-hidden rounded-2xl {{ \App\Services\SiteRenderer::cardWidth($imageItems->count() === 1 ? 1 : 3) }}" style="{{ $cardShadow }}">
                            {!! $windowBar($alt) !!}
                            <div class="relative aspect-[4/3] overflow-hidden">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24" style="background-color: color-mix(in srgb, var(--site-surface) 55%, var(--site-background));">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                @foreach ($listItems as $item)
                    @php $entries = (array) $item['value']; @endphp
                    <div class="flex flex-wrap justify-center gap-5">
                        @foreach ($entries as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                            <div data-slot="{{ $item['slot']->key }}" class="flex flex-col gap-4 rounded-2xl p-7 transition hover:-translate-y-1 {{ \App\Services\SiteRenderer::cardWidth(count($entries)) }}" style="{{ $cardShadow }}">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl" style="background-color: color-mix(in srgb, var(--site-primary) 15%, transparent); color: var(--site-primary);">
                                    @include('site.partials.icon', ['name' => $featureIcons[$i % count($featureIcons)], 'class' => 'h-5 w-5'])
                                </span>
                                <span class="text-lg font-bold leading-snug">{{ $e['title'] }}</span>
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
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-xl px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        {{-- حيطة آراء: أعمدة CSS (masonry) — كل كارت بطوله الطبيعي. --}}
                        @php $columns = match (min(3, count($item['testimonials']))) { 1 => 'mx-auto max-w-xl', 2 => 'sm:columns-2', default => 'sm:columns-2 lg:columns-3' }; @endphp
                        <div class="gap-5 {{ $columns }}">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'class' => 'mb-5 break-inside-avoid rounded-2xl',
                                    'style' => $cardShadow,
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="relative mx-auto max-w-6xl overflow-hidden rounded-3xl px-6 py-16 sm:px-16 sm:py-20" style="background-image: linear-gradient(120deg, var(--site-primary-deep), var(--site-primary)); color: var(--site-on-primary);">
                <div class="pointer-events-none absolute inset-0 opacity-20" style="background-image: linear-gradient(color-mix(in srgb, var(--site-on-primary) 40%, transparent) 1px, transparent 1px), linear-gradient(90deg, color-mix(in srgb, var(--site-on-primary) 40%, transparent) 1px, transparent 1px); background-size: 40px 40px; mask-image: radial-gradient(ellipse at center, black, transparent 75%);" aria-hidden="true"></div>
                <div class="relative grid items-center gap-8 lg:grid-cols-[1.4fr_1fr]" style="{{ $onPrimaryScope }}">
                    <div class="flex flex-col gap-5">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.2rem,4.8vw,3.6rem)] font-extrabold leading-[1.2]">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="opacity: .9;">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                    @if ($linkItems->isNotEmpty() || $contactAction)
                        <div class="flex flex-wrap gap-3 lg:justify-end">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-xl px-9 py-4 text-lg font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary-deep);']])
                            @endforeach
                            @if ($linkItems->isEmpty() && $contactAction)
                                @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-xl px-9 py-4 text-lg font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary-deep);']])
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>

    @else
        {{-- نص عادي: عنوان على جنب والفقرة على الجنب التاني. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-2 lg:gap-16">
                <div class="flex flex-col gap-3">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4vw,3.2rem)] font-extrabold leading-[1.25]">{!! $heading['value'] !!}</h2>
                    @endif
                </div>
                <div class="flex flex-col gap-6">
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-[2] sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'w-fit rounded-xl px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
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
    @include('site.partials.footer', ['footerTheme' => 'rounded'])
@endif
