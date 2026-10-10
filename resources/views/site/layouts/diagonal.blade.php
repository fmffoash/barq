{{--
    "دياجونال" — قصّات مائلة في كل مكان بتدي الصفحة إحساس حركة: هيرو بلون أساسي بعرض الشاشة
    بحافة مايلة والصورة "طالعة" منها، أحزمة مايلة للخدمات والتواصل، وصور على شكل متوازي أضلاع.
    (2026-10-10) اتعدّل: الهيرو بقى يبدأ من فوق الشاشة تحت النافبار (كان فيه شريط بلون الخلفية
    فوقه)، الصورة جنب العنوان مش تحته، ظل الصور بيمشي مع القصّة (drop-shadow على حاوية برّه
    القصّة — box-shadow كان بيبان مستطيل "شبح" تحت كل صورة)، والآراء كروت اقتباس.
    القصّة المايلة على طبقة خلفية جوّه القسم (مش على القسم نفسه) — فالصورة تقدر تعدّي الحافة.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@include('site.partials.nav')

@php
    $card = 'background-color: var(--site-background); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 7%, transparent), 0 14px 30px -20px rgb(0 0 0 / .4);';
    $bodyColor = 'color: color-mix(in srgb, var(--site-text) 78%, var(--site-surface));';
    $primaryButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $invertButton = 'background-color: var(--site-on-primary); color: var(--site-primary);';
    $parallelogram = 'clip-path: polygon(7% 0, 100% 0, 93% 100%, 0 100%);';
    $shadow = 'filter: drop-shadow(0 18px 24px rgb(0 0 0 / .22));';
@endphp

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
        {{-- -mt بارتفاع النافبار: اللون بيبدأ من أول الشاشة والنافبار طايف فوقه (زي جاليري). --}}
        <section id="{{ $section['key'] }}" class="relative -mt-[4.5rem] px-5 pb-20 pt-[calc(4.5rem+3rem)] sm:px-10 sm:pb-24 lg:pb-28 lg:pt-[calc(4.5rem+4.5rem)]">
            {{-- موبايل: الحافة المايلة تحت خالص (الكلام والصورة جوّه اللون). من lg: اللون نازل لآخر
            القسم ناحية الكلام بس، وطالع لـ75% ناحية الصورة — فالصورة بتعدّي الحافة. --}}
            <div class="pointer-events-none absolute inset-0 lg:hidden" style="background-color: var(--site-primary); clip-path: polygon(0 0, 100% 0, 100% calc(100% - 3.5rem), 0 100%);" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 hidden lg:block" style="background-color: var(--site-primary); clip-path: polygon(0 0, 100% 0, 100% 100%, 0 75%);" aria-hidden="true"></div>
            <div @class(['relative mx-auto grid max-w-6xl items-center gap-12', 'lg:grid-cols-[1.2fr_1fr] lg:gap-16' => $heroImage])>
                <div @class(['flex flex-col gap-6', 'items-start text-start' => $heroImage, 'items-center text-center' => ! $heroImage]) style="color: var(--site-on-primary);">
                    <span class="inline-block -skew-x-12 px-4 py-1 text-sm font-extrabold" style="background-color: color-mix(in srgb, var(--site-on-primary) 16%, transparent);">{{ $project->name }}</span>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="max-w-3xl text-[clamp(2.5rem,5.6vw,4.6rem)] font-black leading-[1.2]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose sm:text-xl" style="opacity: .9;">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="mt-1 flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $invertButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $invertButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-4 w-4']])
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    <div class="mx-auto w-full max-w-md lg:max-w-none lg:translate-y-8" style="{{ $shadow }}">
                        <div class="aspect-[4/5] overflow-hidden sm:aspect-[5/5]" style="{{ $parallelogram }}">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        {{-- حزام مايل من فوق وتحت بلون الـsurface، وجوّاه كروت بأرقام على شكل متوازي أضلاع. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-28 sm:px-10 sm:py-32">
            <div class="pointer-events-none absolute inset-0" style="background-color: var(--site-surface); clip-path: polygon(0 3.5rem, 100% 0, 100% calc(100% - 3.5rem), 0 100%);" aria-hidden="true"></div>
            <div data-reveal class="relative mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                @foreach ($listItems as $item)
                    @php $entries = (array) $item['value']; $width = \App\Services\SiteRenderer::cardWidth(count($entries)); @endphp
                    <div class="flex flex-wrap justify-center gap-6">
                        @foreach ($entries as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                            <div data-slot="{{ $item['slot']->key }}" class="flex items-start gap-4 rounded-2xl p-6 {{ $width }}" style="{{ $card }}">
                                <span class="flex h-11 w-14 shrink-0 items-center justify-center text-base font-black" style="{{ $primaryButton }} {{ $parallelogram }}">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="flex flex-col gap-1.5 pt-1.5">
                                    <span class="text-lg font-bold leading-snug">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="text-base leading-relaxed" style="{{ $bodyColor }}">{{ $e['desc'] }}</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-7 py-3 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'gallery')
        {{-- صور متوازي أضلاع متداخلة: القصّة على حاوية الصورة (overflow-hidden)، والظل
        drop-shadow على حاوية برّه — بيمشي مع القصّة بالظبط. --}}
        @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                <div class="{{ $mosaic['grid'] }} auto-rows-[9rem] gap-2 sm:auto-rows-[12rem] lg:auto-rows-[14rem]" style="{{ $shadow }}">
                    @foreach ($imageItems as $item)
                        <div class="overflow-hidden {{ $mosaic['items'][$loop->index] }}" style="clip-path: polygon(5% 0, 100% 0, 95% 100%, 0 100%);">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'class' => 'border-t-4 '.\App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                    'style' => 'border-top-color: var(--site-primary);',
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        {{-- حزام التواصل: لون أساسي بعرض الشاشة بحافة مايلة من فوق. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 pb-20 pt-28 sm:px-10 sm:pb-24 sm:pt-32" style="color: var(--site-on-primary);">
            <div class="pointer-events-none absolute inset-0" style="background-image: linear-gradient(120deg, var(--site-primary), var(--site-primary-deep)); clip-path: polygon(0 4rem, 100% 0, 100% 100%, 0 100%);" aria-hidden="true"></div>
            <div data-reveal class="relative mx-auto flex max-w-4xl flex-col items-center gap-5 text-center">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-3xl text-[clamp(2.2rem,4.6vw,3.6rem)] font-black leading-[1.2]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="opacity: .9;">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty() || $contactAction)
                    <div class="mt-2 flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-9 py-4 text-lg font-bold shadow-lg transition hover:opacity-90', 'style' => $invertButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-full px-9 py-4 text-lg font-bold shadow-lg transition hover:opacity-90', 'style' => $invertButton]])
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @else
        {{-- نص عادي ("من نحن" وأي قسم مضاف): عنوان كبير على جنب وشريحة مايلة باللون الأساسي،
        والنص على الجنب التاني. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
            <div data-reveal @class(['mx-auto grid max-w-6xl gap-8', 'lg:grid-cols-[1fr_1.4fr] lg:items-center lg:gap-16' => $heading && $supportingItems->isNotEmpty()])>
                @if ($heading)
                    <div class="flex flex-col items-start gap-4">
                        @if (filled($section['eyebrow'] ?? null))
                            <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                        @endif
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4vw,3.2rem)] font-black leading-[1.25]">{!! $heading['value'] !!}</h2>
                        <span class="h-3 w-24" style="background-color: var(--site-primary); {{ $parallelogram }}" aria-hidden="true"></span>
                    </div>
                @endif
                <div class="flex flex-col gap-5">
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-xl leading-loose sm:leading-[2]" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                    @endforeach
                    @if ($linkItems->isNotEmpty())
                        <div class="flex flex-wrap gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-7 py-3 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton]])
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
    @include('site.partials.footer', ['footerTheme' => 'plain'])
@endif
