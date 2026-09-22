{{--
    "بولد" — طباعة ضخمة وتباين عالي: أقسام بألوان صلبة full-bleed تتبادل (اللون الأساسي/
    الخلفية)، بدون تدوير أو ظلال خالص — طابع مباشر وصريح بدل الأناقة الهادية.
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
    <nav class="flex items-center justify-between gap-4 px-6 py-6 sm:px-12">
        <a href="#{{ $sections->first()['key'] }}" class="text-2xl font-black">{{ $project->name }}</a>
        <ul class="hidden flex-wrap items-center gap-6 text-sm font-bold sm:flex">
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
        $onPrimary = in_array($section['kind'], ['hero', 'cta']) || $loop->index % 2 === 1;
        $titleItem = $textItems->where('slot.slot_type', 'text')->first();

        // لو القسم فيه أكتر من خانة "text"، أول خانة بس بتاخد شكل العنوان الكبير، والباقي
        // بيترندر كنص مساند أصغر بدل عنوانين ضخمين فوق بعض.
        $heading = $titleItem;
        $supportingItems = $textItems->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
    @endphp

    <section
        id="{{ $section['key'] }}"
        class="px-6 py-16 sm:px-12 {{ $section['kind'] === 'hero' ? 'sm:py-28' : '' }}"
        style="background-color: {{ $onPrimary ? 'var(--site-primary)' : 'var(--site-background)' }}; color: {{ $onPrimary ? 'var(--site-background)' : 'var(--site-text)' }};"
    >
        <div class="mx-auto flex max-w-5xl flex-col gap-6 {{ $section['kind'] === 'hero' || $section['kind'] === 'cta' ? 'items-center text-center' : '' }}">
            @if ($section['kind'] === 'hero')
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-5xl font-black leading-[0.95] sm:text-8xl">{!! $heading['value'] !!}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-2xl text-xl font-medium opacity-80">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block px-10 py-4 text-lg font-black transition hover:opacity-80" style="background-color: var(--site-background); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                @endforeach
            @elseif ($section['kind'] === 'gallery')
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-black">{!! $heading['value'] !!}</h2>
                @endif
                <div class="grid w-full gap-2 sm:grid-cols-3">
                    @foreach ($imageItems as $item)
                        <img src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="aspect-square w-full object-cover" loading="lazy">
                    @endforeach
                </div>
            @elseif ($section['kind'] === 'list')
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-black">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($listItems as $item)
                    <div class="grid w-full gap-4 sm:grid-cols-2">
                        @foreach ((array) $item['value'] as $listItem)
                            <div data-slot="{{ $item['slot']->key }}" class="border-2 px-6 py-5 text-xl font-bold" style="border-color: currentColor;">{{ $listItem }}</div>
                        @endforeach
                    </div>
                @endforeach
            @elseif ($section['kind'] === 'cta')
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-5xl font-black">{!! $heading['value'] !!}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="text-lg opacity-80">{!! $item['value'] !!}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block px-10 py-4 text-lg font-black transition hover:opacity-80" style="background-color: var(--site-background); color: var(--site-primary);">{{ $item['slot']->label() }}</a>
                @endforeach
            @else
                @foreach ($section['items'] as $item)
                    @if ($heading && $item['slot']->key === $heading['slot']->key)<h2 data-slot="{{ $item['slot']->key }}" class="text-4xl font-black">{!! $item['value'] !!}</h2>
                    @elseif ($item['slot']->slot_type === 'link')<a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="inline-block px-8 py-3 text-base font-black" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                    @else<p data-slot="{{ $item['slot']->key }}" class="text-lg opacity-80">{!! $item['value'] !!}</p>@endif
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
    <footer class="px-6 py-8 text-center text-sm font-bold" style="color: var(--site-muted);">
        © {{ now()->year }} {{ $project->name }}
    </footer>
@endif
