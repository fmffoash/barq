{{--
    لوحة عدّاد الذكاء الاصطناعي (2026-10-08) — بتظهر فوق أي صفحة فيها فورم عليه data-ai-run
    (إنشاء مشروع، رسالة شات، اقتراح محتوى). resources/js/ai-run.js هو اللي بيفتحها ويملاها:
    المرحلة الحالية، النسبة، الوقت الباقي (من سرعة الجهاز الحقيقية)، والكلام وهو بيتكتب.
    الكلاسات هنا (مش في الجافاسكريبت) عشان Tailwind يشوفها وقت الـbuild.
--}}
<div
    id="ai-run-panel"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm data-[open=true]:flex"
    role="dialog"
    aria-modal="true"
    aria-labelledby="ai-run-title"
>
    <div class="w-full max-w-xl rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl shadow-black/50">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 id="ai-run-title" class="text-lg font-bold text-slate-100" data-ai-title>بيشتغل...</h2>
                <p class="mt-1 text-sm text-slate-400" data-ai-stage-label aria-live="polite"></p>
            </div>
            <span class="shrink-0 text-3xl font-extrabold tabular-nums text-amber-400" dir="ltr" data-ai-percent>0%</span>
        </div>

        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-800" dir="ltr">
            <div class="h-full w-0 rounded-full bg-amber-400 transition-[width] duration-300 ease-out" data-ai-bar></div>
        </div>

        <div class="mt-2 flex justify-between gap-3 text-xs text-slate-400">
            <span data-ai-remaining>بيحسب الوقت...</span>
            <span class="tabular-nums" data-ai-elapsed></span>
        </div>

        <ol class="mt-5 grid grid-cols-5 gap-1.5 text-center text-[11px] leading-4" data-ai-stages>
            @foreach (['prepare' => 'تجهيز', 'load' => 'تحميل النموذج', 'read' => 'قراية طلبك', 'write' => 'كتابة الرد', 'apply' => 'تطبيق'] as $stage => $label)
                <li
                    data-stage="{{ $stage }}"
                    data-state="waiting"
                    class="group rounded-lg border border-slate-800 px-1 py-2 text-slate-500 transition data-[state=active]:border-amber-400/60 data-[state=active]:bg-amber-400/10 data-[state=active]:text-amber-300 data-[state=done]:border-emerald-500/40 data-[state=done]:text-emerald-400 data-[state=skipped]:opacity-40"
                >
                    <span class="mb-1 block text-base leading-none">
                        <span class="group-data-[state=done]:hidden group-data-[state=active]:inline-block group-data-[state=active]:animate-pulse">●</span>
                        <span class="hidden group-data-[state=done]:inline">✓</span>
                    </span>
                    {{ $label }}
                </li>
            @endforeach
        </ol>

        <div
            class="mt-5 hidden max-h-40 overflow-y-auto rounded-lg border border-slate-800 bg-slate-950 p-3 text-sm leading-7 text-slate-300 data-[show=true]:block"
            data-ai-preview
        ></div>

        <div
            class="mt-5 hidden whitespace-pre-line rounded-lg border px-4 py-3 text-sm data-[tone=ok]:block data-[tone=ok]:border-emerald-500/40 data-[tone=ok]:bg-emerald-500/10 data-[tone=ok]:text-emerald-200 data-[tone=error]:block data-[tone=error]:border-red-800 data-[tone=error]:bg-red-950/50 data-[tone=error]:text-red-300"
            data-ai-result
        ></div>

        <div class="mt-5 flex items-center justify-between gap-3">
            <p class="text-[11px] text-slate-600" data-ai-mode></p>
            <div class="flex gap-2">
                <button type="button" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 transition hover:border-red-400 hover:text-red-300" data-ai-cancel>
                    إلغاء
                </button>
                <button type="button" class="hidden rounded-lg bg-amber-400 px-5 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-300" data-ai-ok>
                    تمام
                </button>
            </div>
        </div>
    </div>
</div>
