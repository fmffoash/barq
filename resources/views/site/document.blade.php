{{--
    الـ shell الموحّد لأي موقع منشور — بيتستخدم من site.show (معاينة حية، @vite) وsite.export
    (تصدير ثابت، رابط CSS نسبي) بنفس المتغيرات بالظبط. التصميم الفعلي (classic/modern/gallery/...)
    بيترندر جوّه الـ body عن طريق site.layouts.{layout} — الاتنين بيستقبلوا نفس $sections/$colors/
    $font بالظبط، فمفيش فرق في البيانات، بس في شكل العرض.
--}}
@php
    // تخصيصات لون/خط خانات بعينها (Phase 8) — بنجمعها هنا مرة واحدة من كل الأقسام، وبنولّد
    // ليها CSS بـ attribute selector على data-slot بدل ما نكتب style= مباشر جوّه كل عنصر في
    // كل تصميم (كان هيبقى لازم نلمس كل تصميم من الـ 13 لوحده). كل layout بس بيحط خاصية
    // data-slot بقيمة مفتاح الخانة على العنصر، والستايل الفعلي بييجي من هنا مركزياً.
    $slotStyles = collect();
    foreach ($sections as $section) {
        foreach ($section['items'] as $item) {
            $style = $item['style'] ?? [];
            if (
                filled($style['color'] ?? null) || filled($style['font'] ?? null)
                || filled($style['zoom'] ?? null) || filled($style['position'] ?? null)
            ) {
                $slotStyles->put($item['slot']->key, $style);
            }
        }
    }

    // وضع التعديل المباشر (WYSIWYG، docs/wysiwyg-editor-plan.md) — $editable بيتحط true
    // بس من site.live-edit (route محمي بـ auth). المسار العام (site.show/site.export)
    // مبيبعتهاش خالص فبترجع false افتراضياً — ده اللي بيمنع أي أثر لوضع التعديل على
    // الموقع العام (صفر سكريبت/CSS تعديل بيتحمّل هناك).
    $editable = $editable ?? false;
@endphp
<!DOCTYPE html>
{{-- حجم الخط العام (Phase 16) بيتحط هنا على <html> نفسه مش <body> — كل كلاسات Tailwind
الحجمية (text-sm/text-3xl/...) بتستخدم rem، ونسبي لـ<html> بالتحديد، فتكبير/تصغير حجم خط
<html> بيكبّر/يصغّر كل نصوص الموقع نسبياً من غير أي تعديل في أي من الـ16 layout. --}}
<html lang="ar" dir="rtl" style="font-size: {{ $fontSizeScale * 100 }}%;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $project->name }}</title>
    @if ($cssMode === 'export')
        <link rel="stylesheet" href="assets/app.css">
    @else
        @vite(['resources/css/app.css'])
    @endif
    @if ($editable)
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- ?v={{ filemtime }} (2026-09-21) — الملفين دول static بدون اسم مبني على hash
        زي أصول Vite، فـCloudflare كان بيكاش نسخة قديمة منهم لحد ما فؤاد يفضل شايف كود
        قديم حتى بعد ما نديبلوي التعديل على السيرفر (اكتشفناها لما إصلاح select القديم مكانش
        باين رغم إن الملف على السيرفر كان صح فعلاً). query string بقيمة وقت آخر تعديل فعلي
        للملف بيغيّر الـURL تلقائي كل مرة نعدّل فيها، فـCloudflare بيعتبره طلب جديد. --}}
        <link rel="stylesheet" href="{{ asset('css/live-editor.css') }}?v={{ filemtime(public_path('css/live-editor.css')) }}">
    @endif
    @if ($slotStyles->isNotEmpty())
        <style>
            @foreach ($slotStyles as $slotKey => $style)
                [data-slot="{{ $slotKey }}"] {
                    @if (filled($style['color'] ?? null))
                        color: {{ $style['color'] }} !important;
                    @endif
                    @if (filled($style['font'] ?? null))
                        font-family: var(--font-{{ $style['font'] }}) !important;
                    @endif
                    {{-- تكبير/تحريك الصورة جوّه إطارها الثابت (المرحلة 2) — object-position
                    بيشتغل بس لو الصورة object-fit:cover (كل <img> خانة صورة عندها object-cover
                    فعلاً، شوف الخطة)، وtransform:scale() محتاج overflow-hidden على حاوية
                    الصورة عشان مايكسرش أي إطار مدوّر الحواف (اتأكد من كل الـ16 layout). --}}
                    @if (filled($style['position'] ?? null))
                        object-position: {{ $style['position'] }} !important;
                    @endif
                    @if (filled($style['zoom'] ?? null))
                        transform: scale({{ $style['zoom'] }});
                    @endif
                }
            @endforeach
        </style>
    @endif
</head>
<body
    class="min-h-screen{{ $editable ? ' bq-live-editable' : '' }}"
    style="
        --site-primary: {{ $colors['primary'] }};
        --site-background: {{ $colors['background'] }};
        --site-surface: {{ $colors['surface'] }};
        --site-text: {{ $colors['text'] }};
        --site-muted: {{ $colors['muted'] }};
        background-color: var(--site-background);
        color: var(--site-text);
        font-family: var(--font-{{ $font ?: 'cairo' }});
        font-weight: {{ $fontWeight }};
        font-style: {{ $fontStyle }};
    "
>
    @include('site.layouts.'.($layout ?: 'classic'))

    @if ($editable)
        @include('site.partials.live-editor', [
            'project' => $project,
            'sections' => $sections,
            'colors' => $colors,
            'font' => $font,
            'site' => $site,
            'variant' => $variant,
            'slotsBySection' => $slotsBySection,
            'sameCategoryTemplates' => $sameCategoryTemplates,
        ])
        <script src="{{ asset('js/live-editor.js') }}?v={{ filemtime(public_path('js/live-editor.js')) }}" defer></script>
    @endif

    @stack('scripts')
</body>
</html>
