@php
    $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
    $listItems = $section['items']->where('slot.slot_type', 'list');
    $imageItems = $section['items']->where('slot.slot_type', 'image');
    $linkItems = $section['items']->where('slot.slot_type', 'link');

    $heading = $textItems->firstWhere('slot.slot_type', 'text');
    $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
@endphp

@switch($section['kind'])
    @case('hero')
        @php $heroImage = $imageItems->first(); @endphp
        <section id="{{ $section['key'] }}" class="px-4 pb-10 pt-8 sm:px-8 sm:pt-14">
            <div
                class="relative mx-auto max-w-6xl overflow-hidden rounded-[2.5rem] shadow-2xl"
                style="background-image: linear-gradient(135deg, var(--site-primary), color-mix(in srgb, var(--site-primary) 55%, black));"
            >
                <div class="grid items-center gap-10 px-8 py-14 sm:px-12 sm:py-20 {{ $heroImage ? 'lg:grid-cols-2' : '' }}">
                    <div class="relative z-10 flex flex-col items-start gap-5 text-right {{ $heroImage ? 'order-2 lg:order-1' : 'mx-auto max-w-2xl items-center text-center' }}">
                        @if ($heading)
                            <h1 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-extrabold leading-tight text-white sm:text-5xl">{!! $heading['value'] !!}</h1>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="text-lg leading-loose" style="color: rgba(255,255,255,.85);">{!! $item['value'] !!}</p>
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

                    @if ($heroImage)
                        <div class="relative order-1 lg:order-2">
                            <div class="absolute -inset-8 rounded-full opacity-30 blur-3xl" style="background-color: var(--site-background);"></div>
                            <div class="relative aspect-square w-full overflow-hidden rounded-[2rem] shadow-2xl ring-4 ring-white/20">
                                <img
                                    data-slot="{{ $heroImage['slot']->key }}"
                                    src="{{ $heroImage['value'] }}"
                                    alt="{{ $heroImage['slot']->label() }}"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                >
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
        @break

    @case('gallery')
        <section id="{{ $section['key'] }}" class="px-4 py-14 sm:px-8">
            <div class="mx-auto flex max-w-6xl flex-col gap-10">
                @if ($textItems->isNotEmpty())
                    <div class="mx-auto max-w-2xl text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-3 leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($imageItems as $item)
                        <div
                            @class([
                                'w-full overflow-hidden rounded-[1.75rem] shadow-xl',
                                'col-span-2 row-span-2 aspect-square' => $loop->first,
                                'aspect-square' => ! $loop->first,
                            ])
                        >
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
            </div>
        </section>
        @break

    @case('list')
        <section id="{{ $section['key'] }}" class="px-4 py-14 sm:px-8">
            <div class="mx-auto flex max-w-6xl flex-col gap-10">
                @if ($textItems->isNotEmpty())
                    <div class="mx-auto max-w-2xl text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-3 leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif

                @foreach ($listItems as $item)
                    <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ((array) $item['value'] as $listItem)
                            <li
                                data-slot="{{ $item['slot']->key }}"
                                class="flex items-start gap-4 rounded-[1.75rem] p-6 shadow-md transition hover:-translate-y-1 hover:shadow-xl"
                                style="background-color: var(--site-surface);"
                            >
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                    style="background-color: color-mix(in srgb, var(--site-primary) 18%, transparent); color: var(--site-primary);"
                                >
                                    {{ $loop->iteration }}
                                </span>
                                <span class="pt-1 text-base leading-relaxed">{{ $listItem }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </section>
        @break

    @case('cta')
        <section id="{{ $section['key'] }}" class="px-4 py-14 sm:px-8">
            <div
                class="mx-auto flex max-w-4xl flex-col items-center gap-4 rounded-[2.5rem] px-8 py-14 text-center shadow-2xl"
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
        <section id="{{ $section['key'] }}" class="px-4 py-14 sm:px-8">
            <div
                class="mx-auto flex max-w-3xl flex-col gap-4 rounded-[2rem] p-10 text-center shadow-md"
                style="background-color: var(--site-surface);"
            >
                @foreach ($section['items'] as $item)
                    @if ($heading && $item['slot']->key === $heading['slot']->key)
                        <h2 data-slot="{{ $item['slot']->key }}" class="text-3xl font-bold">{!! $item['value'] !!}</h2>
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
                        <p data-slot="{{ $item['slot']->key }}" class="leading-loose" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endif
                @endforeach
            </div>
        </section>
@endswitch
