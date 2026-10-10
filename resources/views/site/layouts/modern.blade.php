{{--
    تصميم "مودرن" — نافبار "pill" ثابت + هيرو بكارت متدرّج بارز، وأقسام بشرائط متبادلة الخلفية
    (قسم آه وقسم لأ)، كروت خدمات بأيقونة، فسيفساء صور، كروت آراء، وفوتر كامل. الشكل بيتحدد من
    $section['kind'] (محسوبة في SiteRenderer)، مش من اسم القسم، فيشتغل مع أي قالب.
--}}
@include('site.partials.nav')

@php
    // تقييم جوجل (لو موجود في عنوان قسم الآراء) بيظهر كمان كشارة في الهيرو.
    $siteRating = $sections->first(fn ($s) => ! empty($s['rating']))['rating'] ?? null;
@endphp

@forelse ($sections as $section)
    @include('site.partials.modern-section', ['section' => $section, 'index' => $loop->index, 'siteRating' => $siteRating])
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    @include('site.partials.footer', ['footerTheme' => 'rounded'])
@endif
