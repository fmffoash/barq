{{--
    بارشيال شات الذكاء الاصطناعي — بيتعرض جوّه صفحة المشروع نفسها (projects/show.blade.php)
    وكمان في صفحة /projects/{project}/ai المستقلة (ai-chat/show.blade.php)، عشان فؤاد يقدر
    يكمّل التعديل من مكان واحد بدل ما يدوّر على رابط منفصل (2026-09-20).
--}}
<div class="space-y-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
    <h2 class="text-lg font-semibold text-slate-100">✨ التعديل بالذكاء الاصطناعي</h2>

    <div class="max-h-96 space-y-4 overflow-y-auto" id="chat-log">
        @forelse ($project->aiChatMessages as $message)
            <div class="flex {{ $message->role === 'user' ? 'justify-start' : 'justify-end' }}">
                <div class="max-w-2xl rounded-2xl px-4 py-2.5 text-sm {{ $message->role === 'user' ? 'bg-slate-800 text-slate-200' : 'bg-amber-400/10 text-amber-100 border border-amber-400/30' }}">
                    {{-- لو الرسالة نسخ ولزق طويل (زي صفحة جوجل ماب كاملة) بنقصّرها هنا بس —
                    النص الكامل بيتبعت للذكاء الاصطناعي زي ما هو، ده تقصير عرض بس. --}}
                    {{ \Illuminate\Support\Str::limit($message->content, 400) }}
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">
                لسه مفيش محادثة على المشروع دا — اكتب تحت أي تعديل تحب الذكاء الاصطناعي يعمله
                (تغيير القالب، تعديل نص، تغيير لون/خط...).
            </p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('ai-chat.message', $project) }}" enctype="multipart/form-data" class="space-y-2 border-t border-slate-800 pt-4">
        @csrf
        {{-- صورة جاهزة اختيارية (المرحلة 4، 2026-09-24) — فؤاد يرفق صورة عنده ويقول في
        الرسالة "حطها في كذا" أو "ضيف صورة جديدة"، والذكاء الاصطناعي بيقرر الخانة المناسبة
        من كلام الرسالة (AiProjectAssistantService::applyUpdateImage/applyAddCustomBlock). --}}
        <label class="flex items-center gap-2 text-xs text-slate-400">
            <span>📎 إرفاق صورة (اختياري — عشان تضيفها أو تستبدل بيها صورة موجودة)</span>
            <input type="file" name="image" accept="image/*" class="text-xs text-slate-400 file:mr-2 file:rounded file:border-0 file:bg-slate-800 file:px-2 file:py-1 file:text-slate-200">
        </label>
        <div class="flex gap-2">
            <input
                type="text"
                name="message"
                required
                placeholder="قولّه يعدّل إيه (مثلاً: غيّر القالب لحاجة أفخم، أو ضيف الصورة دي في الهيرو، أو رجّع كل حاجة زي ما كانت)"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
            >
            <button type="submit" data-ai-submit class="shrink-0 rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                ابعت
            </button>
        </div>
    </form>
</div>

@push('scripts')
    <script>
        document.addEventListener('submit', function (event) {
            const btn = event.target.querySelector('[data-ai-submit]');
            if (btn && event.submitter === btn) {
                btn.disabled = true;
                btn.textContent = 'بيفكر...';
            }
        });

        const chatLog = document.getElementById('chat-log');
        if (chatLog) chatLog.scrollTop = chatLog.scrollHeight;
    </script>
@endpush
