{{--
    "بينتو" — كل قسم شبكة خلايا بأحجام مختلفة (كارت كبير + كروت أصغر حواليه) بحواف دائرية.
    الشبكات بتتحسب حسب العدد الفعلي (SiteRenderer::rowSpans/mosaic) فمفيش خانة فاضية في أي صف
    لأي عدد عناصر — ده كان أكبر عيب في التصميم (5 خدمات = خليتين فاضيين).
    الهيرو نفسه بينتو: كارت النص + صورة بطول الشبكة + كارتين صغيرين (أول الخدمات، وتقييم جوجل
    أو زرار تواصل) — دول نسخة عرض بس من غير data-slot وبيودّوا على القسم الحقيقي.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@include('site.partials.nav')

@php
    $tile = 'rounded-[2rem]';
    $tileShadow = 'box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 6%, transparent), 0 12px 30px -18px rgb(0 0 0 / .35);';
    // نص الفقرات على الكروت: أغمق من muted (اللي تباينه كان تحت AA على اللوحات الفاتحة).
    $bodyColor = 'color: color-mix(in srgb, var(--site-text) 78%, var(--site-surface));';
    $servicesSection = $sections->first(fn ($s) => $s['kind'] === 'list');
    $serviceTitles = $servicesSection
        ? collect($servicesSection['items']->firstWhere('slot.slot_type', 'list')['value'] ?? [])->take(3)->map(fn ($e) => \App\Services\SiteRenderer::splitEntry($e)['title'])
        : collect();
    $siteRating = $sections->first(fn ($s) => ! empty($s['rating']))['rating'] ?? null;
@endphp

@forelse ($sections as $section)
    @php
        $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
        $listItems = $section['items']->where('slot.slot_type', 'list');
        $imageItems = $section['items']->where('slot.slot_type', 'image')->values();
        $linkItems = $section['items']->where('slot.slot_type', 'link');

        // لو القسم فيه أكتر من خانة "text" (زي "تواصل معنا": عنوان + ملاحظة قصيرة)، أول
        // خانة بس بتاخد شكل العنوان، والباقي بيترندر كنص مساند.
        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
        $alt = trim(strip_tags((string) ($heading['value'] ?? ''))) ?: $project->name;
    @endphp

    <section id="{{ $section['key'] }}" class="relative px-4 py-6 sm:px-8 {{ $section['kind'] === 'hero' ? 'pt-6 sm:pt-8' : 'sm:py-8' }}">
        <div class="mx-auto max-w-6xl">
            @if ($section['kind'] === 'hero')
                @php
                    $heroImage = $imageItems->first();
                    $smallTiles = ($serviceTitles->isNotEmpty() ? 1 : 0) + (($siteRating || $contactAnchor) ? 1 : 0);
                @endphp
                <div @class(['grid gap-4 sm:grid-cols-3', 'sm:grid-rows-[1fr_auto]' => $smallTiles > 0])>
                    <div class="flex flex-col justify-center gap-5 {{ $tile }} p-8 sm:col-span-2 sm:p-12" style="background-color: var(--site-surface); {{ $tileShadow }}">
                        @if ($heading)
                            <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.2rem,4.6vw,3.6rem)] font-extrabold leading-[1.2]">{!! $heading['value'] !!}</h1>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                        @endforeach
                        <div class="flex flex-wrap gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-7 py-3.5 text-base font-bold shadow-md transition hover:opacity-90', 'style' => 'background-color: var(--site-primary); color: var(--site-on-primary);']])
                            @endforeach
                            @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                                @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-7 py-3.5 text-base font-bold shadow-md transition hover:opacity-90', 'style' => 'background-color: var(--site-primary); color: var(--site-on-primary);']])
                            @endif
                        </div>
                    </div>

                    @if ($heroImage)
                        {{-- الصورة absolute جوّه الخلية — ارتفاعها بيمشي ورا الشبكة مش ورا أبعاد الصورة
                        الأصلية (صورة طولية كانت بتعمل هيرو 600px، والعرضية 210px). --}}
                        <div class="relative min-h-[16rem] overflow-hidden {{ $tile }} sm:row-span-2" style="{{ $tileShadow }}">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="absolute inset-0 h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                    @else
                        <div class="hidden {{ $tile }} sm:row-span-2 sm:block" style="background-image: linear-gradient(160deg, var(--site-primary), var(--site-primary-deep));" aria-hidden="true"></div>
                    @endif

                    @if ($smallTiles > 0)
                    <div @class(['grid gap-4 sm:col-span-2', 'sm:grid-cols-2' => $smallTiles === 2])>
                        @if ($serviceTitles->isNotEmpty())
                            <a href="#{{ $servicesSection['key'] }}" class="flex flex-col gap-3 {{ $tile }} p-6 transition hover:-translate-y-1" style="background-color: color-mix(in srgb, var(--site-primary) 14%, var(--site-surface)); {{ $tileShadow }}">
                                <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $servicesSection['label'] ?? 'خدماتنا' }}</span>
                                <span class="flex flex-wrap gap-2">
                                    @foreach ($serviceTitles as $title)
                                        <span class="rounded-full px-3 py-1 text-sm font-semibold" style="background-color: var(--site-background);">{{ $title }}</span>
                                    @endforeach
                                </span>
                            </a>
                        @endif
                        @if ($siteRating)
                            <div class="flex flex-col justify-center gap-2 {{ $tile }} p-6" style="background-color: var(--site-surface); {{ $tileShadow }}">
                                <span class="text-4xl font-extrabold" style="color: var(--site-primary);">{{ rtrim(rtrim(number_format($siteRating, 1, '.', ''), '0'), '.') }}</span>
                                @include('site.partials.rating-badge', ['badge' => ['rating' => $siteRating, 'class' => 'self-start']])
                            </div>
                        @elseif ($contactAnchor)
                            <a href="#{{ $contactAnchor }}" class="flex items-center justify-between gap-4 {{ $tile }} p-6 transition hover:-translate-y-1" style="background-color: var(--site-primary); color: var(--site-on-primary); {{ $tileShadow }}">
                                <span class="text-lg font-extrabold">{{ $contactAction['label'] ?? 'تواصل معنا' }}</span>
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full" style="background-color: color-mix(in srgb, var(--site-on-primary) 18%, transparent);">
                                    @include('site.partials.icon', ['name' => ($contactAction['type'] ?? null) === 'whatsapp' ? 'whatsapp' : (($contactAction['type'] ?? null) === 'phone' ? 'phone' : 'arrow'), 'class' => 'h-5 w-5'])
                                </span>
                            </a>
                        @endif
                    </div>
                    @endif
                </div>
            @elseif ($section['kind'] === 'gallery')
                @if ($imageItems->count() === 3 && $heading)
                    {{-- 3 صور: صورة كبيرة 2×2 + كارت العنوان + صورتين = 4×2 بالظبط. --}}
                    <div data-reveal class="grid auto-rows-[10rem] grid-cols-2 gap-4 sm:auto-rows-[13rem] sm:grid-cols-4">
                        <div class="col-span-2 row-span-2 overflow-hidden {{ $tile }}" style="{{ $tileShadow }}">
                            <img data-slot="{{ $imageItems[0]['slot']->key }}" src="{{ $imageItems[0]['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <div class="col-span-2 flex flex-col justify-center gap-2 {{ $tile }} p-7" style="background-color: color-mix(in srgb, var(--site-primary) 16%, var(--site-surface));">
                            <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] ?? '' }}</span>
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.6rem,2.6vw,2.2rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                            @foreach ($supportingItems as $item)
                                <p data-slot="{{ $item['slot']->key }}" class="leading-relaxed" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                            @endforeach
                        </div>
                        @foreach ($imageItems->slice(1) as $item)
                            <div class="overflow-hidden {{ $tile }}" style="{{ $tileShadow }}">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                @else
                    @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
                    <div class="flex flex-col gap-4">
                        @if ($heading || $supportingItems->isNotEmpty())
                            <div class="flex flex-col gap-2 {{ $tile }} p-7" style="background-color: color-mix(in srgb, var(--site-primary) 16%, var(--site-surface));">
                                @if ($heading)
                                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.6rem,2.6vw,2.2rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                                @endif
                                @foreach ($supportingItems as $item)
                                    <p data-slot="{{ $item['slot']->key }}" class="leading-relaxed" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                                @endforeach
                            </div>
                        @endif
                        <div data-reveal class="{{ $mosaic['grid'] }} auto-rows-[9rem] gap-4 sm:auto-rows-[12rem]">
                            @foreach ($imageItems as $item)
                                <div class="overflow-hidden {{ $tile }} {{ $mosaic['items'][$loop->index] }}" style="{{ $tileShadow }}">
                                    <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @elseif (in_array($section['kind'], ['list', 'testimonials'], true))
                <div class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-end justify-between gap-4 {{ $tile }} p-7" style="background-color: var(--site-surface); {{ $tileShadow }}">
                        <div class="flex flex-col gap-2">
                            @if ($heading)
                                <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.7rem,2.8vw,2.4rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                            @endif
                            @foreach ($supportingItems as $item)
                                <p data-slot="{{ $item['slot']->key }}" class="leading-relaxed" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                            @endforeach
                        </div>
                        @if ($section['rating'] ?? null)
                            @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating']]])
                        @endif
                    </div>

                    @foreach ($listItems as $item)
                        @if ($section['kind'] === 'testimonials' && isset($item['testimonials']))
                            @php $spans = \App\Services\SiteRenderer::rowSpans(count($item['testimonials'])); @endphp
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-6">
                                @foreach ($item['testimonials'] as $t)
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'class' => $spans[$loop->index].' rounded-[2rem]',
                                    ]])
                                @endforeach
                            </div>
                        @else
                            @php $entries = (array) $item['value']; $spans = \App\Services\SiteRenderer::rowSpans(count($entries)); @endphp
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-6">
                                @foreach ($entries as $i => $entry)
                                    @php $e = \App\Services\SiteRenderer::splitEntry($entry); $featured = $i === 0; @endphp
                                    <div
                                        data-slot="{{ $item['slot']->key }}"
                                        data-reveal
                                        class="flex min-h-[8.5rem] flex-col justify-between gap-4 {{ $tile }} p-6 transition hover:-translate-y-1 {{ $spans[$i] }}"
                                        style="{{ $featured ? 'background-color: var(--site-primary); color: var(--site-on-primary);' : 'background-color: var(--site-surface);' }} {{ $tileShadow }}"
                                    >
                                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl" style="{{ $featured ? 'background-color: color-mix(in srgb, var(--site-on-primary) 18%, transparent);' : 'background-color: color-mix(in srgb, var(--site-primary) 15%, transparent); color: var(--site-primary);' }}">
                                            @include('site.partials.icon', ['name' => 'sparkles', 'class' => 'h-5 w-5'])
                                        </span>
                                        <span class="flex flex-col gap-1">
                                            <span class="{{ $featured ? 'text-xl' : 'text-base' }} font-bold leading-relaxed">{{ $e['title'] }}</span>
                                            @if ($e['desc'])
                                                <span class="text-sm leading-relaxed" style="{{ $featured ? 'color: color-mix(in srgb, var(--site-on-primary) 85%, transparent);' : $bodyColor }}">{{ $e['desc'] }}</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            @elseif ($section['kind'] === 'cta')
                <div data-reveal class="relative flex flex-col items-center gap-4 overflow-hidden {{ $tile }} px-8 py-14 text-center sm:px-14" style="background-image: linear-gradient(135deg, var(--site-primary), var(--site-primary-deep)); color: var(--site-on-primary); {{ $tileShadow }}">
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,3.4vw,2.75rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="color: color-mix(in srgb, var(--site-on-primary) 86%, transparent);">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'mt-3 rounded-full px-9 py-4 text-base font-bold shadow-md transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                    @endforeach
                    @if ($linkItems->isEmpty() && $contactAction)
                        @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'mt-3 rounded-full px-9 py-4 text-base font-bold shadow-md transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                    @endif
                </div>
            @else
                {{-- نص عادي (زي "من نحن"): صف بينتو — كارت عنوان ملوّن + كارت النص بعرض الضعف،
                بدل كارت ضيق في نص شبكة عريضة. --}}
                <div data-reveal @class(['grid gap-4', 'sm:grid-cols-3' => $supportingItems->isNotEmpty() || $linkItems->isNotEmpty()])>
                    <div class="flex flex-col justify-end gap-2 {{ $tile }} p-7 sm:p-8" style="background-color: color-mix(in srgb, var(--site-primary) 16%, var(--site-surface)); {{ $tileShadow }}">
                        @if (filled($section['eyebrow'] ?? null))
                            <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                        @endif
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.7rem,2.8vw,2.4rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                        @endif
                    </div>
                    @if ($supportingItems->isNotEmpty() || $linkItems->isNotEmpty())
                        <div class="flex flex-col items-start justify-center gap-5 {{ $tile }} p-7 sm:col-span-2 sm:p-10" style="background-color: var(--site-surface); {{ $tileShadow }}">
                            @foreach ($supportingItems as $item)
                                <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                            @endforeach
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-7 py-3 text-sm font-bold shadow-sm transition hover:opacity-90', 'style' => 'background-color: var(--site-primary); color: var(--site-on-primary);']])
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    @include('site.partials.footer', ['footerTheme' => 'rounded'])
@endif
