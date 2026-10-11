{{-- "كتاب" — كتاب مفتوح صفحته بتتقلب، وكاب تخرج عايم فوقه بشرّابة بتتهز. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center" style="top: 64%;">
        <div class="bq3d-book">
            @include('site.partials.depth.box', ['box' => ['class' => 'cover', 'w' => '14.4rem', 'h' => '9.8rem', 'd' => '.5rem']])
            <div class="bq3d-page bq3d-page--l"></div>
            <div class="bq3d-page bq3d-page--r"></div>
            <div class="bq3d-page bq3d-page--flip"></div>
        </div>
    </div>
    <div class="bq3d-center bq3d-bobby" style="top: 24%;">
        <div class="bq3d-cap">
            @include('site.partials.depth.box', ['box' => ['class' => 'skull', 'w' => '3.8rem', 'h' => '1.6rem', 'd' => '3.8rem', 'style' => 'transform: translateY(.9rem);']])
            @include('site.partials.depth.box', ['box' => ['class' => 'board', 'w' => '7rem', 'h' => '.3rem', 'd' => '7rem', 'style' => 'transform: rotateY(45deg);']])
            <span class="tassel"></span>
        </div>
    </div>
    <span class="bq3d-ball bq3d-floaty" style="left: 4%; top: 36%; width: 1.3rem; height: 1.3rem;"></span>
    <span class="bq3d-ball bq3d-bobby" style="right: 6%; top: 40%; width: 1rem; height: 1rem; --delay: 1.5s; --bq3d-ball: color-mix(in srgb, var(--site-primary) 45%, #fff);"></span>
</div>
