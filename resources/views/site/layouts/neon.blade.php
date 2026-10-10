{{--
    "نيون" — طابع مضيء/ليلي فوق خلفية داكنة: عناوين ضخمة بتوهّج (text-shadow) باللون الأساسي،
    حدود مضيئة بدل الظلال، وشبكة خطوط خفيفة ورا الهيرو. مناسب لأي نشاط تقني/ترفيهي/رياضي.
    (2026-10-10) اتعاد تصميمه: كان نسخة من "جلاس" (عنوان في النص وتحته صورة عريضة في كارت) —
    دلوقتي الهيرو عمودين: الكلام الضخم المضيء هو البطل، والصورة "بلاطة" صغيرة مضيئة جنبه.
    الخدمات صفوف مرقّمة بخط فاصل مضيء، والآراء كروت بحدود نيون، والتواصل لافتة مضيئة.
    مكتبة القوالب بتديه لوحات ألوان داكنة بس (التوهّج على خلفية فاتحة شكله عيب رندر).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@php
    $glow = fn (int $strength) => 'text-shadow: 0 0 '.(int) ($strength * 0.6).'px color-mix(in srgb, var(--site-primary) 70%, transparent), 0 0 '.$strength.'px color-mix(in srgb, var(--site-primary) 40%, transparent);';
    $edge = 'border-color: color-mix(in srgb, var(--site-primary) 45%, transparent); box-shadow: 0 0 22px -6px color-mix(in srgb, var(--site-primary) 55%, transparent), inset 0 0 18px -10px color-mix(in srgb, var(--site-primary) 60%, transparent);';
    $softEdge = 'border-color: color-mix(in srgb, var(--site-primary) 28%, transparent);';
    $bodyColor = 'color: color-mix(in srgb, var(--site-text) 78%, var(--site-background));';
    $outlineButton = 'border-color: var(--site-primary); color: var(--site-primary); box-shadow: 0 0 18px -2px color-mix(in srgb, var(--site-primary) 55%, transparent), inset 0 0 12px -4px color-mix(in srgb, var(--site-primary) 55%, transparent);';
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary); box-shadow: 0 0 26px -4px var(--site-primary);';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
    // شبكة خطوط رفيعة باللون الأساسي (زخرفة بس) — بتبهت لتحت عشان ماتزحمش النص.
    $gridBackdrop = 'background-image: linear-gradient(color-mix(in srgb, var(--site-primary) 10%, transparent) 1px, transparent 1px), linear-gradient(90deg, color-mix(in srgb, var(--site-primary) 10%, transparent) 1px, transparent 1px); background-size: 56px 56px; mask-image: radial-gradient(ellipse 80% 70% at 50% 30%, #000 30%, transparent 75%); -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 30%, #000 30%, transparent 75%);';
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="relative z-30 border-b" style="{{ $softEdge }} background-color: color-mix(in srgb, var(--site-background) 85%, transparent);">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="#top" class="min-w-0 max-w-[55vw] truncate text-lg font-extrabold sm:max-w-xs" style="color: var(--site-primary); {{ $glow(18) }}">{{ $project->name }}</a>
            <ul class="hidden items-center gap-7 text-sm font-medium md:flex">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="rounded-full border px-4 py-2 text-sm font-bold transition hover:opacity-80" style="{{ $outlineButton }}">تواصل معنا</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md', 'panelStyle' => 'background-color: var(--site-surface); color: var(--site-text); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-primary) 35%, transparent), 0 20px 40px -12px rgb(0 0 0 / .6);']])
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
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-16 sm:px-8 sm:py-24 lg:py-28">
            <div class="pointer-events-none absolute inset-0" style="{{ $gridBackdrop }}" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -top-40 left-1/2 h-[30rem] w-[44rem] max-w-full -translate-x-1/2 rounded-full opacity-25 blur-3xl" style="background-color: var(--site-primary);" aria-hidden="true"></div>
            <div @class(['relative mx-auto grid max-w-6xl items-center gap-14', 'lg:grid-cols-[1.35fr_1fr]' => $heroImage])>
                <div @class(['flex flex-col gap-7', 'items-start text-start' => $heroImage, 'items-center text-center' => ! $heroImage])>
                    <span class="inline-flex items-center gap-2.5 rounded-full border px-4 py-1.5 text-sm font-semibold" style="{{ $softEdge }} color: var(--site-primary);">
                        <span class="h-2 w-2 rounded-full motion-safe:animate-pulse" style="background-color: var(--site-primary); box-shadow: 0 0 10px var(--site-primary);" aria-hidden="true"></span>
                        {{ $project->name }}
                    </span>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.6rem,6.4vw,5.4rem)] font-black leading-[1.15]" style="{{ $glow(46) }}">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose sm:text-xl" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $solidButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-4 w-4']])
                        @endif
                        @if ($navLinks->isNotEmpty())
                            <a href="#{{ $navLinks->first()['key'] }}" class="rounded-full border px-7 py-3.5 text-base font-bold transition hover:opacity-80" style="{{ $outlineButton }}">{{ $navLinks->first()['label'] }}</a>
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    {{-- "بلاطة" الصورة: إطار مضيء + إطار تاني مزاح وراه (زخرفة). --}}
                    <div class="relative mx-auto w-full max-w-sm lg:max-w-none">
                        <div class="pointer-events-none absolute -bottom-4 -start-4 h-full w-full rounded-[2rem] border-2" style="{{ $softEdge }}" aria-hidden="true"></div>
                        <div class="relative aspect-[4/5] overflow-hidden rounded-[2rem] border-2" style="{{ $edge }}">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'gallery')
        @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                <div class="{{ $mosaic['grid'] }} auto-rows-[9rem] gap-3 sm:auto-rows-[12rem] sm:gap-5 lg:auto-rows-[14rem]">
                    @foreach ($imageItems as $item)
                        <div class="overflow-hidden rounded-2xl border {{ $mosaic['items'][$loop->index] }}" style="{{ $edge }}">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        {{-- الخدمات: صفوف مرقّمة بخط فاصل مضيء — عنوان القسم على جنب والصفوف على الجنب التاني. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[1fr_1.5fr] lg:gap-16">
                <div class="flex flex-col items-start gap-4 lg:sticky lg:top-10 lg:self-start">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="text-sm font-bold" style="color: var(--site-primary);">// {{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,3.8vw,3.2rem)] font-black leading-[1.25]" style="{{ $glow(30) }}">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'mt-2 rounded-full border px-7 py-3 text-base font-bold transition hover:opacity-80', 'style' => $outlineButton]])
                    @endforeach
                </div>
                <div class="flex flex-col">
                    @foreach ($listItems as $item)
                        @foreach ((array) $item['value'] as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                            <div data-slot="{{ $item['slot']->key }}" class="group flex items-baseline gap-5 border-b py-6 first:border-t" style="{{ $softEdge }}">
                                <span class="w-10 shrink-0 font-mono text-sm font-bold tabular-nums" style="color: var(--site-primary); {{ $glow(12) }}">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <div class="flex flex-col gap-1.5">
                                    <span class="text-xl font-bold leading-snug sm:text-2xl">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="text-base leading-relaxed" style="{{ $bodyColor }}">{{ $e['desc'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'theme' => 'sharp',
                                    'class' => 'rounded-2xl '.\App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                    'style' => $edge.' background-color: color-mix(in srgb, var(--site-surface) 70%, transparent);',
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="relative mx-auto flex max-w-5xl flex-col items-center gap-6 overflow-hidden rounded-[2rem] border-2 px-6 py-16 text-center sm:px-12 sm:py-20" style="{{ $edge }} background-image: radial-gradient(ellipse at 50% 0%, color-mix(in srgb, var(--site-primary) 22%, transparent), transparent 70%);">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-3xl text-[clamp(2.2rem,5vw,4rem)] font-black leading-[1.2]" style="{{ $glow(40) }}">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty() || $contactAction)
                    <div class="mt-2 flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-9 py-4 text-lg font-bold transition hover:opacity-90', 'style' => $solidButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-full px-9 py-4 text-lg font-bold transition hover:opacity-90', 'style' => $solidButton]])
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @else
        {{-- نص عادي ("من نحن" وأي قسم مضاف): عنوان ضخم مضيء وتحته النص، بخط رأسي مضيء على جنب. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-4xl gap-6 sm:gap-10">
                <span class="w-1 shrink-0 rounded-full" style="background-image: linear-gradient(to bottom, var(--site-primary), transparent); box-shadow: 0 0 14px var(--site-primary);" aria-hidden="true"></span>
                <div class="flex flex-col gap-5">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="text-sm font-bold" style="color: var(--site-primary);">// {{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4vw,3.3rem)] font-black leading-[1.25]" style="{{ $glow(30) }}">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-xl leading-loose sm:text-2xl sm:leading-[2]" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                    @endforeach
                    @if ($linkItems->isNotEmpty())
                        <div class="flex flex-wrap gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full border px-7 py-3 text-base font-bold transition hover:opacity-80', 'style' => $outlineButton]])
                            @endforeach
                        </div>
                    @endif
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
    @include('site.partials.footer', ['footerTheme' => 'sharp'])
@endif
