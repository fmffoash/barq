{{--
    "تايم لاين" — أقسام القوايم (list) بترندر كخط زمني رأسي بخطوات مرقّمة متبادلة يمين/شمال،
    مناسب لأي نشاط بيحب يعرض "خطوات" أو "مراحل" (رحلة عميل، خطوات خدمة...).
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

    <section id="{{ $section['key'] }}" class="px-4 py-14 sm:px-8">
        <div class="mx-auto max-w-4xl">
            @if ($section['kind'] === 'hero')
                <div class="flex flex-col items-center gap-5 rounded-[2rem] p-10 text-center shadow-md sm:p-14" style="background-color: var(--site-surface);">
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-extrabold sm:text-5xl">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-1 inline-block rounded-full px-8 py-3.5 text-base font-bold shadow-md transition hover:opacity-90" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                    @endforeach
                    @if ($imageItems->isNotEmpty())
                        <div class="mt-3 aspect-video w-full overflow-hidden rounded-[1.5rem] shadow-lg">
                            <img data-slot="{{ $imageItems->first()['slot']->key }}" src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endif
                </div>
            @elseif ($section['kind'] === 'list')
                @if ($textItems->isNotEmpty())
                    <div class="mb-10 text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif
                @foreach ($listItems as $item)
                    <ol class="relative flex flex-col gap-10 border-r-2 pr-8 sm:mr-4" style="border-color: color-mix(in srgb, var(--site-primary) 40%, transparent);">
                        @foreach ((array) $item['value'] as $i => $listItem)
                            <li data-slot="{{ $item['slot']->key }}" class="relative">
                                <span class="absolute -right-[2.55rem] flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold shadow-md" style="background-color: var(--site-primary); color: var(--site-background);">{{ $i + 1 }}</span>
                                <p class="rounded-2xl p-5 text-base leading-relaxed shadow-sm" style="background-color: var(--site-surface);">{{ $listItem }}</p>
                            </li>
                        @endforeach
                    </ol>
                @endforeach
            @elseif ($section['kind'] === 'gallery')
                @if ($textItems->isNotEmpty())
                    <div class="mb-6 text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif
                <div class="flex gap-4 overflow-x-auto pb-2">
                    @foreach ($imageItems as $item)
                        <div class="aspect-square h-56 shrink-0 overflow-hidden rounded-[1.5rem] shadow-md">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            @elseif ($section['kind'] === 'cta')
                <div class="flex flex-col items-center gap-4 rounded-[2rem] p-10 text-center shadow-lg sm:p-14" style="background-color: var(--site-primary);">
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-extrabold" style="color: var(--site-background);">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" style="color: color-mix(in srgb, var(--site-background) 80%, transparent);">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block rounded-full px-8 py-3.5 text-base font-bold shadow-md transition hover:opacity-90" style="background-color: var(--site-background); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                    @endforeach
                </div>
            @else
                <div class="mx-auto flex max-w-2xl flex-col gap-4 rounded-[1.75rem] p-8 text-center shadow-sm" style="background-color: var(--site-surface);">
                    @foreach ($section['items'] as $item)
                        @if ($heading && $item['slot']->key === $heading['slot']->key)<h2 data-slot="{{ $item['slot']->key }}" class="text-2xl font-bold">{!! $item['value'] !!}</h2>
                        @elseif ($item['slot']->slot_type === 'link')<a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mx-auto inline-block rounded-full px-7 py-3 text-sm font-bold" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                        @else<p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{!! $item['value'] !!}</p>@endif
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
