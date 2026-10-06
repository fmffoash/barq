{{--
    صورة شكل القالب الحقيقي فوق الكارت (2026-10-06) — لقطة شاشة جاهزة من المعاينة الحقيقية
    (TemplatePreviewService::thumbnailUrl، بتتولّد بـ`php artisan barq:template-thumbnails`)،
    والدوس عليها بيفتح المعاينة الكاملة الحيّة في تاب جديد. لو مفيش صورة مطابقة لشكل القالب
    الحالي (قالب جديد عمله فؤاد بنفسه، أو اتعدّلت ألوانه بعد اللقطة) بيرجع للعرض القديم: صورة
    الفئة + تدرّج ألوان القالب، وزرار المعاينة الكاملة بيفضل شغال.

    المتغيرات: $template (محمّل بـslots وvariants)، $thumbnail (?string).
--}}
@php
    $isLanding = $template->kind === 'landing';

    if (! $thumbnail) {
        $colors = $template->defaultVariant()?->colors_json;
        $heroImage = $template->slots->firstWhere('key', 'hero_image')?->default_value;
        $fallbackStyle = match (true) {
            (bool) $heroImage => "background-image: linear-gradient(to bottom, rgba(0,0,0,.15), rgba(0,0,0,.55)), url('{$heroImage}');",
            isset($colors['primary']) => "background-image: linear-gradient(135deg, {$colors['primary']}, ".($colors['background'] ?? '#0f172a').');',
            default => '',
        };
    }
@endphp

<a
    href="{{ $isLanding ? route('templates.preview', $template) : route('templates.show', $template) }}"
    @if ($isLanding) target="_blank" rel="noopener" title="معاينة كاملة في تاب جديد" @endif
    class="group/preview relative block aspect-[16/10] overflow-hidden bg-slate-950"
>
    @if ($thumbnail)
        <img
            src="{{ $thumbnail }}"
            alt="شكل قالب {{ $template->name }}"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-cover object-top transition duration-300 group-hover/preview:scale-[1.03]"
        >
    @else
        <div class="flex h-full w-full items-center justify-center bg-cover bg-center" style="{{ $fallbackStyle }}">
            @unless ($fallbackStyle)
                <span class="text-3xl opacity-30">🖼️</span>
            @endunless
        </div>
    @endif

    @if ($isLanding)
        <span class="absolute bottom-2 start-2 rounded-full bg-slate-950/75 px-2.5 py-1 text-xs text-slate-200 backdrop-blur">
            {{ \App\Models\Template::layoutLabel($template->layout) }}
        </span>

        <span class="absolute inset-0 flex items-center justify-center bg-slate-950/55 opacity-0 transition group-hover/preview:opacity-100">
            <span class="rounded-full bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 shadow-lg">👁 معاينة كاملة</span>
        </span>
    @endif
</a>
