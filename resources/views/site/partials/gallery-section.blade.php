@php
    $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
    $listItems = $section['items']->where('slot.slot_type', 'list');
    $imageItems = $section['items']->where('slot.slot_type', 'image');
    $linkItems = $section['items']->where('slot.slot_type', 'link');
    $imageOnRight = $index % 2 === 0;

    $heading = $textItems->firstWhere('slot.slot_type', 'text');
    $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
@endphp

@switch($section['kind'])
    @case('hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section
            id="{{ $section['key'] }}"
            class="relative flex min-h-[70vh] flex-col items-center justify-center gap-5 overflow-hidden px-6 py-20 text-center sm:px-10"
            style="{{ $heroImage ? '' : 'background-color: var(--site-surface);' }}"
        >
            @if ($heroImage)
                {{-- صورة حقيقية (<img data-slot>) بدل background-image مباشر على الـsection —
                عشان تكبير/تحريك الصورة (المرحلة 2) يشتغل، بنفس آلية overflow-hidden
                الموجودة على الـsection أصلاً. --}}
                <div class="absolute inset-0">
                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="" class="h-full w-full object-cover" aria-hidden="true">
                    <div class="pointer-events-none absolute inset-0" style="background-image: linear-gradient(to top, color-mix(in srgb, var(--site-background) 85%, transparent), color-mix(in srgb, var(--site-background) 30%, transparent));"></div>
                </div>
            @endif
            {{-- pointer-events-none على الحاوية + pointer-events-auto على كل عنصر قابل
            للتعديل فعلياً (المرحلة 2) — من غيرها مساحات الفراغ حوالين النص بتمنع الدوس على
            صورة الهيرو تحتها لأن الحاوية طالعة فوقها بـz-10. --}}
            <div class="relative z-10 mx-auto flex max-w-3xl flex-col items-center gap-5" style="pointer-events: none;">
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-extrabold drop-shadow-sm sm:text-5xl" style="pointer-events: auto;">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose sm:text-xl" style="color: var(--site-muted); pointer-events: auto;">{!! $item['value'] !!}</p>
                @endforeach

                @foreach ($linkItems as $item)
                    <a
                        data-slot="{{ $item['slot']->key }}"
                        href="{{ $item['value'] }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-2 inline-block rounded-full px-8 py-3.5 text-base font-semibold shadow-lg transition hover:opacity-90"
                        style="background-color: var(--site-primary); color: var(--site-background); pointer-events: auto;"
                    >
                        {{ $item['slot']->label() }}
                    </a>
                @endforeach
            </div>
        </section>
        @break

    @case('gallery')
        @php $mainImage = $imageItems->first(); $restImages = $imageItems->skip(1); @endphp
        <section id="{{ $section['key'] }}" class="relative px-6 py-16 sm:px-10">
            <div class="mx-auto flex max-w-5xl flex-col items-center gap-10 lg:flex-row {{ $imageOnRight ? '' : 'lg:flex-row-reverse' }}">
                @if ($mainImage)
                    <div class="aspect-[4/3] w-full flex-1 overflow-hidden rounded-[1.75rem] shadow-2xl lg:w-1/2">
                        <img
                            data-slot="{{ $mainImage['slot']->key }}"
                            src="{{ $mainImage['value'] }}"
                            alt="{{ $mainImage['slot']->label() }}"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        >
                    </div>
                @endif

                <div class="flex flex-1 flex-col gap-4 text-center lg:text-right">
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                </div>
            </div>

            @if ($restImages->isNotEmpty())
                <div class="mx-auto mt-8 grid max-w-5xl gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($restImages as $item)
                        <div class="aspect-square w-full overflow-hidden rounded-[1.5rem] shadow-lg">
                            <img
                                data-slot="{{ $item['slot']->key }}"
                                src="{{ $item['value'] }}"
                                alt="{{ $item['slot']->label() }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            >
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
        @break

    @case('list')
        <section id="{{ $section['key'] }}" class="relative px-6 py-16 sm:px-10">
            <div class="mx-auto flex max-w-4xl flex-col gap-8">
                @if ($textItems->isNotEmpty())
                    <div class="text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-3 leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif

                @foreach ($listItems as $item)
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ((array) $item['value'] as $listItem)
                            <li
                                data-slot="{{ $item['slot']->key }}"
                                class="flex items-start gap-3 rounded-[1.5rem] p-5 text-base leading-relaxed shadow-md"
                                style="background-color: var(--site-surface);"
                            >
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: var(--site-primary);"></span>
                                <span>{{ $listItem }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </section>
        @break

    @case('cta')
        <section id="{{ $section['key'] }}" class="relative px-6 py-16 sm:px-10">
            <div
                class="mx-auto flex max-w-3xl flex-col items-center gap-4 rounded-[2.5rem] px-8 py-14 text-center shadow-2xl"
                style="background-image: linear-gradient(135deg, var(--site-primary), color-mix(in srgb, var(--site-primary) 55%, black));"
            >
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold text-white">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="leading-loose" style="color: rgba(255,255,255,.85);">{!! $item['value'] !!}</p>
                @endforeach

                @foreach ($linkItems as $item)
                    <a
                        data-slot="{{ $item['slot']->key }}"
                        href="{{ $item['value'] }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-2 inline-block rounded-full bg-white px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90"
                        style="color: var(--site-primary);"
                    >
                        {{ $item['slot']->label() }}
                    </a>
                @endforeach
            </div>
        </section>
        @break

    @default
        <section id="{{ $section['key'] }}" class="relative px-6 py-16 sm:px-10">
            <div class="mx-auto flex max-w-2xl flex-col gap-4 text-center">
                @foreach ($section['items'] as $item)
                    @if ($heading && $item['slot']->key === $heading['slot']->key)
                        <h2 data-slot="{{ $item['slot']->key }}" class="text-3xl font-bold">{!! $item['value'] !!}</h2>
                    @elseif ($item['slot']->slot_type === 'link')
                        <a
                            data-slot="{{ $item['slot']->key }}"
                            href="{{ $item['value'] }}"
                            target="_blank"
                            rel="noopener"
                            class="mx-auto inline-block rounded-full px-8 py-3 text-base font-semibold shadow-md transition hover:opacity-90"
                            style="background-color: var(--site-primary); color: var(--site-background);"
                        >
                            {{ $item['slot']->label() }}
                        </a>
                    @else
                        <p data-slot="{{ $item['slot']->key }}" class="leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endif
                @endforeach
            </div>
        </section>
@endswitch
