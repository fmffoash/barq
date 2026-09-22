{{--
    "نيون" — طابع مضيء/تقني: عناوين وحدود بتوهّج (text-shadow/box-shadow) باللون الأساسي فوق
    خلفية داكنة، بدل الظلال العادية. مناسب لأي نشاط تقني/ترفيهي/رياضي بيحب حس "حيوية".
--}}
@php
    $navSections = $sections->skip(1);
    $navLabel = fn (string $key) => match ($key) {
        'hero' => 'الرئيسية', 'about' => 'من نحن', 'services', 'menu' => 'خدماتنا',
        'gallery' => 'معرض الصور', 'testimonials' => 'آراء العملاء', 'pricing' => 'الأسعار',
        'faq' => 'الأسئلة الشائعة', 'contact', 'cta' => 'تواصل معنا',
        default => \Illuminate\Support\Str::of($key)->replace(['_', '-'], ' ')->trim()->title()->toString(),
    };
@endphp

@if ($navSections->isNotEmpty())
    <nav class="flex items-center justify-between gap-4 border-b px-6 py-5 sm:px-10" style="border-color: color-mix(in srgb, var(--site-primary) 30%, transparent);">
        <a href="#{{ $sections->first()['key'] }}" class="text-lg font-extrabold" style="color: var(--site-primary); text-shadow: 0 0 12px color-mix(in srgb, var(--site-primary) 70%, transparent);">{{ $project->name }}</a>
        <ul class="hidden flex-wrap items-center gap-6 text-sm font-medium sm:flex">
            @foreach ($navSections as $s)
                <li><a href="#{{ $s['key'] }}" class="transition hover:opacity-70">{{ $navLabel($s['key']) }}</a></li>
            @endforeach
        </ul>
    </nav>
@endif

@forelse ($sections as $section)
    @php
        $textItems = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
        $listItems = $section['items']->where('slot.slot_type', 'list');
        $imageItems = $section['items']->where('slot.slot_type', 'image');
        $linkItems = $section['items']->where('slot.slot_type', 'link');
        $isHeroOrCta = in_array($section['kind'], ['hero', 'cta']);

        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    @endphp

    <section id="{{ $section['key'] }}" class="px-4 py-14 sm:px-8">
        <div class="mx-auto max-w-5xl {{ $isHeroOrCta ? 'flex flex-col items-center gap-5 text-center' : 'flex flex-col gap-6' }}">
            @if ($section['kind'] === 'hero')
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-extrabold sm:text-6xl" style="color: var(--site-text); text-shadow: 0 0 18px color-mix(in srgb, var(--site-primary) 65%, transparent), 0 0 40px color-mix(in srgb, var(--site-primary) 35%, transparent);">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a
                        href="{{ $item['value'] }}" target="_blank" rel="noopener"
                        class="mt-2 inline-block rounded-full border-2 px-8 py-3.5 text-base font-bold transition hover:opacity-90"
                        style="border-color: var(--site-primary); color: var(--site-primary); box-shadow: 0 0 20px color-mix(in srgb, var(--site-primary) 45%, transparent);"
                    >{{ $item['slot']->label() }}</a>
                @endforeach
                @if ($imageItems->isNotEmpty())
                    <img src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="mt-4 aspect-video w-full rounded-2xl border-2 object-cover" style="border-color: var(--site-primary); box-shadow: 0 0 30px color-mix(in srgb, var(--site-primary) 35%, transparent);" loading="lazy">
                @endif
            @elseif ($section['kind'] === 'gallery')
                @if ($textItems->isNotEmpty())
                    <div class="text-center">
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
                        <img src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="aspect-square w-full rounded-xl border object-cover" style="border-color: color-mix(in srgb, var(--site-primary) 45%, transparent); box-shadow: 0 0 16px color-mix(in srgb, var(--site-primary) 25%, transparent);" loading="lazy">
                    @endforeach
                </div>
            @elseif ($section['kind'] === 'list')
                @if ($textItems->isNotEmpty())
                    <div class="text-center">
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
                            <li data-slot="{{ $item['slot']->key }}" class="flex items-center gap-3 rounded-xl border px-5 py-4 text-base" style="border-color: color-mix(in srgb, var(--site-primary) 30%, transparent);">
                                <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: var(--site-primary); box-shadow: 0 0 10px var(--site-primary);"></span>
                                <span>{{ $listItem }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            @elseif ($section['kind'] === 'cta')
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold" style="text-shadow: 0 0 16px color-mix(in srgb, var(--site-primary) 55%, transparent);">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block rounded-full border-2 px-8 py-3.5 text-base font-bold transition hover:opacity-90" style="border-color: var(--site-primary); color: var(--site-primary); box-shadow: 0 0 20px color-mix(in srgb, var(--site-primary) 45%, transparent);">{{ $item['slot']->label() }}</a>
                @endforeach
            @else
                <div class="mx-auto flex max-w-xl flex-col gap-4 text-center">
                    @foreach ($section['items'] as $item)
                        @if ($heading && $item['slot']->key === $heading['slot']->key)<h2 data-slot="{{ $item['slot']->key }}" class="text-2xl font-bold">{!! $item['value'] !!}</h2>
                        @elseif ($item['slot']->slot_type === 'link')<a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mx-auto inline-block rounded-full border-2 px-7 py-3 text-sm font-bold" style="border-color: var(--site-primary); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
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
    <footer class="border-t px-6 py-8 text-center text-sm" style="border-color: color-mix(in srgb, var(--site-primary) 30%, transparent); color: var(--site-muted);">
        © {{ now()->year }} {{ $project->name }}
    </footer>
@endif
