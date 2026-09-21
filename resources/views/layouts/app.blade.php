<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', '')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <nav class="border-b border-slate-800 bg-slate-900/60 backdrop-blur">
        <div class="mx-auto flex max-w-[100rem] items-center justify-between px-4 py-3">
            <div class="flex items-center gap-6">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 text-lg font-extrabold tracking-tight text-amber-400">
                    <span class="text-2xl leading-none">⚡</span>
                </a>

                @auth
                    <a
                        href="{{ route('projects.index') }}"
                        class="text-sm font-medium {{ request()->routeIs('projects.*') ? 'text-amber-400' : 'text-slate-400 hover:text-amber-400' }} transition"
                    >
                        المشاريع
                    </a>

                    <a
                        href="{{ route('templates.index') }}"
                        class="text-sm font-medium {{ request()->routeIs('templates.*') ? 'text-amber-400' : 'text-slate-400 hover:text-amber-400' }} transition"
                    >
                        القوالب
                    </a>

                    <a
                        href="{{ route('ai-chat.create') }}"
                        class="text-sm font-medium {{ request()->routeIs('ai-chat.*') ? 'text-amber-400' : 'text-slate-400 hover:text-amber-400' }} transition"
                    >
                        ✨ أنشئ بالذكاء الاصطناعي
                    </a>
                @endauth
            </div>

            @auth
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-400">{{ auth()->user()->name }}</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-400 transition hover:text-amber-400">
                            تسجيل الخروج
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    {{-- عرض السنتر بقى max-w-[100rem] بدل 6xl (1152px، 2026-09-21) — فؤاد لاحظ إن الصفحة
    فاضية على الشاشات الواسعة وبتفضل ثابتة العرض مهما كبّرت المتصفح. --}}
    <main class="mx-auto max-w-[100rem] px-4 py-8">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
