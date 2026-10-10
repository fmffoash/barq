{{--
    كارت رأي عميل مشترك بين التصميمات. كل الإعدادات في مصفوفة واحدة $card (عشان مفيش متغير من
    الصفحة يتسرّب جوّه @include):
      't'        => رأي جاهز من SiteRenderer::parseTestimonial() — {quote, name, initial, stars}
                    (عقد 3: "الرأي — الاسم ★N"). النجوم بتظهر بس لو الرأي نفسه فيه تقييم (★N) —
                    مفيش نجوم متألّفة على رأي مالوش تقييم.
      'slotKey'  => مفتاح خانة القايمة (data-slot على الكارت، زي عناصر <li> زمان — المحرر بيستبعد
                    خانات list من التعديل/الترتيب الحر، ده بس عشان تخصيص لون/خط الخانة يطبّق).
      'theme'    => rounded (كارت دائري على surface، الافتراضي) · sharp (حدود رفيعة، حواف حادة) ·
                    editorial (اقتباس كبير من غير كارت) · minimal (سطر هادي بخط فاصل) ·
                    bold (إطار 4px وعلامة تنصيص ضخمة).
      'class' / 'style' => إضافات من التصميم (العرض في الشبكة مثلاً). 'tagStyle' => لثيم bold.
--}}
@php
    $tcT = $card['t'];
    $tcTheme = $card['theme'] ?? 'rounded';
    $tcWrapper = match ($tcTheme) {
        'sharp' => 'flex flex-col gap-5 border p-8',
        'editorial' => 'flex flex-col gap-6 py-2',
        'minimal' => 'flex flex-col gap-4 border-t pt-6',
        'bold' => 'flex flex-col gap-5 border-4 p-7 sm:p-9',
        default => 'flex flex-col gap-5 rounded-[1.75rem] p-7 sm:p-8',
    };
    $tcWrapperStyle = match ($tcTheme) {
        'sharp' => 'border-color: color-mix(in srgb, var(--site-text) 14%, transparent);',
        'minimal' => 'border-color: color-mix(in srgb, var(--site-text) 12%, transparent);',
        'bold' => 'border-color: currentColor;',
        'editorial' => '',
        default => 'background-color: var(--site-surface); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 7%, transparent), 0 12px 30px -14px rgb(0 0 0 / .3);',
    };
    $tcQuoteClass = match ($tcTheme) {
        'editorial' => 'text-2xl leading-relaxed sm:text-[1.7rem]',
        'bold' => 'text-xl font-bold leading-relaxed sm:text-2xl',
        default => 'text-lg leading-loose',
    };
    $tcGlyph = match ($tcTheme) { 'bold' => 'h-14 w-14', 'editorial' => 'h-10 w-10', default => 'h-8 w-8' };
@endphp
<figure data-slot="{{ $card['slotKey'] }}" class="{{ $tcWrapper }} {{ $card['class'] ?? '' }}" style="{{ $tcWrapperStyle }} {{ $card['style'] ?? '' }}">
    <div class="flex items-center justify-between gap-4">
        <span style="color: var(--site-primary);">
            @include('site.partials.icon', ['name' => 'quote', 'class' => $tcGlyph])
        </span>
        @if ($tcT['stars'])
            <span class="flex items-center gap-0.5" role="img" aria-label="تقييم {{ $tcT['stars'] }} من 5">
                @for ($tcS = 1; $tcS <= 5; $tcS++)
                    <span style="color: {{ $tcS <= $tcT['stars'] ? '#f5b301' : 'color-mix(in srgb, currentColor 22%, transparent)' }};">
                        @include('site.partials.icon', ['name' => 'star', 'class' => 'h-4 w-4'])
                    </span>
                @endfor
            </span>
        @endif
    </div>

    <blockquote class="{{ $tcQuoteClass }}">{{ $tcT['quote'] }}</blockquote>

    @if ($tcT['name'])
        <figcaption class="mt-auto flex items-center gap-3">
            @if ($tcTheme === 'bold')
                <span class="inline-block px-3 py-1 text-sm font-black" style="{{ $card['tagStyle'] ?? 'background-color: var(--site-primary); color: var(--site-on-primary);' }}">{{ $tcT['name'] }}</span>
            @elseif ($tcTheme === 'editorial' || $tcTheme === 'minimal')
                <span class="h-px w-8 shrink-0" style="background-color: var(--site-primary);" aria-hidden="true"></span>
                <span class="text-sm font-bold" style="color: var(--site-muted);">{{ $tcT['name'] }}</span>
            @else
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center text-base font-bold {{ $tcTheme === 'sharp' ? '' : 'rounded-full' }}"
                    style="background-color: color-mix(in srgb, var(--site-primary) 18%, transparent); color: var(--site-primary);"
                    aria-hidden="true"
                >{{ $tcT['initial'] }}</span>
                <span class="text-base font-bold">{{ $tcT['name'] }}</span>
            @endif
        </figcaption>
    @endif
</figure>
