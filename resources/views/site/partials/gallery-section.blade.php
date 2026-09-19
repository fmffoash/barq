@php
    $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
    $listItems = $section['items']->where('slot.slot_type', 'list');
    $imageItems = $section['items']->where('slot.slot_type', 'image');
    $linkItems = $section['items']->where('slot.slot_type', 'link');
    $imageOnRight = $index % 2 === 0;
@endphp

@switch($section['kind'])
    @case('hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section
            id="{{ $section['key'] }}"
            class="relative flex min-h-[70vh] flex-col items-center justify-center gap-5 overflow-hidden px-6 py-20 text-center sm:px-10"
            style="{{ $heroImage ? "background-image: linear-gradient(to top, color-mix(in srgb, var(--site-background) 85%, transparent), color-mix(in srgb, var(--site-background) 30%, transparent)), url('{$heroImage['value']}'); background-size: cover; background-position: center;" : 'background-color: var(--site-surface);' }}"
        >
            <div class="relative z-10 mx-auto flex max-w-3xl flex-col items-center gap-5">
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')
                        <h1 class="text-4xl font-extrabold drop-shadow-sm sm:text-5xl">{{ $item['value'] }}</h1>
                    @else
                        <p class="text-lg leading-loose sm:text-xl" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                    @endif
                @endforeach

                @foreach ($linkItems as $item)
                    <a
                        href="{{ $item['value'] }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-2 inline-block rounded-full px-8 py-3.5 text-base font-semibold shadow-lg transition hover:opacity-90"
                        style="background-color: var(--site-primary); color: var(--site-background);"
                    >
                        {{ $item['slot']->label() }}
                    </a>
                @endforeach
            </div>
        </section>
        @break

    @case('gallery')
        @php $mainImage = $imageItems->first(); $restImages = $imageItems->skip(1); @endphp
        <section id="{{ $section['key'] }}" class="px-6 py-16 sm:px-10">
            <div class="mx-auto flex max-w-5xl flex-col items-center gap-10 lg:flex-row {{ $imageOnRight ? '' : 'lg:flex-row-reverse' }}">
                @if ($mainImage)
                    <img
                        src="{{ $mainImage['value'] }}"
                        alt="{{ $mainImage['slot']->label() }}"
                        class="aspect-[4/3] w-full flex-1 rounded-[1.75rem] object-cover shadow-2xl lg:w-1/2"
                        loading="lazy"
                    >
                @endif

                <div class="flex flex-1 flex-col gap-4 text-center lg:text-right">
                    @foreach ($textItems as $item)
                        @if ($item['slot']->slot_type === 'text')
                            <h2 class="text-3xl font-bold">{{ $item['value'] }}</h2>
                        @else
                            <p class="leading-loose" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                        @endif
                    @endforeach
                </div>
            </div>

            @if ($restImages->isNotEmpty())
                <div class="mx-auto mt-8 grid max-w-5xl gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($restImages as $item)
                        <img
                            src="{{ $item['value'] }}"
                            alt="{{ $item['slot']->label() }}"
                            class="aspect-square w-full rounded-[1.5rem] object-cover shadow-lg"
                            loading="lazy"
                        >
                    @endforeach
                </div>
            @endif
        </section>
        @break

    @case('list')
        <section id="{{ $section['key'] }}" class="px-6 py-16 sm:px-10">
            <div class="mx-auto flex max-w-4xl flex-col gap-8">
                @if ($textItems->isNotEmpty())
                    <div class="text-center">
                        @foreach ($textItems as $item)
                            @if ($item['slot']->slot_type === 'text')
                                <h2 class="text-3xl font-bold">{{ $item['value'] }}</h2>
                            @else
                                <p class="mt-3 leading-loose" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                            @endif
                        @endforeach
                    </div>
                @endif

                @foreach ($listItems as $item)
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ((array) $item['value'] as $listItem)
                            <li
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
        <section id="{{ $section['key'] }}" class="px-6 py-16 sm:px-10">
            <div
                class="mx-auto flex max-w-3xl flex-col items-center gap-4 rounded-[2.5rem] px-8 py-14 text-center shadow-2xl"
                style="background-image: linear-gradient(135deg, var(--site-primary), color-mix(in srgb, var(--site-primary) 55%, black));"
            >
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')
                        <h2 class="text-3xl font-bold text-white">{{ $item['value'] }}</h2>
                    @else
                        <p class="leading-loose" style="color: rgba(255,255,255,.85);">{{ $item['value'] }}</p>
                    @endif
                @endforeach

                @foreach ($linkItems as $item)
                    <a
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
        <section id="{{ $section['key'] }}" class="px-6 py-16 sm:px-10">
            <div class="mx-auto flex max-w-2xl flex-col gap-4 text-center">
                @foreach ($section['items'] as $item)
                    @if ($item['slot']->slot_type === 'text')
                        <h2 class="text-3xl font-bold">{{ $item['value'] }}</h2>
                    @elseif ($item['slot']->slot_type === 'link')
                        <a
                            href="{{ $item['value'] }}"
                            target="_blank"
                            rel="noopener"
                            class="mx-auto inline-block rounded-full px-8 py-3 text-base font-semibold shadow-md transition hover:opacity-90"
                            style="background-color: var(--site-primary); color: var(--site-background);"
                        >
                            {{ $item['slot']->label() }}
                        </a>
                    @else
                        <p class="leading-loose" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                    @endif
                @endforeach
            </div>
        </section>
@endswitch
