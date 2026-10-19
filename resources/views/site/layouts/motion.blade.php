@php
    $D = new \App\Support\DesignedPage($sections, $contactAction ?? null);
    $navigation = $sections->reject(fn ($section) => $section['key'] === 'hero');
    $sceneImage = $motionProfile['scene_image'] ?? '/images/scenes3d/sparkles_3d.png';
    $servicesAnchor = $sections->contains('key', 'services') ? '#services' : '#'.($navigation->first()['key'] ?? 'top');
    $heroSection = $sections->firstWhere('key', 'hero');
    $heroLink = ($heroSection['items'] ?? collect())->firstWhere('slot.slot_type', 'link');
    $heroLinkKey = $heroLink['slot']->key ?? 'contact_link';
    $cta = $project->template->slots->firstWhere('key', $heroLinkKey)?->label_ar ?: ($siteMeta['heroCta']['label'] ?? 'تواصل معانا');
@endphp
<div class="bm-progress" aria-hidden="true"></div>
<header class="bm-header">
    <div class="bm-shell bm-nav">
        <a class="bm-brand" href="#top"><span class="bm-mark" aria-hidden="true">✳︎</span>{{ $project->name }}</a>
        <nav class="bm-links" id="main-navigation" aria-label="أقسام الصفحة">
            @foreach ($navigation as $section)
                <a href="#{{ $section['key'] }}">{{ $section['label'] ?: strip_tags((string) ($section['items']->firstWhere('slot.slot_type', 'text')['value'] ?? 'اكتشف المزيد')) }}</a>
            @endforeach
        </nav>
        @if ($contactAnchor)<a class="bm-nav-cta" href="{{ $contactAnchor }}">خلّينا نتكلم ↖</a>@endif
        <button class="bm-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="فتح قائمة التنقل">القائمة ☰</button>
    </div>
