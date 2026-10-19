{{-- إحصائيات القوالب (2026-10-19) — الأدمن بس. الأرقام من template_events (كل اختيار/تبديل قالب) --}}
@extends('layouts.app')

@section('title', 'إحصائيات القوالب')

@php
    $input = 'rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400';
    $sortLink = fn (string $key) => route('template-stats.index', array_filter([...$filters, 'sort' => $key]));
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-slate-100">📊 إحصائيات القوالب</h1>
        <a href="{{ route('templates.index') }}" class="text-sm text-slate-400 hover:text-amber-400">← صفحة القوالب</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-300">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-900/50 bg-red-950/40 px-4 py-3 text-sm text-red-300">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- أولوية 3D في الاختيار التلقائي --}}
    <form method="POST" action="{{ route('template-stats.prefer-3d') }}" class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border {{ $prefer3d ? 'border-amber-500/60 bg-amber-500/10' : 'border-slate-800 bg-slate-900/60' }} p-4">
        @csrf
        @method('PUT')
        <div>
            <p class="font-semibold text-slate-100">🧊 الأولوية لقوالب 3D: <span class="{{ $prefer3d ? 'text-amber-400' : 'text-slate-400' }}">{{ $prefer3d ? 'شغالة' : 'مقفولة' }}</span></p>
            <p class="mt-1 text-xs text-slate-400">لما حد يطلب موقع، الذكاء الاصطناعي بيختار من قوالب 3D بس (تصميمات الرسومات + موشن 3D) لو الفئة فيها، ولو مفيش بيختار من الكل. القالب اللي اليوزر يسمّيه بالاسم بيتطبّق زي ما هو.</p>
        </div>
        <input type="hidden" name="prefer_3d" value="{{ $prefer3d ? 0 : 1 }}">
        <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $prefer3d ? 'border border-slate-600 text-slate-200 hover:border-amber-400' : 'bg-amber-400 text-slate-950 hover:bg-amber-300' }}">
            {{ $prefer3d ? 'اقفل الأولوية' : 'شغّل الأولوية' }}
        </button>
    </form>

    {{-- ملخص --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['القوالب', $summary['total'], $summary['active'].' شغال'],
            ['قوالب 3D', $summary['threeD'], 'نصيبها من الاختيارات '.$summary['threeDShare'].'%'],
            ['اتستخدمت', $summary['used'], $summary['unused'].' عمرها ما اتستخدمت'],
            ['التبديلات', $summary['switches'], 'على '.$summary['projects'].' مشروع'],
        ] as [$label, $value, $hint])
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <p class="text-xs text-slate-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-100">{{ number_format($value) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 grid gap-3 lg:grid-cols-2">
        @foreach ([['🏆 الأكتر استخدام', $topChosen, 'chosen', 'مرة'], ['🔁 الأكتر اتبدّل', $topSwitched, 'switched', 'تبديل']] as [$title, $list, $key, $unit])
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <p class="mb-3 font-semibold text-slate-100">{{ $title }}</p>
                @forelse ($list as $row)
                    <div class="flex items-center justify-between gap-3 border-t border-slate-800 py-2 text-sm first:border-t-0">
                        <span class="truncate text-slate-200">{{ $row['name'] }}@if ($row['is3d']) <span class="text-xs text-amber-400">3D</span>@endif</span>
                        <span class="shrink-0 text-slate-400">{{ $row[$key] }} {{ $unit }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">لسه مفيش.</p>
                @endforelse
            </div>
        @endforeach
    </div>

    {{-- فلترة --}}
    <form method="GET" action="{{ route('template-stats.index') }}" class="mb-4 flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ابحث باسم القالب" class="{{ $input }}">
        <select name="category" class="{{ $input }}">
            <option value="">كل الفئات</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
            @endforeach
        </select>
        <select name="type" class="{{ $input }}">
            @foreach (['all' => 'كل الأنواع', '3d' => 'قوالب 3D بس', 'regular' => 'العادية بس'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? 'all') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="usage" class="{{ $input }}">
            @foreach (['all' => 'أي استخدام', 'used' => 'اتستخدمت', 'unused' => 'عمرها ما اتستخدمت', 'switched' => 'اتستخدمت واتبدّلت'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['usage'] ?? 'all') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
        <button type="submit" class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-300">فلترة</button>
    </form>

    {{-- الحذف: فورم للمختارين بالعلامة، وفورم لزرار الحذف في كل سطر (زرار الصف بيبعت رقمه بس) --}}
    <form id="bulk-delete" method="POST" action="{{ route('template-stats.destroy') }}" onsubmit="return confirm('متأكد؟ القوالب المختارة هتتمسح نهائي (اللي عليها مشاريع مش هتتمسح).')">
        @csrf
        @method('DELETE')
    </form>
    <form id="row-delete" method="POST" action="{{ route('template-stats.destroy') }}">
        @csrf
        @method('DELETE')
    </form>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-400">
        <span>{{ number_format($rows->total()) }} قالب</span>
        <button type="submit" form="bulk-delete" class="rounded-lg border border-red-900/60 px-3 py-1.5 text-red-300 hover:bg-red-950/40">🗑 امسح المختار</button>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-800 text-xs text-slate-400">
                <tr>
                    <th class="p-3 text-start"><input type="checkbox" aria-label="اختار الكل" onclick="document.querySelectorAll('.stat-pick').forEach(c => c.checked = this.checked)" class="rounded border-slate-700 bg-slate-950 text-amber-500"></th>
                    <th class="p-3 text-start"><a href="{{ $sortLink('name') }}" class="hover:text-amber-400">القالب</a></th>
                    <th class="p-3 text-start">الفئة</th>
                    <th class="p-3 text-center"><a href="{{ $sortLink('chosen') }}" class="hover:text-amber-400">اتختار</a></th>
                    <th class="p-3 text-center"><a href="{{ $sortLink('current') }}" class="hover:text-amber-400">شغال دلوقتي</a></th>
                    <th class="p-3 text-center"><a href="{{ $sortLink('switched') }}" class="hover:text-amber-400">اتبدّل</a></th>
                    <th class="p-3 text-center"><a href="{{ $sortLink('rate') }}" class="hover:text-amber-400">نسبة التبديل</a></th>
                    <th class="p-3 text-center"><a href="{{ $sortLink('last') }}" class="hover:text-amber-400">آخر استخدام</a></th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-t border-slate-800/70 {{ $row['active'] ? '' : 'opacity-60' }}">
                        <td class="p-3">
                            @if ($row['current'] === 0)
                                <input type="checkbox" form="bulk-delete" name="ids[]" value="{{ $row['id'] }}" aria-label="اختار {{ $row['name'] }}" class="stat-pick rounded border-slate-700 bg-slate-950 text-amber-500">
                            @endif
                        </td>
                        <td class="p-3">
                            <a href="{{ route('templates.preview', $row['id']) }}" target="_blank" class="font-medium text-slate-100 hover:text-amber-400">{{ $row['name'] }}</a>
                            <div class="mt-1 flex flex-wrap gap-1 text-[11px]">
                                @if ($row['is3d'])<span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-amber-300">3D</span>@endif
                                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-slate-400">{{ $row['layout'] }}</span>
                                @if ($row['archived'])<span class="rounded-full bg-slate-800 px-2 py-0.5 text-slate-400">📦 مؤرشف</span>@endif
                            </div>
                        </td>
                        <td class="p-3 text-slate-400">{{ $row['category'] }}</td>
                        <td class="p-3 text-center font-semibold text-slate-100">{{ $row['chosen'] }}</td>
                        <td class="p-3 text-center text-slate-200">{{ $row['current'] }}</td>
                        <td class="p-3 text-center {{ $row['switched'] ? 'text-red-300' : 'text-slate-500' }}">{{ $row['switched'] }}</td>
                        <td class="p-3 text-center text-slate-400">{{ $row['chosen'] ? $row['rate'].'%' : '—' }}</td>
                        <td class="p-3 text-center text-xs text-slate-400">{{ $row['last_used'] ? \Illuminate\Support\Carbon::parse($row['last_used'])->diffForHumans() : '—' }}</td>
                        <td class="p-3 text-end whitespace-nowrap">
                            <a href="{{ route('templates.edit', $row['id']) }}" class="text-xs text-slate-400 hover:text-amber-400">تعديل</a>
                            @if ($row['current'] === 0)
                                <button type="submit" form="row-delete" name="ids[]" value="{{ $row['id'] }}"
                                        onclick="return confirm({{ \Illuminate\Support\Js::from('متأكد إنك عايز تمسح «'.$row['name'].'» نهائي؟') }})"
                                        class="ms-3 text-xs text-red-300 hover:text-red-200">🗑 حذف</button>
                            @else
                                <span class="ms-3 text-xs text-slate-600" title="عليه مشاريع شغالة — أرشفه من صفحة القوالب بدل الحذف">🔒</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-6 text-center text-slate-500">مفيش قوالب بالفلترة دي.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
@endsection
