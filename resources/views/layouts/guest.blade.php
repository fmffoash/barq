<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', '')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <a href="{{ route('login') }}" class="mb-8 flex items-center gap-2 text-3xl font-extrabold tracking-tight text-amber-400">
            <span class="text-4xl leading-none">⚡</span>
        </a>

        <div class="w-full max-w-sm rounded-2xl border border-slate-800 bg-slate-900/60 p-8 shadow-2xl shadow-black/40 backdrop-blur">
            @yield('content')
        </div>

        <p class="mt-8 text-sm text-slate-500">لوحة تحكم داخلية — طفرة</p>
    </div>
</body>
</html>