</header>
<main>
@foreach ($sections as $section)
    @php
        $texts = $section['items']->whereIn('slot.slot_type', ['text', 'textarea']);
        $heading = $texts->firstWhere('slot.slot_type', 'text');
        $supporting = $texts->reject(fn ($item) => $heading && $item['slot']->key === $heading['slot']->key);
        $images = $section['items']->where('slot.slot_type', 'image');
        $lists = $section['items']->where('slot.slot_type', 'list');
        $links = $section['items']->where('slot.slot_type', 'link');
    @endphp
    @if ($section['key'] === 'hero')
        <section class="bm-hero" id="hero">
            <div class="bm-shell">
                <div class="bm-hero-grid">
                    <div class="bm-copy">
                        @if ($D->text('hero_eyebrow'))<p class="bm-eyebrow"><span class="bm-dot" aria-hidden="true"></span><span data-slot="hero_eyebrow">{!! $D->text('hero_eyebrow') !!}</span></p>@endif
                        @if ($D->text('hero_title'))<h1 data-slot="hero_title">{!! $D->text('hero_title') !!}</h1>@endif
                        @if ($D->text('hero_subtitle'))<p class="bm-sub" data-slot="hero_subtitle">{!! $D->text('hero_subtitle') !!}</p>@endif
                        <div class="bm-actions">
                            <a class="bm-button" data-slot="{{ $heroLinkKey }}" href="{{ $D->href($heroLinkKey) }}" @if ($D->external($heroLinkKey)) target="_blank" rel="noopener" @endif>{{ $cta }} <span aria-hidden="true">↖</span></a>
                            <a class="bm-button secondary" href="{{ $servicesAnchor }}">اكتشف التفاصيل ↓</a>
                        </div>
                        <div class="bm-hero-note"><span class="bm-note-symbol" aria-hidden="true">✳︎</span><span>أهلاً بيك في {{ $project->name }}. خد وقتك واكتشفنا.</span></div>
                    </div>
                    <div class="bm-stage"><div class="bm-stage-inner">
                        @if ($D->image('hero_image'))<div class="bm-photo-frame"><img class="bm-photo" data-slot="hero_image" src="{{ $D->image('hero_image') }}" alt="{{ $project->name }}" width="640" height="760" fetchpriority="high"></div>@endif
                        <div class="bm-orbit" aria-hidden="true"></div>
                        <div class="bm-sculpture" aria-hidden="true"><div class="bm-cube"><i></i><i></i><i></i><i></i><i></i><i></i></div></div>
                        <div class="bm-object" aria-hidden="true"><img src="{{ $sceneImage }}" alt="" width="195" height="195"></div>
                        <div class="bm-sticker" aria-hidden="true">تفاصيل صغيرة<br>إحساس مختلف ✳︎</div>
                        <div class="bm-caption">{{ $project->name }}<small>{!! $D->text('hero_eyebrow') !!}</small></div>
                    </div></div>
                </div>
                <div class="bm-scroll"><span dir="ltr">MADE FOR THE MOMENT</span><a href="{{ $servicesAnchor }}">كمّل الحكاية ↓</a></div>
            </div>
        </section>
        @if ($D->services())
            <div class="bm-marquee" aria-hidden="true"><div class="bm-marquee-track">@foreach ([1, 2] as $copy)@foreach (array_slice($D->services(), 0, 4) as $service)<span>{{ $service['title'] }} <b>✳︎</b></span>@endforeach @endforeach</div></div>
        @endif
    @else
        <section class="bm-content-section {{ $section['kind'] === 'cta' ? 'bm-contact' : '' }}" id="{{ $section['key'] }}">
            <div class="bm-shell">
                @if ($heading)<h2 data-slot="{{ $heading['slot']->key }}">{!! $heading['value'] !!}</h2>@endif
                @if ($section['rating'] ?? null)@include('site.partials.rating-badge', ['badge' => ['rating' => $section['rating']]])@endif
                @foreach ($supporting as $item)<p class="bm-section-copy" data-slot="{{ $item['slot']->key }}">{!! $item['value'] !!}</p>@endforeach
                @foreach ($lists as $item)
                    @if (isset($item['testimonials']))
                        <div class="bm-reviews">@foreach ($item['testimonials'] as $review)<figure data-slot="{{ $item['slot']->key }}" class="bm-review">@if ($review['stars'])<span class="bq-stars" role="img" aria-label="تقييم {{ $review['stars'] }} من 5">{{ str_repeat('★', $review['stars']) }}</span>@endif<blockquote class="bq-quote">{{ $review['quote'] }}</blockquote><figcaption>{{ $review['name'] }}</figcaption></figure>@endforeach</div>
                    @else
                        <ul class="svc" data-slot="{{ $item['slot']->key }}">@foreach ((array) $item['value'] as $entry)@php $service = \App\Services\SiteRenderer::splitEntry($entry); @endphp<li><h3>{{ $service['title'] }}</h3>@if ($service['desc'])<p>{{ $service['desc'] }}</p>@endif</li>@endforeach</ul>
                    @endif
                @endforeach
                @if ($images->isNotEmpty())<div class="bm-gallery">@foreach ($images as $item)<div class="ph"><img data-slot="{{ $item['slot']->key }}" src="{{ $item['value'] }}" alt="{{ $project->name }}" loading="lazy"></div>@endforeach</div>@endif
                @foreach ($links as $item)<a class="bm-button" data-slot="{{ $item['slot']->key }}" href="{{ $item['value'] }}" @if ($item['external'] ?? false) target="_blank" rel="noopener" @endif>{{ $item['label'] ?? 'تواصل معانا' }} ↖</a>@endforeach
            </div>
        </section>
    @endif
@endforeach
</main>
<footer class="bm-shell bm-footer"><span>{{ $project->name }}</span><span>كل الحقوق محفوظة</span></footer>
@if (! $editable)
    <button class="bm-control" aria-pressed="false" type="button">إيقاف الحركة ◉</button>
    <a class="bm-top" href="#top" aria-label="العودة لأعلى الصفحة">↑</a>
@endif
