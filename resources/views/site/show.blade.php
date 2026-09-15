<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $project->name }}</title>
    @vite(['resources/css/app.css'])
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
    @forelse ($sections as $section)
        @include('site.partials.section', ['section' => $section])
    @empty
        <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
            <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
            <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
        </div>
    @endforelse
</body>
</html>
