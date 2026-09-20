@extends('layouts.app')

@section('title', 'إضافة قالب')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('templates.index') }}" class="text-sm text-slate-400 transition hover:text-amber-400">
                &rarr; القوالب
            </a>
        </div>

        <h1 class="mb-6 text-2xl font-bold text-slate-100">قالب جديد</h1>

        <form method="POST" action="{{ route('templates.store') }}" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            @csrf

            @include('templates._form')

            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                    حفظ ومتابعة
                </button>
            </div>
        </form>
    </div>
@endsection
