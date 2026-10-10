{{--
    "جلاس" — بقع ألوان متدرجة ورا الصفحة كلها، وكروت زجاجية (backdrop-blur) طايفة فوقها — طابع
    فاخر/عصري. (2026-10-10) اتعاد تصميمه: كان شبه "نيون" بالظبط (نفس الهيرو: عنوان في النص وتحته
    صورة في كارت) والزجاج نفسه مكانش باين لأن البقعتين كانوا في أركان الشاشة بعيد عن الكروت.
    - الهيرو: صورة كبيرة بحواف دائرية وكارت زجاجي فيه العنوان طايف فوقها في الركن.
    - البقع جوّه حاوية الصفحة نفسها (مش fixed) على ارتفاعات مختلفة، فكل كارت تحته لون يتشاف.
    - "من نحن" من غير كارت (نص كبير وخط رفيع)، الخدمات بلاطات زجاج بأيقونة، المعرض فسيفساء،
      الآراء كروت زجاج، والتواصل شريط زجاج ملوّن بعرض الصفحة.
    قواعد المحرر المباشر: data-slot على كل عنصر خانة وكل <img>، position:relative على كل
    <section>، bq-free-position-boundary + pointer-events على الكارت اللي فوق صورة الهيرو، صفر
    transform على <img>.
--}}
@php
    $glass = 'background-color: color-mix(in srgb, var(--site-surface) 58%, transparent); border-color: color-mix(in srgb, var(--site-text) 12%, transparent); box-shadow: inset 0 1px 0 color-mix(in srgb, #ffffff 22%, transparent), 0 24px 50px -28px rgb(0 0 0 / .45);';
    $glassClass = 'border backdrop-blur-xl backdrop-saturate-150';
    $bodyColor = 'color: color-mix(in srgb, var(--site-text) 80%, var(--site-surface));';
    $primaryButton = 'background-color: var(--site-primary); color: var(--site-on-primary);';
@endphp

