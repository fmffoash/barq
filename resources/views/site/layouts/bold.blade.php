{{--
    "بولد" — طباعة ضخمة وبلوكات ألوان صلبة بعرض الشاشة وحدود 4px، من غير تدوير ولا ظلال ناعمة —
    طابع مباشر وصريح (جيم، أطفال، عربيات...).
    - الهيرو على اللون الأساسي، وصورته جنب العنوان في إطار بظل "صلب" (كانت مش بتظهر خالص قبل كده).
    - شريط خدمات متحرك (CSS بس، بيقف لو الزائر طالب تقليل الحركة/في المحرر) تحت الهيرو مباشرة.
    - الألوان بتتبادل بترتيب ثابت حسب مكان القسم الفعلي: الهيرو "أساسي"، اللي بعده "خلفية" ثم
      "أساسي" بالتبادل، والتواصل دايماً "حبر" (لون النص كخلفية) — فمفيش قسمين جنب بعض بنفس اللون
      (كان الهيرو و"من نحن" بيلزقوا في بلوك واحد).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@php
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
    $tones = [
        'primary' => 'background-color: var(--site-primary); color: var(--site-on-primary);',
        'base' => 'background-color: var(--site-background); color: var(--site-text);',
        'ink' => 'background-color: var(--site-text); color: var(--site-background);',
    ];
    // زرار "معكوس" مقروء على كل لون: على الأساسي = حبر، على الخلفية/الحبر = أساسي.
    $buttonOn = [
        'primary' => 'background-color: var(--site-text); color: var(--site-background);',
        'base' => 'background-color: var(--site-primary); color: var(--site-on-primary);',
        'ink' => 'background-color: var(--site-primary); color: var(--site-on-primary);',
    ];
    $servicesSection = $sections->first(fn ($s) => $s['kind'] === 'list');
    $marqueeItems = $servicesSection
        ? collect($servicesSection['items']->firstWhere('slot.slot_type', 'list')['value'] ?? [])->map(fn ($e) => \App\Services\SiteRenderer::splitEntry($e)['title'])->filter()->values()
        : collect();
    $middleIndex = 0;
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="relative z-30 border-b-4" style="{{ $tones['primary'] }} border-color: currentColor;">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-10">
            <a href="#top" class="min-w-0 max-w-[55vw] truncate text-xl font-black sm:max-w-sm sm:text-2xl">{{ $project->name }}</a>
            <ul class="hidden items-center gap-7 text-sm font-bold md:flex">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="border-2 px-4 py-2 text-sm font-black transition hover:opacity-80" style="{{ $buttonOn['primary'] }} border-color: currentColor;">تواصل معنا</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md', 'sharp' => true, 'panelStyle' => 'background-color: var(--site-text); color: var(--site-background);']])
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

        // لو القسم فيه أكتر من خانة "text"، أول خانة بس بتاخد شكل العنوان الكبير، والباقي
        // بيترندر كنص مساند أصغر بدل عنوانين ضخمين فوق بعض.
        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
        $alt = trim(strip_tags((string) ($heading['value'] ?? ''))) ?: $project->name;

        if ($section['kind'] === 'hero') {
            $tone = 'primary';
        } elseif ($section['kind'] === 'cta') {
            $tone = 'ink';
        } else {
            $tone = $middleIndex % 2 === 0 ? 'base' : 'primary';
            $middleIndex++;
        }
    @endphp

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="relative px-6 py-16 sm:px-12 sm:py-24" style="{{ $tones['primary'] }}">
            <div @class(['mx-auto grid max-w-6xl items-center gap-12', 'lg:grid-cols-[1.15fr_1fr]' => $heroImage])>
                <div @class(['flex flex-col gap-6', 'items-start text-start' => $heroImage, 'items-center text-center' => ! $heroImage])>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.5rem,6.6vw,5.6rem)] font-black leading-[1.15]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-xl font-semibold leading-loose" style="opacity: .88;">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'border-4 px-9 py-4 text-lg font-black transition hover:opacity-85', 'style' => $buttonOn['primary'].' border-color: currentColor;']])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'border-4 px-9 py-4 text-lg font-black transition hover:opacity-85', 'style' => $buttonOn['primary'].' border-color: currentColor;', 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    {{-- إطار 4px وظل "صلب" مزاح (من غير blur) — هوية التصميم. --}}
                    <div class="relative aspect-[4/5] w-full max-w-md justify-self-center overflow-hidden border-4 sm:max-w-none" style="border-color: var(--site-text); box-shadow: -14px 14px 0 var(--site-text);">
                        <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                    </div>
                @endif
            </div>
        </section>

        @if ($marqueeItems->isNotEmpty())
            {{-- نسخة عرض من الخدمات (من غير data-slot) — نسختين ورا بعض والشريط بيتحرك نص عرضه
            بالظبط فبيلف من غير قفزة. --}}
            <div class="overflow-hidden border-y-4 py-4" style="{{ $tones['ink'] }} border-color: var(--site-text);" aria-hidden="true">
                <div class="bq-marquee-track flex w-max">
                    @foreach ([1, 2] as $copy)
                        <div class="flex shrink-0 items-center">
                            @foreach ($marqueeItems->count() < 6 ? $marqueeItems->concat($marqueeItems) : $marqueeItems as $title)
                                <span class="whitespace-nowrap px-6 text-xl font-black sm:text-2xl">{{ $title }}</span>
                                <span class="text-xl" style="color: var(--site-primary);">✦</span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <section id="{{ $section['key'] }}" class="relative px-6 py-16 sm:px-12 sm:py-24" style="{{ $tones[$tone] }}">
            <div data-reveal @class(['mx-auto flex max-w-6xl flex-col gap-8', 'items-center text-center' => $section['kind'] === 'cta'])>
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-4xl text-[clamp(2rem,4.6vw,3.6rem)] font-black leading-[1.2]">{!! $heading['value'] !!}</h2>
                @endif
                @if ($section['rating'] ?? null)
                    @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating'], 'class' => 'self-start rounded-none border-2', 'style' => 'border-color: currentColor; background-color: transparent;']])
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-3xl text-xl font-medium leading-loose" style="opacity: .85;">{!! $item['value'] !!}</p>
                @endforeach

                @if ($imageItems->isNotEmpty())
                    @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
                    <div class="{{ $mosaic['grid'] }} auto-rows-[9rem] gap-3 sm:auto-rows-[12rem] sm:gap-4 lg:auto-rows-[14rem]">
                        @foreach ($imageItems as $item)
                            <div class="overflow-hidden border-4 {{ $mosaic['items'][$loop->index] }}" style="border-color: currentColor;">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                @endif

                @foreach ($listItems as $item)
                    @if ($section['kind'] === 'testimonials' && isset($item['testimonials']))
                        @php $spans = \App\Services\SiteRenderer::rowSpans(count($item['testimonials'])); @endphp
                        <div class="grid grid-cols-2 gap-5 sm:grid-cols-6">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'theme' => 'bold',
                                    'class' => $spans[$loop->index],
                                    'tagStyle' => $buttonOn[$tone],
                                ]])
                            @endforeach
                        </div>
                    @else
                        @php $entries = (array) $item['value']; $spans = \App\Services\SiteRenderer::rowSpans(count($entries)); @endphp
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-6">
                            @foreach ($entries as $i => $entry)
                                @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                <div data-slot="{{ $item['slot']->key }}" class="flex flex-col gap-2 border-4 p-4 sm:p-6 {{ $spans[$i] }}" style="border-color: currentColor;">
                                    <span class="text-sm font-black" style="opacity: .7;">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="text-lg font-black leading-snug sm:text-2xl">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="text-base font-medium" style="opacity: .8;">{{ $e['desc'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach

                @if ($linkItems->isNotEmpty() || ($section['kind'] === 'cta' && $contactAction))
                    <div class="flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'border-4 px-10 py-4 text-lg font-black transition hover:opacity-85', 'style' => $buttonOn[$tone].' border-color: currentColor;']])
                        @endforeach
                        @if ($section['kind'] === 'cta' && $linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'border-4 px-10 py-4 text-lg font-black transition hover:opacity-85', 'style' => $buttonOn[$tone].' border-color: currentColor;']])
                        @endif
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
