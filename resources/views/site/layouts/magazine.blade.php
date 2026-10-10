{{--
    "مجلة" — طابع تحريري: هيرو بعنوان كبير في النص وصورة عريضة (21:9)، وكل قسم على شبكة
    غير متماثلة (رقم القسم + العنوان في عمود ضيق ثابت، والمحتوى في عمود أعرض)، وآراء العملاء
    كاقتباسات كبيرة (pull-quotes). أدوات "المجلة" عربية فعلاً: رقم قسم بخط رفيع ملوّن بدل
    uppercase/تباعد حروف (اللي مالهمش أي أثر على الحروف العربية — اتقاس: 1px فرق بس).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@php
    $navLinks = $siteMeta['nav'] ?? [];
    $hairline = 'border-color: color-mix(in srgb, var(--site-text) 14%, transparent);';
@endphp

@if (! empty($navLinks))
    <nav class="border-b" style="{{ $hairline }}">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-6">
            <a href="#top" class="min-w-0 truncate text-xl font-extrabold">{{ $project->name }}</a>
            <ul class="hidden items-center gap-7 text-sm font-semibold md:flex" style="color: var(--site-muted);">
                @foreach ($navLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            @include('site.partials.nav-menu', ['menu' => ['links' => $navLinks, 'hideAt' => 'md', 'sharp' => true]])
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
        $number = str_pad((string) $loop->index, 2, '0', STR_PAD_LEFT);
    @endphp

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="relative px-6 pb-10 pt-14 sm:pt-20">
            <div class="mx-auto flex max-w-4xl flex-col items-center gap-6 text-center">
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.5rem,6vw,4.75rem)] font-extrabold leading-[1.2]">{!! $heading['value'] !!}</h1>
                @endif
                <span class="h-1 w-20 rounded-full" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-xl leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'mt-1 px-9 py-4 text-base font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-primary); color: var(--site-on-primary);']])
                @endforeach
                @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                    @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'text-base font-bold underline decoration-2 underline-offset-8', 'style' => 'color: var(--site-primary);', 'icon' => 'arrow', 'iconClass' => 'order-last h-4 w-4']])
                @endif
            </div>
            @if ($heroImage)
                <div class="mx-auto mt-12 aspect-[4/3] w-full max-w-6xl overflow-hidden sm:aspect-[21/9]">
                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                </div>
            @endif
        </section>
    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-6 py-16">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col items-center gap-4 border-y-2 py-14 text-center" style="border-color: var(--site-primary);">
                <span class="inline-flex items-center gap-3 text-sm font-bold" style="color: var(--site-primary);">
                    <span class="h-px w-10" style="background-color: var(--site-primary);" aria-hidden="true"></span>{{ $number }} — {{ $section['eyebrow'] ?? 'تواصل معنا' }}<span class="h-px w-10" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                </span>
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2rem,4vw,3.25rem)] font-extrabold leading-[1.25]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'mt-4 px-10 py-4 text-base font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-primary); color: var(--site-on-primary);']])
                @endforeach
                @if ($linkItems->isEmpty() && $contactAction)
                    @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'mt-4 px-10 py-4 text-base font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-primary); color: var(--site-on-primary);']])
                @endif
            </div>
        </section>
    @else
        {{-- باقي الأقسام: شبكة 12 عمود غير متماثلة — رقم القسم + العنوان في 4 أعمدة، والمحتوى في 8.
        عمود العنوان مش sticky عمداً: أي حاوية sticky بتبقى "الكنفاه" بتاع الترتيب الحر للعنوان
        بدل الـsection (اتجرّب: العنوان كان بيزحف 56px عن مكان الماوس). --}}
        <section id="{{ $section['key'] }}" class="relative border-t px-6 py-16 sm:py-20" style="{{ $hairline }}">
            <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-12 lg:gap-12">
                <div class="flex flex-col items-start gap-4 lg:col-span-4">
                    <span class="inline-flex items-center gap-3 text-sm font-bold" style="color: var(--site-primary);">
                        <span class="h-px w-10" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                        {{ $number }}@if (filled($section['eyebrow'] ?? null)) — {{ $section['eyebrow'] }}@endif
                    </span>
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,3.2vw,2.75rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                    @if ($section['rating'] ?? null)
                        @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating']]])
                    @endif
                </div>

                <div data-reveal class="flex flex-col gap-8 lg:col-span-8">
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-xl leading-loose sm:text-[1.35rem] sm:leading-[2]">{!! $item['value'] !!}</p>
                    @endforeach

                    @if ($imageItems->isNotEmpty())
                        @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
                        <div class="{{ $mosaic['grid'] }} auto-rows-[9rem] gap-2 sm:auto-rows-[11rem] lg:auto-rows-[12rem]">
                            @foreach ($imageItems as $item)
                                <div class="overflow-hidden {{ $mosaic['items'][$loop->index] }}">
                                    <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($listItems as $item)
                        @if ($section['kind'] === 'testimonials' && isset($item['testimonials']))
                            <div class="flex flex-col divide-y" style="{{ $hairline }}">
                                @foreach ($item['testimonials'] as $t)
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'theme' => 'editorial',
                                        'class' => $loop->first ? 'pb-8' : 'py-8',
                                        'style' => 'border-color: color-mix(in srgb, var(--site-text) 14%, transparent);',
                                    ]])
                                @endforeach
                            </div>
                        @else
                            <div class="grid gap-x-10 sm:grid-cols-2">
                                @foreach ((array) $item['value'] as $i => $entry)
                                    @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                    <div data-slot="{{ $item['slot']->key }}" class="flex items-baseline gap-4 border-b py-5" style="{{ $hairline }}">
                                        <span class="text-sm font-extrabold" style="color: var(--site-primary);">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="flex flex-col gap-1">
                                            <span class="text-lg font-bold">{{ $e['title'] }}</span>
                                            @if ($e['desc'])
                                                <span class="text-sm leading-relaxed" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'self-start px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-primary); color: var(--site-on-primary);']])
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
