{{--
    التصميم الافتراضي القديم — عمود واحد بسيط، بدون نافبار أو هيرو مميز. نفس السلوك بالحرف
    من أول Phase 1، محفوظ كخيار خفيف لأي قالب مش محتاج تصميم أكتر تفصيلاً.
--}}
@forelse ($sections as $section)
    @include('site.partials.section', ['section' => $section])
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse
