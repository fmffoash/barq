{{--
    "سبليت" — هيرو نص الشاشة كاملة مقسوم نص نص (نص/صورة)، وأقسام edge-to-edge حادة الحواف
    (بدون تدوير أو ظلال) بخط فاصل واضح بينها بدل الكروت — تباين قوي ومباشر.
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
    <nav class="flex items-center justify-between gap-4 border-b px-6 py-5 sm:px-10" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
        <a href="#{{ $sections->first()['key'] }}" class="text-lg font-extrabold" style="color: var(--site-primary);">{{ $project->name }}</a>
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
        $imageStart = $loop->index % 2 === 0;
    @endphp

    @if ($section['kind'] === 'hero')
        <section id="{{ $section['key'] }}" class="grid min-h-[75vh] items-stretch {{ $imageItems->isNotEmpty() ? 'lg:grid-cols-2' : '' }}">
            <div class="flex flex-col items-start justify-center gap-5 px-6 py-16 text-right sm:px-14 {{ $imageItems->isNotEmpty() ? 'order-2 lg:order-1' : 'mx-auto max-w-2xl items-center text-center' }}">
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')
                        <h1 class="text-4xl font-extrabold leading-tight sm:text-6xl">{{ $item['value'] }}</h1>
                    @else
                        <p class="text-lg leading-loose" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                    @endif
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block px-8 py-3.5 text-base font-bold transition hover:opacity-90" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                @endforeach
            </div>
            @if ($imageItems->isNotEmpty())
                <div class="order-1 min-h-[40vh] lg:order-2">
                    <img src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="h-full w-full object-cover" loading="lazy">
                </div>
            @endif
        </section>
    @elseif ($section['kind'] === 'gallery')
        <section id="{{ $section['key'] }}" class="grid border-t {{ $imageItems->isNotEmpty() ? 'lg:grid-cols-2' : '' }}" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
            <div class="flex flex-col justify-center gap-4 px-6 py-14 text-right sm:px-14 {{ $imageStart ? 'order-2 lg:order-1' : 'order-2' }}">
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')
                        <h2 class="text-3xl font-bold">{{ $item['value'] }}</h2>
                    @else
                        <p class="leading-loose" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                    @endif
                @endforeach
            </div>
            @if ($imageItems->isNotEmpty())
                <div class="grid min-h-[24rem] {{ $imageItems->count() > 1 ? 'grid-cols-2' : '' }} {{ $imageStart ? 'order-1 lg:order-2' : 'order-1' }}">
                    @foreach ($imageItems as $item)
                        <img src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="h-full w-full object-cover" loading="lazy">
                    @endforeach
                </div>
            @endif
        </section>
    @elseif ($section['kind'] === 'list')
        <section id="{{ $section['key'] }}" class="border-t px-6 py-14 sm:px-14" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
            <div class="mx-auto max-w-5xl">
                @if ($textItems->isNotEmpty())
                    <div class="mb-8 text-center">
                        @foreach ($textItems as $item)
                            @if ($item['slot']->slot_type === 'text')<h2 class="text-3xl font-bold">{{ $item['value'] }}</h2>@else<p class="mt-2" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                        @endforeach
                    </div>
                @endif
                @foreach ($listItems as $item)
                    <ul class="divide-y" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
                        @foreach ((array) $item['value'] as $i => $listItem)
                            <li class="flex items-center gap-4 py-4 text-base" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
                                <span class="text-2xl font-extrabold" style="color: var(--site-primary);">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span>{{ $listItem }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </section>
    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="border-t px-6 py-16 text-center sm:px-14" style="background-color: var(--site-primary); border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
            <div class="mx-auto flex max-w-2xl flex-col items-center gap-4">
                @foreach ($textItems as $item)
                    @if ($item['slot']->slot_type === 'text')<h2 class="text-3xl font-extrabold" style="color: var(--site-background);">{{ $item['value'] }}</h2>@else<p style="color: color-mix(in srgb, var(--site-background) 80%, transparent);">{{ $item['value'] }}</p>@endif
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block px-8 py-3.5 text-base font-bold transition hover:opacity-90" style="background-color: var(--site-background); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                @endforeach
            </div>
        </section>
    @else
        <section id="{{ $section['key'] }}" class="border-t px-6 py-14 text-center sm:px-14" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent);">
            <div class="mx-auto flex max-w-2xl flex-col gap-4">
                @foreach ($section['items'] as $item)
                    @if ($item['slot']->slot_type === 'text')<h2 class="text-3xl font-bold">{{ $item['value'] }}</h2>
                    @elseif ($item['slot']->slot_type === 'link')<a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mx-auto inline-block px-8 py-3 text-base font-semibold transition hover:opacity-90" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                    @else<p style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
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
    <footer class="border-t px-6 py-8 text-center text-sm" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent); color: var(--site-muted);">
        © {{ now()->year }} {{ $project->name }}
    </footer>
@endif
