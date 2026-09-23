{{--
    "دوتون" — بوستر جريء: الصور بتترندر بتأثير ثنائي اللون (grayscale + طبقة لونية فوقها
    بـ mix-blend-mode) بدل الصورة العادية، مع طباعة كبيرة وكتل لونية صلبة — طابع إعلاني قوي.
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
    <nav class="flex items-center justify-between gap-4 px-6 py-6 sm:px-12" style="background-color: var(--site-primary);">
        <a href="#{{ $sections->first()['key'] }}" class="text-xl font-black" style="color: var(--site-background);">{{ $project->name }}</a>
        <ul class="hidden flex-wrap items-center gap-6 text-sm font-bold sm:flex" style="color: var(--site-background);">
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
        $heroImage = $imageItems->first();

        $heading = $textItems->firstWhere('slot.slot_type', 'text');
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    @endphp

    @if ($section['kind'] === 'hero')
        <section id="{{ $section['key'] }}" class="relative overflow-hidden px-6 py-24 text-center sm:px-10">
            @if ($heroImage)
                <div class="absolute inset-0">
                    <img data-slot="{{ $heroImage['slot']->key }}" src="{{ $heroImage['value'] }}" alt="" class="h-full w-full object-cover grayscale contrast-125" aria-hidden="true">
                    {{-- pointer-events-none إجباري (المرحلة 2) — من غيرها الطبقتين الزخرفيتين
                    دول بيقفوا فوق الصورة في الترتيب ويمنعوا أي دوس عليها خالص. --}}
                    <div class="pointer-events-none absolute inset-0 mix-blend-multiply" style="background-color: var(--site-primary);"></div>
                    <div class="pointer-events-none absolute inset-0" style="background-color: color-mix(in srgb, var(--site-background) 35%, transparent);"></div>
                </div>
            @else
                <div class="absolute inset-0" style="background-color: var(--site-primary);"></div>
            @endif
            {{-- pointer-events-none على الحاوية + pointer-events-auto على كل عنصر قابل
            للتعديل فعلياً (المرحلة 2) — من غيرها مساحات الفراغ/الـgap جوّه الحاوية دي (اللي
            بتظهر فيها صورة الهيرو تحتها) بتمنع الدوس على الصورة نفسها لأن الحاوية طالعة
            فوقها بـz-10. --}}
            <div class="relative z-10 mx-auto flex max-w-2xl flex-col items-center gap-5" style="pointer-events: none;">
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-5xl font-black uppercase leading-[0.95] sm:text-7xl" style="color: var(--site-background); pointer-events: auto;">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg font-medium" style="color: color-mix(in srgb, var(--site-background) 90%, transparent); pointer-events: auto;">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a data-slot="{{ $item['slot']->key }}" href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block px-10 py-4 text-base font-black transition hover:opacity-90" style="background-color: var(--site-background); color: var(--site-primary); pointer-events: auto;">{{ $item['slot']->label() }}</a>
                @endforeach
            </div>
        </section>
    @elseif ($section['kind'] === 'gallery')
        <section id="{{ $section['key'] }}" class="relative px-6 py-16 sm:px-10">
            <div class="mx-auto max-w-5xl">
                @if ($textItems->isNotEmpty())
                    <div class="mb-8 text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-black uppercase">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif
                <div class="grid gap-1 sm:grid-cols-3">
                    @foreach ($imageItems as $item)
                        <div class="relative aspect-square overflow-hidden">
                            <img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="h-full w-full object-cover grayscale contrast-125" loading="lazy">
                            <div class="pointer-events-none absolute inset-0 mix-blend-multiply transition group-hover:opacity-0" style="background-color: var(--site-primary); opacity: .55;"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @elseif ($section['kind'] === 'list')
        <section id="{{ $section['key'] }}" class="relative px-6 py-16 text-center sm:px-10" style="background-color: var(--site-surface);">
            <div class="mx-auto max-w-4xl">
                @if ($textItems->isNotEmpty())
                    <div class="mb-10">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-black uppercase">{!! $heading['value'] !!}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{!! $item['value'] !!}</p>
                        @endforeach
                    </div>
                @endif
                @foreach ($listItems as $item)
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ((array) $item['value'] as $listItem)
                            <div data-slot="{{ $item['slot']->key }}" class="p-6 text-xl font-black" style="background-color: var(--site-primary); color: var(--site-background);">{{ $listItem }}</div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
    @elseif ($section['kind'] === 'cta')
        <section id="{{ $section['key'] }}" class="relative px-6 py-20 text-center sm:px-10" style="background-color: var(--site-primary);">
            <div class="mx-auto flex max-w-2xl flex-col items-center gap-4">
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-black uppercase" style="color: var(--site-background);">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" style="color: color-mix(in srgb, var(--site-background) 85%, transparent);">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a data-slot="{{ $item['slot']->key }}" href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block px-10 py-4 text-base font-black transition hover:opacity-90" style="background-color: var(--site-background); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                @endforeach
            </div>
        </section>
    @else
        <section id="{{ $section['key'] }}" class="relative px-6 py-16 text-center sm:px-10">
            <div class="mx-auto flex max-w-2xl flex-col gap-4">
                @foreach ($section['items'] as $item)
                    @if ($heading && $item['slot']->key === $heading['slot']->key)<h2 data-slot="{{ $item['slot']->key }}" class="text-3xl font-black uppercase">{!! $item['value'] !!}</h2>
                    @elseif ($item['slot']->slot_type === 'link')<a data-slot="{{ $item['slot']->key }}" href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mx-auto inline-block px-8 py-3 text-base font-black" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
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
    <footer class="px-6 py-8 text-center text-sm font-bold" style="background-color: var(--site-primary); color: var(--site-background);">
        © {{ now()->year }} {{ $project->name }}
    </footer>
@endif
