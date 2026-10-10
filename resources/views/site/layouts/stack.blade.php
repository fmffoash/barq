{{--
    "ستاك" — طابع "سكراب بوك" مرح: صور فوق بعض بميلان خفيف وشريط لاصق، صور بولارويد، وخدمات
    على هيئة ورق ملاحظات ملوّن. مكتبة القوالب بتديه لوحات فاتحة بس (طابع الورق).
    (2026-10-10) اتعاد تصميمه: الصورة نفسها كانت متلفّة جوّه إطار مش متلفّ (بيبان مثلثات فاضية
    في الأركان)، والهيرو والمعرض كانوا صغيرين جداً (صور 160px)، والكروت العريضة المايلة كانت بتصعّب
    القراية. دلوقتي الميلان على الإطار بس (مش الصورة — التكبير في المحرر المباشر بيستخدم transform
    على الصورة)، والميلان من sm وطالع بس (الموبايل مستقيم).
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، صفر transform على <img>.
--}}
@include('site.partials.nav')

@php
    $bodyColor = 'color: color-mix(in srgb, var(--site-text) 78%, var(--site-surface));';
    $primaryButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
    $ring = 'box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 8%, transparent), 0 18px 34px -18px rgb(0 0 0 / .35);';
    $paper = 'background-color: color-mix(in srgb, #ffffff 82%, var(--site-surface));';
    $tape = 'background-color: color-mix(in srgb, var(--site-primary) 30%, #ffffff); opacity: .85;';
    $tilts = ['sm:-rotate-2', 'sm:rotate-2', 'sm:-rotate-1', 'sm:rotate-3', 'sm:-rotate-3', 'sm:rotate-1'];
    $noteFills = [
        'background-color: var(--site-surface);',
        'background-color: color-mix(in srgb, var(--site-primary) 14%, var(--site-surface));',
        'background-color: color-mix(in srgb, var(--site-primary) 7%, #ffffff);',
    ];
