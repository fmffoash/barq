{{--
    فوتر مشترك بـ3 أعمدة: اسم النشاط + سطر تعريف (الوصف المختصر للهيرو) · روابط سريعة · التواصل
    (ملاحظة التواصل + زرار واتساب/اتصال لو موجود)، وتحت شريط © + "لفوق".
    كل النصوص هنا نسخة للعرض بس من SiteRenderer ($siteMeta، نص عادي) — من غير data-slot عمداً،
    عشان المحرر المباشر يفضل يعدّل الخانة الأصلية في مكانها مش نسختها هنا.
    $footerTheme: rounded (خلفية surface بحواف دائرية فوق، الافتراضي) · sharp (خط فاصل وحواف
    حادة) · plain (من غير خلفية).
--}}
@php
    $footerTheme = $footerTheme ?? 'rounded';
    $ftLinks = $siteMeta['nav'] ?? [];
    $ftSharp = $footerTheme === 'sharp';
@endphp
<footer
    @class([
        'relative mt-10 px-6 pb-8 pt-14 sm:px-10',
        'rounded-t-[2.5rem]' => $footerTheme === 'rounded',
        'border-t' => $footerTheme !== 'rounded',
    ])
    style="{{ $footerTheme === 'rounded' ? 'background-color: var(--site-surface);' : '' }} border-color: color-mix(in srgb, var(--site-text) 10%, transparent);"
>
    <div class="mx-auto grid max-w-6xl gap-10 text-sm sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1.2fr]">
        <div class="flex flex-col gap-3">
            <a href="#top" class="text-xl font-extrabold" style="color: var(--site-primary);">{{ $project->name }}</a>
            @if (filled($siteMeta['tagline'] ?? null))
                <p class="max-w-sm leading-relaxed" style="color: var(--site-muted);">{{ $siteMeta['tagline'] }}</p>
            @endif
        </div>

        @if (! empty($ftLinks))
            <nav aria-label="روابط سريعة" class="flex flex-col gap-3">
                <span class="text-xs font-bold" style="color: var(--site-muted);">روابط سريعة</span>
                <ul class="grid grid-cols-2 gap-x-6 gap-y-2.5 font-medium">
                    @foreach ($ftLinks as $ftLink)
                        <li><a href="#{{ $ftLink['key'] }}" class="transition hover:opacity-70">{{ $ftLink['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if (filled($siteMeta['contactNote'] ?? null) || ($contactAction ?? null) || ($contactAnchor ?? null))
            <div class="flex flex-col items-start gap-3">
                <span class="text-xs font-bold" style="color: var(--site-muted);">تواصل معنا</span>
                @if (filled($siteMeta['contactNote'] ?? null))
                    <p class="leading-relaxed">{{ $siteMeta['contactNote'] }}</p>
                @endif
                @if ($contactAction ?? null)
                    @include('site.partials.link-button', ['btn' => [
                        'href' => $contactAction['href'],
                        'label' => $contactAction['label'],
                        'class' => 'mt-1 px-5 py-2.5 text-sm font-bold transition hover:opacity-90 '.($ftSharp ? '' : 'rounded-full'),
                        'style' => 'background-color: var(--site-primary); color: var(--site-on-primary);',
                        'iconClass' => 'h-4 w-4 shrink-0',
                    ]])
                @elseif ($contactAnchor ?? null)
                    <a href="#{{ $contactAnchor }}" class="font-bold underline decoration-2 underline-offset-4" style="color: var(--site-primary);">تفاصيل التواصل</a>
                @endif
            </div>
        @endif
    </div>

    <div class="mx-auto mt-12 flex max-w-6xl flex-wrap items-center justify-between gap-4 border-t pt-6 text-xs" style="border-color: color-mix(in srgb, var(--site-text) 10%, transparent); color: var(--site-muted);">
        <span>© {{ now()->year }} {{ $project->name }}</span>
        <a href="#top" class="inline-flex items-center gap-1.5 font-semibold transition hover:opacity-70">
            لفوق
            @include('site.partials.icon', ['name' => 'arrow-up', 'class' => 'h-3.5 w-3.5'])
        </a>
    </div>
</footer>
