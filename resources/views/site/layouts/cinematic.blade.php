{{--
    "سينمائي" (2026-10-11) — فاخر بطابع فيلم: الهيرو "لقطة" بعرض الشاشة بنسبة سينما (2.39:1 على
    الكمبيوتر) بين شريطين أسود، والعنوان تحتها زي الترجمة؛ كل قسم "مشهد" بترقيم (مشهد ٠٢)، المعرض
    شريط فيلم أفقي بثقوب على الجنبين وتمرير بالسحب، الخدمات "تترات" في النص، والتواصل تذكرة عرض.
    على خلفيات داكنة بس (LAYOUT_MODES).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>، النص فوق الصورة جوّه حاوية relative z-10 +
    bq-free-position-boundary + pointer-events none/auto.
--}}
@php
    $hairline = 'border-color: color-mix(in srgb, var(--site-text) 14%, transparent);';
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $outlineButton = 'border-color: color-mix(in srgb, var(--site-text) 40%, transparent);';
    $sprockets = 'background-image: repeating-linear-gradient(to left, transparent 0 14px, color-mix(in srgb, var(--site-text) 22%, transparent) 14px 28px); background-size: 100% 8px; background-repeat: no-repeat;';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
    $scene = 1;
    $arabicDigits = fn (int $n) => strtr(str_pad((string) $n, 2, '0', STR_PAD_LEFT), ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="relative z-30 border-b px-5 sm:px-10" style="{{ $hairline }} background-color: #000; color: #f5f5f4;">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 py-4">
            <a href="#top" class="min-w-0 max-w-[55vw] truncate text-lg font-bold sm:max-w-sm">{{ $project->name }}</a>
            <ul class="hidden items-center gap-8 text-sm font-medium md:flex" style="color: rgb(245 245 244 / .75);">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:text-white">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="px-4 py-2 text-sm font-bold transition hover:opacity-85" style="{{ $solidButton }}">احجز الآن</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md', 'sharp' => true, 'panelStyle' => 'background-color: #0a0a0a; color: #f5f5f4;']])
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
        $sceneLabel = 'مشهد '.$arabicDigits($section['kind'] === 'hero' ? 1 : ++$scene);
    @endphp

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="relative" style="background-color: #000; color: #f5f5f4;">
            @if ($heroImage)
                {{-- اللقطة: نسبة سينما على الكمبيوتر، أعلى شوية على الموبايل عشان الصورة متبقاش شريط رفيع. --}}
                <div class="relative aspect-[4/3] w-full overflow-hidden sm:aspect-[2.39/1]">
                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                    <div class="pointer-events-none absolute inset-0" style="background-image: linear-gradient(to bottom, rgb(0 0 0 / .35), transparent 30%, transparent 65%, rgb(0 0 0 / .85));"></div>
                    <div class="bq-free-position-boundary pointer-events-none absolute inset-x-0 top-0 z-10 flex items-center justify-between px-5 py-4 text-xs font-bold sm:px-10" style="color: rgb(245 245 244 / .8);">
                        <span>{{ $sceneLabel }}</span>
                        <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full" style="background-color: #ef4444;" aria-hidden="true"></span>{{ $project->name }}</span>
                    </div>
                </div>
            @endif
            {{-- "الترجمة": العنوان والوصف في الشريط الأسود تحت اللقطة. --}}
            <div class="relative z-10 mx-auto flex max-w-5xl flex-col items-center gap-6 px-6 pb-16 pt-10 text-center sm:pb-20 {{ $heroImage ? '' : 'min-h-[70vh] justify-center' }}">
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.4rem,5.6vw,4.8rem)] font-bold leading-[1.2]">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose sm:text-xl" style="color: rgb(245 245 244 / .75);">{!! $item['value'] !!}</p>
                @endforeach
                <div class="mt-2 flex flex-wrap justify-center gap-3">
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                    @endforeach
                    @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                        @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton, 'icon' => false]])
                    @endif
                    @if ($navLinks->isNotEmpty())
                        <a href="#{{ $navLinks->first()['key'] }}" class="inline-flex items-center gap-2 border px-9 py-4 text-base font-bold transition hover:opacity-75" style="border-color: rgb(245 245 244 / .4);">
                            <span class="h-0 w-0 border-y-[6px] border-e-[10px] border-y-transparent" style="border-inline-end-color: currentColor;" aria-hidden="true"></span>
                            {{ $navLinks->first()['label'] }}
                        </a>
                    @endif
                </div>
            </div>
        </section>

    @else
        @php $isCta = $section['kind'] === 'cta'; @endphp
        <section id="{{ $section['key'] }}" class="relative overflow-hidden border-t px-5 py-20 sm:px-10 sm:py-28" style="{{ $hairline }}">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-14">
                @unless ($isCta)
                    <div class="mx-auto flex max-w-2xl flex-col items-center gap-5 text-center">
                        <span class="flex items-center gap-3 text-xs font-bold" style="color: var(--site-primary);">
                            <span class="h-px w-10" style="background-color: currentColor;" aria-hidden="true"></span>
                            {{ $sceneLabel }}@if (filled($section['eyebrow'] ?? null)) · {{ $section['eyebrow'] }}@endif
                            <span class="h-px w-10" style="background-color: currentColor;" aria-hidden="true"></span>
                        </span>
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4.4vw,3.6rem)] font-bold leading-[1.25]">{!! $heading['value'] !!}</h2>
                        @endif
                        @if (! empty($section['rating']))
                            @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating']]])
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endunless

                @if ($section['kind'] === 'gallery')
                    {{-- شريط فيلم: تمرير أفقي بالسحب (scroll-snap)، ثقوب فوق وتحت. --}}
                    <div class="-mx-5 sm:-mx-10" style="background-color: #000;">
                        <div class="h-2" style="{{ $sprockets }}" aria-hidden="true"></div>
                        <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto px-5 py-3 sm:px-10" style="scrollbar-width: thin;">
                            @foreach ($imageItems as $item)
                                <div class="relative aspect-[3/2] w-[78vw] shrink-0 snap-center overflow-hidden sm:w-[30rem]">
                                    <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                                </div>
                            @endforeach
                        </div>
                        <div class="h-2" style="{{ $sprockets }}" aria-hidden="true"></div>
                    </div>

                @elseif ($section['kind'] === 'list')
                    {{-- "تترات": كل خدمة سطر في النص — الاسم كبير والوصف تحته صغير. --}}
                    @foreach ($listItems as $item)
                        <ul class="mx-auto flex max-w-3xl flex-col items-center gap-9 text-center">
                            @foreach ((array) $item['value'] as $entry)
                                @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                <li data-slot="{{ $item['slot']->key }}" class="flex flex-col items-center gap-2">
                                    <span class="text-2xl font-bold sm:text-3xl">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $e['desc'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endforeach

                @elseif ($section['kind'] === 'testimonials')
                    @foreach ($listItems as $item)
                        @if (isset($item['testimonials']))
                            <div class="flex flex-wrap justify-center gap-x-6 gap-y-14">
                                @foreach ($item['testimonials'] as $t)
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'theme' => 'editorial',
                                        'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])).' text-center items-center',
                                    ]])
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                @elseif ($isCta)
                    {{-- تذكرة عرض: كعب مقطوع بخط متقطّع ونصف دايرتين على الجنبين. --}}
                    <div class="relative mx-auto grid w-full max-w-4xl overflow-hidden sm:grid-cols-[1fr_auto]" style="background-color: var(--site-surface);">
                        <span class="pointer-events-none absolute -start-4 top-1/2 h-8 w-8 -translate-y-1/2 rounded-full" style="background-color: var(--site-background);" aria-hidden="true"></span>
                        <span class="pointer-events-none absolute -end-4 top-1/2 h-8 w-8 -translate-y-1/2 rounded-full" style="background-color: var(--site-background);" aria-hidden="true"></span>
                        <div class="flex flex-col gap-5 p-8 sm:p-12">
                            <span class="text-xs font-bold" style="color: var(--site-primary);">{{ $sceneLabel }} · {{ $project->name }}</span>
                            @if ($heading)
                                <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4.6vw,3.4rem)] font-bold leading-[1.2]">{!! $heading['value'] !!}</h2>
                            @endif
                            @foreach ($supportingItems as $item)
                                <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-center border-t-2 border-dashed p-8 sm:border-s-2 sm:border-t-0 sm:p-10" style="{{ $hairline }}">
                            @if ($linkItems->isNotEmpty() || $contactAction)
                                <div class="flex flex-col gap-3">
                                    @foreach ($linkItems as $item)
                                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-9 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                                    @endforeach
                                    @if ($linkItems->isEmpty() && $contactAction)
                                        @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'px-9 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                                    @endif
                                </div>
                            @else
                                <span class="text-4xl font-bold" style="color: var(--site-primary);" aria-hidden="true">★</span>
                            @endif
                        </div>
                    </div>

                @else
                    @if ($linkItems->isNotEmpty())
                        <div class="flex flex-wrap justify-center gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'border px-9 py-4 text-base font-bold transition hover:opacity-75', 'style' => $outlineButton]])
                            @endforeach
                        </div>
                    @endif
                @endif

                @if (in_array($section['kind'], ['list', 'gallery'], true) && $linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-9 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
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
