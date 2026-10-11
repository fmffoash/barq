{{--
    هيكل صفحة مشترك لتصميمات الـ3D (الدفعة 3، 2026-10-11) — كل تصميم ملف صغير في site/layouts بيحدد
    $depth ويستدعي الملف ده. اللي بيفرق تصميم عن التاني: المجسم (site/partials/depth/scenes)، شكل
    الهيرو، شكل الكروت والمعرض، وحواف الكروت وخلفية الهيرو.
      'scene'    => اسم المجسم (brew/helix/gloss/forge/skyline/...)
      'hero'     => 'split' (كلام + صورة في إطار مايل والمجسم طالع منه) | 'stage' (المجسم كبير في النص
                    والكلام تحته والصورة على لوح أرضي مايل)
      'cards'    => 'tilt' | 'block' | 'fan'      'gallery' => 'cover' | 'floor'
      'radius'   => حواف الكروت (CSS)              'type3d'  => عنوان الهيرو بعمق
      'backdrop' => 'grid' | 'dots' | 'glow'      'footer'  => 'rounded' | 'sharp'
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل <section>،
    صفر transform على <img> (الميل على الحاويات بس، وبيتقفل في المحرر — app.css).
--}}
@php
    $dp = $depth + ['hero' => 'split', 'cards' => 'tilt', 'gallery' => 'cover', 'radius' => '1.75rem', 'type3d' => false, 'backdrop' => 'glow', 'footer' => 'rounded'];
    $radius = $dp['radius'];
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary); box-shadow: 0 5px 0 var(--site-primary-deep), 0 16px 30px -12px var(--site-primary);';
    $ghostButton = 'background-color: color-mix(in srgb, var(--site-text) 7%, transparent); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--site-text) 14%, transparent);';
    $cardSurface = 'background-color: var(--site-surface); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 8%, transparent), 0 24px 48px -28px rgb(0 0 0 / .45);';
    $onPrimaryScope = '--site-primary: var(--site-on-primary); --site-muted: color-mix(in srgb, var(--site-on-primary) 82%, transparent); --site-surface: color-mix(in srgb, var(--site-on-primary) 14%, transparent); --site-text: var(--site-on-primary);';
    $backdrop = match ($dp['backdrop']) {
        'grid' => 'background-image: linear-gradient(color-mix(in srgb, var(--site-text) 6%, transparent) 1px, transparent 1px), linear-gradient(90deg, color-mix(in srgb, var(--site-text) 6%, transparent) 1px, transparent 1px); background-size: 3rem 3rem; mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000, transparent 75%);',
        'dots' => 'background-image: radial-gradient(color-mix(in srgb, var(--site-primary) 30%, transparent) 1.5px, transparent 1.5px); background-size: 1.6rem 1.6rem; mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000, transparent 75%);',
        default => 'background-image: radial-gradient(ellipse 55% 60% at 70% 30%, color-mix(in srgb, var(--site-primary) 24%, transparent), transparent 70%), radial-gradient(ellipse 40% 50% at 15% 80%, color-mix(in srgb, var(--site-primary-deep) 18%, transparent), transparent 70%);',
    };
    $rating = $sections->firstWhere('kind', 'testimonials')['rating'] ?? null;
    $sceneView = 'site.partials.depth.scenes.'.$dp['scene'];
@endphp

