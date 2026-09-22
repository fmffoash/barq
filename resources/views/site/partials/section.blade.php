{{--
    تصميم "كلاسيك" — أبسط الثلاثة هيكلياً (عمود واحد، بدون نافبار)، لكن بنفس لغة التصميم
    (كروت دائرية الحواف، ظلال، هيرو مميز) زي modern/gallery — مش عمود نص عادي زي زمان.
    بيستخدم نفس $section['kind'] المحسوبة في SiteRenderer (hero/gallery/list/cta/text).
--}}
@php
    $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
    $listItems = $section['items']->where('slot.slot_type', 'list');
    $imageItems = $section['items']->where('slot.slot_type', 'image');
    $linkItems = $section['items']->where('slot.slot_type', 'link');
    $isHero = $section['kind'] === 'hero';
    $isCta = $section['kind'] === 'cta';

    // لو القسم فيه أكتر من خانة "text" (زي "تواصل معنا": عنوان + ملاحظة قصيرة)، أول خانة
    // بس بتاخد شكل العنوان الكبير (h2)، والباقي بيترندر كنص مساند أصغر بدل عنوانين ضخمين
    // فوق بعض.
    $heading = $textItems->firstWhere('slot.slot_type', 'text');
@endphp

<section id="{{ $section['key'] }}" class="px-4 py-10 sm:px-8">
    <div
        @class([
            'mx-auto flex max-w-3xl flex-col items-center gap-5 rounded-[2.5rem] px-8 py-14 text-center shadow-2xl' => $isHero || $isCta,
            'mx-auto flex max-w-4xl flex-col gap-6 rounded-[2rem] p-8 shadow-md sm:p-10' => ! $isHero && ! $isCta,
        ])
        style="{{ $isHero || $isCta
            ? 'background-image: linear-gradient(135deg, var(--site-primary), color-mix(in srgb, var(--site-primary) 55%, black));'
            : 'background-color: var(--site-surface);' }}"
    >
        @foreach ($section['items'] as $item)
            @php $slot = $item['slot']; $value = $item['value']; @endphp

            @switch($slot->slot_type)
                @case('image')
                    {{-- max-h هنا على الـ<img> نفسه (مش الحاوية) عمداً — الحاوية مفيهاش
                    aspect-ratio ولا height ثابتة، لو حطينا h-full على الصورة وmax-h على
                    الحاوية بس، الحاوية هتنهار لـheight:0 (تبعية دائرية: ارتفاعها معتمد على
                    محتواها اللي بدوره معتمد على ارتفاعها). سيبنا الصورة تاخد ارتفاعها الطبيعي
                    من نسبة أبعادها الحقيقية (زي ما كان قبل المرحلة 2)، والحاوية بترص حواليها
                    بس (overflow-hidden لمنع تكبير الصورة (المرحلة 2) من الخروج برّه الإطار). --}}
                    <div
                        @class([
                            'mx-auto w-full overflow-hidden rounded-[1.75rem] shadow-xl',
                            'ring-4 ring-white/20' => $isHero,
                        ])
                    >
                        <img
                            data-slot="{{ $slot->key }}"
                            src="{{ $value }}"
                            alt="{{ $slot->label() }}"
                            @class([
                                'w-full object-cover',
                                'max-h-[420px]' => $isHero,
                                'max-h-[380px]' => ! $isHero,
                            ])
                            loading="lazy"
                        >
                    </div>
                    @break

                @case('link')
                    <a
                        href="{{ $value }}"
                        target="_blank"
                        rel="noopener"
                        @class([
                            'inline-block rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90',
                            'bg-white' => $isHero || $isCta,
                        ])
                        style="{{ $isHero || $isCta ? 'color: var(--site-primary);' : 'background-color: var(--site-primary); color: var(--site-background);' }}"
                    >
                        {{ $slot->label() }}
                    </a>
                    @break

                @case('list')
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ((array) $value as $listItem)
                            <li
                                data-slot="{{ $slot->key }}"
                                class="flex items-start gap-3 rounded-[1.5rem] px-5 py-4 text-base leading-relaxed shadow-sm"
                                style="background-color: {{ $isHero || $isCta ? 'rgba(255,255,255,.12)' : 'var(--site-background)' }}; {{ $isHero || $isCta ? 'color: white;' : '' }}"
                            >
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $isHero || $isCta ? 'white' : 'var(--site-primary)' }};"></span>
                                <span>{{ $listItem }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @break

                @case('textarea')
                    <p
                        data-slot="{{ $slot->key }}"
                        class="whitespace-pre-line text-lg leading-loose"
                        style="color: {{ $isHero || $isCta ? 'rgba(255,255,255,.85)' : 'var(--site-muted)' }};"
                    >
                        {!! $value !!}
                    </p>
                    @break

                @case('text')
                    @if ($heading && $slot->key === $heading['slot']->key)
                        <h2
                            data-slot="{{ $slot->key }}"
                            @class([
                                'text-3xl font-bold sm:text-4xl',
                                'text-white' => $isHero || $isCta,
                            ])
                        >
                            {!! $value !!}
                        </h2>
                    @else
                        <p
                            data-slot="{{ $slot->key }}"
                            class="whitespace-pre-line text-lg leading-loose"
                            style="color: {{ $isHero || $isCta ? 'rgba(255,255,255,.85)' : 'var(--site-muted)' }};"
                        >
                            {!! $value !!}
                        </p>
                    @endif
                    @break
            @endswitch
        @endforeach
    </div>
</section>
