{{--
    "تايم لاين" — الصفحة كأنها رحلة: خط رأسي باللون الأساسي بيمشي جنب كلام الهيرو، والخدمات خط
    زمني حقيقي بنقط مرقّمة بتتبادل يمين/شمال الخط من الشاشات المتوسطة (موبايل: خط واحد على جنب).
    (2026-10-10) اتعاد تصميمه: الخط الزمني كان بيتطبّق على أي قايمة (آراء العملاء كانت بتطلع
    "خطوة 1 و2")، والتبادل المكتوب في الوصف مكانش متنفّذ أصلاً، والنقط كانت مزاحة 8px عن الخط.
    دلوقتي الآراء كروت اقتباس، والمعرض صفوف صور متساوية في النص (أي عدد).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@include('site.partials.nav')

@php
    $card = 'background-color: var(--site-surface); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 7%, transparent), 0 14px 32px -20px rgb(0 0 0 / .4);';
    $bodyColor = 'color: color-mix(in srgb, var(--site-text) 78%, var(--site-surface));';
    $primaryButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $rail = 'background-color: color-mix(in srgb, var(--site-primary) 35%, transparent);';
    $servicesSection = $sections->first(fn ($s) => $s['kind'] === 'list');
    $firstService = $servicesSection
        ? \App\Services\SiteRenderer::splitEntry(collect($servicesSection['items']->firstWhere('slot.slot_type', 'list')['value'] ?? [])->first())['title']
        : null;
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
        <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-8 sm:py-20">
            <div @class(['mx-auto grid max-w-6xl items-center gap-14', 'lg:grid-cols-[1.1fr_1fr]' => $heroImage])>
                {{-- الكلام جنب خط رأسي بنقطتين (بداية ونهاية "الرحلة") — زخرفة بس. --}}
                <div class="relative flex flex-col items-start gap-6 ps-8 sm:ps-12">
                    <span class="pointer-events-none absolute bottom-3 start-[5px] top-3 w-0.5 rounded-full" style="background-image: linear-gradient(to bottom, var(--site-primary), color-mix(in srgb, var(--site-primary) 10%, transparent));" aria-hidden="true"></span>
                    <span class="pointer-events-none absolute start-0 top-2 h-3 w-3 rounded-full" style="background-color: var(--site-primary); box-shadow: 0 0 0 5px color-mix(in srgb, var(--site-primary) 20%, transparent);" aria-hidden="true"></span>
                    <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $project->name }}</span>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.4rem,5.2vw,4.3rem)] font-extrabold leading-[1.2]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="mt-1 flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-md transition hover:opacity-90', 'style' => $primaryButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-md transition hover:opacity-90', 'style' => $primaryButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-4 w-4']])
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                        <div class="aspect-[4/5] overflow-hidden rounded-[2rem] sm:aspect-[5/5]" style="box-shadow: 0 30px 60px -30px rgb(0 0 0 / .5);">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                        @if ($firstService && $servicesSection)
                            {{-- نسخة عرض من أول خدمة (من غير data-slot) — بتودّي على قسم الخدمات. --}}
                            <a href="#{{ $servicesSection['key'] }}" class="absolute -bottom-5 start-5 flex max-w-[80%] items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold sm:start-8" style="{{ $card }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" style="{{ $primaryButton }}">@include('site.partials.icon', ['name' => 'check', 'class' => 'h-4 w-4'])</span>
                                <span class="truncate">{{ $firstService }}</span>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-5xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                @foreach ($listItems as $item)
                    {{-- الخط: موبايل على بُعد 1.25rem من البداية، ومن md في النص بالظبط (start-1/2 +
                    هامش سالب بنص العرض — logical فبيشتغل RTL وLTR). النقطة بنفس الحسبة فمركزها على
                    الخط بالظبط. --}}
                    <ol class="relative flex flex-col gap-8 md:gap-6">
                        <span class="pointer-events-none absolute bottom-6 start-5 top-6 -ms-px w-0.5 md:start-1/2" style="{{ $rail }}" aria-hidden="true"></span>
                        @foreach ((array) $item['value'] as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); $even = $i % 2 === 0; @endphp
                            <li class="relative ps-16 md:grid md:grid-cols-2 md:ps-0">
                                <span class="absolute start-0 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full text-sm font-extrabold tabular-nums md:start-1/2 md:-ms-5" style="{{ $primaryButton }} box-shadow: 0 0 0 6px var(--site-background);" aria-hidden="true">{{ $i + 1 }}</span>
                                <div data-slot="{{ $item['slot']->key }}" @class(['flex flex-col gap-2 rounded-2xl p-6', 'md:col-start-1 md:me-12' => $even, 'md:col-start-2 md:ms-12' => ! $even]) style="{{ $card }}">
                                    <span class="text-lg font-bold leading-snug sm:text-xl">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="text-base leading-relaxed" style="{{ $bodyColor }}">{{ $e['desc'] }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
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

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24" style="background-color: color-mix(in srgb, var(--site-surface) 55%, var(--site-background));">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'gallery')
        {{-- صفوف صور متساوية في النص (5 = 3+2، 4 = 2+2...) بدل شريط بيسيب فراغ على جنب. --}}
        @php
            // موبايل عمودين (3 صور ورا بعض بعرض الشاشة كانوا أطول من 1200px تمرير).
            $width = match (true) {
                $imageItems->count() <= 1 => 'w-full',
                in_array($imageItems->count(), [2, 4], true) => 'w-[calc(50%-0.5rem)] sm:w-[calc(50%-0.75rem)]',
                default => 'w-[calc(50%-0.5rem)] sm:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)]',
            };
        @endphp
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                <div class="flex flex-wrap justify-center gap-4 sm:gap-6">
                    @foreach ($imageItems as $item)
                        <div @class(['overflow-hidden rounded-[1.5rem]', $width, 'aspect-[16/9]' => $imageItems->count() === 1, 'aspect-[4/3]' => $imageItems->count() > 1]) style="box-shadow: 0 18px 40px -26px rgb(0 0 0 / .55);">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="relative mx-auto flex max-w-5xl flex-col items-center gap-5 overflow-hidden rounded-[2.5rem] px-6 py-16 text-center sm:px-12" style="background-image: linear-gradient(135deg, var(--site-primary), var(--site-primary-deep)); color: var(--site-on-primary);">
                <span class="flex h-12 w-12 items-center justify-center rounded-full text-base font-extrabold" style="background-color: color-mix(in srgb, var(--site-on-primary) 16%, transparent);" aria-hidden="true">@include('site.partials.icon', ['name' => 'check', 'class' => 'h-6 w-6'])</span>
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-3xl text-[clamp(2rem,4.2vw,3.3rem)] font-extrabold leading-[1.25]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="opacity: .9;">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty() || $contactAction)
                    <div class="mt-2 flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-9 py-4 text-lg font-bold shadow-lg transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                        @endforeach
                        @if ($linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-full px-9 py-4 text-lg font-bold shadow-lg transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @else
        {{-- نص عادي ("من نحن" وأي قسم مضاف): كارت "محطة" بشريط باللون الأساسي على جنب. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-20">
            <div data-reveal class="relative mx-auto flex max-w-4xl flex-col gap-5 rounded-[2rem] p-8 ps-10 sm:p-12 sm:ps-14" style="{{ $card }}">
                <span class="pointer-events-none absolute inset-y-10 start-0 w-1.5 rounded-e-full" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                @if (filled($section['eyebrow'] ?? null))
                    <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                @endif
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.85rem,3.4vw,2.7rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose sm:text-xl sm:leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-7 py-3 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton]])
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
    @include('site.partials.footer')
@endif
