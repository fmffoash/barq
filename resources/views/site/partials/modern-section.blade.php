{{--
    قسم واحد في تصميم "مودرن" — الشكل حسب $section['kind']. الأقسام العادية بتتبادل خلفيتها
    ($index فردي = شريط بلون أهدى بعرض الشاشة) عشان الصفحة يبقى ليها إيقاع بدل نفس الخلفية.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@php
    $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
    $listItems = $section['items']->where('slot.slot_type', 'list');
    $imageItems = $section['items']->where('slot.slot_type', 'image')->values();
    $linkItems = $section['items']->where('slot.slot_type', 'link');

    $heading = $textItems->firstWhere('slot.slot_type', 'text');
    $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    $alt = trim(strip_tags((string) ($heading['value'] ?? ''))) ?: $project->name;

    $banded = $index % 2 === 1;
    $bandStyle = $banded ? 'background-color: color-mix(in srgb, var(--site-surface) 55%, var(--site-background));' : '';
    $cardSurface = $banded ? 'var(--site-background)' : 'var(--site-surface)';
    $gradient = 'background-image: linear-gradient(135deg, var(--site-primary), var(--site-primary-deep)); color: var(--site-on-primary);';
    $onPrimaryMuted = 'color: color-mix(in srgb, var(--site-on-primary) 86%, transparent);';
    $invertedButton = 'background-color: var(--site-on-primary); color: var(--site-primary);';
@endphp

@switch($section['kind'])
    @case('hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="relative px-4 pb-10 pt-6 sm:px-8 sm:pt-10">
            <div class="relative isolate mx-auto max-w-6xl overflow-hidden rounded-[2.5rem] shadow-2xl" style="{{ $gradient }}">
                {{-- دواير ضوء زخرفية خلف المحتوى (pointer-events-none — مش بتمنع الدوس على الخانات). --}}
                <div class="pointer-events-none absolute -z-10 -end-24 -top-24 h-72 w-72 rounded-full opacity-25 blur-3xl" style="background-color: var(--site-on-primary);" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -z-10 -bottom-32 -start-16 h-80 w-80 rounded-full opacity-20 blur-3xl" style="background-color: var(--site-background);" aria-hidden="true"></div>

                <div @class(['grid items-center gap-10 px-7 py-12 sm:px-12 sm:py-16', 'lg:grid-cols-[1.1fr_1fr]' => $heroImage])>
                    <div @class([
                        'flex flex-col gap-5',
                        'order-2 items-center text-center lg:order-1 lg:items-start lg:text-start' => $heroImage,
                        'mx-auto max-w-3xl items-center text-center' => ! $heroImage,
                    ])>
                        @if ($siteRating ?? null)
                            @include('site.partials.rating-badge', ['badge' => ['rating' => $siteRating, 'style' => 'background-color: color-mix(in srgb, var(--site-on-primary) 16%, transparent);']])
                        @endif
                        @if ($heading)
                            <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.25rem,4.8vw,3.9rem)] font-extrabold leading-[1.2]">{!! $heading['value'] !!}</h1>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose" style="{{ $onPrimaryMuted }}">{!! $item['value'] !!}</p>
                        @endforeach

                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl', 'style' => $invertedButton]])
                            @endforeach
                            @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                                @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl', 'style' => $invertedButton]])
                            @endif
                        </div>
                    </div>

                    @if ($heroImage)
                        <div class="relative order-1 lg:order-2">
                            <div class="relative aspect-[4/3] w-full overflow-hidden rounded-[2rem] shadow-2xl lg:aspect-square" style="box-shadow: 0 0 0 4px color-mix(in srgb, var(--site-on-primary) 20%, transparent), 0 30px 60px -15px rgb(0 0 0 / .45);">
                                <img
                                    data-slot="{{ $heroImage['slot']->key }}"
                                    src="{{ $heroImage['value'] }}"
                                    alt="{{ $alt }}"
                                    class="h-full w-full object-cover"
                                    loading="eager"
                                    fetchpriority="high"
                                >
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
        @break

    @case('gallery')
        @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
        <section id="{{ $section['key'] }}" class="relative px-4 py-16 sm:px-8 sm:py-20" style="{{ $bandStyle }}">
            <div class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])

                <div data-reveal class="{{ $mosaic['grid'] }} auto-rows-[9rem] gap-3 sm:auto-rows-[12rem] sm:gap-4 lg:auto-rows-[14rem]">
                    @foreach ($imageItems as $item)
                        <div class="overflow-hidden rounded-[1.75rem] shadow-lg {{ $mosaic['items'][$loop->index] }}">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('list')
    @case('testimonials')
        <section id="{{ $section['key'] }}" class="relative px-4 py-16 sm:px-8 sm:py-20" style="{{ $bandStyle }}">
            <div class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])

                @foreach ($listItems as $item)
                    @if ($section['kind'] === 'testimonials' && isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                    'style' => 'background-color: '.$cardSurface.';',
                                ]])
                            @endforeach
                        </div>
                    @else
                        @php $entries = (array) $item['value']; @endphp
                        <ul class="flex flex-wrap justify-center gap-6">
                            @foreach ($entries as $entry)
                                @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                <li
                                    data-slot="{{ $item['slot']->key }}"
                                    data-reveal
                                    class="flex items-start gap-4 rounded-[1.75rem] p-6 shadow-md transition hover:-translate-y-1 hover:shadow-xl {{ \App\Services\SiteRenderer::cardWidth(count($entries)) }}"
                                    style="background-color: {{ $cardSurface }}; box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 6%, transparent), 0 10px 25px -15px rgb(0 0 0 / .3);"
                                >
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl" style="background-color: color-mix(in srgb, var(--site-primary) 16%, transparent); color: var(--site-primary);">
                                        @include('site.partials.icon', ['name' => 'sparkles', 'class' => 'h-5 w-5'])
                                    </span>
                                    <span class="flex flex-col gap-1 pt-1.5">
                                        <span class="text-base font-bold leading-relaxed">{{ $e['title'] }}</span>
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
        </section>
        @break

    @case('cta')
        <section id="{{ $section['key'] }}" class="relative px-4 py-16 sm:px-8">
            <div data-reveal class="relative isolate mx-auto flex max-w-6xl flex-col items-center gap-4 overflow-hidden rounded-[2.5rem] px-8 py-16 text-center shadow-2xl sm:px-16" style="{{ $gradient }}">
                <div class="pointer-events-none absolute -z-10 -start-20 -top-20 h-64 w-64 rounded-full opacity-20 blur-3xl" style="background-color: var(--site-on-primary);" aria-hidden="true"></div>
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,3.4vw,2.75rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="{{ $onPrimaryMuted }}">{!! $item['value'] !!}</p>
                @endforeach

                @foreach ($linkItems as $item)
                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'mt-3 rounded-full px-9 py-4 text-base font-bold shadow-lg transition hover:-translate-y-0.5', 'style' => $invertedButton]])
                @endforeach
                @if ($linkItems->isEmpty() && $contactAction)
                    @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'mt-3 rounded-full px-9 py-4 text-base font-bold shadow-lg transition hover:-translate-y-0.5', 'style' => $invertedButton]])
                @endif
            </div>
        </section>
        @break

    @default
        {{-- نص عادي (زي "من نحن"): عمودين على الشاشات الكبيرة — العنوان في جنب والنص المساند
        بخط فاصل ملوّن في الجنب التاني، بدل كارت صغير في نص الشاشة. --}}
        <section id="{{ $section['key'] }}" class="relative px-4 py-16 sm:px-8 sm:py-20" style="{{ $bandStyle }}">
            <div data-reveal class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1fr_1.4fr] lg:gap-16">
                <div class="flex flex-col items-start gap-3">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="inline-flex items-center gap-2 text-sm font-bold" style="color: var(--site-primary);">
                            <span class="h-0.5 w-6 rounded-full" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                            {{ $section['eyebrow'] }}
                        </span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.85rem,3.2vw,2.6rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                </div>
                @if ($supportingItems->isNotEmpty() || $linkItems->isNotEmpty())
                    <div class="flex flex-col items-start gap-5 border-s-4 ps-6" style="border-color: var(--site-primary);">
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3 text-base font-bold shadow-md transition hover:opacity-90', 'style' => 'background-color: var(--site-primary); color: var(--site-on-primary);']])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
@endswitch
