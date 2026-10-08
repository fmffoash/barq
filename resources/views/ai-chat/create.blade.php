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

    {{-- data-ai-run: resources/js/ai-run.js بيبعته بالعدّاد (AiRunController)، والـaction العادي
    للحالة اللي الجافاسكريبت مش شغال فيها. --}}
    <form method="POST" action="{{ route('ai-chat.store') }}" id="ai-create-form" data-ai-run="create" data-ai-run-url="{{ route('ai-runs.start') }}" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
        @csrf
        <textarea
            name="message"
            rows="8"
            required
            autofocus
            placeholder="مثال: عيادة أسنان في المهندسين اسمها د. أحمد، بنعمل تقويم وتبييض وحشو، التليفون 01012345678، شغالين من 10 الصبح لـ10 بالليل..."
            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none focus:border-amber-400"
        >{{ old('message') }}</textarea>

        {{-- صور مع الرسالة (2026-10-08) — كوبي جوجل مابس بصوره، أو صور جاهزة بالسحب/الزرار/Ctrl+V.
        resources/js/photo-tray.js بيعرضها ويبعتها (photos[] ملفات، photo_urls[] صور جوجل). --}}
        <div class="mt-3 rounded-lg border border-dashed border-slate-800 p-3 transition data-[drag=true]:border-amber-400 data-[drag=true]:bg-amber-400/5" data-photo-tray>
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden" data-photo-input>
            <div class="mb-3 flex flex-wrap items-center gap-2 empty:hidden" data-photo-list></div>
            <div data-photo-urls></div>
            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                <button type="button" class="rounded-lg border border-slate-700 px-3 py-1.5 text-slate-300 transition hover:border-amber-400 hover:text-amber-400" data-photo-pick>
                    📷 أضف صور
                </button>
                <span>أو الزق كوبي جوجل مابس بالصور (Ctrl+V)، أو اسحب صور هنا — أول صورة بتبقى صورة الغلاف.</span>
            </div>
            <p class="mt-2 text-xs text-amber-300/80" data-photo-hint></p>
        </div>

        {{-- خيارات "حدد بنفسك" (اختيارية بالكامل، Phase 14 — 2026-09-21) — فؤاد طلب يقدر
        يحدد القالب/اللون/الخط بنفسه بدل ما يسيب الذكاء الاصطناعي يخمّن، خصوصاً إن التخمين
        ممكن يغلط أو يكرر نفس القالب. سايبها الذكاء الاصطناعي يخمّن لو محدّدش حاجة هنا. --}}
        <details class="mt-4 rounded-lg border border-slate-800 bg-slate-950/50 px-4 py-3" id="manual-options">
            <summary class="cursor-pointer text-sm font-medium text-slate-300">
                ⚙️ أو حدد بنفسك (اختياري) — نوع النشاط، القالب، اللون، الخط
            </summary>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="category-select" class="mb-1.5 block text-sm font-medium text-slate-300">نوع النشاط</label>
                    <select id="category-select" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400">
                        <option value="">— بلاش، خلي الذكاء الاصطناعي يختار —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="template-select" class="mb-1.5 block text-sm font-medium text-slate-300">القالب</label>
                    <div class="flex gap-2">
                        <select id="template-select" name="template_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400" disabled>
                            <option value="">اختار نوع النشاط الأول</option>
                        </select>
                        {{-- زرار "تصفح القوالب" (Phase 14) — بيفتح صفحة القوالب فلترة على
                        نفس الفئة المختارة في تاب جديد، عشان يشوف شكل كل قالب فعلياً
                        (المعاينة الحية) قبل ما يختار من القايمة جنبه. --}}
                        <a
                            id="browse-templates-link"
                            href="{{ route('templates.index') }}"
                            target="_blank"
                            rel="noopener"
                            class="pointer-events-none shrink-0 rounded-lg border border-slate-700 px-3 py-2.5 text-sm text-slate-500 opacity-40 transition"
                        >
                            🖼️ تصفح
                        </a>
                        {{-- معاينة كاملة للقالب المختار بالظبط (2026-10-06) — بيتفعّل لما يتختار قالب. --}}
                        <a
                            id="preview-template-link"
                            href="#"
                            data-url-pattern="{{ route('templates.preview', ['template' => '__ID__']) }}"
                            target="_blank"
                            rel="noopener"
                            class="pointer-events-none shrink-0 rounded-lg border border-slate-700 px-3 py-2.5 text-sm text-slate-500 opacity-40 transition"
                        >
                            👁 معاينة
                        </a>
                    </div>
                </div>

                <div>
                    <label for="color-input" class="mb-1.5 block text-sm font-medium text-slate-300">اللون الأساسي (اختياري)</label>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="color-enabled" class="rounded border-slate-700 bg-slate-950 text-amber-400 focus:ring-amber-400">
                        <input type="color" id="color-input" name="color" value="#f59e0b" disabled class="h-10 w-16 rounded border border-slate-700 bg-slate-950 disabled:opacity-40">
                        <label for="color-enabled" class="text-xs text-slate-500">استخدم اللون ده بدل لون القالب الافتراضي</label>
                    </div>
                </div>

                {{-- منتقي الخط المشترك (custom dropdown بيعرض شكل كل خط فعلياً — شوف
                templates/partials/font-select.blade.php لسبب استبدال <select> العادي). --}}
                @include('templates.partials.font-select', ['font' => null, 'fieldName' => 'font', 'withInherit' => true])
            </div>
        </details>

        <div class="mt-4 flex justify-end">
            <button type="submit" data-ai-submit class="rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                ابدأ
            </button>
        </div>
    </form>

    <p class="mt-3 text-xs text-slate-600">
        ملحوظة: النموذج شغال محلي (صفر بيانات بتتبعت لأي API خارجي) — هتشوف عدّاد بالمرحلة والوقت الباقي وهو شغال.
    </p>
