{{--
    "تيرمينال" (2026-10-11) — طابع تقني للبرمجيات والشركات الناشئة: خلفية شبكة خطوط رفيعة، كل قسم
    "نافذة" بشريط عنوان (3 نقط + اسم ملف لاتيني صغير)، العناوين قبلها علامة أمر (›) باللون الأساسي
    ومؤشر بيرمش بعد عنوان الهيرو، الخدمات سطور مرقّمة زي الكود، والتواصل سطر أمر بزرار "تشغيل".
    على خلفيات داكنة بس (LAYOUT_MODES).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@php
    $grid = 'background-image: linear-gradient(color-mix(in srgb, var(--site-text) 5%, transparent) 1px, transparent 1px), linear-gradient(90deg, color-mix(in srgb, var(--site-text) 5%, transparent) 1px, transparent 1px); background-size: 32px 32px;';
    $window = 'background-color: color-mix(in srgb, var(--site-surface) 92%, transparent); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 12%, transparent), 0 24px 50px -30px rgb(0 0 0 / .7);';
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $outlineButton = 'box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--site-primary) 70%, transparent); color: var(--site-primary);';
    $files = ['hero' => 'index.html', 'about' => 'about.md', 'services' => 'services.json', 'gallery' => 'gallery/', 'testimonials' => 'reviews.log', 'contact' => 'contact.sh'];
    $windowBar = function (string $file) {
        return '<div class="flex items-center gap-2 border-b px-4 py-2.5" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);" aria-hidden="true">'
            .'<span class="h-2.5 w-2.5 rounded-full" style="background-color: #ff5f57;"></span>'
            .'<span class="h-2.5 w-2.5 rounded-full" style="background-color: #febc2e;"></span>'
            .'<span class="h-2.5 w-2.5 rounded-full" style="background-color: #28c840;"></span>'
            .'<span class="ms-auto font-mono text-xs" style="color: var(--site-muted);" dir="ltr">~/'.e($file).'</span>'
            .'</div>';
    };
    $prompt = '<span class="me-3 select-none font-mono" style="color: var(--site-primary);" aria-hidden="true">›</span>';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $menuLinks = $contactAnchor ? $navLinks->reject(fn ($l) => $l['key'] === $contactAnchor)->values() : $navLinks;
@endphp

<div class="relative" style="{{ $grid }}">
    @if ($navLinks->isNotEmpty())
        <div class="sticky top-0 z-30 border-b backdrop-blur-md" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent); background-color: color-mix(in srgb, var(--site-background) 85%, transparent);">
            <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3.5 sm:px-10">
                <a href="#top" class="flex min-w-0 items-center gap-2 text-base font-bold">
                    <span class="font-mono" style="color: var(--site-primary);" dir="ltr" aria-hidden="true">&gt;_</span>
                    <span class="max-w-[50vw] truncate sm:max-w-sm">{{ $project->name }}</span>
                </a>
                <ul class="hidden items-center gap-1 text-sm md:flex">
                    @foreach ($menuLinks as $link)
                        <li><a href="#{{ $link['key'] }}" class="rounded-md px-3 py-1.5 transition hover:opacity-70" style="color: var(--site-muted);">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
                <div class="flex shrink-0 items-center gap-2">
                    @if ($contactAnchor)
                        <a href="#{{ $contactAnchor }}" class="rounded-md px-4 py-2 text-sm font-bold transition hover:opacity-85" style="{{ $solidButton }}">تواصل معنا</a>
                    @endif
                    @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md']])
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
            $file = $files[$section['key']] ?? (preg_replace('/[^a-z0-9_-]/i', '', $section['key']) ?: 'section').'.txt';
        @endphp

        @if ($section['kind'] === 'hero')
            @php $heroImage = $imageItems->first(); @endphp
            <section id="{{ $section['key'] }}" class="relative px-5 py-14 sm:px-10 sm:py-20">
                <div @class(['mx-auto grid max-w-6xl items-center gap-10', 'lg:grid-cols-[1.15fr_1fr]' => $heroImage])>
                    <div class="overflow-hidden rounded-xl" style="{{ $window }}">
                        {!! $windowBar($file) !!}
                        <div class="flex flex-col gap-6 p-6 sm:p-10">
                            <span class="font-mono text-xs" style="color: var(--site-muted);" dir="ltr">$ ./start --welcome</span>
                            @if ($heading)
                                <div class="flex items-start">
                                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.3rem,5.2vw,4.2rem)] font-bold leading-[1.2]">{!! $heading['value'] !!}</h1>
                                </div>
                                <span class="bq-terminal-caret -mt-3 block h-1.5 w-10 rounded-sm" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                            @endif
                            @foreach ($supportingItems as $item)
                                <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                            @endforeach
                            <div class="flex flex-wrap gap-3">
                                @foreach ($linkItems as $item)
                                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-md px-7 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
                                @endforeach
                                @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                                    @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-md px-7 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $solidButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
                                @endif
                                @if (! empty($siteMeta['nav']))
                                    <a href="#{{ $siteMeta['nav'][0]['key'] }}" class="rounded-md px-7 py-3.5 text-base font-bold transition hover:opacity-80" style="{{ $outlineButton }}">{{ $siteMeta['nav'][0]['label'] }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if ($heroImage)
                        <div class="overflow-hidden rounded-xl" style="{{ $window }}">
                            {!! $windowBar('preview.jpg') !!}
                            <div class="relative aspect-[4/3] overflow-hidden">
                                <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                            </div>
                        </div>
                    @endif
                </div>
            </section>

        @else
            @php $isCta = $section['kind'] === 'cta'; @endphp
            <section id="{{ $section['key'] }}" class="relative px-5 py-12 sm:px-10 sm:py-16">
                <div data-reveal class="mx-auto max-w-6xl overflow-hidden rounded-xl" style="{{ $window }}">
                    {!! $windowBar($file) !!}
                    <div class="flex flex-col gap-10 p-6 sm:p-10">
                        <div class="flex flex-col gap-4">
                            @if (filled($section['eyebrow'] ?? null))
                                <span class="text-xs font-semibold" style="color: var(--site-primary);"><span class="font-mono" aria-hidden="true">//</span> {{ $section['eyebrow'] }}</span>
                            @endif
                            @if ($heading)
                                <div class="flex items-baseline">
                                    {!! $prompt !!}<h2 data-slot="{{ $heading['slot']->key }}" class="{{ $isCta ? 'text-[clamp(2.2rem,5vw,3.8rem)]' : 'text-[clamp(1.9rem,3.8vw,3rem)]' }} font-bold leading-[1.25]">{!! $heading['value'] !!}</h2>
                                </div>
                            @endif
                            @if (! empty($section['rating']))
                                @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating'], 'class' => 'w-fit']])
                            @endif
                            @foreach ($supportingItems as $item)
                                <p data-slot="{{ $item['slot']->key }}" class="max-w-3xl text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                            @endforeach
                        </div>

                        @if ($section['kind'] === 'gallery')
                            @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
                            <div class="{{ $mosaic['grid'] }} auto-rows-[8rem] gap-2 sm:auto-rows-[10rem] sm:gap-3">
                                @foreach ($imageItems as $i => $item)
                                    <div class="relative overflow-hidden rounded-lg {{ $mosaic['items'][$i] }}" style="box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 12%, transparent);">
                                        <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                                    </div>
                                @endforeach
                            </div>

                        @elseif ($section['kind'] === 'list')
                            {{-- سطور مرقّمة زي محرر كود: رقم السطر + الاسم + الوصف كتعليق. --}}
                            @foreach ($listItems as $item)
                                <ol class="flex flex-col overflow-hidden rounded-lg" style="background-color: color-mix(in srgb, var(--site-background) 60%, transparent);">
                                    @foreach ((array) $item['value'] as $i => $entry)
                                        @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                        <li data-slot="{{ $item['slot']->key }}" class="grid grid-cols-[2.5rem_1fr] items-baseline gap-x-4 gap-y-1 border-b px-4 py-4 last:border-b-0 sm:grid-cols-[3rem_1fr_1fr]" style="border-color: color-mix(in srgb, var(--site-text) 8%, transparent);">
                                            <span class="font-mono text-sm" style="color: var(--site-muted);" dir="ltr">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                            <span class="text-lg font-bold">{{ $e['title'] }}</span>
                                            @if ($e['desc'])
                                                <span class="col-start-2 text-sm sm:col-start-3" style="color: var(--site-primary);">// {{ $e['desc'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @endforeach

                        @elseif ($section['kind'] === 'testimonials')
                            @foreach ($listItems as $item)
                                @if (isset($item['testimonials']))
                                    <div class="flex flex-wrap justify-center gap-4">
                                        @foreach ($item['testimonials'] as $t)
                                            @include('site.partials.testimonial-card', ['card' => [
                                                't' => $t,
                                                'slotKey' => $item['slot']->key,
                                                'theme' => 'sharp',
                                                'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])).' rounded-lg',
                                                'style' => 'background-color: color-mix(in srgb, var(--site-background) 60%, transparent);',
                                            ]])
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        @endif

                        @if ($linkItems->isNotEmpty() || ($isCta && $contactAction))
                            <div class="flex flex-wrap items-center gap-3 rounded-lg px-4 py-3" style="background-color: color-mix(in srgb, var(--site-background) 60%, transparent);">
                                <span class="font-mono text-sm" style="color: var(--site-muted);" dir="ltr">$ run</span>
                                @foreach ($linkItems as $item)
                                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-md px-7 py-3 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
                                @endforeach
                                @if ($isCta && $linkItems->isEmpty() && $contactAction)
                                    @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-md px-7 py-3 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
                                @endif
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
        @include('site.partials.footer', ['footerTheme' => 'sharp'])
    @endif
</div>
