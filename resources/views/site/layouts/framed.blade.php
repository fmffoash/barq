{{--
    "فريمد" — طابع بروشور/دعوة رسمية: كل قسم جوّه إطار رفيع بزوايا مميّزة (corner accents)،
    عناوين بتباعد حروف وسطر فاصل صغير، مناسب لأي نشاط رسمي/فاخر (محاماة، عقارات، مناسبات).
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
    <nav class="flex items-center justify-between gap-4 px-6 py-8 sm:px-14">
        <a href="#{{ $sections->first()['key'] }}" class="text-lg font-bold tracking-widest">{{ $project->name }}</a>
        <ul class="hidden flex-wrap items-center gap-7 text-xs font-semibold uppercase tracking-widest sm:flex">
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

        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    @endphp

    <section id="{{ $section['key'] }}" class="px-6 py-10 sm:px-14">
        <div class="relative mx-auto max-w-4xl border p-8 sm:p-14" style="border-color: color-mix(in srgb, var(--site-primary) 45%, transparent);">
            <span class="absolute -right-px -top-px h-6 w-6 border-r-2 border-t-2" style="border-color: var(--site-primary);"></span>
            <span class="absolute -left-px -top-px h-6 w-6 border-l-2 border-t-2" style="border-color: var(--site-primary);"></span>
            <span class="absolute -bottom-px -right-px h-6 w-6 border-b-2 border-r-2" style="border-color: var(--site-primary);"></span>
            <span class="absolute -bottom-px -left-px h-6 w-6 border-b-2 border-l-2" style="border-color: var(--site-primary);"></span>

            <div class="flex flex-col gap-5 {{ in_array($section['kind'], ['hero', 'cta']) ? 'items-center text-center' : '' }}">
                @if ($section['kind'] === 'hero')
                    @if ($heading)
                        <h1 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold tracking-wide sm:text-5xl">{!! $heading['value'] !!}</h1>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-base" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    <span class="h-px w-20" style="background-color: var(--site-primary);"></span>
                    @foreach ($linkItems as $item)
                        <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="border px-8 py-3 text-xs font-bold uppercase tracking-widest transition hover:opacity-70" style="border-color: var(--site-primary); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                    @endforeach
                    @if ($imageItems->isNotEmpty())
                        <img src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="mt-2 aspect-video w-full object-cover" loading="lazy">
                    @endif
                @elseif ($section['kind'] === 'gallery')
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-2xl font-bold tracking-wide">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ($imageItems as $item)
                            <img src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="aspect-square w-full object-cover" loading="lazy">
                        @endforeach
                    </div>
                @elseif ($section['kind'] === 'list')
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-2xl font-bold tracking-wide">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($listItems as $item)
                        <ul class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                            @foreach ((array) $item['value'] as $listItem)
                                <li data-slot="{{ $item['slot']->key }}" class="flex items-center gap-3 border-b pb-3 text-base" style="border-color: color-mix(in srgb, var(--site-primary) 25%, transparent);">
                                    <span style="color: var(--site-primary);">✦</span>
                                    <span>{{ $listItem }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                @elseif ($section['kind'] === 'cta')
                    @if ($heading)
                        <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold tracking-wide">{!! $heading['value'] !!}</h2>
                    @endif
                    @foreach ($supportingItems as $item)
                        <p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                    @endforeach
                    @foreach ($linkItems as $item)
                        <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="border px-8 py-3 text-xs font-bold uppercase tracking-widest transition hover:opacity-70" style="border-color: var(--site-primary); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                    @endforeach
                @else
                    @foreach ($section['items'] as $item)
                        @if ($heading && $item['slot']->key === $heading['slot']->key)<h2 data-slot="{{ $item['slot']->key }}" class="text-2xl font-bold tracking-wide">{!! $item['value'] !!}</h2>
                        @elseif ($item['slot']->slot_type === 'link')<a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mx-auto border px-8 py-3 text-xs font-bold uppercase tracking-widest" style="border-color: var(--site-primary); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                        @else<p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{!! $item['value'] !!}</p>@endif
                    @endforeach
                @endif
            </div>
        </div>
    </section>
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    <footer class="px-6 py-10 text-center text-xs uppercase tracking-widest" style="color: var(--site-muted);">
        © {{ now()->year }} {{ $project->name }}
    </footer>
@endif
