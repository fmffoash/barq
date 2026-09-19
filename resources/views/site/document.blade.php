{{--
    الـ shell الموحّد لأي موقع منشور — بيتستخدم من site.show (معاينة حية، @vite) وsite.export
    (تصدير ثابت، رابط CSS نسبي) بنفس المتغيرات بالظبط. التصميم الفعلي (classic/modern/gallery)
    بيترندر جوّه الـ body عن طريق site.layouts.{layout} — الاتنين بيستقبلوا نفس $sections/$colors
    بالظبط، فمفيش فرق في البيانات، بس في شكل العرض.
--}}
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
</head>
<body
    class="min-h-screen font-[Cairo]"
    style="
        --site-primary: {{ $colors['primary'] }};
        --site-background: {{ $colors['background'] }};
        --site-surface: {{ $colors['surface'] }};
        --site-text: {{ $colors['text'] }};
        --site-muted: {{ $colors['muted'] }};
        background-color: var(--site-background);
        color: var(--site-text);
    "
>
    @include('site.layouts.'.($layout ?: 'classic'))
</body>
</html>