@endsection

@push('scripts')
    {{-- @json بيهرّب < و > — اسم قالب فيه "</script>" مايقدرش يقفل الوسم ده. --}}
    <script id="ai-templates-data" type="application/json">@json($templates)</script>
    <script>
        // فلترة القالب بالفئة المختارة (2026-09-21) — كل البيانات (300 قالب، id/name/category
        // بس) متحمّلة في الصفحة من الأول، فالفلترة بتحصل فوراً في المتصفح من غير أي نداء
        // تاني للسيرفر. لو الأدمن معملش أي اختيار هنا، القوايم دي بتفضل disabled فمش
        // بتتبعت مع الفورم خالص، والذكاء الاصطناعي بيشتغل عادي زي ما كان (يخمّن كل حاجة).
        (function () {
            const allTemplates = JSON.parse(document.getElementById('ai-templates-data').textContent);
            const categorySelect = document.getElementById('category-select');
            const templateSelect = document.getElementById('template-select');
            const browseLink = document.getElementById('browse-templates-link');
            const browseBaseUrl = browseLink.href;
            const previewLink = document.getElementById('preview-template-link');
            const colorEnabled = document.getElementById('color-enabled');

            function updatePreviewLink() {
                const id = templateSelect.disabled ? '' : templateSelect.value;
                const on = id !== '';
                previewLink.href = on ? previewLink.dataset.urlPattern.replace('__ID__', encodeURIComponent(id)) : '#';
                previewLink.classList.toggle('pointer-events-none', !on);
                previewLink.classList.toggle('opacity-40', !on);
                previewLink.classList.toggle('text-slate-500', !on);
                previewLink.classList.toggle('text-amber-400', on);
            }

            templateSelect.addEventListener('change', updatePreviewLink);
            const colorInput = document.getElementById('color-input');

            categorySelect.addEventListener('change', function () {
                const category = categorySelect.value;
                templateSelect.innerHTML = '';

                if (category === '') {
                    templateSelect.disabled = true;
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'اختار نوع النشاط الأول';
                    templateSelect.appendChild(opt);
                    browseLink.href = browseBaseUrl;
                    browseLink.classList.add('pointer-events-none', 'opacity-40', 'text-slate-500');
                    browseLink.classList.remove('text-amber-400', 'hover:bg-amber-400/10');
                    updatePreviewLink();
                    return;
                }

                browseLink.href = browseBaseUrl + '?category=' + encodeURIComponent(category);
                browseLink.classList.remove('pointer-events-none', 'opacity-40', 'text-slate-500');
                browseLink.classList.add('text-amber-400', 'hover:bg-amber-400/10');

                templateSelect.disabled = false;
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = '— بلاش، خلي الذكاء الاصطناعي يختار من الفئة دي —';
                templateSelect.appendChild(placeholder);

                allTemplates
                    .filter(function (t) { return t.category === category; })
                    .forEach(function (t) {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = t.name;
                        templateSelect.appendChild(opt);
                    });
                updatePreviewLink();
            });

            colorEnabled.addEventListener('change', function () {
                colorInput.disabled = !colorEnabled.checked;
            });

            updatePreviewLink();
        })();
    </script>
@endpush
