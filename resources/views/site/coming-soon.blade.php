<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $project->name }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center gap-3 bg-slate-950 px-6 text-center font-[Cairo] text-slate-100">
    <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
    <p class="text-slate-500">الموقع دا لسه بيتجهّز، تعال زورنا تاني قريب.</p>
</body>
</html>
