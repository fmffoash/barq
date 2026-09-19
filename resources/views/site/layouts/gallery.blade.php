{{--
    تصميم "جاليري" — هيرو بصورة خلفية كاملة (لو متاحة)، وباقي الأقسام بتتبادل بين صورة/نص
    (زجزاج) بدل العمود الواحد. مناسب لأي نشاط بصورة قوية (مطاعم، عقارات، بورتفوليو...).
--}}
@include('site.partials.nav')

@forelse ($sections as $section)
    @include('site.partials.gallery-section', ['section' => $section, 'index' => $loop->index])
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    @include('site.partials.footer')
@endif
