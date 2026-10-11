{{-- "احتفال" — علبة هدية بشريطة بتلف، وبلالين طالعة، وقصاقيص ملونة نازلة. --}}
<div class="bq3d-scene" aria-hidden="true">
    @foreach ([['14%', '0s', '#f43f5e'], ['30%', '1.1s', '#facc15'], ['52%', '2.2s', '#22d3ee'], ['70%', '.6s', '#a3e635'], ['86%', '1.7s', '#c084fc'], ['42%', '3s', '#fb923c']] as [$left, $delay, $color])
        <span class="bq3d-confetti" style="left: {{ $left }}; top: 0; --delay: {{ $delay }}; --c: {{ $color }};"></span>
    @endforeach
    <span class="bq3d-balloon" style="left: 4%; top: 6%; --bq3d-ball: var(--site-primary);"></span>
    <span class="bq3d-balloon" style="left: 20%; top: 0; --delay: 1.4s; --bq3d-ball: color-mix(in srgb, var(--site-primary) 45%, #fff); scale: .8;"></span>
    <span class="bq3d-balloon" style="right: 6%; top: 10%; --delay: .7s; --bq3d-ball: #f59e0b; scale: .9;"></span>
    <div class="bq3d-center" style="top: 62%;">
        <div class="bq3d-turny" style="--from: -40deg; --to: 30deg; transform: rotateX(-14deg) rotateY(-40deg);">
            <div class="bq3d-gift">
                @include('site.partials.depth.box', ['box' => ['class' => 'wrap', 'w' => '7rem', 'h' => '5.6rem', 'd' => '7rem', 'style' => 'transform: translateY(.8rem);']])
                @include('site.partials.depth.box', ['box' => ['class' => 'lid', 'w' => '7.8rem', 'h' => '1.4rem', 'd' => '7.8rem', 'style' => 'transform: translateY(-2.6rem);']])
                <div style="position: absolute; transform: translateY(-3.6rem);">
                    <span class="bq3d-bow" style="transform: rotateZ(-25deg);"></span>
                    <span class="bq3d-bow" style="transform: rotateY(180deg) rotateZ(-25deg);"></span>
                </div>
            </div>
        </div>
    </div>
</div>
