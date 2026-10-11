{{--
    "ريترو" (2026-10-11) — طابع السبعينات المرح: شمس أشعة (repeating-conic-gradient) ورا صورة
    الهيرو الدايرية بحلقة سميكة، شارة "نجمة" بإسم النشاط، أحزمة خطوط ملوّنة متموّجة بين الأقسام،
    الخدمات "تذاكر" بحواف مقصوصة وخط متقطّع، والمعرض إطارات مدوّرة سميكة مايلة يمين وشمال. على
    خلفيات فاتحة بس (LAYOUT_MODES).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img> (الميل على الإطار — rotate مش transform).
--}}
@php
    $ink = 'var(--site-text)';
    $chunkyButton = 'background-color: var(--site-primary); color: var(--site-on-primary); box-shadow: 0 5px 0 var(--site-primary-deep);';
    $inkButton = 'background-color: var(--site-text); color: var(--site-background); box-shadow: 0 5px 0 color-mix(in srgb, var(--site-text) 60%, transparent);';
    // 3 خطوط ملوّنة (أساسي/أغمق/لون النص) — حزام فاصل بين الأقسام.
    $stripes = '<div class="flex flex-col" aria-hidden="true"><span class="h-3" style="background-color: var(--site-primary);"></span><span class="h-3" style="background-color: var(--site-primary-deep);"></span><span class="h-3" style="background-color: var(--site-text);"></span></div>';
    $starClip = 'clip-path: polygon(50% 0%, 61% 15%, 79% 9%, 79% 27%, 97% 33%, 87% 50%, 97% 67%, 79% 73%, 79% 91%, 61% 85%, 50% 100%, 39% 85%, 21% 91%, 21% 73%, 3% 67%, 13% 50%, 3% 33%, 21% 27%, 21% 9%, 39% 15%);';
    $tilts = ['-rotate-3', 'rotate-2', '-rotate-1', 'rotate-3', '-rotate-2', 'rotate-1'];
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="relative z-30 px-5 pt-5 sm:px-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 rounded-full border-[3px] px-5 py-3" style="border-color: {{ $ink }}; background-color: var(--site-surface);">
            <a href="#top" class="min-w-0 max-w-[50vw] truncate text-xl font-black sm:max-w-sm">{{ $project->name }}</a>
            <ul class="hidden items-center gap-6 text-sm font-black md:flex">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-60">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="rounded-full px-5 py-2 text-sm font-black transition hover:opacity-90" style="{{ $chunkyButton }}">تواصل معنا</a>
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
    @endphp

    @if (! $loop->first)
        {!! $stripes !!}
    @endif

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-14 sm:px-10 sm:py-20">
            <div @class(['mx-auto grid max-w-6xl items-center gap-12', 'lg:grid-cols-[1.1fr_1fr]' => $heroImage])>
                <div @class(['flex flex-col gap-7', 'items-start' => $heroImage, 'items-center text-center' => ! $heroImage])>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.8rem,6.4vw,5.4rem)] font-black leading-[1.12]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg font-medium leading-loose sm:text-xl">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="flex flex-wrap gap-4">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-9 py-4 text-lg font-black transition hover:translate-y-0.5', 'style' => $chunkyButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-9 py-4 text-lg font-black transition hover:translate-y-0.5', 'style' => $chunkyButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    <div class="relative mx-auto aspect-square w-full max-w-lg">
                        {{-- شمس بأشعة (زخرفة) — بتلف ببطء على الموقع العام بس. --}}
                        <div class="bq-retro-sun pointer-events-none absolute inset-0 rounded-full" style="background-image: repeating-conic-gradient(var(--site-primary) 0deg 9deg, transparent 9deg 18deg); mask-image: radial-gradient(circle, black 45%, transparent 71%);" aria-hidden="true"></div>
                        <div class="absolute inset-[16%] overflow-hidden rounded-full border-[6px]" style="border-color: {{ $ink }}; background-color: var(--site-surface); box-shadow: 0 0 0 8px var(--site-background), 0 0 0 12px var(--site-primary-deep);">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                        <div class="pointer-events-none absolute end-0 top-[6%] flex h-28 w-28 rotate-12 items-center justify-center p-5 text-center text-xs font-black leading-tight sm:h-32 sm:w-32" style="{{ $starClip }} background-color: var(--site-text); color: var(--site-background);">
                            <span class="line-clamp-3">{{ $project->name }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'gallery')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-14">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'h2Class' => 'text-[clamp(2.2rem,4.8vw,3.8rem)] font-black leading-[1.15]']])
                <div class="flex flex-wrap justify-center gap-6 sm:gap-10">
                    @foreach ($imageItems as $i => $item)
                        <div class="relative aspect-[4/5] w-[calc(50%-0.75rem)] overflow-hidden rounded-[2rem] border-[5px] sm:w-[calc(33.333%-1.7rem)] {{ $tilts[$i % count($tilts)] }}" style="border-color: {{ $ink }}; background-color: var(--site-surface); box-shadow: 8px 8px 0 var(--site-primary);">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24" style="background-color: var(--site-surface);">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'h2Class' => 'text-[clamp(2.2rem,4.8vw,3.8rem)] font-black leading-[1.15]']])
                @foreach ($listItems as $item)
                    @php $entries = (array) $item['value']; @endphp
                    <div class="flex flex-wrap justify-center gap-6">
                        @foreach ($entries as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                            {{-- "تذكرة": حواف مقصوصة نص دايرية على الجنبين + خط متقطّع بين الرقم والاسم. --}}
                            <div data-slot="{{ $item['slot']->key }}" class="relative flex items-stretch rounded-3xl border-[3px] {{ \App\Services\SiteRenderer::cardWidth(count($entries)) }}" style="border-color: {{ $ink }}; background-color: var(--site-background);">
                                <span class="pointer-events-none absolute -start-3 top-1/2 h-6 w-6 -translate-y-1/2 rounded-full border-[3px]" style="border-color: {{ $ink }}; background-color: var(--site-surface); clip-path: inset(0 50% 0 0);" aria-hidden="true"></span>
                                <span class="flex w-20 shrink-0 items-center justify-center text-3xl font-black" style="color: var(--site-primary);">{{ $i + 1 }}</span>
                                <span class="flex flex-col justify-center gap-1.5 border-s-[3px] border-dashed p-6" style="border-color: {{ $ink }};">
                                    <span class="text-xl font-black leading-snug">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="font-medium" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-4">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-black transition hover:translate-y-0.5', 'style' => $chunkyButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null, 'h2Class' => 'text-[clamp(2.2rem,4.8vw,3.8rem)] font-black leading-[1.15]']])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $i => $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])).' border-[3px] '.($i % 2 === 0 ? '-rotate-1' : 'rotate-1'),
                                    'style' => 'border-color: var(--site-text); box-shadow: 6px 6px 0 var(--site-primary-deep);',
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-20 sm:px-10 sm:py-28" style="background-color: var(--site-primary); color: var(--site-on-primary);">
            <div class="bq-retro-sun pointer-events-none absolute -bottom-1/2 left-1/2 aspect-square w-[60rem] -translate-x-1/2 rounded-full opacity-20" style="background-image: repeating-conic-gradient(var(--site-on-primary) 0deg 8deg, transparent 8deg 16deg); mask-image: radial-gradient(circle, black 30%, transparent 70%);" aria-hidden="true"></div>
            <div data-reveal class="relative mx-auto flex max-w-3xl flex-col items-center gap-6 text-center">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.6rem,6vw,4.8rem)] font-black leading-[1.12]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-xl font-medium leading-loose" style="opacity: .9;">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty() || $contactAction)
                    <div class="mt-2 flex flex-wrap justify-center gap-4">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-10 py-4 text-lg font-black transition hover:translate-y-0.5', 'style' => $inkButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-full px-10 py-4 text-lg font-black transition hover:translate-y-0.5', 'style' => $inkButton]])
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @else
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-3xl flex-col items-center gap-6 rounded-[3rem] border-[3px] px-6 py-14 text-center sm:px-14" style="border-color: {{ $ink }}; background-color: var(--site-surface); box-shadow: 10px 10px 0 var(--site-primary);">
                @if (filled($section['eyebrow'] ?? null))
                    <span class="rounded-full px-4 py-1 text-sm font-black" style="background-color: var(--site-text); color: var(--site-background);">{{ $section['eyebrow'] }}</span>
                @endif
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4.4vw,3.2rem)] font-black leading-[1.25]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg font-medium leading-[2] sm:text-xl">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-4">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-black transition hover:translate-y-0.5', 'style' => $chunkyButton]])
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
    {!! $stripes !!}
    @include('site.partials.footer', ['footerTheme' => 'plain'])
@endif
