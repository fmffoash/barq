{{--
    قسم واحد في تصميم "كلاسيك" — كارت دائري الحواف لكل قسم، الشكل حسب $section['kind']
    (hero/gallery/list/testimonials/cta/text، محسوبة في SiteRenderer).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة (كل <img> كمان)، position:relative على كل
    <section>، صفر transform على <img>، وقيمة كل خانة نص لازقة في العنصر من غير مسافات/سطور
    حواليها (whitespace-pre-line كان بيحوّلها لسطور فاضية فوق وتحت كل فقرة).
--}}
@php
    $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
    $listItems = $section['items']->where('slot.slot_type', 'list');
    $imageItems = $section['items']->where('slot.slot_type', 'image')->values();
    $linkItems = $section['items']->where('slot.slot_type', 'link');
    $kind = $section['kind'];
    $onPrimaryBlock = in_array($kind, ['hero', 'cta'], true);

    // لو القسم فيه أكتر من خانة "text" (زي "تواصل معنا": عنوان + ملاحظة قصيرة)، أول خانة
    // بس بتاخد شكل العنوان، والباقي بيترندر كنص مساند.
    $heading = $textItems->firstWhere('slot.slot_type', 'text');
    $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    $alt = trim(strip_tags((string) ($heading['value'] ?? ''))) ?: $project->name;

    $gradient = 'background-image: linear-gradient(135deg, var(--site-primary), var(--site-primary-deep)); color: var(--site-on-primary);';
    $onPrimaryMuted = 'color: color-mix(in srgb, var(--site-on-primary) 86%, transparent);';
    $invertedButton = 'background-color: var(--site-on-primary); color: var(--site-primary);';
    $primaryButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
@endphp

@switch($kind)
    @case('hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="relative px-4 pb-6 pt-6 sm:px-8 sm:pt-8">
            <div class="mx-auto max-w-5xl overflow-hidden rounded-[2.5rem] shadow-2xl" style="{{ $gradient }}">
                <div @class(['grid items-center gap-8 p-7 sm:p-12', 'lg:grid-cols-2 lg:gap-12' => $heroImage])>
                    <div @class([
                        'flex flex-col gap-5',
                        'items-center text-center lg:items-start lg:text-start' => $heroImage,
                        'mx-auto max-w-3xl items-center py-6 text-center' => ! $heroImage,
                    ])>
                        @if ($heading)
                            <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4.6vw,3.4rem)] font-extrabold leading-[1.25]">{!! $heading['value'] !!}</h1>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose" style="{{ $onPrimaryMuted }}">{!! $item['value'] !!}</p>
                        @endforeach
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'mt-2 rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $invertedButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'mt-2 rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $invertedButton]])
                        @endif
                    </div>

                    @if ($heroImage)
                        <div class="aspect-[4/3] w-full overflow-hidden rounded-[1.75rem] shadow-xl" style="box-shadow: 0 0 0 4px color-mix(in srgb, var(--site-on-primary) 22%, transparent), 0 25px 50px -12px rgb(0 0 0 / .35);">
                            <img
                                data-slot="{{ $heroImage['slot']->key }}"
                                src="{{ $heroImage['value'] }}"
                                alt="{{ $alt }}"
                                class="h-full w-full object-cover"
                                loading="eager"
                                fetchpriority="high"
                            >
                        </div>
                    @endif
                </div>
            </div>
        </section>
        @break

    @case('cta')
        <section id="{{ $section['key'] }}" class="relative px-4 py-6 sm:px-8">
            <div data-reveal class="mx-auto flex max-w-5xl flex-col items-center gap-4 rounded-[2.5rem] px-8 py-14 text-center shadow-2xl sm:px-14" style="{{ $gradient }}">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-extrabold leading-[1.3] sm:text-4xl">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="{{ $onPrimaryMuted }}">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'mt-3 rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $invertedButton]])
                @endforeach
                @if ($linkItems->isEmpty() && $contactAction)
                    @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'mt-3 rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $invertedButton]])
                @endif
            </div>
        </section>
        @break

    @default
        <section id="{{ $section['key'] }}" class="relative px-4 py-6 sm:px-8">
            <div data-reveal class="mx-auto flex max-w-5xl flex-col gap-7 rounded-[2rem] p-7 shadow-md sm:p-10" style="background-color: var(--site-surface);">
                @if ($heading || $supportingItems->isNotEmpty() || ($section['rating'] ?? null))
                    <div @class(['flex flex-col gap-3', 'items-center text-center' => in_array($kind, ['list', 'testimonials'], true)])>
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold leading-[1.3] sm:text-4xl">{!! $heading['value'] !!}</h2>
                        @endif
                        @if ($section['rating'] ?? null)
                            @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating']]])
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="max-w-3xl text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif

                @if ($imageItems->isNotEmpty())
                    @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
                    <div class="{{ $mosaic['grid'] }} auto-rows-[8.5rem] gap-3 sm:auto-rows-[11rem] sm:gap-4 lg:auto-rows-[13rem]">
                        @foreach ($imageItems as $item)
                            <div class="overflow-hidden rounded-[1.5rem] {{ $mosaic['items'][$loop->index] }}">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                @endif

                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']) && $kind === 'testimonials')
                        <div class="flex flex-wrap justify-center gap-5">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'theme' => 'rounded',
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                    'style' => 'background-color: var(--site-background);',
                                ]])
                            @endforeach
                        </div>
                    @else
                        @php $entries = (array) $item['value']; @endphp
                        <ul class="flex flex-wrap justify-center gap-4">
                            @foreach ($entries as $entry)
                                @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                <li
                                    data-slot="{{ $item['slot']->key }}"
                                    class="flex items-start gap-3 rounded-[1.5rem] px-5 py-4 text-base leading-relaxed {{ \App\Services\SiteRenderer::cardWidth(count($entries)) }}"
                                    style="background-color: var(--site-background);"
                                >
                                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full" style="background-color: color-mix(in srgb, var(--site-primary) 18%, transparent); color: var(--site-primary);">
                                        @include('site.partials.icon', ['name' => 'check', 'class' => 'h-3.5 w-3.5'])
                                    </span>
                                    <span class="flex flex-col gap-1">
                                        <span class="font-semibold">{{ $e['title'] }}</span>
                                        @if ($e['desc'])
                                            <span class="text-sm" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach

                @foreach ($linkItems as $item)
                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'mx-auto rounded-full px-8 py-3.5 text-base font-bold shadow-md transition hover:opacity-90', 'style' => $primaryButton]])
                @endforeach
            </div>
        </section>
@endswitch
