@php
    $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
    $listItems = $section['items']->where('slot.slot_type', 'list');
    $imageItems = $section['items']->where('slot.slot_type', 'image');
    $linkItems = $section['items']->where('slot.slot_type', 'link');
    $bandSurface = $index % 2 === 1;
@endphp

@switch($section['kind'])
    @case('hero')
        <section
            id="{{ $section['key'] }}"
            class="px-6 py-20 text-center sm:px-10 sm:py-28"
            style="background-color: var(--site-surface);"
        >
            <div class="mx-auto flex max-w-3xl flex-col items-center gap-5">
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')
                        <h1 class="text-4xl font-extrabold sm:text-5xl">{{ $item['value'] }}</h1>
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

                @foreach ($imageItems as $item)
                    <img
                        src="{{ $item['value'] }}"
                        alt="{{ $item['slot']->label() }}"
                        class="mt-4 max-h-[420px] w-full rounded-2xl object-cover shadow-xl"
                        loading="lazy"
                    >
                @endforeach
            </div>
        </section>
        @break

    @case('gallery')
        <section
            id="{{ $section['key'] }}"
            class="px-6 py-16 sm:px-10"
            style="background-color: {{ $bandSurface ? 'var(--site-surface)' : 'var(--site-background)' }};"
        >
            <div class="mx-auto flex max-w-5xl flex-col gap-8">
                @if ($textItems->isNotEmpty())
                    <div class="mx-auto max-w-2xl text-center">
                        @foreach ($textItems as $item)
                            @if ($item['slot']->slot_type === 'text')
                                <h2 class="text-3xl font-bold">{{ $item['value'] }}</h2>
                            @else
                                <p class="mt-3 leading-loose" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($imageItems as $item)
                        <img
                            src="{{ $item['value'] }}"
                            alt="{{ $item['slot']->label() }}"
                            class="aspect-square w-full rounded-xl object-cover shadow-md"
                            loading="lazy"
                        >
                    @endforeach
                </div>
            </div>
        </section>
        @break

    @case('list')
        <section
            id="{{ $section['key'] }}"
            class="px-6 py-16 sm:px-10"
            style="background-color: {{ $bandSurface ? 'var(--site-surface)' : 'var(--site-background)' }};"
        >
            <div class="mx-auto flex max-w-5xl flex-col gap-8">
                @if ($textItems->isNotEmpty())
                    <div class="mx-auto max-w-2xl text-center">
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
                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ((array) $item['value'] as $listItem)
                            <li
                                class="rounded-xl border-r-4 px-5 py-4 text-base leading-relaxed shadow-sm"
                                style="background-color: var(--site-background); border-color: var(--site-primary);"
                            >
                                {{ $listItem }}
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </section>
        @break

    @case('cta')
        <section
            id="{{ $section['key'] }}"
            class="px-6 py-16 text-center sm:px-10"
            style="background-color: var(--site-surface);"
        >
            <div class="mx-auto flex max-w-2xl flex-col items-center gap-4">
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')
                        <h2 class="text-3xl font-bold">{{ $item['value'] }}</h2>
                    @else
                        <p class="leading-loose" style="color: var(--site-muted);">{{ $item['value'] }}</p>
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

    @default
        <section
            id="{{ $section['key'] }}"
            class="px-6 py-16 sm:px-10"
            style="background-color: {{ $bandSurface ? 'var(--site-surface)' : 'var(--site-background)' }};"
        >
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
