{{--
    "مينيمال" — أهدى تصميم في المكتبة عن قصد، ومختلف بوضوح عن "فريمد" (اللي كله في النص جوّه
    إطارات): هنا كل حاجة على جنب البداية (يمين)، هيرو "نصّي" بعنوان ضخم رفيع (font-light) يملا
    أول الشاشة والصورة تحته كشريط عريض، مسافات واسعة جداً، صفر كروت/ظلال/تدرجات — بس خطوط
    فاصلة رفيعة وأرقام أقسام صغيرة ("02 / من نحن"). اللون الأساسي للتفاصيل والأزرار بس.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@php
    $navLinks = $siteMeta['nav'] ?? [];
    $hairline = 'border-color: color-mix(in srgb, var(--site-text) 10%, transparent);';
    $outlineButton = 'border-color: var(--site-primary); color: var(--site-primary);';
@endphp

@if (! empty($navLinks))
    <nav class="px-6 sm:px-12">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 py-8">
            <a href="#top" class="min-w-0 truncate text-base font-bold">{{ $project->name }}</a>
            <ul class="hidden items-center gap-9 text-sm md:flex" style="color: var(--site-muted);">
                @foreach ($navLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="border-b border-transparent pb-0.5 transition hover:border-current">{{ $link['label'] }}</a></li>
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
        <section id="{{ $section['key'] }}" class="relative px-6 sm:px-12">
            <div class="mx-auto flex min-h-[70vh] max-w-5xl flex-col justify-center gap-8 py-16 sm:py-24">
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="max-w-4xl text-[clamp(2.6rem,6.4vw,5.4rem)] font-light leading-[1.2]">{!! $heading['value'] !!}</h1>
                @endif
                <div class="flex flex-col gap-8 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex max-w-xl flex-col gap-6">
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'border px-8 py-3 text-sm font-medium transition hover:opacity-70', 'style' => $outlineButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'border px-8 py-3 text-sm font-medium transition hover:opacity-70', 'style' => $outlineButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-4 w-4']])
                        @endif
                    </div>
                </div>
            </div>
            @if ($heroImage)
                <div class="mx-auto mb-10 aspect-[4/3] w-full max-w-5xl overflow-hidden sm:aspect-[21/9]">
                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                </div>
            @endif
        </section>
    @else
        <section id="{{ $section['key'] }}" class="relative px-6 sm:px-12">
            <div data-reveal class="mx-auto grid max-w-5xl gap-8 border-t py-20 sm:py-28 lg:grid-cols-[13rem_1fr] lg:gap-12" style="{{ $hairline }}">
                <span class="text-sm" style="color: var(--site-muted);">{{ $number }}@if (filled($section['eyebrow'] ?? null)) / {{ $section['eyebrow'] }}@endif</span>

                <div class="flex flex-col gap-8">
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-3xl text-[clamp(1.9rem,3.6vw,3rem)] font-light leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                    @if ($section['rating'] ?? null)
                        @include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating'], 'class' => 'self-start rounded-none', 'style' => 'background-color: transparent; padding-inline: 0;']])
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach

                    @foreach ($listItems as $item)
                        @if ($section['kind'] === 'testimonials' && isset($item['testimonials']))
                            <div class="grid gap-x-12 gap-y-10 sm:grid-cols-2">
                                @foreach ($item['testimonials'] as $t)
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'theme' => 'minimal',
                                    ]])
                                @endforeach
                            </div>
                        @else
                            {{-- الخط الفاصل على كل <li> نفسه (inline style) — divide-y بيحط الحد على
                            العناصر الأبناء بلون currentColor، والـstyle اللي كان على الـ<ul> مكانش
                            بيوصلهم (كانت خطوط سودا تقيلة بدل خطوط رفيعة). --}}
                            <ul class="flex flex-col">
                                @foreach ((array) $item['value'] as $i => $entry)
                                    @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                    <li data-slot="{{ $item['slot']->key }}" class="group flex items-baseline gap-6 border-b py-5 text-lg" style="{{ $hairline }}">
                                        <span class="w-8 shrink-0 text-sm" style="color: var(--site-muted);">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="flex flex-1 flex-col gap-1">
                                            <span>{{ $e['title'] }}</span>
                                            @if ($e['desc'])
                                                <span class="text-sm leading-relaxed" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                            @endif
                                        </span>
                                        <span class="transition group-hover:-translate-x-1" style="color: var(--site-primary);" aria-hidden="true">@include('site.partials.icon', ['name' => 'arrow', 'class' => 'h-4 w-4'])</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach

                    @if ($linkItems->isNotEmpty() || ($section['kind'] === 'cta' && $contactAction))
                        <div class="flex flex-wrap gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'border px-8 py-3 text-sm font-medium transition hover:opacity-70', 'style' => $outlineButton]])
                            @endforeach
                            @if ($section['kind'] === 'cta' && $linkItems->isEmpty() && $contactAction)
                                @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'border px-8 py-3 text-sm font-medium transition hover:opacity-70', 'style' => $outlineButton]])
                            @endif
                        </div>
                    @endif
                </div>

                {{-- الصور بعرض المحتوى كله (العمودين) — التصميم الهادي بيعتمد على الصورة نفسها. --}}
                @if ($imageItems->isNotEmpty())
                    @php
                        $imageCount = $imageItems->count();
                        $mosaic = $imageCount >= 5 ? \App\Services\SiteRenderer::mosaic($imageCount) : null;
                        $gridClass = match ($imageCount) {
                            1 => 'grid grid-cols-1',
                            2 => 'grid grid-cols-2',
                            3 => 'grid grid-cols-3',
                            4 => 'grid grid-cols-2',
                            default => $mosaic['grid'].' auto-rows-[9rem] sm:auto-rows-[13rem]',
                        };
                    @endphp
                    <div class="{{ $gridClass }} gap-3 sm:gap-6 lg:col-span-2">
                        @foreach ($imageItems as $item)
                            <div class="overflow-hidden {{ $mosaic ? $mosaic['items'][$loop->index] : ($imageCount === 1 ? 'aspect-[21/9]' : ($imageCount === 4 ? 'aspect-[4/3]' : 'aspect-[4/5]')) }}">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
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
    @include('site.partials.footer', ['footerTheme' => 'plain'])
@endif
