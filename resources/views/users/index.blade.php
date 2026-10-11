{{-- إدارة اليوزرات (2026-10-11) — الأدمن بس. الباسورد مابيتعرضش أبداً (متشفّر)، بيتغيّر أو يتولّد مؤقت يظهر مرة واحدة --}}
@extends('layouts.app')

@section('title', 'اليوزرات')

@php
    $input = 'w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400';
@endphp

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-slate-100">👥 اليوزرات</h1>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-300">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-900/50 bg-red-950/40 px-4 py-3 text-sm text-red-300">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    @if ($generated = session('generated_password'))
        <div class="mb-6 rounded-xl border border-amber-500/60 bg-amber-500/10 p-4 text-sm text-amber-200">
            <p class="mb-2 font-bold">الباسورد المؤقت لـ «{{ $generated['name'] }}» — بيظهر مرة واحدة بس، انسخه وابعته:</p>
            <div class="flex items-center gap-2">
                <code id="gen-pass" dir="ltr" class="rounded-lg bg-slate-950 px-3 py-2 text-base font-bold tracking-wider text-white">{{ $generated['password'] }}</code>
                <button type="button" class="rounded-lg bg-amber-500 px-3 py-2 text-xs font-bold text-slate-950"
                        onclick="navigator.clipboard.writeText(document.getElementById('gen-pass').textContent); this.textContent='✓ اتنسخ';">نسخ</button>
            </div>
        </div>
    @endif

    {{-- يوزر جديد --}}
    <details class="mb-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-5" @if($errors->has('email') || $errors->has('name')) open @endif>
        <summary class="cursor-pointer font-semibold text-amber-400">+ يوزر جديد</summary>
        <form method="POST" action="{{ route('users.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            <div>
                <label class="mb-1 block text-xs text-slate-400">الاسم</label>
                <input name="name" value="{{ old('name') }}" required maxlength="100" class="{{ $input }}">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-400">الإيميل (بيدخل بيه)</label>
                <input name="email" type="email" value="{{ old('email') }}" required dir="ltr" class="{{ $input }}">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-400">الباسورد (فاضي = يتولّد مؤقت ويظهرلك مرة واحدة)</label>
                <input name="password" type="text" minlength="8" autocomplete="new-password" dir="ltr" class="{{ $input }}">
            </div>
            <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-300">
                <input type="checkbox" name="is_admin" value="1" class="rounded border-slate-700 bg-slate-950 text-amber-500">
                أدمن (بيشوف كل المشاريع ويدير اليوزرات والقوالب، وبيدخل بكود على الإيميل)
            </label>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-300">إضافة</button>
            </div>
        </form>
    </details>

    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800 text-xs text-slate-400">
                    <th class="px-4 py-3 text-start">اليوزر</th>
                    <th class="px-4 py-3 text-start">الدور</th>
                    <th class="px-4 py-3 text-start">المشاريع</th>
                    <th class="px-4 py-3 text-start">الحالة</th>
                    <th class="px-4 py-3 text-start">آخر دخول</th>
                    <th class="px-4 py-3 text-start">الباسورد</th>
                    <th class="px-4 py-3 text-start">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                @php $me = $user->id === auth()->id(); @endphp
                <tr class="border-b border-slate-800/60 align-top">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-slate-100">{{ $user->name }} @if($me)<span class="text-xs text-amber-400">(إنت)</span>@endif</div>
                        <div class="text-xs text-slate-500" dir="ltr">{{ $user->email }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @if ($user->is_admin)
                            <span class="rounded-full bg-amber-400/10 px-2 py-0.5 text-xs text-amber-400">أدمن</span>
                        @else
                            <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400">يوزر</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-200">{{ $user->projects_count }}</td>
                    <td class="px-4 py-3">
                        @if ($user->status === \App\Models\User::BLOCKED)
                            <span class="rounded-full bg-red-900/40 px-2 py-0.5 text-xs text-red-300">محظور</span>
                        @elseif ($user->status === \App\Models\User::SUSPENDED && ! $user->canLogIn())
                            <span class="rounded-full bg-orange-900/40 px-2 py-0.5 text-xs text-orange-300">موقوف لحد {{ $user->suspended_until->timezone('Africa/Cairo')->format('Y-m-d') }}</span>
                        @else
                            <span class="rounded-full bg-emerald-900/40 px-2 py-0.5 text-xs text-emerald-300">شغال</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400">{{ $user->last_login_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        {{-- الباسورد متشفّر ومابيتعرضش — تغيير بس --}}
                        <form method="POST" action="{{ route('users.password', $user) }}" class="flex flex-wrap items-center gap-1">
                            @csrf @method('PUT')
                            <input name="password" type="text" minlength="8" placeholder="باسورد جديد" autocomplete="new-password" dir="ltr" class="w-32 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1 text-xs text-white">
                            <button type="submit" class="rounded-lg bg-slate-800 px-2 py-1 text-xs text-slate-200 hover:bg-slate-700" title="فاضي = يتولّد مؤقت">تغيير</button>
                        </form>
                    </td>
                    <td class="px-4 py-3">
                        @unless ($me)
                            <div class="flex flex-wrap items-center gap-1">
                                @if ($user->status !== \App\Models\User::ACTIVE && ! $user->canLogIn())
                                    <form method="POST" action="{{ route('users.status', $user) }}">@csrf @method('PUT')
                                        <input type="hidden" name="action" value="activate">
                                        <button class="rounded-lg bg-emerald-800/60 px-2 py-1 text-xs text-emerald-200 hover:bg-emerald-700">تفعيل</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('users.status', $user) }}" class="flex items-center gap-1">@csrf @method('PUT')
                                        <input type="hidden" name="action" value="suspend">
                                        <input name="days" type="number" min="1" max="365" value="7" class="w-14 rounded-lg border border-slate-700 bg-slate-950 px-1 py-1 text-xs text-white" title="عدد الأيام">
                                        <button class="rounded-lg bg-orange-900/50 px-2 py-1 text-xs text-orange-200 hover:bg-orange-800">إيقاف مؤقت</button>
                                    </form>
                                    <form method="POST" action="{{ route('users.status', $user) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('حظر «'.$user->name.'»؟ مش هيقدر يدخل لحد ما تفعّله.') }})">@csrf @method('PUT')
                                        <input type="hidden" name="action" value="block">
                                        <button class="rounded-lg bg-red-900/50 px-2 py-1 text-xs text-red-200 hover:bg-red-800">حظر</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('مسح «'.$user->name.'» نهائياً؟ مشاريعه هتتنقل لحسابك.') }})">@csrf @method('DELETE')
                                    <button class="rounded-lg bg-slate-800 px-2 py-1 text-xs text-red-300 hover:bg-red-900/60">🗑 مسح</button>
                                </form>
                            </div>
                        @endunless
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-xs text-slate-500">ℹ الباسوردات متخزّنة متشفّرة بطريقة مابترجعش، فمحدش يقدر يشوفها (ولا الأدمن). تقدر تحط باسورد جديد، أو تسيب الخانة فاضية ويتولّد باسورد مؤقت يظهرلك مرة واحدة.</p>
@endsection