<div class="relative isolate">
    {{-- البقع: نسخة زخرفية بس، ورا كل حاجة (-z-10 جوّه isolate) ومن غير ما تاخد دوس. --}}
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -end-40 top-[3%] h-[34rem] w-[34rem] rounded-full opacity-35 blur-3xl" style="background-color: var(--site-primary);"></div>
        <div class="absolute -start-48 top-[24%] h-[30rem] w-[30rem] rounded-full opacity-30 blur-3xl" style="background-color: color-mix(in srgb, var(--site-primary) 55%, var(--site-text));"></div>
        <div class="absolute -end-32 top-[46%] h-[28rem] w-[28rem] rounded-full opacity-30 blur-3xl" style="background-color: var(--site-primary);"></div>
        <div class="absolute -start-40 top-[66%] h-[32rem] w-[32rem] rounded-full opacity-30 blur-3xl" style="background-color: color-mix(in srgb, var(--site-primary) 70%, var(--site-background));"></div>
        <div class="absolute -end-48 top-[84%] h-[30rem] w-[30rem] rounded-full opacity-25 blur-3xl" style="background-color: color-mix(in srgb, var(--site-primary) 55%, var(--site-text));"></div>
    </div>

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
            <section id="{{ $section['key'] }}" class="relative px-4 pb-10 pt-6 sm:px-8 sm:pb-16">
                <div
                    class="relative mx-auto flex min-h-[78vh] max-w-6xl flex-col justify-end overflow-hidden rounded-[2.5rem] p-3 sm:p-8 lg:p-10"
                    style="{{ $heroImage ? '' : 'background-image: linear-gradient(140deg, color-mix(in srgb, var(--site-primary) 30%, var(--site-surface)), var(--site-background));' }}"
                >
                    @if ($heroImage)
                        <div class="absolute inset-0">
                            <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                        </div>
                        <div class="pointer-events-none absolute inset-0" style="background-image: linear-gradient(to top, rgb(0 0 0 / .42), rgb(0 0 0 / 0) 60%);"></div>
                    @endif
                    {{-- pointer-events-none على الكارت + auto على العناصر القابلة للتعديل: الفراغ
                    حوالين النص مايمنعش الدوس على صورة الهيرو تحته (المحرر المباشر). --}}
                    <div class="bq-free-position-boundary relative z-10 flex w-full max-w-xl flex-col items-start gap-5 rounded-[2rem] p-7 sm:p-10 {{ $glassClass }}" style="{{ $glass }} pointer-events: none;">
                        @if ($heading)
                            <h1 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(2.2rem,4.8vw,3.9rem)] font-extrabold leading-[1.2]" style="pointer-events: auto;">{!! $heading['value'] !!}</h1>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="{{ $bodyColor }} pointer-events: auto;">{!! $item['value'] !!}</p>
                        @endforeach
                        <div class="flex flex-wrap gap-3" style="pointer-events: auto;">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $primaryButton]])
                            @endforeach
                            @if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
                                @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90', 'style' => $primaryButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-4 w-4']])
                            @endif
                        </div>
                    </div>
                </div>
            </section>

        @elseif ($section['kind'] === 'gallery')
            @php $mosaic = \App\Services\SiteRenderer::mosaic($imageItems->count()); @endphp
            <section id="{{ $section['key'] }}" class="relative px-4 py-14 sm:px-8 sm:py-20">
                <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                    @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null]])
                    <div class="{{ $mosaic['grid'] }} auto-rows-[9rem] gap-3 sm:auto-rows-[12rem] sm:gap-4 lg:auto-rows-[14rem]">
                        @foreach ($imageItems as $item)
                            <div class="overflow-hidden rounded-[1.75rem] {{ $mosaic['items'][$loop->index] }}" style="box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 10%, transparent), 0 18px 40px -24px rgb(0 0 0 / .5);">
                                <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

        @elseif ($section['kind'] === 'list' || $section['kind'] === 'testimonials')
            <section id="{{ $section['key'] }}" class="relative px-4 py-14 sm:px-8 sm:py-20">
                <div data-reveal class="mx-auto flex max-w-6xl flex-col gap-10">
                    @include('site.partials.section-heading', ['head' => ['heading' => $heading, 'supporting' => $supportingItems, 'label' => $section['eyebrow'] ?? null, 'rating' => $section['rating'] ?? null]])
                    @foreach ($listItems as $item)
                        @if ($section['kind'] === 'testimonials' && isset($item['testimonials']))
                            <div class="flex flex-wrap justify-center gap-6">
                                @foreach ($item['testimonials'] as $t)
                                    @include('site.partials.testimonial-card', ['card' => [
                                        't' => $t,
                                        'slotKey' => $item['slot']->key,
                                        'class' => $glassClass.' '.\App\Services\SiteRenderer::cardWidth(count($item['testimonials'])),
                                        'style' => $glass,
                                    ]])
                                @endforeach
                            </div>
                        @else
                            @php $entries = (array) $item['value']; $width = \App\Services\SiteRenderer::cardWidth(count($entries)); @endphp
                            <div class="flex flex-wrap justify-center gap-6">
                                @foreach ($entries as $entry)
                                    @php $e = \App\Services\SiteRenderer::splitEntry($entry); @endphp
                                    <div data-slot="{{ $item['slot']->key }}" class="flex flex-col gap-4 rounded-[1.75rem] p-7 {{ $glassClass }} {{ $width }}" style="{{ $glass }}">
                                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl" style="background-color: color-mix(in srgb, var(--site-primary) 20%, transparent); color: var(--site-primary);">
                                            @include('site.partials.icon', ['name' => 'sparkles', 'class' => 'h-5 w-5'])
                                        </span>
                                        <span class="text-lg font-bold leading-snug">{{ $e['title'] }}</span>
                                        @if ($e['desc'])
                                            <span class="text-base leading-relaxed" style="{{ $bodyColor }}">{{ $e['desc'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    @if ($linkItems->isNotEmpty())
                        <div class="flex flex-wrap justify-center gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-7 py-3 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton]])
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

        @elseif ($section['kind'] === 'cta')
            <section id="{{ $section['key'] }}" class="relative px-4 py-14 sm:px-8 sm:py-20">
                <div
                    data-reveal
                    class="mx-auto flex max-w-6xl flex-col items-center gap-5 rounded-[2.5rem] px-6 py-14 text-center sm:px-12 sm:py-20 {{ $glassClass }}"
                    style="{{ $glass }} background-image: linear-gradient(135deg, color-mix(in srgb, var(--site-primary) 30%, transparent), color-mix(in srgb, var(--site-primary) 6%, transparent));"
                >
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="max-w-3xl text-[clamp(2rem,4vw,3.2rem)] font-extrabold leading-[1.25]">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-lg leading-loose" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                    @endforeach
                    @if ($linkItems->isNotEmpty() || $contactAction)
                        <div class="mt-2 flex flex-wrap justify-center gap-3">
                            @foreach ($linkItems as $item)
                                @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-9 py-4 text-lg font-bold shadow-lg transition hover:opacity-90', 'style' => $primaryButton]])
                            @endforeach
                            @if ($linkItems->isEmpty() && $contactAction)
                                @include('site.partials.link-button', ['btn' => $contactAction + ['class' => 'rounded-full px-9 py-4 text-lg font-bold shadow-lg transition hover:opacity-90', 'style' => $primaryButton]])
                            @endif
                        </div>
                    @endif
                </div>
            </section>

        @else
            {{-- نص عادي ("من نحن" وأي قسم مضاف): من غير كارت — عنوان على جنب ونص كبير على
            الجنب التاني بخط رفيع باللون الأساسي. --}}
            <section id="{{ $section['key'] }}" class="relative px-4 py-14 sm:px-8 sm:py-20">
                <div data-reveal @class(['mx-auto grid max-w-6xl gap-8', 'lg:grid-cols-[1fr_1.4fr] lg:gap-16' => $heading && $supportingItems->isNotEmpty()])>
                    @if ($heading)
                        <div class="flex flex-col gap-4">
                            @if (filled($section['eyebrow'] ?? null))
                                <span class="text-sm font-bold" style="color: var(--site-primary);">{{ $section['eyebrow'] }}</span>
                            @endif
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-[clamp(1.9rem,3.4vw,2.8rem)] font-extrabold leading-[1.3]">{!! $heading['value'] !!}</h2>
                            <span class="h-1 w-16 rounded-full" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                        </div>
                    @endif
                    <div class="flex flex-col justify-center gap-5">
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-xl leading-loose sm:text-2xl sm:leading-[2]" style="{{ $bodyColor }}">{!! $item['value'] !!}</p>
                        @endforeach
                        @if ($linkItems->isNotEmpty())
                            <div class="flex flex-wrap gap-3">
                                @foreach ($linkItems as $item)
                                    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-7 py-3 text-base font-bold transition hover:opacity-90', 'style' => $primaryButton]])
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
        @include('site.partials.footer')
    @endif
</div>
