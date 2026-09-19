{{--
    تصميم "مودرن" — نافبار ثابت + هيرو بارز + أقسام بشرائط متبادلة الخلفية، وشبكات للقوايم
    والصور بدل العمود الواحد. الشكل بيتحدد من $section['kind'] (محسوبة في SiteRenderer)، مش
    من اسم القسم نفسه، فيشتغل مع أي قالب أياً كان اسم أقسامه.
--}}
@include('site.partials.nav')

@forelse ($sections as $section)
    @include('site.partials.modern-section', ['section' => $section, 'index' => $loop->index])
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    @include('site.partials.footer')
@endif
