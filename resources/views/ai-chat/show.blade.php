@extends('layouts.app')

@section('title', $project->name . ' — شات الذكاء الاصطناعي')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('ai-chat.create') }}" class="text-sm text-slate-400 transition hover:text-amber-400">
                &rarr; مشروع جديد بالشات
            </a>
            <h1 class="mt-1 text-2xl font-bold text-slate-100">{{ $project->name }}</h1>
            <p class="text-sm text-slate-500">
                {{ $project->template->name }} — {{ $project->template->category }}
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('projects.site.edit', $project) }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 transition hover:border-amber-400 hover:text-amber-400">
                عدّل يدوي
            </a>
            @if ($project->site)
                <a href="{{ $project->site->previewUrl() }}" target="_blank" rel="noopener" dir="ltr" class="rounded-lg border border-amber-400/60 px-3 py-1.5 text-sm font-semibold text-amber-400 transition hover:bg-amber-400 hover:text-slate-950">
                    معاينة الموقع ←
                </a>
            @endif
        </div>
    </div>

    <div class="space-y-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
        <div class="space-y-4" id="chat-log">
            @forelse ($project->aiChatMessages as $message)
                <div class="flex {{ $message->role === 'user' ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-2xl rounded-2xl px-4 py-2.5 text-sm {{ $message->role === 'user' ? 'bg-slate-800 text-slate-200' : 'bg-amber-400/10 text-amber-100 border border-amber-400/30' }}">
                        {{-- لو الرسالة نسخ ولزق طويل (زي صفحة جوجل ماب كاملة) بنقصّرها هنا بس —
                        النص الكامل بيتبعت للذكاء الاصطناعي زي ما هو، ده تقصير عرض بس. --}}
                        {{ \Illuminate\Support\Str::limit($message->content, 400) }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">لسه مفيش رسائل.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('ai-chat.message', $project) }}" class="flex gap-2 border-t border-slate-800 pt-4">
            @csrf
            <input
                type="text"
                name="message"
                required
                autofocus
                placeholder="قولّه يعدّل إيه (مثلاً: غيّر القالب لحاجة أفخم، أو عدّل العنوان الرئيسي لـ...)"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
            >
            <button type="submit" data-ai-submit class="shrink-0 rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                ابعت
            </button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('submit', function (event) {
            const btn = event.target.querySelector('[data-ai-submit]');
            if (btn && event.submitter === btn) {
                btn.disabled = true;
                btn.textContent = 'بيفكر...';
            }
        });

        const log = document.getElementById('chat-log');
        if (log) log.scrollTop = log.scrollHeight;
    </script>
@endpush
