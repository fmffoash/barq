{{-- خطوة كود الإيميل لدخول الأدمن (2026-10-11) --}}
@extends('layouts.guest')

@section('title', 'كود الدخول')

@section('content')
    <h1 class="mb-2 text-center text-xl font-bold text-white">كود الدخول</h1>
    <p class="mb-6 text-center text-sm text-slate-400">بعتنا كود من 6 أرقام على {{ $email }} — صالح 10 دقايق.</p>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-900/50 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-300">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-900/50 bg-red-950/40 px-4 py-3 text-sm text-red-300">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.code.verify') }}" class="space-y-4">
        @csrf
        <input
            type="text"
            name="code"
            inputmode="numeric"
            pattern="[0-9]{6}"
            maxlength="6"
            required
            autofocus
            autocomplete="one-time-code"
            dir="ltr"
            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-3 text-center text-2xl font-bold tracking-[0.5em] text-white focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
            placeholder="••••••"
        >
        <button type="submit" class="w-full rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-amber-400">
            تأكيد
        </button>
    </form>

    <div class="mt-4 flex items-center justify-between text-sm">
        <form method="POST" action="{{ route('login.code.resend') }}">
            @csrf
            <button type="submit" class="text-amber-400 hover:underline">ابعت كود جديد</button>
        </form>
        <a href="{{ route('login') }}" class="text-slate-400 hover:underline">رجوع</a>
    </div>
@endsection
