{{-- "تورتة" — تورتة 3 أدوار بطبقات كريمة، وشمعة نارها بترقص، على طبق. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center" style="top: 54%;">
        <div class="bq3d-turny" style="--from: -30deg; --to: 20deg; transform: rotateX(-14deg) rotateY(-30deg);">
            <div class="bq3d-cake" style="transform: rotateX(-6deg);">
                <span class="plate"></span>
                @foreach ([[5.2, '3.2rem', 3], [3.9, '2.8rem', 0], [2.6, '2.4rem', -2.6]] as [$r, $h, $y])
                    @include('site.partials.depth.cylinder', ['cyl' => ['class' => 'tier', 'r' => $r, 'h' => $h, 'n' => 22, 'style' => 'transform: translateY('.$y.'rem);']])
                    @include('site.partials.depth.cylinder', ['cyl' => ['class' => 'cream', 'r' => $r + 0.08, 'h' => '.55rem', 'n' => 22, 'caps' => false, 'style' => 'transform: translateY(calc('.$y.'rem - '.$h.' / 2 + .3rem));']])
                @endforeach
                <div class="bq3d-candle">
                    @include('site.partials.depth.box', ['box' => ['class' => 'stick', 'w' => '.55rem', 'h' => '2.6rem', 'd' => '.55rem', 'style' => 'transform: translateY(-5.1rem);']])
                    <span class="bq3d-flame" style="top: -7.8rem;"></span>
                </div>
            </div>
        </div>
    </div>
    <span class="bq3d-ball bq3d-floaty" style="left: 6%; top: 16%; width: 1.3rem; height: 1.3rem; --bq3d-ball: #f472b6;"></span>
    <span class="bq3d-ball bq3d-bobby" style="right: 8%; top: 26%; width: 1rem; height: 1rem; --delay: 1s; --bq3d-ball: #fbbf24;"></span>
</div>
