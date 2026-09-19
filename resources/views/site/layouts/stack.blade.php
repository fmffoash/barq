{{--
    "ستاك" — صور وكروت متراكبة بميلان خفيف (زي كومة صور فوق بعض)، طابع مرح وغير رسمي.
    كل صورة/كارت بميلان (rotate) بسيط متبادل بدل الشبكة المنتظمة.
--}}
@include('site.partials.nav')

@forelse ($sections as $section)
    @php
        $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
        $listItems = $section['items']->where('slot.slot_type', 'list');
        $imageItems = $section['items']->where('slot.slot_type', 'image');
        $linkItems = $section['items']->where('slot.slot_type', 'link');
        $tilts = ['-rotate-3', 'rotate-2', '-rotate-2', 'rotate-3', '-rotate-1'];
    @endphp

    <section id="{{ $section['key'] }}" class="px-4 py-14 sm:px-8">
        <div class="mx-auto max-w-5xl">
            @if ($section['kind'] === 'hero')
                <div class="grid items-center gap-10 {{ $imageItems->isNotEmpty() ? 'lg:grid-cols-2' : '' }}">
                    <div class="flex flex-col items-start gap-5 text-right {{ $imageItems->isNotEmpty() ? '' : 'mx-auto max-w-2xl items-center text-center' }}">
                        @foreach ($textItems as $item)
                            @if ($item['slot']->slot_type === 'text')<h1 data-slot="{{ $item['slot']->key }}" class="text-4xl font-extrabold sm:text-5xl">{{ $item['value'] }}</h1>@else<p data-slot="{{ $item['slot']->key }}" class="text-lg" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                        @endforeach
                        @foreach ($linkItems as $item)
                            <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="rotate-1 inline-block rounded-2xl px-8 py-3.5 text-base font-bold shadow-lg transition hover:rotate-0" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                        @endforeach
                    </div>
                    @if ($imageItems->isNotEmpty())
                        <div class="relative mx-auto h-64 w-64 sm:h-80 sm:w-80">
                            <div class="absolute inset-0 -rotate-6 rounded-[2rem]" style="background-color: var(--site-surface);"></div>
                            <img src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="relative h-full w-full rotate-3 rounded-[2rem] object-cover shadow-2xl" loading="lazy">
                        </div>
                    @endif
                </div>
            @elseif ($section['kind'] === 'gallery')
                @if ($textItems->isNotEmpty())
                    <div class="mb-10 text-center">
                        @foreach ($textItems as $item)
                            @if ($item['slot']->slot_type === 'text')<h2 data-slot="{{ $item['slot']->key }}" class="text-3xl font-bold">{{ $item['value'] }}</h2>@else<p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                        @endforeach
                    </div>
                @endif
                <div class="flex flex-wrap justify-center gap-x-2 gap-y-8 py-4">
                    @foreach ($imageItems as $item)
                        <img src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="h-40 w-40 shrink-0 rounded-[1.5rem] object-cover shadow-xl ring-4 ring-white/10 transition hover:z-10 hover:rotate-0 hover:scale-105 {{ $tilts[$loop->index % count($tilts)] }}" loading="lazy">
                    @endforeach
                </div>
            @elseif ($section['kind'] === 'list')
                @if ($textItems->isNotEmpty())
                    <div class="mb-10 text-center">
                        @foreach ($textItems as $item)
                            @if ($item['slot']->slot_type === 'text')<h2 data-slot="{{ $item['slot']->key }}" class="text-3xl font-bold">{{ $item['value'] }}</h2>@else<p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                        @endforeach
                    </div>
                @endif
                @foreach ($listItems as $item)
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ((array) $item['value'] as $listItem)
                            <div data-slot="{{ $item['slot']->key }}" class="rounded-[1.75rem] p-6 text-base font-medium shadow-lg transition hover:-translate-y-1 {{ $tilts[$loop->index % count($tilts)] }}" style="background-color: var(--site-surface);">{{ $listItem }}</div>
                        @endforeach
                    </div>
                @endforeach
            @elseif ($section['kind'] === 'cta')
                <div class="mx-auto flex max-w-2xl -rotate-1 flex-col items-center gap-4 rounded-[2rem] p-10 text-center shadow-2xl sm:p-14" style="background-color: var(--site-primary);">
                    @foreach ($textItems as $item)
                        @if ($item['slot']->slot_type === 'text')<h2 data-slot="{{ $item['slot']->key }}" class="text-3xl font-bold" style="color: var(--site-background);">{{ $item['value'] }}</h2>@else<p data-slot="{{ $item['slot']->key }}" style="color: color-mix(in srgb, var(--site-background) 80%, transparent);">{{ $item['value'] }}</p>@endif
                    @endforeach
                    @foreach ($linkItems as $item)
                        <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block rounded-full px-8 py-3.5 text-base font-bold shadow-md transition hover:opacity-90" style="background-color: var(--site-background); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                    @endforeach
                </div>
            @else
                <div class="mx-auto flex max-w-2xl flex-col gap-4 rounded-[1.75rem] p-8 text-center shadow-md" style="background-color: var(--site-surface);">
                    @foreach ($section['items'] as $item)
                        @if ($item['slot']->slot_type === 'text')<h2 data-slot="{{ $item['slot']->key }}" class="text-2xl font-bold">{{ $item['value'] }}</h2>
                        @elseif ($item['slot']->slot_type === 'link')<a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mx-auto inline-block rounded-full px-7 py-3 text-sm font-bold" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                        @else<p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    @include('site.partials.footer')
@endif
