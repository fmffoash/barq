@extends('layouts.app')

@section('title', 'إعدادات الذكاء الاصطناعي')

@section('content')
    @php
        $fitText = ['good' => 'مناسب للجهاز ده', 'tight' => 'على الحد — هيشتغل بس الرام هتبقى ضيقة', 'no' => 'محتاج رام أكتر من اللي في الجهاز', 'unknown' => 'مش عارفين رام الجهاز'];
        $fitClass = ['good' => 'text-emerald-400', 'tight' => 'text-amber-400', 'no' => 'text-red-400', 'unknown' => 'text-slate-400'];
    @endphp

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-100">⚙️ إعدادات الذكاء الاصطناعي</h1>
        <p class="mt-1 text-sm text-slate-500">
            حالة برنامج الذكاء الاصطناعي (Ollama) على الجهاز اللي البرنامج شغال عليه، والنموذج المستخدم، وسرعته الحقيقية.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-800 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-300">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-800 bg-red-950/50 px-4 py-3 text-sm text-red-300">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- الحالة --}}
        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            <h2 class="mb-4 text-lg font-bold text-slate-100">الحالة</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-400">Ollama</dt>
                    <dd>
                        @if ($server['up'])
                            <span class="font-bold text-emerald-400">✓ شغال</span>
                            @if ($server['version'])<span class="text-slate-500" dir="ltr">v{{ $server['version'] }}</span>@endif
                        @else
                            <span class="font-bold text-red-400">✗ مش شغال</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-400">النموذج المستخدم</dt>
                    <dd class="font-mono text-slate-100" dir="ltr">{{ $model }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-400">متسطّب؟</dt>
                    <dd>
                        @if (! $server['up'])
                            <span class="text-slate-500">—</span>
                        @elseif ($modelInstalled)
                            <span class="text-emerald-400">✓ أيوه</span>
                            <span class="text-slate-500">· {{ $isLoaded ? 'محمّل في الذاكرة دلوقتي' : 'مش محمّل (أول طلب هياخد وقت تحميل)' }}</span>
                        @else
                            <span class="font-bold text-red-400">✗ لأ</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-400">حجم السياق</dt>
                    <dd class="text-slate-100">{{ number_format($numCtx) }} توكن</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-400">أقصى وقت انتظار</dt>
                    <dd class="text-slate-100">{{ intdiv($timeout, 60) }} دقيقة</dd>
                </div>
            </dl>

            @unless ($server['up'])
                <div class="mt-4 rounded-lg border border-red-900 bg-red-950/40 p-3 text-sm text-red-300">
                    شغّله وحدّث الصفحة —
                    @if (windows_os())
                        افتح برنامج Ollama من قايمة ابدأ.
                    @else
                        على السيرفر: <code class="font-mono" dir="ltr">systemctl start ollama</code>
                    @endif
                </div>
            @elseif (! $modelInstalled)
                <div class="mt-4 rounded-lg border border-amber-900 bg-amber-950/30 p-3 text-sm text-amber-200">
                    النموذج مش متسطّب. نزّله من الطرفية: <code class="font-mono" dir="ltr">ollama pull {{ $model }}</code> — أو اختار نموذج متسطّب تحت.
                </div>
            @endunless
        </section>

        {{-- السرعة --}}
        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            <h2 class="mb-4 text-lg font-bold text-slate-100">السرعة على الجهاز ده</h2>
            @if ($speed['measured'])
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-400">كتابة الرد</dt>
                        <dd class="font-bold text-slate-100">{{ $speed['eval_tps'] }} توكن/ثانية</dd>
                    </div>
                    @if ($speed['prompt_tps'])
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-400">قراية الطلب</dt>
                            <dd class="text-slate-100">{{ $speed['prompt_tps'] }} توكن/ثانية</dd>
                        </div>
                    @endif
                    @if ($speed['load_s'])
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-400">تحميل النموذج في الذاكرة</dt>
                            <dd class="text-slate-100">{{ $speed['load_s'] }} ثانية</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-400">إنشاء موقع كامل (تقريباً)</dt>
                        <dd class="font-bold text-amber-400">{{ $speed['site_minutes'] }} دقيقة</dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs text-slate-500">من آخر {{ $speed['runs'] }} طلب حقيقي — بيتحدّث لوحده مع كل طلب.</p>
            @else
                <p class="text-sm text-slate-400">لسه متقاستش — اعمل اختبار سرعة (طلب صغير، أقل من دقيقة غالباً) عشان العدّادات تبقى دقيقة من أول مرة.</p>
            @endif

            <form method="POST" action="{{ route('ai-settings.benchmark') }}" data-ai-run="benchmark" data-ai-run-url="{{ route('ai-runs.start') }}" class="mt-5">
                @csrf
                <button type="submit" data-ai-submit @disabled(! $server['up'] || ! $modelInstalled) class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-40">
                    ⏱️ اختبار السرعة
                </button>
            </form>
        </section>

        {{-- تغيير النموذج --}}
        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            <h2 class="mb-4 text-lg font-bold text-slate-100">النموذج وحجم السياق</h2>
            <form method="POST" action="{{ route('ai-settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="model" class="mb-1.5 block text-sm font-medium text-slate-300">النموذج</label>
                    <select id="model" name="model" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-slate-100" dir="ltr">
                        <option value="">الافتراضي من الإعدادات ({{ $defaultModel }})</option>
                        @foreach ($server['models'] as $m)
                            <option value="{{ $m['name'] }}" @selected($m['name'] === $model && $model !== $defaultModel)>{{ $m['name'] }} — {{ $m['size_gb'] }} GB</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">من النماذج المتسطّبة بس. أي نموذج غير qwen3 ممكن يشتغل بس البرومبتات متجرّبة على qwen3.</p>
                </div>
                <div>
                    <label for="num_ctx" class="mb-1.5 block text-sm font-medium text-slate-300">حجم السياق (قد إيه الطلب ممكن يبقى طويل)</label>
                    <select id="num_ctx" name="num_ctx" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-slate-100">
                        <option value="">الافتراضي من الإعدادات ({{ number_format($defaultNumCtx) }})</option>
                        @foreach ($numCtxChoices as $choice)
                            <option value="{{ $choice }}" @selected($choice === $numCtx && $numCtx !== $defaultNumCtx)>{{ number_format($choice) }} توكن</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">أكبر = بيستحمل كوبي جوجل مابس طويل من غير ما يتقص، بس بياخد رام أكتر. أقل من 6000 بيقص التعليمات في طلبات الإنشاء.</p>
                </div>
                <button type="submit" class="rounded-lg border border-slate-700 bg-slate-800 px-5 py-2.5 text-sm font-bold text-slate-100 transition hover:border-amber-500">حفظ</button>
            </form>

            @if (count($server['models']))
                <table class="mt-6 w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 text-right text-xs text-slate-500">
                            <th class="pb-2 font-medium">متسطّب على الجهاز</th>
                            <th class="pb-2 font-medium">الحجم</th>
                            <th class="pb-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($server['models'] as $m)
                            <tr class="border-b border-slate-800/60">
                                <td class="py-2 font-mono text-slate-200" dir="ltr">{{ $m['name'] }} <span class="text-slate-500">{{ $m['params'] }} {{ $m['quantization'] }}</span></td>
                                <td class="py-2 text-slate-400" dir="ltr">{{ $m['size_gb'] }} GB</td>
                                <td class="py-2 text-xs {{ $m['loaded'] ? 'text-emerald-400' : 'text-slate-600' }}">{{ $m['loaded'] ? 'محمّل' : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        {{-- الجهاز ونماذج مقترحة --}}
        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            <h2 class="mb-4 text-lg font-bold text-slate-100">الجهاز ونماذج تناسبه</h2>
            <dl class="mb-5 grid grid-cols-3 gap-3 text-center text-sm">
                <div class="rounded-lg bg-slate-950/60 p-3">
                    <dt class="text-xs text-slate-500">الرام</dt>
                    <dd class="mt-1 font-bold text-slate-100"><span dir="ltr">{{ $hardware['ram_total_gb'] !== null ? $hardware['ram_total_gb'].' GB' : '—' }}</span></dd>
                    @if ($hardware['ram_free_gb'] !== null)<dd class="text-xs text-slate-500">فاضي <span dir="ltr">{{ $hardware['ram_free_gb'] }} GB</span></dd>@endif
                </div>
                <div class="rounded-lg bg-slate-950/60 p-3">
                    <dt class="text-xs text-slate-500">أنوية المعالج</dt>
                    <dd class="mt-1 font-bold text-slate-100">{{ $hardware['cpu_cores'] ?? '—' }}</dd>
                </div>
                <div class="rounded-lg bg-slate-950/60 p-3">
                    <dt class="text-xs text-slate-500">مساحة فاضية</dt>
                    <dd class="mt-1 font-bold text-slate-100"><span dir="ltr">{{ $hardware['disk_free_gb'] !== null ? $hardware['disk_free_gb'].' GB' : '—' }}</span></dd>
                </div>
            </dl>

            <ul class="space-y-3">
                @foreach ($catalog as $item)
                    <li class="rounded-lg border border-slate-800 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-mono text-sm font-bold text-slate-100" dir="ltr">{{ $item['name'] }}</span>
                            <span class="text-xs text-slate-400">{{ $item['label'] }} · رام <span dir="ltr">~{{ $item['ram_gb'] }} GB</span></span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $item['note'] }}</p>
                        <p class="mt-1 text-xs {{ $fitClass[$item['fit']] }}">{{ $fitText[$item['fit']] }}</p>
                        @if ($item['installed'])
                            <p class="mt-1 text-xs text-emerald-400">✓ متسطّب</p>
                        @elseif ($item['fit'] !== 'no')
                            <p class="mt-1 text-xs text-slate-500">للتسطيب من الطرفية: <code class="font-mono text-slate-300" dir="ltr">ollama pull {{ $item['name'] }}</code></p>
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-xs text-slate-500">
                على السيرفر الرام مشتركة مع طفرة — نموذج أكبر من qwen3:8b ممكن يبطّأ السيرفر كله وقت التوليد.
                التسطيب من الطرفية بس (مش من الصفحة) عشان تنزيل جيجات قرار يتاخد بإيد.
            </p>
        </section>
    </div>
@endsection
