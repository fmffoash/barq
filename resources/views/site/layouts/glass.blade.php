{{--
    "جلاس" — خلفية بقع لونية متدرجة ثابتة (fixed) وكروت زجاجية شفافة (backdrop-blur) طايفة
    فوقها — طابع عصري تقني. كل الكروت شفافة بحدود خفيفة بدل خلفية صلدة.
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

<div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full opacity-40 blur-3xl" style="background-color: var(--site-primary);"></div>
    <div class="absolute -bottom-32 -left-32 h-96 w-96 rounded-full opacity-25 blur-3xl" style="background-color: var(--site-primary);"></div>
</div>

@if ($navSections->isNotEmpty())
    <div class="sticky top-4 z-20 px-4 sm:px-8">
        <nav class="mx-auto flex max-w-4xl items-center justify-between gap-4 rounded-full border px-5 py-3 shadow-lg backdrop-blur-xl" style="background-color: color-mix(in srgb, var(--site-surface) 40%, transparent); border-color: color-mix(in srgb, var(--site-text) 15%, transparent);">
            <a href="#{{ $sections->first()['key'] }}" class="text-base font-extrabold" style="color: var(--site-primary);">{{ $project->name }}</a>
            <ul class="hidden flex-wrap items-center gap-6 text-sm font-medium sm:flex">
                @foreach ($navSections as $s)
                    <li><a href="#{{ $s['key'] }}" class="transition hover:opacity-70">{{ $navLabel($s['key']) }}</a></li>
                @endforeach
            </ul>
        </nav>
    </div>
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

    <section id="{{ $section['key'] }}" class="px-4 py-10 sm:px-8">
        <div
            class="mx-auto max-w-5xl rounded-[2rem] border p-8 shadow-xl backdrop-blur-xl sm:p-12 {{ $isHeroOrCta ? 'flex flex-col items-center gap-5 text-center' : 'flex flex-col gap-6' }}"
            style="background-color: color-mix(in srgb, var(--site-surface) 45%, transparent); border-color: color-mix(in srgb, var(--site-text) 15%, transparent);"
        >
            @if ($section['kind'] === 'hero')
                @if ($heading)
                    <h1 data-slot="{{ $heading['slot']->key }}" class="text-4xl font-extrabold sm:text-6xl">{{ $heading['value'] }}</h1>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" class="max-w-xl text-lg" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block rounded-full px-8 py-3.5 text-base font-bold shadow-md transition hover:opacity-90" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                @endforeach
                @if ($imageItems->isNotEmpty())
                    <img src="{{ $imageItems->first()['value'] }}" alt="{{ $imageItems->first()['slot']->label() }}" class="mt-4 aspect-video w-full rounded-[1.5rem] object-cover shadow-lg" loading="lazy">
                @endif
            @elseif ($section['kind'] === 'gallery')
                @if ($textItems->isNotEmpty())
                    <div class="text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{{ $heading['value'] }}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                        @endforeach
                    </div>
                @endif
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($imageItems as $item)
                        <img src="{{ $item['value'] }}" alt="{{ $item['slot']->label() }}" class="aspect-square w-full rounded-[1.25rem] object-cover shadow-md" loading="lazy">
                    @endforeach
                </div>
            @elseif ($section['kind'] === 'list')
                @if ($textItems->isNotEmpty())
                    <div class="text-center">
                        @if ($heading)
                            <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{{ $heading['value'] }}</h2>
                        @endif
                        @foreach ($supportingItems as $item)
                            <p data-slot="{{ $item['slot']->key }}" class="mt-2" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                        @endforeach
                    </div>
                @endif
                @foreach ($listItems as $item)
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ((array) $item['value'] as $listItem)
                            <div data-slot="{{ $item['slot']->key }}" class="rounded-2xl border p-5 text-base shadow-sm" style="background-color: color-mix(in srgb, var(--site-background) 30%, transparent); border-color: color-mix(in srgb, var(--site-text) 12%, transparent);">{{ $listItem }}</div>
                        @endforeach
                    </div>
                @endforeach
            @elseif ($section['kind'] === 'cta')
                @if ($heading)
                    <h2 data-slot="{{ $heading['slot']->key }}" class="text-3xl font-bold">{{ $heading['value'] }}</h2>
                @endif
                @foreach ($supportingItems as $item)
                    <p data-slot="{{ $item['slot']->key }}" style="color: var(--site-muted);">{{ $item['value'] }}</p>
                @endforeach
                @foreach ($linkItems as $item)
                    <a href="{{ $item['value'] }}" target="_blank" rel="noopener" class="mt-2 inline-block rounded-full px-8 py-3.5 text-base font-bold shadow-md transition hover:opacity-90" style="background-color: var(--site-primary); color: var(--site-background);">{{ $item['slot']->label() }}</a>
                @endforeach
            @else
                <div class="mx-auto flex max-w-xl flex-col gap-4 text-center">
                    @foreach ($section['items'] as $item)
                        @if ($heading && $item['slot']->key === $heading['slot']->key)<h2 data-slot="{{ $item['slot']->key }}" class="text-2xl font-bold">{{ $item['value'] }}</h2>
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
    <footer class="px-6 py-8 text-center text-sm" style="color: var(--site-muted);">
        © {{ now()->year }} {{ $project->name }}
    </footer>
@endif
