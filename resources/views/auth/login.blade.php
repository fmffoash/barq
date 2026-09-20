@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
    <h1 class="mb-6 text-center text-xl font-bold text-white">تسجيل الدخول</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-900/50 bg-red-950/40 px-4 py-3 text-sm text-red-300">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-300">البريد الإلكتروني</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                placeholder="admin@example.com"
            >
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-300">كلمة المرور</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                placeholder="••••••••"
            >
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-400">
            <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-400">
            تذكرني
        </label>

        <button
            type="submit"
            class="w-full rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-900"
        >
            دخول
        </button>
    </form>
@endsection
