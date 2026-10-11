{{-- "كاميرا" — كاميرا بعدسة بارزة، ريش الفتحة جوه العدسة بتلف، وفلاش بيلمع كل شوية. --}}
<div class="bq3d-scene" aria-hidden="true">
    <span class="bq3d-flash" style="left: 10%; top: 8%;"></span>
    <div class="bq3d-center" style="top: 54%;">
        <div class="bq3d-turny" style="--from: -38deg; --to: 18deg; transform: rotateX(-12deg) rotateY(-38deg);">
            <div class="bq3d-camera" style="transform: rotateX(-4deg);">
                @include('site.partials.depth.box', ['box' => ['class' => 'body', 'w' => '11rem', 'h' => '7rem', 'd' => '4rem']])
                @include('site.partials.depth.box', ['box' => ['class' => 'grip', 'w' => '2rem', 'h' => '7.2rem', 'd' => '4.6rem', 'style' => 'transform: translateX(-4.7rem);']])
                @include('site.partials.depth.box', ['box' => ['class' => 'body', 'w' => '3.6rem', 'h' => '1.4rem', 'd' => '3rem', 'style' => 'transform: translate3d(1.8rem, -4.1rem, 0);']])
                @include('site.partials.depth.cylinder', ['cyl' => ['class' => 'lens', 'r' => 2.9, 'h' => '3.4rem', 'n' => 20, 'style' => 'transform: translateZ(3.6rem) rotateX(90deg);']])
                <span class="bq3d-glass" style="transform: translateZ(5.32rem);"></span>
                <span class="bq3d-iris" style="--z: 5.36rem; transform: translateZ(5.36rem);"></span>
            </div>
        </div>
    </div>
    <span class="bq3d-ball bq3d-bobby" style="right: 6%; bottom: 12%; width: 1.3rem; height: 1.3rem;"></span>
</div>