@endphp

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
        <section id="{{ $section['key'] }}" class="relative flex min-h-[70vh] items-center overflow-hidden px-5 py-14 sm:px-8 sm:py-20">
            <div @class(['mx-auto grid w-full max-w-6xl items-center gap-16', 'lg:grid-cols-[1.15fr_1fr]' => $heroImage])>
                <div @class(['flex flex-col gap-6', 'items-start text-start' => $heroImage, 'items-center text-center' => ! $heroImage])>
                    <span class="inline-flex -rotate-2 items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold" style="{{ $primaryButton }}">
                        @include('site.partials.icon', ['name' => 'sparkles', 'class' => 'h-4 w-4'])
                        {{ $project->name }}
                    </span>
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.5rem,5.6vw,4.6rem)] font-black leading-[1.2]">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg leading-loose sm:text-xl" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="mt-1 flex flex-wrap gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-2xl px-8 py-4 text-base font-bold shadow-lg transition hover:-translate-y-0.5', 'style' => $primaryButton]])
                        @endforeach
                        @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                            @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-2xl px-8 py-4 text-base font-bold shadow-lg transition hover:-translate-y-0.5', 'style' => $primaryButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-4 w-4']])
                        @endif
                    </div>
                </div>
                @if ($heroImage)
                    {{-- كومة صور: كارتين زخرفة ورا + الإطار الأساسي متلفّ (الصورة جوّاه مستقيمة). --}}
                    <div class="relative mx-auto h-[24rem] w-[19rem] sm:h-[28rem] sm:w-[22rem]">
                        <div class="pointer-events-none absolute inset-0 -rotate-6 rounded-[1.75rem]" style="background-color: color-mix(in srgb, var(--site-primary) 22%, transparent);" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute inset-0 rotate-6 rounded-[1.75rem]" style="{{ $paper }} {{ $ring }}" aria-hidden="true"></div>
                        <div class="absolute inset-0 rotate-2 rounded-[1.75rem] p-3 pb-12" style="{{ $paper }} {{ $ring }}">
                            <div class="h-full w-full overflow-hidden rounded-[1.25rem]">
                                <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                            </div>
                        </div>
                        <span class="pointer-events-none absolute -top-3 left-1/2 h-8 w-32 -translate-x-1/2 -rotate-3" style="{{ $tape }}" aria-hidden="true"></span>
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'gallery')
        {{-- صور بولارويد متداخلة شوية، كل واحدة بميلان مختلف وبتتعدل لما الماوس يقف عليها. --}}
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                <div class="flex flex-wrap justify-center gap-6 sm:gap-0">
                    @foreach ($imageItems as $item)
                        <div class="relative w-[calc(50%-0.75rem)] rounded-xl p-2 pb-8 transition hover:z-10 hover:rotate-0 sm:-mx-2 sm:w-60 sm:p-2.5 sm:pb-12 lg:w-64 {{ $tilts[$loop->index % count($tilts)] }}" style="{{ $paper }} {{ $ring }}">
                            <div class="aspect-[4/5] overflow-hidden rounded-lg">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    @elseif ($section['kind'] === 'list')
        {{-- الخدمات ورق ملاحظات بألوان متبادلة ورقم كبير — عرض ثابت في صفوف في النص (أي عدد). --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                @foreach ($listItems as $item)
                    <div class="flex flex-wrap justify-center gap-6">
                        @foreach ((array) $item['value'] as $i => $entry)
                            @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                            <div data-slot="{{ $item['slot']->key }}" class="relative flex min-h-36 w-[calc(50%-0.75rem)] flex-col gap-3 rounded-2xl p-5 pt-8 sm:min-h-44 sm:w-60 sm:p-6 sm:pt-8 {{ $i % 2 === 0 ? 'sm:-rotate-1' : 'sm:rotate-1' }}" style="{{ $noteFills[$i % count($noteFills)] }} {{ $ring }}">
                                <span class="pointer-events-none absolute -top-2.5 left-1/2 h-5 w-20 -translate-x-1/2 rotate-2" style="{{ $tape }}" aria-hidden="true"></span>
                                <span class="text-4xl font-black leading-none" style="color: var(--site-primary);">{{ $i + 1 }}</span>
                                <span class="text-lg font-bold leading-snug">{{ $e['title'] }}</span>
                                @if ($e['desc'])
                                    <span class="text-base leading-relaxed" style="{{ $bodyColor }}">{{ $e['desc'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-2xl px-7 py-3 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton]])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    @elseif ($section['kind'] === 'testimonials')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24" style="background-color: color-mix(in srgb, var(--site-primary) 6%, var(--site-background));">
            <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-12">
                @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])
                @foreach ($listItems as $item)
                    @if (isset($item['testimonials']))
                        <div class="flex flex-wrap justify-center gap-6">
                            @foreach ($item['testimonials'] as $t)
                                @include('site.partials.testimonial-card', ['card' => [
                                    't' => $t,
                                    'slotKey' => $item['slot']->key,
                                    'class' => \App\Services\SiteRenderer::cardWidth(count($item['testimonials'])).' '.($loop->index % 2 === 0 ? 'sm:rotate-1' : 'sm:-rotate-1'),
                                    'style' => $paper.' '.$ring,
                                ]])
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-24">
            <div data-reveal class="relative mx-auto flex max-w-4xl flex-col items-center gap-5 rounded-[2rem] px-6 py-14 text-center shadow-2xl sm:-rotate-1 sm:px-12 sm:py-16" style="{{ $primaryButton }}">
                <span class="pointer-events-none absolute -top-3 left-1/2 h-7 w-28 -translate-x-1/2 rotate-2" style="{{ $paper }} opacity: .9;" aria-hidden="true"></span>
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-3xl text-[clamp(2rem,4.2vw,3.3rem)] font-black leading-[1.25]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="opacity: .9;">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty() || $contactAction)
                    <div class="mt-2 flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-2xl px-9 py-4 text-lg font-bold shadow-lg transition hover:-translate-y-0.5', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                        @endforeach
                        @if ($linkItems->isEmpty() && $contactAction)
                            @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-2xl px-9 py-4 text-lg font-bold shadow-lg transition hover:-translate-y-0.5', 'style' => 'background-color: var(--site-on-primary); color: var(--site-primary);']])
                        @endif
                    </div>
                @endif
            </div>
        </section>

    @else
        {{-- نص عادي ("من نحن" وأي قسم مضاف): ورقة متلزّقة بشريط. --}}
        <section id="{{ $section['key'] }}" class="relative px-5 py-16 sm:px-8 sm:py-20">
            <div data-reveal class="relative mx-auto flex max-w-3xl flex-col items-center gap-5 rounded-2xl px-7 py-12 text-center sm:rotate-1 sm:px-14" style="{{ $paper }} {{ $ring }}">
                <span class="pointer-events-none absolute -top-3 left-1/2 h-7 w-28 -translate-x-1/2 -rotate-2" style="{{ $tape }}" aria-hidden="true"></span>
                @if (filled($section['eyebrow'] ?? null))
                    <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                @endif
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,3.6vw,2.8rem)] font-black leading-[1.3]">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose sm:text-xl sm:leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                @endforeach
                @if ($linkItems->isNotEmpty())
                    <div class="flex flex-wrap justify-center gap-3">
                        @foreach ($linkItems as $item)
                            @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-2xl px-7 py-3 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton]])
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
    @include('site.partials.footer')
@endif
