{{--
    "أورورا" (2026-10-11) — عصري بإضاءة شفق: بقع ألوان ضبابية بتتحرك ببطء ورا الصفحة كلها (CSS
    بس، مقفولة لطالب تقليل الحركة وفي المحرر المباشر)، هيرو نصّين: الكلام + صورة جوّه "كارت مضيء"
    بإطار متدرّج بيلف، وشرائح معلومات عائمة. الخدمات كروت بإطار متدرّج، المعرض موزاييك بحواف
    دائرية، والتواصل لوحة بتدرّج اللون الأساسي.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img> (الحركة على طبقات الزخرفة بس، وكلها pointer-events-none).
--}}
@php
    $glassPanel = 'background-color: color-mix(in srgb, var(--site-surface) 72%, transparent); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 9%, transparent), 0 30px 60px -30px rgb(0 0 0 / .45);';
    $gradientRing = 'background-image: conic-gradient(from var(--bq-aurora-angle, 0deg), var(--site-primary), color-mix(in srgb, var(--site-primary) 30%, var(--site-surface)), var(--site-primary-deep), var(--site-primary));';
    $solidButton = 'background-color: var(--site-primary); color: var(--site-on-primary); box-shadow: 0 14px 34px -14px var(--site-primary);';
    $ghostButton = 'background-color: color-mix(in srgb, var(--site-text) 7%, transparent);';
    $onPrimaryScope = '--site-primary: var(--site-on-primary); --site-muted: color-mix(in srgb, var(--site-on-primary) 82%, transparent); --site-surface: color-mix(in srgb, var(--site-on-primary) 14%, transparent); --site-text: var(--site-on-primary);';
    // شريحة عائمة جنب صورة الهيرو: تقييم جوجل لو موجود، وإلا عدد الخدمات (نسخة عرض بس).
    $serviceCount = collect($sections->firstWhere('kind', 'list')['items'] ?? [])
        ->where('slot.slot_type', 'list')->sum(fn ($i) => count((array) $i['value']));
    $serviceLabel = match (true) {
        $serviceCount === 1 => 'خدمة واحدة', $serviceCount === 2 => 'خدمتين',
        $serviceCount <= 10 => $serviceCount.' خدمات', default => $serviceCount.' خدمة',
    };
    $rating = $sections->firstWhere('kind', 'testimonials')['rating'] ?? null;
@endphp

{{-- البقع الضبابية: fixed ورا كل حاجة (z-0)، والمحتوى كله relative فوقها. --}}
<div class="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden="true">
    <div class="bq-aurora-blob absolute -top-40 start-[-10rem] h-[34rem] w-[34rem] rounded-full blur-3xl" style="background-color: color-mix(in srgb, var(--site-primary) 34%, transparent);"></div>
    <div class="bq-aurora-blob bq-aurora-blob--slow absolute top-1/3 end-[-12rem] h-[30rem] w-[30rem] rounded-full blur-3xl" style="background-color: color-mix(in srgb, var(--site-primary-deep) 30%, transparent);"></div>
    <div class="bq-aurora-blob absolute bottom-[-14rem] start-1/3 h-[28rem] w-[28rem] rounded-full blur-3xl" style="background-color: color-mix(in srgb, var(--site-text) 10%, transparent);"></div>
</div>

