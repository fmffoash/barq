{{--
    "دياجونال" — قصّات مائلة (clip-path) بين قسم الهيرو/الـ cta والخلفية بدل الحواف المستقيمة
    العادية، بتدي إحساس حركة وديناميكية للصفحة.
--}}
@include('site.partials.nav')

@forelse ($sections as $section)
    @php
        $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
        $listItems = $section['items']->where('slot.slot_type', 'list');
        $imageItems = $section['items']->where('slot.slot_type', 'image');
        $linkItems = $section['items']->where('slot.slot_type', 'link');

        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    @endphp

    @if ($section['kind'] === 'hero')
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-6 pb-24 pt-14 text-center sm:px-10" style="background-color: var(--site-primary); clip-path: polygon(0 0, 100% 0, 100% 88%, 0 100%);">
            <div class="relative z-10 mx-auto flex max-w-2xl flex-col items-center gap-5">
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-extrabold sm:text-6xl" style="color: var(--site-background);">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg" style="color: color-mix(in srgb, var(--site-background) 85%, transparent);">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90" style="background-color: var(--site-background); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                @endforeach
            </div>
        </section>
        @if ($imageItems->isNotEmpty())
            <div class="-mt-16 px-6 sm:px-10">
                <div class="relative z-10 mx-auto aspect-[16/9] max-w-4xl w-full overflow-hidden rounded-[1.75rem] shadow-2xl">
                    <img data-slot="{{ $imageItems->first()['slot']->key }}" src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="h-full w-full object-cover" loading="lazy">
                </div>
            </div>
        @endif
    @elseif ($section['kind'] === 'gallery')
        <section id="{{ $section['key'] }}" class="px-6 py-16 sm:px-10">
            <div class="mx-auto max-w-5xl">
                @if ($textItems->isNotEmpty())
                    <div class="mb-8 text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($imageItems as $item)
                        <div class="aspect-square w-full overflow-hidden shadow-md">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="h-full w-full object-cover" style="clip-path: polygon(0 6%, 100% 0, 100% 94%, 0 100%);" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @elseif ($section['kind'] === 'list')
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-6 py-20 sm:px-10" style="background-color: var(--site-surface); clip-path: polygon(0 6%, 100% 0, 100% 100%, 0 94%);">
            <div class="mx-auto max-w-4xl">
                @if ($textItems->isNotEmpty())
                    <div class="mb-8 text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif
                @foreach ($listItems as $item)
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ((array) $item['value'] as $listItem)
                            <li data-slot="{{ $item['slot']->key }}" class="flex items-start gap-3 rounded-lg px-5 py-4 text-base shadow-sm" style="background-color: var(--site-background);">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0" style="background-color: var(--site-primary);"></span>
                                <span>{{ $listItem }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </section>
    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-6 py-20 text-center sm:px-10" style="background-color: var(--site-primary); clip-path: polygon(0 8%, 100% 0, 100% 100%, 0 92%);">
            <div class="mx-auto flex max-w-2xl flex-col items-center gap-4">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold" style="color: var(--site-background);">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" style="color: color-mix(in srgb, var(--site-background) 85%, transparent);">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block rounded-full px-8 py-3.5 text-base font-bold shadow-lg transition hover:opacity-90" style="background-color: var(--site-background); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                @endforeach
            </div>
        </section>
    @else
        <section id="{{ $section['key'] }}" class="px-6 py-16 text-center sm:px-10">
            <div class="mx-auto flex max-w-2xl flex-col gap-4">
                @foreach ($section['items'] as $item)
                    @if ($heading && $item['slot']->key === $heading['slot']->key)<h2 data-slot="{{ $item['slot']->key }}" class="text-3xl font-bold">{!! $item['value'] !!}</h2>
                    @elseif ($item['slot']->slot_type === 'link')<a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mx-auto inline-block rounded-full px-8 py-3 text-base font-semibold" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                    @else<p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{!! $item['value'] !!}</p>@endif
                @endforeach
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
