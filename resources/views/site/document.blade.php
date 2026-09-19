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
            if (filled($style['color'] ?? null) || filled($style['font'] ?? null)) {
                $slotStyles->put($item['slot']->key, $style);
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $project->name }}</title>
    @if ($cssMode === 'export')
        <link rel="stylesheet" href="assets/app.css">
    @else
        @vite(['resources/css/app.css'])
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
                }
            @endforeach
        </style>
    @endif
</head>
<body
    class="min-h-screen"
    style="
        --site-primary: {{ $colors['primary'] }};
        --site-background: {{ $colors['background'] }};
        --site-surface: {{ $colors['surface'] }};
        --site-text: {{ $colors['text'] }};
        --site-muted: {{ $colors['muted'] }};
        background-color: var(--site-background);
        color: var(--site-text);
        font-family: var(--font-{{ $font ?: 'cairo' }});
    "
>
    @include('site.layouts.'.($layout ?: 'classic'))
</body>
</html>
