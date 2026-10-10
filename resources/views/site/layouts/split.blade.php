{{--
    "سبليت" — هيرو مقسوم نص نص (نص/صورة) بارتفاع الشاشة، وأقسام edge-to-edge حادة الحواف بخطوط
    فاصلة رفيعة بدل الكروت. كل قسم ليه رقم كبير (01، 02...) كتفصيلة تحريرية، ونافبار ثابت فوق
    بزرار تواصل. قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على
    كل <section>، صفر transform على <img>.
--}}
@php
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
    $hairline = 'border-color: color-mix(in srgb, var(--site-text) 12%, transparent);';
    $primaryButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="sticky top-0 z-30 border-b backdrop-blur-md" style="{{ $hairline }} background-color: color-mix(in srgb, var(--site-background) 86%, transparent);">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-10">
            <a href="#top" class="min-w-0 max-w-[55vw] truncate text-lg font-extrabold sm:max-w-sm" style="color: var(--site-primary);">{{ $project->name }}</a>
            <ul class="hidden items-center gap-7 text-sm font-semibold md:flex">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="px-4 py-2 text-sm font-bold transition hover:opacity-90" style="{{ $primaryButton }}">تواصل معنا</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md', 'sharp' => true]])
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
        $number = str_pad((string) $loop->index, 2, '0', STR_PAD_LEFT);

        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
        $alt = trim(strip_tags((string) ($heading['value'] ?? ''))) ?: $project->name;
    @endphp

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" @class(['relative grid min-h-[82vh] items-stretch', 'lg:grid-cols-2' => $heroImage])>
            <div @class([
                'flex flex-col justify-center gap-6 px-6 py-14 sm:px-14 lg:py-20',
                'order-2 items-start text-start lg:order-1' => $heroImage,
                'mx-auto max-w-3xl items-center text-center' => ! $heroImage,
            ])>
                <span class="h-1 w-16" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.4rem,5vw,4.25rem)] font-extrabold leading-[1.2]">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                @endforeach
                <div class="mt-2 flex flex-wrap gap-3">
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-8 py-4 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton]])
                    @endforeach
                    @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                        @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'px-8 py-4 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton, 'icon' => 'arrow']])
                    @endif
                </div>
            </div>
            @if ($heroImage)
                <div class="relative order-1 min-h-[42vh] overflow-hidden lg:order-2">
                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="absolute inset-0 h-full w-full object-cover" loading="eager" fetchpriority="high">
                </div>
            @endif
        </section>
    @elseif ($section['kind'] === 'gallery')
        {{-- عنوان بعرض الصفحة فوق شريط صور edge-to-edge (فسيفساء من غير خانات فاضية لأي عدد
        صور) — بدل نص فاضي في جنب وصور ناقصة في الجنب التاني. --}}
        @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
        <section id="{{ $section['key'] }}" class="relative border-t" style="{{ $hairline }}">
            <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-6 py-14 sm:px-14 lg:flex-row lg:items-end">
                <div class="flex items-end gap-5">
                    <span class="text-6xl font-extrabold leading-none sm:text-7xl" style="color: transparent; -webkit-text-stroke: 1.5px var(--site-primary);" aria-hidden="true">{{ $number }}</span>
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.85rem,3.2vw,2.6rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                </div>
                @if ($supportingItems->isNotEmpty())
                    <div class="flex max-w-xl flex-col gap-3">
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="{{ $mosaic['grid'] }} auto-rows-[10rem] gap-px sm:auto-rows-[14rem] lg:auto-rows-[17rem]" style="background-color: color-mix(in srgb, var(--site-text) 12%, transparent);">
                @foreach ($imageItems as $item)
                    <div class="overflow-hidden {{ $mosaic['items'][$loop->index] }}">
                        <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                    </div>
                @endforeach
            </div>
        </section>
    @elseif (in_array($section['kind'], ['list', 'testimonials'], true))
        <section id="{{ $section['key'] }}" class="relative border-t px-6 py-16 sm:px-14 sm:py-20" style="{{ $hairline }}">
            <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1fr_2.2fr] lg:gap-16">
                <div class="flex flex-col items-start gap-4">
                    <span class="text-6xl font-extrabold leading-none sm:text-7xl" style="color: transparent; -webkit-text-stroke: 1.5px var(--site-primary);" aria-hidden="true">{{ $number }}</span>
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.85rem,3.2vw,2.6rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                    @if ($section['rating'] ?? null)
                        @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating'], 'class' => 'rounded-none']])
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                </div>

                <div class="flex flex-col gap-6">
                    @foreach ($listItems as $item)
                        @if ($section['kind'] === 'testimonials' && isset($item['testimonials']))
                            <div class="grid gap-5 sm:grid-cols-2">
                                @foreach ($item['testimonials'] as $t)
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'theme' => 'sharp',
                                        // كارت فردي في الآخر بياخد العرض كله بدل نص صف فاضي.
                                        'class' => $loop->last && $loop->count % 2 === 1 ? 'sm:col-span-2' : '',
                                    ]])
                                @endforeach
                            </div>
                        @else
                            <ul class="grid border-t sm:grid-cols-2 sm:gap-x-12" style="{{ $hairline }}">
                                @foreach ((array) $item['value'] as $i => $entry)
                                    @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                    <li data-slot="{{ $item['slot']->key }}" class="flex items-baseline gap-5 border-b py-5" style="{{ $hairline }}">
                                        <span class="text-sm font-extrabold" style="color: var(--site-primary);">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="flex flex-col gap-1">
                                            <span class="text-lg font-bold">{{ $e['title'] }}</span>
                                            @if ($e['desc'])
                                                <span class="text-sm leading-relaxed" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-6 py-20 sm:px-14" style="background-color: var(--site-primary); color: var(--site-on-primary);">
            <div data-reveal class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 lg:flex-row lg:items-center">
                <div class="flex max-w-3xl flex-col gap-4">
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,3.8vw,3.1rem)] font-extrabold leading-[1.25]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="color: color-mix(in srgb, var(--site-on-primary) 85%, transparent);">{!! $item['value'] !!}</p>
                    @endforeach
                </div>
                <div class="flex shrink-0 flex-wrap gap-3">
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-9 py-4 text-base font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                    @endforeach
                    @if ($linkItems->isEmpty() && $contactAction)
                        @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'px-9 py-4 text-base font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                    @endif
                </div>
            </div>
        </section>
    @else
        {{-- نص عادي (زي "من نحن"): رقم القسم والعنوان في عمود، وخط رأسي رفيع، والنص في عمود
        أعرض — نفس هوية التقسيم بدل فقرة متوسّطة في نص الصفحة. --}}
        <section id="{{ $section['key'] }}" class="relative border-t px-6 py-16 sm:px-14 sm:py-20" style="{{ $hairline }}">
            <div data-reveal class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1fr_2.2fr] lg:gap-16">
                <div class="flex flex-col items-start gap-4">
                    <span class="text-6xl font-extrabold leading-none sm:text-7xl" style="color: transparent; -webkit-text-stroke: 1.5px var(--site-primary);" aria-hidden="true">{{ $number }}</span>
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.85rem,3.2vw,2.6rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                </div>
                @if ($supportingItems->isNotEmpty() || $linkItems->isNotEmpty())
                    <div class="flex flex-col items-start gap-6 lg:border-s lg:ps-16" style="{{ $hairline }}">
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="max-w-prose text-xl leading-loose">{!! $item['value'] !!}</p>
                        @endforeach
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton]])
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
