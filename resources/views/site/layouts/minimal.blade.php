{{--
    "مينيمال" — أبسط تصميم في المكتبة عن قصد: مسافات واسعة، خطوط رفيعة بدل الظلال، بدون
    تدرجات ألوان (gradient)، واللون الأساسي مستخدم بس للأزرار والخط الفاصل تحت العنوان.
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
    <nav class="flex items-center justify-between gap-4 px-6 py-8 sm:px-16">
        <a href="#{{ $sections->first()['key'] }}" class="text-base font-bold">{{ $project->name }}</a>
        <ul class="hidden flex-wrap items-center gap-8 text-sm sm:flex">
            @foreach ($navSections as $s)
                <li><a href="#{{ $s['key'] }}" class="border-b border-transparent pb-0.5 transition hover:border-current">{{ $navLabel($s['key']) }}</a></li>
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
    @endphp

    <section id="{{ $section['key'] }}" class="px-6 py-16 sm:px-16">
        <div class="mx-auto flex max-w-3xl flex-col gap-6 {{ $section['kind'] === 'hero' || $section['kind'] === 'cta' ? 'items-center text-center' : '' }}">
            @if ($section['kind'] === 'hero')
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')
                        <h1 data-slot="{{ $item['slot']->key }}" class="text-4xl font-light leading-tight sm:text-6xl">{{ $item['value'] }}</h1>
                    @else
                        <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                    @endif
                @endforeach
                <span class="h-px w-16" style="background-color: var(--site-primary);"></span>
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="border px-8 py-3 text-sm font-medium transition hover:opacity-70" style="border-color: var(--site-primary); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                @endforeach
                @if ($imageItems->isNotEmpty())
                    <img src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="mt-6 aspect-video w-full object-cover" loading="lazy">
                @endif
            @elseif ($section['kind'] === 'gallery')
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')<h2 data-slot="{{ $item['slot']->key }}" class="text-2xl font-light">{{ $item['value'] }}</h2>@else<p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                @endforeach
                <div class="grid w-full gap-3 sm:grid-cols-3">
                    @foreach ($imageItems as $item)
                        <img src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="aspect-square w-full object-cover" loading="lazy">
                    @endforeach
                </div>
            @elseif ($section['kind'] === 'list')
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')<h2 data-slot="{{ $item['slot']->key }}" class="text-2xl font-light">{{ $item['value'] }}</h2>@else<p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                @endforeach
                @foreach ($listItems as $item)
                    <ul class="w-full divide-y" style="border-color: color-mix(in srgb, var(--site-text) 12%, transparent);">
                        @foreach ((array) $item['value'] as $listItem)
                            <li data-slot="{{ $item['slot']->key }}" class="flex items-center justify-between py-4 text-base">
                                <span>{{ $listItem }}</span>
                                <span style="color: var(--site-primary);">—</span>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            @elseif ($section['kind'] === 'cta')
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')<h2 data-slot="{{ $item['slot']->key }}" class="text-3xl font-light">{{ $item['value'] }}</h2>@else<p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="border px-8 py-3 text-sm font-medium transition hover:opacity-70" style="border-color: var(--site-primary); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                @endforeach
            @else
                @foreach ($section['items'] as $item)
                    @if ($item['slot']->slot_type === 'text')<h2 data-slot="{{ $item['slot']->key }}" class="text-2xl font-light">{{ $item['value'] }}</h2>
                    @elseif ($item['slot']->slot_type === 'link')<a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="border px-8 py-3 text-sm font-medium transition hover:opacity-70" style="border-color: var(--site-primary); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                    @else<p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                @endforeach
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
    <footer class="px-6 py-10 text-center text-sm" style="color: var(--site-muted);">
        © {{ now()->year }} {{ $project->name }}
    </footer>
@endif
