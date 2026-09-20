@extends('layouts.app')

@section('title', 'أنشئ مشروع بالذكاء الاصطناعي')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-100">✨ أنشئ مشروع بالذكاء الاصطناعي</h1>
        <p class="mt-1 text-sm text-slate-500">
            اكتب وصف النشاط في رسالة واحدة — أي بيانات عندك حتى لو ملخبطة (اسم، خدمات، تليفون، أي حاجة).
            المساعد هيختارلك أنسب قالب من مكتبتنا ويملّي خاناته، وبعد كده تقدر تكمّل تتكلم معاه
            (زي "غيّر القالب"، "خلي الألوان زرقا"، "عدّل العنوان الرئيسي").
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-red-800 bg-red-950/50 px-4 py-3 text-sm text-red-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-800 bg-red-950/50 px-4 py-3 text-sm text-red-300">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('ai-chat.store') }}" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
        @csrf
        <textarea
            name="message"
            rows="8"
            required
            autofocus
            placeholder="مثال: عيادة أسنان في المهندسين اسمها د. أحمد، بنعمل تقويم وتبييض وحشو، التليفون 01012345678، شغالين من 10 الصبح لـ10 بالليل..."
            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none focus:border-amber-400"
        >{{ old('message') }}</textarea>

        <div class="mt-4 flex justify-end">
            <button type="submit" data-ai-submit class="rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                ابدأ
            </button>
        </div>
    </form>

    <p class="mt-3 text-xs text-slate-600">
        ملحوظة: التوليد بياخد نص دقيقة تقريباً (النموذج شغال محلي على السيرفر، صفر بيانات بتتبعت لأي API خارجي).
    </p>
@endsection

@push('scripts')
    <script>
        document.addEventListener('submit', function (event) {
            if (!event.target.contains(document.querySelector('[data-ai-submit]'))) return;
            const btn = event.target.querySelector('[data-ai-submit]');
            if (btn && event.submitter === btn) {
                btn.disabled = true;
                btn.textContent = 'بيفكر... (نص دقيقة تقريباً)';
            }
        });
    </script>
@endpush
