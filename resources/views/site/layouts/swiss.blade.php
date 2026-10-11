{{--
    "سويسري" (2026-10-11) — الطراز الدولي: شبكة صارمة وخطوط فاصلة رفيعة، رقم كبير لكل قسم ("01")
    في عمود جانبي ثابت والمحتوى في العمود الأعرض، عنوان هيرو ضخم بعرض الصفحة وتحته سطر معلومات
    مقسوم بخطوط، والخدمات جدول صفوف (رقم | اسم | وصف). هادي ومنظم ومن غير زخرفة.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@php
    $rule = 'border-color: color-mix(in srgb, var(--site-text) 16%, transparent);';
    $strongRule = 'border-color: var(--site-text);';
    $solidButton = 'background-color: var(--site-text); color: var(--site-background);';
    $accentButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $navLinks = collect($siteMeta['nav'] ?? []);
    $sectionNumber = 0;
@endphp

@if ($navLinks->isNotEmpty())
    <nav class="relative z-30 border-b px-5 sm:px-10" style="{{ $strongRule }}">
        <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-4 py-4 md:grid-cols-12">
            <a href="#top" class="min-w-0 truncate text-lg font-bold md:col-span-3">{{ $project->name }}</a>
            <ul class="hidden items-center gap-8 text-sm font-semibold md:col-span-7 md:flex">
                @foreach ($navLinks as $i => $link)
                    <li><a href="#{{ $link['key'] }}" class="flex items-baseline gap-1.5 transition hover:opacity-60"><span class="text-xs font-bold" style="color: var(--site-primary);" dir="ltr">0{{ $i + 1 }}</span>{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="flex items-center justify-end gap-2 md:col-span-2">
                @if ($contactAnchor)
                    <a href="#{{ $contactAnchor }}" class="hidden px-4 py-2 text-sm font-bold transition hover:opacity-85 sm:inline-block" style="{{ $accentButton }}">تواصل</a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $navLinks->all(), 'hideAt' => 'md', 'sharp' => true]])
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
        $number = $section['kind'] === 'hero' ? null : str_pad((string) (++$sectionNumber), 2, '0', STR_PAD_LEFT);
    @endphp

    @if ($section['kind'] === 'hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="relative px-5 pb-16 pt-12 sm:px-10 sm:pb-24 sm:pt-16">
            <div class="mx-auto flex max-w-7xl flex-col gap-10">
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="max-w-6xl text-[clamp(2.9rem,8vw,7.2rem)] font-bold leading-[1.05]">{!! $heading['value'] !!}</h1>
                @endif
                <div class="grid gap-8 border-t pt-8 md:grid-cols-12" style="{{ $strongRule }}">
                    <div class="flex flex-col gap-6 md:col-span-5">
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose sm:text-xl">{!! $item['value'] !!}</p>
                        @endforeach
                        <div class="flex flex-wrap gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-8 py-4 text-base font-bold transition hover:opacity-85', 'style' => $accentButton]])
                            @endforeach
                            @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                                @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'px-8 py-4 text-base font-bold transition hover:opacity-85', 'style' => $accentButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
                            @endif
                        </div>
                    </div>
                    @if ($heroImage)
                        <div class="relative aspect-[16/10] overflow-hidden md:col-span-7" style="background-color: var(--site-surface);">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                    @endif
                </div>
            </div>
        </section>

    @else
        {{-- كل الأقسام: عمود جانبي (الرقم + اسم القسم) ثابت وقت التمرير، والمحتوى في 9 أعمدة. --}}
        @php $isCta = $section['kind'] === 'cta'; @endphp
        <section id="{{ $section['key'] }}" class="relative px-5 sm:px-10" style="{{ $isCta ? 'background-color: var(--site-primary); color: var(--site-on-primary);' : '' }}">
            <div class="mx-auto grid max-w-7xl gap-8 border-t py-14 sm:py-20 md:grid-cols-12" style="{{ $isCta ? 'border-color: transparent;' : $strongRule }}">
                <div class="flex items-baseline gap-4 md:col-span-3 md:flex-col md:gap-2 md:self-start lg:sticky lg:top-8">
                    <span class="text-5xl font-bold leading-none sm:text-6xl" style="{{ $isCta ? '' : 'color: var(--site-primary);' }}" dir="ltr">{{ $number }}</span>
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="text-sm font-bold" style="{{ $isCta ? 'opacity: .8;' : 'color: var(--site-muted);' }}">{{ $section['eyebrow'] }}</span>
                    @endif
                </div>
                <div data-reveal class="flex min-w-0 flex-col gap-8 md:col-span-9">
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="{{ $isCta ? 'text-[clamp(2.4rem,5.6vw,4.6rem)]' : 'text-[clamp(2rem,4.2vw,3.4rem)]' }} font-bold leading-[1.15]">{!! $heading['value'] !!}</h2>
                    @endif
                    @if (! empty($section['rating']))
                        @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating'], 'class' => 'w-fit']])
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-3xl text-lg leading-loose sm:text-xl" style="{{ $isCta ? 'opacity: .88;' : 'color: var(--site-muted);' }}">{!! $item['value'] !!}</p>
                    @endforeach

                    @if ($section['kind'] === 'gallery')
                        {{-- مربعات متساوية على شبكة 2/3 أعمدة؛ آخر صورة بتاخد عرض الباقي في صفها (بنسبة
                        عرض مطابقة لارتفاع الصف) عشان مفيش خانة فاضية. --}}
                        @php $count = $imageItems->count(); @endphp
                        <div class="grid grid-cols-2 gap-px sm:grid-cols-3" style="background-color: color-mix(in srgb, var(--site-text) 16%, transparent);">
                            @foreach ($imageItems as $item)
                                @php
                                    $mobile = $loop->last && $count % 2 === 1 ? 'col-span-2 aspect-[2/1]' : 'aspect-square';
                                    $desktop = ! $loop->last ? 'sm:col-span-1 sm:aspect-square' : match ($count % 3) {
                                        1 => 'sm:col-span-3 sm:aspect-[3/1]',
                                        2 => 'sm:col-span-2 sm:aspect-[2/1]',
                                        default => 'sm:col-span-1 sm:aspect-square',
                                    };
                                @endphp
                                <div class="relative overflow-hidden {{ $mobile }} {{ $desktop }}" style="background-color: var(--site-background);">
                                    <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                                </div>
                            @endforeach
                        </div>

                    @elseif ($section['kind'] === 'list')
                        @foreach ($listItems as $item)
                            <ol class="flex flex-col border-t" style="{{ $strongRule }}">
                                @foreach ((array) $item['value'] as $i => $entry)
                                    @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                    <li data-slot="{{ $item['slot']->key }}" class="grid grid-cols-[3rem_1fr] gap-x-4 gap-y-1 border-b py-6 sm:grid-cols-[4rem_1fr_1fr] sm:items-baseline" style="{{ $rule }}">
                                        <span class="text-sm font-bold" style="color: var(--site-primary);" dir="ltr">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="text-xl font-bold leading-snug sm:text-2xl">{{ $e['title'] }}</span>
                                        @if ($e['desc'])
                                            <span class="col-start-2 leading-relaxed sm:col-start-3" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endforeach

                    @elseif ($section['kind'] === 'testimonials')
                        @foreach ($listItems as $item)
                            @if (isset($item['testimonials']))
                                <div class="grid gap-10 sm:grid-cols-2">
                                    @foreach ($item['testimonials'] as $t)
                                        @include('site.partials.testimonial-card', ['card' => [
                                            't' => $t,
                                            'slotKey' => $item['slot']->key,
                                            'theme' => 'minimal',
                                            'class' => $loop->last && count($item['testimonials']) % 2 === 1 ? 'sm:col-span-2' : '',
                                        ]])
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @endif

                    @if ($linkItems->isNotEmpty() || ($isCta && $contactAction))
                        <div class="flex flex-wrap gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'px-9 py-4 text-base font-bold transition hover:opacity-85', 'style' => $isCta ? 'background-color: var(--site-on-primary); color: var(--site-primary-deep);' : $solidButton]])
                            @endforeach
                            @if ($isCta && $linkItems->isEmpty() && $contactAction)
                                @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'px-9 py-4 text-base font-bold transition hover:opacity-85', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary-deep);']])
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
    @include('site.partials.footer', ['footerTheme' => 'plain'])
@endif
