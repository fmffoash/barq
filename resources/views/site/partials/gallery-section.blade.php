{{--
    قسم واحد في تصميم "جاليري" — تصميم بيعتمد على الصور: هيرو بصورة بعرض الشاشة تحت النافبار،
    ومعرض صور فسيفساء كبير، وباقي الأقسام هادية حواليه.
    الهيرو: طبقة تعتيم سودا على كل اللوحات (الفاتحة كانت بتعمل الصورة "لبنية" والنص الرمادي
    مايتقريش)، والنص أبيض دايماً فوقها.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، bq-free-position-boundary + pointer-events على حاوية النص فوق صورة الهيرو، صفر
    transform على <img>.
--}}
@php
    $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
    $listItems = $section['items']->where('slot.slot_type', 'list');
    $imageItems = $section['items']->where('slot.slot_type', 'image')->values();
    $linkItems = $section['items']->where('slot.slot_type', 'link');

    $heading = $textItems->firstWhere('slot.slot_type', 'text');
    $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    $alt = trim(strip_tags((string) ($heading['value'] ?? ''))) ?: $project->name;
    $softBand = 'background-color: color-mix(in srgb, var(--site-surface) 60%, var(--site-background));';
    $primaryButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
@endphp

@switch($section['kind'])
    @case('hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section
            id="{{ $section['key'] }}"
            @class([
                'relative -mt-[4.5rem] flex min-h-[86vh] flex-col justify-end overflow-hidden px-6 pb-16 pt-32 sm:px-10 sm:pb-24',
            ])
            style="{{ $heroImage ? 'color: #ffffff;' : 'background-image: linear-gradient(160deg, var(--site-surface), var(--site-background));' }}"
        >
            @if ($heroImage)
                {{-- صورة حقيقية (<img data-slot>) بدل background-image — عشان تكبير/تحريك الصورة
                (المرحلة 2) يشتغل، بنفس آلية overflow-hidden على الـsection. --}}
                <div class="absolute inset-0">
                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                    <div class="pointer-events-none absolute inset-0" style="background-image: linear-gradient(to top, rgb(0 0 0 / .78), rgb(0 0 0 / .38) 55%, rgb(0 0 0 / .18));"></div>
                </div>
            @endif
            {{-- pointer-events-none على الحاوية + pointer-events-auto على كل عنصر قابل للتعديل
            فعلياً (المرحلة 2) — من غيرها مساحات الفراغ حوالين النص بتمنع الدوس على صورة الهيرو
            تحتها لأن الحاوية طالعة فوقها بـz-10.
            bq-free-position-boundary (المرحلة 3) — الحاوية دي هي offsetParent أي خانة نص جواها،
            فالترتيب الحر كان بيتحسب نسبة لعرضها هي بس؛ الكلاس marker بس، والفعلي (position:
            absolute+inset:0 لما فيه ترتيب حر شغال فعلاً على خانة جواها) في document.blade.php. --}}
            <div class="bq-free-position-boundary relative z-10 mx-auto flex w-full max-w-6xl flex-col items-start gap-5 text-start" style="pointer-events: none;">
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="max-w-3xl text-[clamp(2.4rem,5.4vw,4.4rem)] font-extrabold leading-[1.2] drop-shadow-[0_2px_14px_rgb(0_0_0/.35)]" style="pointer-events: auto;">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose sm:text-xl" style="{{ $heroImage ? 'color: rgb(255 255 255 / .9);' : 'color: var(--site-muted);' }} pointer-events: auto;">{!! $item['value'] !!}</p>
                @endforeach

                <div class="mt-2 flex flex-wrap items-center gap-3" style="pointer-events: auto;">
                    @foreach ($linkItems as $item)
                        @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $primaryButton]])
                    @endforeach
                    @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                        @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $primaryButton]])
                    @endif
                </div>
            </div>
        </section>
        @break

    @case('gallery')
        @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
        <section id="{{ $section['key'] }}" class="relative px-4 py-16 sm:px-8 sm:py-24">
            <div class="mx-auto flex max-w-6xl flex-col gap-10">
                <div class="flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-end">
                    @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'label' => $section['eyebrow'] ?? null, 'align' => 'start']])
                    @if ($supportingItems->isNotEmpty())
                        <div class="flex max-w-xl flex-col gap-3">
                            @foreach ($supportingItems as $item)
                                <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div data-reveal class="{{ $mosaic['grid'] }} auto-rows-[10rem] gap-2 sm:auto-rows-[13rem] sm:gap-3 lg:auto-rows-[16rem]">
                    @foreach ($imageItems as $item)
                        <div class="overflow-hidden rounded-[1.25rem] {{ $mosaic['items'][$loop->index] }}">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('list')
    @case('testimonials')
        <section id="{{ $section['key'] }}" class="relative px-4 py-16 sm:px-8 sm:py-24" style="{{ $softBand }}">
            <div class="mx-auto flex max-w-6xl flex-col gap-10">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])

                @foreach ($listItems as $item)
                    @if ($section['kind'] === 'testimonials' && isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                    'style' => 'background-color: var(--site-background);',
                                ]])
                            @endforeach
                        </div>
                    @else
                        @php $entries = (array) $item['value']; @endphp
                        <ul class="flex flex-wrap justify-center gap-5">
                            @foreach ($entries as $entry)
                                @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                <li
                                    data-slot="{{ $item['slot']->key }}"
                                    data-reveal
                                    class="flex items-start gap-4 rounded-[1.25rem] p-5 {{ \App\Services\SiteRenderer::cardWidth(count($entries)) }}"
                                    style="background-color: var(--site-background); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 7%, transparent);"
                                >
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full" style="background-color: var(--site-primary); color: var(--site-on-primary);">@include('site.partials.icon', ['name' => 'check', 'class' => 'h-4 w-4'])</span>
                                    <span class="flex flex-col gap-1 pt-1">
                                        <span class="text-base font-bold leading-relaxed">{{ $e['title'] }}</span>
                                        @if ($e['desc'])
                                            <span class="text-sm leading-relaxed" style="color: var(--site-muted);">{{ $e['desc'] }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
            </div>
        </section>
        @break

    @case('cta')
        <section id="{{ $section['key'] }}" class="relative px-4 py-16 sm:px-8 sm:py-20">
            <div data-reveal class="mx-auto flex max-w-4xl flex-col items-center gap-4 rounded-[2.5rem] px-8 py-14 text-center shadow-2xl sm:px-14" style="background-image: linear-gradient(135deg, var(--site-primary), var(--site-primary-deep)); color: var(--site-on-primary);">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,3.4vw,2.75rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="color: color-mix(in srgb, var(--site-on-primary) 86%, transparent);">{!! $item['value'] !!}</p>
                @endforeach

                @foreach ($linkItems as $item)
                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'mt-3 rounded-full px-9 py-4 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                @endforeach
                @if ($linkItems->isEmpty() && $contactAction)
                    @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'mt-3 rounded-full px-9 py-4 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                @endif
            </div>
        </section>
        @break

    @default
        {{-- نص عادي (زي "من نحن"): "بيان" بخط كبير مقروء في عمودين بدل فقرة متوسّطة طويلة. --}}
        <section id="{{ $section['key'] }}" class="relative px-4 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[1fr_2fr] lg:gap-16">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'label' => $section['eyebrow'] ?? null, 'align' => 'start']])
                @if ($supportingItems->isNotEmpty() || $linkItems->isNotEmpty())
                    <div class="flex flex-col items-start gap-5">
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-xl leading-loose sm:text-2xl sm:leading-[1.9]">{!! $item['value'] !!}</p>
                        @endforeach
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3 text-base font-bold shadow-md transition hover:opacity-90', 'style' => $primaryButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
@endswitch
