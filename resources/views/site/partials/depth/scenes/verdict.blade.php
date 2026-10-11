{{-- "ميزان" — ميزان عدالة دهبي: الدراع بيتهز والكفتين معلقين لتحت. --}}
<div class="bq3d-scene" aria-hidden="true">
    <span class="bq3d-ring-glow" style="opacity: .6;"></span>
    <div class="bq3d-center" style="top: 54%;">
        <div class="bq3d-scales">
            @include('site.partials.depth.box', ['box' => ['class' => 'gold', 'w' => '7rem', 'h' => '.7rem', 'd' => '3.6rem', 'style' => 'transform: translateY(5.8rem);']])
            @include('site.partials.depth.box', ['box' => ['class' => 'gold', 'w' => '3.6rem', 'h' => '.9rem', 'd' => '2.2rem', 'style' => 'transform: translateY(5rem);']])
            @include('site.partials.depth.box', ['box' => ['class' => 'gold', 'w' => '.7rem', 'h' => '10.4rem', 'd' => '.7rem', 'style' => 'transform: translateY(-.4rem);']])
            <span class="bq3d-ball" style="left: -.9rem; top: -6.6rem; width: 1.8rem; height: 1.8rem; --bq3d-ball: #d9b45a;"></span>
            <div class="bq3d-beam">
                @include('site.partials.depth.box', ['box' => ['class' => 'gold', 'w' => '13rem', 'h' => '.45rem', 'd' => '.45rem']])
                @foreach ([-6.3, 6.3] as $x)
                    <div class="bq3d-hang" style="transform: translateX({{ $x }}rem);">
                        <div class="bq3d-hang-rot">
                            <span class="bq3d-chain" style="transform: rotateZ(28deg);"></span>
                            <span class="bq3d-chain" style="transform: rotateZ(-28deg);"></span>
                            <span class="bq3d-pan"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