<div class="relative z-10">
    @include('site.partials.nav')

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
            <section id="{{ $section['key'] }}" class="relative px-5 pb-20 pt-14 sm:px-10 sm:pb-28 sm:pt-20">
                <div @class(['mx-auto grid max-w-6xl items-center gap-14', 'lg:grid-cols-[1.1fr_1fr]' => $heroImage])>
                    <div @class(['flex flex-col gap-7', 'items-start' => $heroImage, 'items-center text-center' => ! $heroImage])>
                        <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold" style="{{ $glassPanel }}">
                            <span class="h-2 w-2 rounded-full" style="background-color: var(--site-primary); box-shadow: 0 0 12px var(--site-primary);" aria-hidden="true"></span>
                            {{ $project->name }}
                        </span>
                        @if ($heading)
                            <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.6rem,6vw,5rem)] font-extrabold leading-[1.15]">{!! $heading['value'] !!}</h1>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                        <div class="flex flex-wrap gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-4 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
                            @endforeach
                            @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                                @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-8 py-4 text-base font-bold transition hover:opacity-90', 'style' => $solidButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
                            @endif
                            @if (! empty($siteMeta['nav']))
                                <a href="#{{ $siteMeta['nav'][0]['key'] }}" class="rounded-full px-8 py-4 text-base font-bold transition hover:opacity-75" style="{{ $ghostButton }}">{{ $siteMeta['nav'][0]['label'] }}</a>
                            @endif
                        </div>
                    </div>
                    @if ($heroImage)
                        {{-- الإطار المتدرّج بيلف (متغيّر CSS متحرّك على الإطار نفسه)، والصورة جوّه حاوية
                        overflow-hidden ثابتة. الشرائح العائمة نسخة عرض بس (من غير data-slot). --}}
                        <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                            <div class="bq-aurora-ring rounded-[2.2rem] p-[3px]" style="{{ $gradientRing }}">
                                <div class="relative aspect-[4/5] overflow-hidden rounded-[2.1rem]" style="background-color: var(--site-surface);">
                                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                                </div>
                            </div>
                            @if ($rating)
                                <div class="pointer-events-none absolute -bottom-5 start-4 rounded-2xl p-1.5 backdrop-blur-md sm:start-[-1.5rem]" style="{{ $glassPanel }}">
                                    @include('site.partials.rating-badge', ['badge' => ['rating' => $rating, 'style' => 'background-color: transparent;']])
                                </div>
                            @elseif ($serviceCount > 0)
                                <div class="pointer-events-none absolute -bottom-5 start-4 flex items-center gap-3 rounded-2xl px-4 py-3 backdrop-blur-md sm:start-[-1.5rem]" style="{{ $glassPanel }}">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl" style="background-color: var(--site-primary); color: var(--site-on-primary);">@include('site.partials.icon', ['name' => 'sparkles', 'class' => 'h-5 w-5'])</span>
                                    <span class="text-sm font-bold">{{ $serviceLabel }} في انتظارك</span>
                                </div>
                            @endif
                            @if ($contactAction)
                                <div class="pointer-events-none absolute -top-4 end-4 flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold backdrop-blur-md sm:end-[-1rem]" style="{{ $glassPanel }}">
                                    <span style="color: var(--site-primary);">@include('site.partials.icon', ['name' => $contactAction['type'] === 'whatsapp' ? 'whatsapp' : 'phone', 'class' => 'h-4 w-4'])</span>
                                    رد سريع
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

        @elseif ($section['kind'] === 'gallery')
            @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
            <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
                <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                    @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => null]])
                    <div class="{{ $mosaic['grid'] }} auto-rows-[9rem] gap-3 sm:auto-rows-[11rem] sm:gap-4">
                        @foreach ($imageItems as $i => $item)
                            <div class="group relative overflow-hidden rounded-[1.6rem] {{ $mosaic['items'][$i] }}" style="box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 8%, transparent);">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                                <div class="pointer-events-none absolute inset-0 opacity-0 transition duration-500 group-hover:opacity-100" style="background-image: linear-gradient(to top, color-mix(in srgb, var(--site-primary) 45%, transparent), transparent 60%);"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

        @elseif ($section['kind'] === 'list')
            <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
                <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                    @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                    @foreach ($listItems as $item)
                        @php $entries = (array) $item['value']; @endphp
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($entries as $i => $entry)
                                @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                {{-- إطار متدرّج 1.5px (حاوية ملوّنة + كارت جوّاها). --}}
                                <div data-slot="{{ $item['slot']->key }}" class="rounded-[1.75rem] p-[1.5px] {{ \App\Services\SiteRenderer::cardWidth(count($entries)) }}" style="background-image: linear-gradient(140deg, color-mix(in srgb, var(--site-primary) 70%, transparent), color-mix(in srgb, var(--site-text) 8%, transparent) 45%, color-mix(in srgb, var(--site-primary-deep) 50%, transparent));">
                                    <div class="flex h-full flex-col gap-5 rounded-[calc(1.75rem-1.5px)] p-7" style="background-color: color-mix(in srgb, var(--site-surface) 92%, var(--site-background));">
                                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg font-extrabold" style="background-image: linear-gradient(140deg, var(--site-primary), var(--site-primary-deep)); color: var(--site-on-primary);">{{ $i + 1 }}</span>
                                        <span class="text-xl font-bold leading-snug">{{ $e['title'] }}</span>
                                        @if ($e['desc'])
                                            <span class="leading-relaxed" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    @if ($linkItems->isNotEmpty())
                        <div class="flex flex-wrap justify-center gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

        @elseif ($section['kind'] === 'testimonials')
            <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
                <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                    @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])
                    @foreach ($listItems as $item)
                        @if (isset($item['testimonials']))
                            <div class="flex flex-wrap justify-center gap-6">
                                @foreach ($item['testimonials'] as $t)
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])).' backdrop-blur-md',
                                        'style' => 'background-color: color-mix(in srgb, var(--site-surface) 70%, transparent);',
                                    ]])
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>

        @elseif ($section['kind'] === 'cta')
            <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
                <div data-reveal class="relative mx-auto max-w-6xl overflow-hidden rounded-[2.5rem] px-6 py-16 text-center sm:px-16 sm:py-20" style="background-image: radial-gradient(circle at 15% 20%, color-mix(in srgb, var(--site-on-primary) 18%, transparent), transparent 45%), linear-gradient(135deg, var(--site-primary), var(--site-primary-deep)); color: var(--site-on-primary);">
                    <div class="mx-auto flex max-w-2xl flex-col items-center gap-6" style="{{ $onPrimaryScope }}">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.2rem,5vw,3.8rem)] font-extrabold leading-[1.2]">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose sm:text-xl" style="opacity: .9;">{!! $item['value'] !!}</p>
                        @endforeach
                        @if ($linkItems->isNotEmpty() || $contactAction)
                            <div class="mt-2 flex flex-wrap justify-center gap-3">
                                @foreach ($linkItems as $item)
                                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-9 py-4 text-lg font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary-deep);']])
                                @endforeach
                                @if ($linkItems->isEmpty() && $contactAction)
                                    @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-full px-9 py-4 text-lg font-bold transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary-deep);']])
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </section>

        @else
            <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-10 sm:py-24">
                <div data-reveal class="mx-auto flex max-w-4xl flex-col items-center gap-6 rounded-[2.5rem] px-6 py-14 text-center backdrop-blur-md sm:px-14" style="{{ $glassPanel }}">
                    @if (filled($section['eyebrow'] ?? null))
                        <span class="rounded-full px-4 py-1 text-sm font-bold" style="background-color: color-mix(in srgb, var(--site-primary) 16%, transparent); color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                    @endif
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-[2] sm:text-xl" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    @if ($linkItems->isNotEmpty())
                        <div class="flex flex-wrap justify-center gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold transition hover:opacity-90', 'style' => $solidButton]])
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
        @include('site.partials.footer', ['footerTheme' => 'rounded'])
    @endif
</div>
