{{--
    "باوهاوس" (2026-10-11) — تكوين هندسي جريء: دواير وأنصاص دواير ومربعات بألوان صلبة (الأساسي/
    لون النص/الـsurface) مركّبة حوالين الصورة، والصورة نفسها جوّه شكل هندسي (ربع دايرة/دايرة).
    الخدمات بلاطات كل واحدة عليها رمز هندسي مختلف، المعرض بأشكال متبادلة (دايرة/مربع/قوس)، والآراء
    والتواصل كتل لونية بخطوط سميكة. على خلفيات فاتحة بس (LAYOUT_MODES).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img> (الأشكال border-radius على الحاوية).
--}}
@php
    $primaryBlock = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $inkBlock = 'background-color: var(--site-text); color: var(--site-background);';
    $surfaceBlock = 'background-color: var(--site-surface); color: var(--site-text);';
    $onPrimaryScope = '--site-primary: var(--site-on-primary); --site-muted: color-mix(in srgb, var(--site-on-primary) 82%, transparent); --site-surface: color-mix(in srgb, var(--site-on-primary) 12%, transparent);';
    // شكل كل صورة/رمز بالترتيب — دايرة، ربع دايرة، قوس، مربع.
    $shapes = ['rounded-full', 'rounded-ss-[100%]', 'rounded-t-full', 'rounded-none', 'rounded-ee-[100%]', 'rounded-b-full'];
    $glyphs = ['circle', 'square', 'half', 'triangle'];
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="relative z-30 border-b-4" style="border-color: var(--site-text);">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-10">
            <a href="#top" class="flex min-w-0 items-center gap-3 text-xl font-black">
                <span class="flex shrink-0 items-center" aria-hidden="true">
                    <span class="h-5 w-5 rounded-full" style="background-color: var(--site-primary);"></span>
                    <span class="h-5 w-5" style="background-color: var(--site-text);"></span>
                </span>
                <span class="max-w-[50vw] truncate sm:max-w-sm">{{ $project->name }}</span>
            </a>
            <ul class="hidden items-center gap-7 text-sm font-black md:flex">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-60">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="rounded-full px-5 py-2 text-sm font-black transition hover:opacity-85" style="{{ $primaryBlock }}">تواصل معنا</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md']])
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
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-14 sm:px-10 sm:py-20">
            <div @class(['mx-auto grid max-w-7xl items-center gap-12', 'lg:grid-cols-2' => $heroImage])>
                <div @class(['flex flex-col gap-7', 'items-center text-center' => ! $heroImage])>
                    <div class="flex items-center gap-2" aria-hidden="true">
                        <span class="h-4 w-16" style="background-color: var(--site-primary);"></span>
                        <span class="h-4 w-4 rounded-full" style="background-color: var(--site-text);"></span>
                        <span class="h-0 w-0 border-x-8 border-b-[14px] border-x-transparent" style="border-bottom-color: var(--site-primary);"></span>
                    </div>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.8rem,6.6vw,5.8rem)] font-black leading-[1.08]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl border-s-8 ps-5 text-lg font-medium leading-loose sm:text-xl" style="border-color: var(--site-primary);">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-9 py-4 text-lg font-black transition hover:opacity-85', 'style' => $primaryBlock]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-9 py-4 text-lg font-black transition hover:opacity-85', 'style' => $primaryBlock, 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    {{-- تكوين: مربع لون نص ورا، دايرة لون أساسي فوق، والصورة ربع دايرة كبيرة. --}}
                    <div class="relative mx-auto aspect-square w-full max-w-lg">
                        <div class="pointer-events-none absolute bottom-0 end-0 h-[46%] w-[46%]" style="background-color: var(--site-text);" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute start-0 top-0 h-[34%] w-[34%] rounded-full" style="background-color: var(--site-primary);" aria-hidden="true"></div>
                        <div class="absolute inset-[9%] overflow-hidden rounded-ss-[100%]" style="background-color: var(--site-surface);">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                        <div class="pointer-events-none absolute bottom-[4%] start-[4%] h-14 w-28 rounded-t-full" style="background-color: var(--site-surface); box-shadow: inset 0 0 0 4px var(--site-text);" aria-hidden="true"></div>
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'gallery')
        <section id="{{ $section['key'] }}" class="relative border-t-4 px-5 py-16 sm:px-10 sm:py-24" style="border-color: var(--site-text);">
            <div data-reveal class="mx-auto flex max-w-7xl flex-col gap-12">
                <div class="flex flex-col gap-3">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="inline-flex w-fit items-center gap-2 text-sm font-black"><span class="h-3 w-3 rounded-full" style="background-color: var(--site-primary);" aria-hidden="true"></span>{{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.2rem,5vw,4rem)] font-black leading-[1.12]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                </div>
                {{-- flex-wrap في النص: آخر صف ناقص بيتوسّط بدل خانة فاضية على جنب. --}}
                <div class="flex flex-wrap justify-center gap-5 sm:gap-8">
                    @foreach ($imageItems as $i => $item)
                        <div class="relative aspect-square w-[calc(50%-0.625rem)] sm:w-[calc(33.333%-1.34rem)]">
                            <div class="pointer-events-none absolute inset-0 translate-x-3 translate-y-3 {{ $shapes[$i % count($shapes)] }}" style="background-color: {{ $i % 2 === 0 ? 'var(--site-primary)' : 'var(--site-text)' }};" aria-hidden="true"></div>
                            <div class="relative h-full w-full overflow-hidden {{ $shapes[$i % count($shapes)] }}" style="background-color: var(--site-surface);">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        <section id="{{ $section['key'] }}" class="relative border-t-4 px-5 py-16 sm:px-10 sm:py-24" style="border-color: var(--site-text);">
            <div data-reveal class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[1fr_2fr]">
                <div class="flex flex-col gap-4 lg:sticky lg:top-8 lg:self-start">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="inline-flex w-fit items-center gap-2 text-sm font-black"><span class="h-3 w-3" style="background-color: var(--site-primary);" aria-hidden="true"></span>{{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.2rem,4.6vw,3.8rem)] font-black leading-[1.12]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'w-fit rounded-full px-8 py-3.5 text-base font-black transition hover:opacity-85', 'style' => $primaryBlock]])
                    @endforeach
                </div>
                @foreach ($listItems as $item)
                    @php $entries = (array) $item['value']; @endphp
                    <div class="grid gap-0 border-4 sm:grid-cols-2" style="border-color: var(--site-text);">
                        @foreach ($entries as $i => $entry)
                            @php
                                $e = \App\Services\SiteRenderer::splitEntry($entry);
                                $glyph = $glyphs[$i % count($glyphs)];
                                $fill = [$surfaceBlock, $primaryBlock, '', $inkBlock][$i % 4];
                            @endphp
                            <div data-slot="{{ $item['slot']->key }}" @class(['flex min-h-48 flex-col justify-between gap-6 p-7 sm:p-8', 'sm:col-span-2' => $loop->last && count($entries) % 2 === 1]) style="{{ $fill }} box-shadow: inset 0 0 0 2px var(--site-text);">
                                <span class="flex h-12 items-center" aria-hidden="true">
                                    @if ($glyph === 'circle')
                                        <span class="h-12 w-12 rounded-full" style="background-color: currentColor;"></span>
                                    @elseif ($glyph === 'square')
                                        <span class="h-11 w-11 rotate-12" style="background-color: currentColor;"></span>
                                    @elseif ($glyph === 'half')
                                        <span class="h-6 w-12 rounded-t-full" style="background-color: currentColor;"></span>
                                    @else
                                        <span class="h-0 w-0 border-x-[24px] border-b-[42px] border-x-transparent" style="border-bottom-color: currentColor;"></span>
                                    @endif
                                </span>
                                <span class="flex flex-col gap-2">
                                    <span class="text-xl font-black leading-snug sm:text-2xl">{{ $e['title'] }}</span>
                                    @if ($e['desc'])
                                        <span class="text-base font-medium" style="opacity: .8;">{{ $e['desc'] }}</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-16 sm:px-10 sm:py-24" style="{{ $primaryBlock }}">
            <div class="pointer-events-none absolute -end-24 -top-24 h-72 w-72 rounded-full" style="background-color: color-mix(in srgb, var(--site-on-primary) 14%, transparent);" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-10 start-10 h-20 w-40 rounded-t-full" style="background-color: color-mix(in srgb, var(--site-on-primary) 14%, transparent);" aria-hidden="true"></div>
            <div data-reveal class="relative mx-auto flex max-w-7xl flex-col gap-12" style="{{ $onPrimaryScope }}">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null, 'align' => 'start', 'h2Class' => 'text-[clamp(2.2rem,5vw,4rem)] font-black leading-[1.12]']])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'theme' => 'bold',
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                    'tagStyle' => 'background-color: var(--site-on-primary); color: var(--site-primary-deep);',
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-20 sm:px-10 sm:py-28" style="{{ $inkBlock }}">
            <div class="pointer-events-none absolute -bottom-32 -end-20 h-80 w-80 rounded-full" style="background-color: var(--site-primary);" aria-hidden="true"></div>
            <div class="pointer-events-none absolute end-56 top-10 hidden h-24 w-24 sm:block" style="background-color: color-mix(in srgb, var(--site-background) 20%, transparent);" aria-hidden="true"></div>
            <div data-reveal class="relative mx-auto flex max-w-7xl flex-col items-start gap-6">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-4xl text-[clamp(2.6rem,6.4vw,5.4rem)] font-black leading-[1.08]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-xl leading-loose" style="opacity: .85;">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty() || $contactAction)
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-10 py-4 text-lg font-black transition hover:opacity-90', 'style' => $primaryBlock]])
                        @endforeach
                        @if ($linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-full px-10 py-4 text-lg font-black transition hover:opacity-90', 'style' => $primaryBlock]])
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @else
        {{-- نص عادي: عمود رموز هندسية على جنب + الفقرة بخط كبير. --}}
        <section id="{{ $section['key'] }}" class="relative border-t-4 px-5 py-16 sm:px-10 sm:py-24" style="border-color: var(--site-text);">
            <div data-reveal class="mx-auto grid max-w-6xl gap-10 sm:grid-cols-[auto_1fr]">
                <div class="hidden flex-col gap-3 sm:flex" aria-hidden="true">
                    <span class="h-16 w-16 rounded-full" style="background-color: var(--site-primary);"></span>
                    <span class="h-16 w-16" style="background-color: var(--site-text);"></span>
                    <span class="h-8 w-16 rounded-t-full" style="background-color: var(--site-primary);"></span>
                </div>
                <div class="flex flex-col items-start gap-6">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="text-sm font-black" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4.4vw,3.4rem)] font-black leading-[1.2]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-xl font-medium leading-[2] sm:text-2xl">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-black transition hover:opacity-85', 'style' => $primaryBlock]])
                    @endforeach
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
