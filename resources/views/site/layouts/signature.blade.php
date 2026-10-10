{{--
    "سيجنتشر" — تصميم فاخر بطابع ضيافة راقٍ (مطاعم/فنادق/صالونات...): هيرو بصورة بعرض الشاشة
    وتعتيم غامق، عناوين أقسام بين خطين رفيعين، الخدمات "منيو" حقيقي (اسم — خط منقّط — سعر/وصف لو
    النص فيه " — ")، صور المعرض على شكل أقواس، الآراء اقتباسات هادية، والتواصل "كارت حجز" بإطار.
    (2026-10-10) اتصلح: عنوان الهيرو كان مستخبي خالص ورا الصورة (حاوية النص من غير position،
    وطبقة الصورة absolute بتترسم فوقها)، والتعتيم كان بيبهت الصورة للون الخلفية ("ضباب" على
    اللوحات الفاتحة). شلنا الحرف الأول الكبير (drop cap) — ::first-letter بيقطع وصل الحرف العربي.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، bq-free-position-boundary + pointer-events على حاوية النص فوق صورة الهيرو، صفر
    transform على <img>.
--}}
@php
    $hairline = 'border-color: color-mix(in srgb, var(--site-text) 12%, transparent);';
    $bodyColor = 'color: color-mix(in srgb, var(--site-text) 78%, var(--site-background));';
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $outlineButton = 'border-color: var(--site-primary); color: var(--site-primary);';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
@endphp

