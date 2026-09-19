{{--
    "مجلة" — طابع تحريري: عناوين كبيرة بتباعد حروف، خط فاصل تحت العنوان، تاج/label صغير فوق كل
    قسم، واقتباسات كبيرة لعلامات التنصيص بدل كروت الآراء العادية. الألوان مستخدمة بحرص (أساساً
    نص/خلفية، اللون الأساسي كخط أو تفصيلة صغيرة بس).
--}}
@php
    $navSections = $sections->skip(1);
    $navLabel = fn (string $key) => match ($key) {
        'hero' => 'الرئيسية', 'about' => 'من نحن', 'services', 'menu' => 'خدماتنا',
        'gallery' => 'معرض الصور', 'testimonials' => 'آراء العملاء', 'pricing' => 'الأسعار',
        'faq' => 'الأسئلة الشائعة', 'contact', 'cta' => 'تواصل معنا',
        default => \Illuminate\Support\Str::of($key)->replace(['_', '-'], ' ')->trim()->title()->toString(),
    };
    $tag = fn (string $key) => match ($key) {
        'about' => 'تعرّف علينا', 'services', 'menu' => 'ما نقدمه', 'gallery' => 'لقطات',
        'testimonials' => 'شهادات', 'contact', 'cta' => 'ابدأ الآن', default => 'قسم',
    };
@endphp

@if ($navSections->isNotEmpty())
    <nav class="flex items-center justify-between gap-4 px-6 py-6 sm:px-14">
        <a href="#{{ $sections->first()['key'] }}" class="text-xl font-extrabold uppercase tracking-widest">{{ $project->name }}</a>
        <ul class="hidden flex-wrap items-center gap-6 text-xs font-semibold uppercase tracking-wide sm:flex">
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
    @endphp

    <section id="{{ $section['key'] }}" class="px-6 py-14 sm:px-14">
        <div class="mx-auto max-w-4xl">
            @unless ($section['kind'] === 'hero')
                <span class="mb-3 block text-xs font-bold uppercase tracking-[0.2em]" style="color: var(--site-primary);">{{ $tag($section['key']) }}</span>
            @endunless

            @if ($section['kind'] === 'hero')
                <div class="flex flex-col items-center gap-6 py-10 text-center">
                    @foreach ($textItems as $item)
                        @if ($item['slot']->slot_type === 'text')
                            <h1 class="text-5xl font-extrabold uppercase leading-[1.05] tracking-tight sm:text-7xl">{{ $item['value'] }}</h1>
                        @else
                            <p class="max-w-xl text-lg" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                        @endif
                    @endforeach
                    <span class="h-1 w-24 rounded-full" style="background-color: var(--site-primary);"></span>
                    @foreach ($linkItems as $item)
                        <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="text-sm font-bold uppercase tracking-widest underline decoration-2 underline-offset-4" style="color: var(--site-primary);">{{ $item['slot']->label() }} ←</a>
                    @endforeach
                    @if ($imageItems->isNotEmpty())
                        <img src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="mt-4 aspect-[16/9] w-full object-cover" loading="lazy">
                    @endif
                </div>
            @elseif ($section['kind'] === 'gallery')
                <h2 class="mb-6 text-3xl font-extrabold uppercase tracking-tight">
                    @foreach ($textItems->where('slot.slot_type', 'text') as $item){{ $item['value'] }}@endforeach
                </h2>
                @foreach ($textItems->where('slot.slot_type', 'textarea') as $item)
                    <p class="mb-6 max-w-2xl" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                @endforeach
                <div class="grid gap-1 sm:grid-cols-3">
                    @foreach ($imageItems as $item)
                        <img src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="aspect-square w-full object-cover grayscale transition hover:grayscale-0" loading="lazy">
                    @endforeach
                </div>
            @elseif ($section['kind'] === 'list')
                <h2 class="mb-8 text-3xl font-extrabold uppercase tracking-tight">
                    @foreach ($textItems->where('slot.slot_type', 'text') as $item){{ $item['value'] }}@endforeach
                </h2>
                @foreach ($listItems as $item)
                    <div class="grid gap-x-10 gap-y-6 sm:grid-cols-2">
                        @foreach ((array) $item['value'] as $i => $listItem)
                            <div class="border-b pb-4" style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent);">
                                <span class="text-xs font-bold" style="color: var(--site-primary);">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} —</span>
                                <span class="text-lg">{{ $listItem }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @elseif ($section['kind'] === 'cta')
                <div class="border-y py-12 text-center" style="border-color: var(--site-primary);">
                    @foreach ($textItems as $item)
                        @if ($item['slot']->slot_type === 'text')<h2 class="text-4xl font-extrabold uppercase tracking-tight">{{ $item['value'] }}</h2>@else<p class="mt-3" style="color: var(--site-muted);">{{ $item['value'] }}</p>@endif
                    @endforeach
                    @foreach ($linkItems as $item)
                        <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-5 inline-block px-10 py-4 text-sm font-bold uppercase tracking-widest transition hover:opacity-90" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                    @endforeach
                </div>
            @else
                <div class="text-center italic">
                    @foreach ($textItems as $item)
                        @if ($item['slot']->slot_type === 'text')
                            <p class="text-4xl leading-snug" style="color: var(--site-primary);">"{{ $item['value'] }}"</p>
                        @else
                            <p class="mt-4 not-italic" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                        @endif
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
    <footer class="border-t px-6 py-8 text-center text-xs uppercase tracking-widest" style="border-color: color-mix(in srgb, var(--site-text) 15%, transparent); color: var(--site-muted);">
        © {{ now()->year }} {{ $project->name }}
    </footer>
@endif
