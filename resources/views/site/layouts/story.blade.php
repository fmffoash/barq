{{--
    "حكاية" (2026-10-11) — سرد هادي بالتمرير (scrollytelling): الهيرو "فصل أول" — رقم ضخم وعنوان
    بخط عناوين كبير وجنبه الصورة في دايرة كبيرة زي ختم. كل قسم بعد كده عمودين: عنوان القسم ورقمه
    ثابتين على جنب (sticky) وانت بتمرّر المحتوى على الجنب التاني — الخدمات فقرات طويلة مرقّمة، المعرض
    صور كبيرة ورا بعض، والآراء اقتباسات كبيرة. التواصل خاتمة الحكاية في النص.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@php
    $rule = 'border-color: color-mix(in srgb, var(--site-text) 12%, transparent);';
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $linkButton = 'color: var(--site-primary); border-color: currentColor;';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $chapter = 1;
    $words = ['الأول', 'الثاني', 'الثالث', 'الرابع', 'الخامس', 'السادس', 'السابع', 'الثامن', 'التاسع', 'العاشر'];
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="relative z-30 px-5 sm:px-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 border-b py-5" style="{{ $rule }}">
            <a href="#top" class="min-w-0 max-w-[55vw] truncate text-lg font-bold sm:max-w-sm">{{ $project->name }}</a>
            <ul class="hidden items-center gap-7 text-sm md:flex" style="color: var(--site-muted);">
                @foreach ($navLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            @include('site.partials.nav-menu', ['menu' => ['links' => $navLinks->all(), 'hideAt' => 'md']])
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
        $number = $section['kind'] === 'hero' ? 1 : ++$chapter;
        $chapterLabel = 'الفصل '.($words[$number - 1] ?? $number);
    @endphp

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="relative px-5 pb-20 pt-14 sm:px-10 sm:pb-28 sm:pt-20">
            <div @class(['mx-auto grid max-w-6xl items-center gap-12', 'lg:grid-cols-[1.3fr_1fr]' => $heroImage])>
                <div class="flex flex-col gap-7">
                    <div class="flex items-end gap-4">
                        <span class="text-[clamp(4.5rem,11vw,8.5rem)] font-bold leading-none" style="color: var(--site-primary);" dir="ltr" aria-hidden="true">01</span>
                        <span class="pb-3 text-sm font-semibold" style="color: var(--site-muted);">{{ $chapterLabel }} · {{ $project->name }}</span>
                    </div>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.4rem,5.4vw,4.6rem)] font-bold leading-[1.25]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-[2] sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="flex flex-wrap items-center gap-6">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-4 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-8 py-4 text-base font-bold transition hover:opacity-90', 'style' => $solidButton, 'icon' => false]])
                        @endif
                        @if ($navLinks->isNotEmpty())
                            <a href="#{{ $navLinks->first()['key'] }}" class="inline-flex items-center gap-2 border-b-2 pb-1 text-base font-bold transition hover:opacity-75" style="{{ $linkButton }}">
                                ابدأ الحكاية
                                @include('site.partials.icon', ['name' => 'arrow', 'class' => 'h-4 w-4 rotate-[-90deg]'])
                            </a>
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    <div class="relative mx-auto aspect-square w-full max-w-md">
                        <div class="pointer-events-none absolute inset-0 rounded-full border-2 border-dashed" style="border-color: color-mix(in srgb, var(--site-primary) 55%, transparent);" aria-hidden="true"></div>
                        <div class="absolute inset-[5%] overflow-hidden rounded-full" style="background-color: var(--site-surface);">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-5 py-24 sm:px-10 sm:py-32" style="background-color: var(--site-surface);">
            <div data-reveal class="mx-auto flex max-w-3xl flex-col items-center gap-6 text-center">
                <span class="text-sm font-semibold" style="color: var(--site-primary);">الخاتمة</span>
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.4rem,5.4vw,4.2rem)] font-bold leading-[1.25]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty() || $contactAction)
                    <div class="mt-2 flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-10 py-4 text-lg font-bold transition hover:opacity-90', 'style' => $solidButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-full px-10 py-4 text-lg font-bold transition hover:opacity-90', 'style' => $solidButton]])
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @else
        {{-- عمودين: العنوان ثابت (sticky) على جنب والمحتوى بيتمرّر جنبه. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 sm:px-10">
            <div class="mx-auto grid max-w-6xl gap-10 border-t py-16 sm:py-24 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20" style="{{ $rule }}">
                <div class="flex flex-col gap-4 lg:sticky lg:top-10 lg:self-start">
                    <span class="flex items-center gap-3 text-sm font-semibold" style="color: var(--site-primary);">
                        <span dir="ltr">{{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="h-px w-10" style="background-color: currentColor;" aria-hidden="true"></span>
                        {{ $chapterLabel }}@if (filled($section['eyebrow'] ?? null)) · {{ $section['eyebrow'] }}@endif
                    </span>
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4vw,3.2rem)] font-bold leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                    @if (! empty($section['rating']))
                        @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating'], 'class' => 'w-fit']])
                    @endif
                </div>

                <div data-reveal class="flex min-w-0 flex-col gap-10">
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-xl leading-[2.1] sm:text-2xl sm:leading-[2]">{!! $item['value'] !!}</p>
                    @endforeach

                    @if ($section['kind'] === 'gallery')
                        <div class="flex flex-col gap-6">
                            @foreach ($imageItems as $i => $item)
                                <figure class="flex flex-col gap-2">
                                    <div class="relative overflow-hidden rounded-2xl {{ $i % 3 === 0 ? 'aspect-[16/10]' : 'aspect-[4/3]' }}" style="background-color: var(--site-surface);">
                                        <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                                    </div>
                                    <figcaption class="text-xs" style="color: var(--site-muted);" dir="ltr">— {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</figcaption>
                                </figure>
                            @endforeach
                        </div>

                    @elseif ($section['kind'] === 'list')
                        @foreach ($listItems as $item)
                            <ol class="flex flex-col">
                                @foreach ((array) $item['value'] as $i => $entry)
                                    @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                    <li data-slot="{{ $item['slot']->key }}" class="flex gap-6 border-b py-7 first:pt-0" style="{{ $rule }}">
                                        <span class="w-10 shrink-0 text-3xl font-bold leading-none" style="color: var(--site-primary);" dir="ltr">{{ $i + 1 }}</span>
                                        <span class="flex flex-col gap-2">
                                            <span class="text-2xl font-bold leading-snug">{{ $e['title'] }}</span>
                                            @if ($e['desc'])
                                                <span class="text-lg leading-relaxed" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        @endforeach

                    @elseif ($section['kind'] === 'testimonials')
                        @foreach ($listItems as $item)
                            @if (isset($item['testimonials']))
                                <div class="flex flex-col gap-12">
                                    @foreach ($item['testimonials'] as $t)
                                        @include('site.partials.testimonial-card', ['card' => [
                                            't' => $t,
                                            'slotKey' => $item['slot']->key,
                                            'theme' => 'editorial',
                                        ]])
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @endif

                    @if ($linkItems->isNotEmpty())
                        <div class="flex flex-wrap gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
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