@if ($navLinks->isNotEmpty())
    <div class="sticky top-0 z-30 border-b backdrop-blur-md" style="{{ $hairline }} background-color: color-mix(in srgb, var(--site-background) 82%, transparent);">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-10">
            <a href="#top" class="min-w-0 max-w-[55vw] truncate text-xl font-bold sm:max-w-sm">{{ $project->name }}</a>
            <ul class="hidden items-center gap-8 text-sm font-semibold md:flex">
                @foreach ($menuLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="border px-4 py-2 text-sm font-bold transition hover:opacity-75" style="{{ $outlineButton }}">احجز الآن</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md', 'sharp' => true]])
            </div>
        </nav>
    </div>
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
        <section id="{{ $section['key'] }}" class="relative flex min-h-[88vh] items-center justify-center overflow-hidden px-6 py-24 text-center sm:px-12" style="{{ $heroImage ? 'color: #ffffff;' : '' }}">
            @if ($heroImage)
                <div class="absolute inset-0">
                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                </div>
                {{-- تعتيم أسود (مش لون الخلفية) — الصورة تفضل غنية ومشبّعة على أي لوحة، والنص الأبيض
                مقروء. pointer-events-none عشان الدوس يوصل للصورة في المحرر المباشر. --}}
                <div class="pointer-events-none absolute inset-0" style="background-image: radial-gradient(ellipse at center, rgb(0 0 0 / .45), rgb(0 0 0 / .72));"></div>
            @else
                <div class="pointer-events-none absolute left-1/2 top-1/2 h-[36rem] w-[36rem] max-w-full -translate-x-1/2 -translate-y-1/2 rounded-full opacity-20 blur-3xl" style="background-color: var(--site-primary);" aria-hidden="true"></div>
            @endif
            {{-- relative z-10: من غيرها طبقة الصورة (absolute) بتترسم فوق الكلام وتخفيه خالص. --}}
            <div class="bq-free-position-boundary relative z-10 mx-auto flex max-w-3xl flex-col items-center gap-7" style="pointer-events: none;">
                <div class="flex items-center gap-4 text-sm font-bold" style="color: var(--site-primary);">
                    <span class="h-px w-12" style="background-color: currentColor;" aria-hidden="true"></span>
                    {{ $project->name }}
                    <span class="h-px w-12" style="background-color: currentColor;" aria-hidden="true"></span>
                </div>
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.6rem,6vw,5.2rem)] font-bold leading-[1.2]" style="pointer-events: auto;">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose sm:text-xl" style="{{ $heroImage ? 'color: rgb(255 255 255 / .86);' : $bodyColor }} pointer-events: auto;">{!! $item['value'] !!}</p>
                @endforeach
                <div class="mt-2 flex flex-wrap justify-center gap-3" style="pointer-events: auto;">
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                    @endforeach
                    @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                        @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton, 'icon' => false]])
                    @endif
                    @if ($navLinks->isNotEmpty())
                        <a href="#{{ $navLinks->first()['key'] }}" class="border px-9 py-4 text-base font-bold transition hover:opacity-75" style="border-color: currentColor;">{{ $navLinks->first()['label'] }}</a>
                    @endif
                </div>
            </div>
        </section>

    @else
        {{-- رأس قسم موحّد: سطر صغير بين خطين + العنوان + النص المساند — في النص. --}}
        @php
            $isCta = $section['kind'] === 'cta';
            $band = in_array($section['kind'], ['list', 'testimonials'], true) ? 'background-color: color-mix(in srgb, var(--site-surface) 60%, var(--site-background));' : '';
        @endphp
        <section id="{{ $section['key'] }}" class="relative px-5 py-20 sm:px-10 sm:py-28" style="{{ $band }}">
            <div data-reveal @class(['mx-auto flex flex-col gap-14', 'max-w-6xl' => ! $isCta, 'max-w-3xl' => $isCta])>
                @unless ($isCta)
                    <div class="mx-auto flex max-w-2xl flex-col items-center gap-5 text-center">
                        @if (filled($section['eyebrow'] ?? null))
                            <div class="flex items-center gap-4 text-sm font-bold" style="color: var(--site-primary);">
                                <span class="h-px w-10" style="background-color: currentColor;" aria-hidden="true"></span>
                                {{ $section['eyebrow'] }}
                                <span class="h-px w-10" style="background-color: currentColor;" aria-hidden="true"></span>
                            </div>
                        @endif
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4vw,3.2rem)] font-bold leading-[1.3]">{!! $heading['value'] !!}</h2>
                        @endif
                        @if ($section['rating'] ?? null)
                            @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating']]])
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" @class(['leading-loose', 'text-xl sm:text-2xl sm:leading-[2]' => $section['kind'] === 'text', 'text-lg' => $section['kind'] !== 'text']) style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endunless

                @if ($section['kind'] === 'gallery')
                    {{-- صور على شكل أقواس (نوافذ) — صفوف في النص لأي عدد. --}}
                    @php
                        $archWidth = match (true) {
                            $imageItems->count() <= 1 => 'w-full max-w-md',
                            in_array($imageItems->count(), [2, 4], true) => 'w-[calc(50%-0.75rem)] sm:w-[calc(50%-1rem)] lg:max-w-sm',
                            default => 'w-[calc(50%-0.75rem)] sm:w-[calc(33.333%-1.35rem)]',
                        };
                    @endphp
                    <div class="flex flex-wrap justify-center gap-6 sm:gap-8">
                        @foreach ($imageItems as $item)
                            <div class="aspect-[3/4] overflow-hidden rounded-t-[999px] {{ $archWidth }}" style="box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-primary) 35%, transparent), 0 24px 44px -28px rgb(0 0 0 / .6);">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>

                @elseif ($section['kind'] === 'list')
                    {{-- "منيو": اسم — خط منقّط — سعر/وصف قصير. --}}
                    @foreach ($listItems as $item)
                        <div class="grid gap-x-16 sm:grid-cols-2">
                            @foreach ((array) $item['value'] as $entry)
                                @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                <div data-slot="{{ $item['slot']->key }}" class="flex items-baseline gap-3 border-b py-5" style="{{ $hairline }}">
                                    <span class="text-lg font-bold">{{ $e['title'] }}</span>
                                    <span class="mb-1 min-w-6 flex-1 border-b border-dotted" style="border-color: color-mix(in srgb, var(--site-primary) 45%, transparent);" aria-hidden="true"></span>
                                    @if ($e['desc'])
                                        <span class="text-base font-bold sm:whitespace-nowrap" style="color: var(--site-primary);">{{ $e['desc'] }}</span>
                                    @else
                                        <span class="h-1.5 w-1.5 rotate-45" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                @elseif ($section['kind'] === 'testimonials')
                    @foreach ($listItems as $item)
                        @if (isset($item['testimonials']))
                            <div class="flex flex-wrap justify-center gap-x-12 gap-y-14">
                                @foreach ($item['testimonials'] as $t)
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'theme' => 'editorial',
                                        'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                    ]])
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                @elseif ($isCta)
                    {{-- "كارت حجز": إطار رفيع بإطار تاني جوّاه. --}}
                    <div class="border p-2" style="border-color: color-mix(in srgb, var(--site-primary) 50%, transparent);">
                        <div class="flex flex-col items-center gap-6 border px-6 py-14 text-center sm:px-12" style="border-color: color-mix(in srgb, var(--site-primary) 25%, transparent); background-color: color-mix(in srgb, var(--site-primary) 6%, transparent);">
                            @if (filled($section['eyebrow'] ?? null))
                                <div class="flex items-center gap-4 text-sm font-bold" style="color: var(--site-primary);">
                                    <span class="h-px w-10" style="background-color: currentColor;" aria-hidden="true"></span>
                                    {{ $section['eyebrow'] }}
                                    <span class="h-px w-10" style="background-color: currentColor;" aria-hidden="true"></span>
                                </div>
                            @endif
                            @if ($heading)
                                <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4vw,3.2rem)] font-bold leading-[1.3]">{!! $heading['value'] !!}</h2>
                            @endif
                            @foreach ($supportingItems as $item)
                                <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                            @endforeach
                            @if ($linkItems->isNotEmpty() || $contactAction)
                                <div class="mt-2 flex flex-wrap justify-center gap-3">
                                    @foreach ($linkItems as $item)
                                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                                    @endforeach
                                    @if ($linkItems->isEmpty() && $contactAction)
                                        @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'px-10 py-4 text-base font-bold transition hover:opacity-85', 'style' => $solidButton]])
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if (! $isCta && $linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'border px-9 py-3.5 text-base font-bold transition hover:opacity-75', 'style' => $outlineButton]])
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