{{-- overflow-x: clip — قلبة الظهور الـ3D والمجسمات الطالعة برّه الحاويات ماتعملش سكرول عرضي على
الموبايل (clip مش بيعمل حاوية سكرول، فالنافبار الـsticky بيفضل شغال). --}}
<div style="overflow-x: clip;">
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
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 pb-20 pt-12 sm:px-10 sm:pb-28 sm:pt-16">
            <div class="pointer-events-none absolute inset-0" style="{{ $backdrop }}" aria-hidden="true"></div>

            @if ($dp['hero'] === 'stage')
                <div class="relative mx-auto flex max-w-6xl flex-col items-center gap-8 text-center">
                    <div class="flex h-[15rem] items-center justify-center sm:h-[20rem]">
                        @include($sceneView)
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold" style="{{ $ghostButton }}">
                        <span class="h-2 w-2 rounded-full" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                        {{ $project->name }}
                    </span>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" @class(['max-w-4xl text-[clamp(2.5rem,6vw,4.8rem)] font-extrabold leading-[1.15]', 'bq3d-type' => $dp['type3d']])>{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="flex flex-wrap justify-center gap-4">
                        @include('site.partials.depth.hero-buttons')
                    </div>
                    @if ($heroImage)
                        <div class="bq3d-frame-stage mt-6 w-full max-w-5xl">
                            <div class="bq3d-floor relative aspect-[16/8] overflow-hidden" style="border-radius: {{ $radius }}; {{ $cardSurface }}">
                                <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div @class(['relative mx-auto grid max-w-6xl items-center gap-14', 'lg:grid-cols-[1.05fr_1fr]' => true])>
                    <div class="flex flex-col items-start gap-7">
                        <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold" style="{{ $ghostButton }}">
                            <span class="h-2 w-2 rounded-full" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                            {{ $project->name }}
                        </span>
                        @if ($heading)
                            <h1 data-slot="{{ $heading['slot']->key }}" @class(['text-[clamp(2.5rem,5.6vw,4.6rem)] font-extrabold leading-[1.15]', 'bq3d-type' => $dp['type3d']])>{!! $heading['value'] !!}</h1>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                        <div class="flex flex-wrap gap-4">
                            @include('site.partials.depth.hero-buttons')
                        </div>
                        @if ($rating)
                            @include('site.partials.rating-badge', ['badge' => ['rating' => $rating]])
                        @endif
                    </div>
                    <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                        @if ($heroImage)
                            <div class="bq3d-frame-stage">
                                <div class="bq3d-frame relative aspect-[4/5] overflow-hidden sm:aspect-[5/5]" style="border-radius: {{ $radius }}; background-color: var(--site-surface);">
                                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                                </div>
                            </div>
                            <div class="absolute -bottom-16 start-[-3rem] sm:start-[-5rem]" style="--bq3d-size: 17rem;">
                                @include($sceneView)
                            </div>
                        @else
                            <div class="flex justify-center">@include($sceneView)</div>
                        @endif
                    </div>
                </div>
            @endif
        </section>

    @elseif ($section['kind'] === 'gallery')
        @php $count = $imageItems->count(); $mid = ($count - 1) / 2; @endphp
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="bq3d-rise mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                @if ($dp['gallery'] === 'floor')
                    <div class="bq3d-gallery bq3d-gallery--floor">
                        <div class="bq3d-plane grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5">
                            @foreach ($imageItems as $i => $item)
                                <div class="bq3d-tile relative aspect-square overflow-hidden" style="border-radius: {{ $radius }}; {{ $cardSurface }}">
                                    <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="bq3d-gallery bq3d-gallery--cover grid grid-cols-2 gap-4 md:flex md:items-center md:justify-center md:gap-3">
                        @foreach ($imageItems as $i => $item)
                            @php $k = $mid > 0 ? round(($mid - $i) / max($mid, 1), 2) : 0; @endphp
                            <div class="bq3d-tile relative aspect-[3/4] overflow-hidden md:w-[15rem] md:shrink" style="--k: {{ $k }}; --dz: {{ abs($k) }}; border-radius: {{ $radius }}; {{ $cardSurface }}">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="bq3d-rise mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                @foreach ($listItems as $item)
                    @php $entries = (array) $item['value']; @endphp
                    <div class="bq3d-cards flex flex-wrap justify-center gap-7">
                        @foreach ($entries as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                            <div data-slot="{{ $item['slot']->key }}" class="bq3d-card bq3d-card--{{ $dp['cards'] }} flex flex-col gap-5 p-7 {{ \App\Services\SiteRenderer::cardWidth(count($entries)) }}" style="border-radius: {{ $radius }}; {{ $dp['cards'] === 'block' ? 'background-color: var(--site-surface);' : $cardSurface }}">
                                <span class="bq3d-pop flex h-14 w-14 items-center justify-center text-xl font-extrabold" style="border-radius: calc({{ $radius }} * .6); background-image: linear-gradient(140deg, var(--site-primary), var(--site-primary-deep)); color: var(--site-on-primary); box-shadow: 0 4px 0 var(--site-primary-deep), 0 14px 24px -10px var(--site-primary);">{{ $i + 1 }}</span>
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
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold transition hover:translate-y-0.5', 'style' => $solidButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="bq3d-rise mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        <div class="bq3d-cards flex flex-wrap justify-center gap-7">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])).' bq3d-card bq3d-card--fan',
                                    'style' => 'border-radius: '.$radius.'; '.$cardSurface,
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="bq3d-rise relative mx-auto max-w-6xl overflow-hidden px-6 py-16 text-center sm:px-16 sm:py-20" style="border-radius: calc({{ $radius }} * 1.4); background-image: linear-gradient(135deg, var(--site-primary), var(--site-primary-deep)); color: var(--site-on-primary); box-shadow: 0 10px 0 color-mix(in srgb, var(--site-primary-deep) 70%, #000), 0 40px 60px -30px rgb(0 0 0 / .5);">
                <div class="bq3d-still pointer-events-none absolute -end-10 -top-10 opacity-40 sm:opacity-60" style="--bq3d-size: 14rem;" aria-hidden="true">
                    @include($sceneView)
                </div>
                <div class="relative mx-auto flex max-w-2xl flex-col items-center gap-6" style="{{ $onPrimaryScope }}">
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="bq3d-type text-[clamp(2.2rem,5vw,3.8rem)] font-extrabold leading-[1.2]" style="--bq3d-type: color-mix(in srgb, var(--site-on-primary) 25%, transparent);">{!! $heading['value'] !!}</h2>
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
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="bq3d-rise bq3d-card--block mx-auto flex max-w-4xl flex-col items-center gap-6 px-6 py-14 text-center sm:px-14" style="border-radius: {{ $radius }}; background-color: var(--site-surface); --bq3d-edge: color-mix(in srgb, var(--site-primary) 55%, var(--site-surface));">
                @if (filled($section['eyebrow'] ?? null))
                    <span class="rounded-full px-4 py-1 text-sm font-bold" style="background-color: color-mix(in srgb, var(--site-primary) 16%, transparent); color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                @endif
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-[2] sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold transition hover:translate-y-0.5', 'style' => $solidButton]])
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
    @include('site.partials.footer', ['footerTheme' => $dp['footer']])
@endif
</div>
