{{-- "أثر" — بصمة رجل حيوان بارزة (طبقات بعمق) بتتمايل، وعضمة بتلف، وقلب عايم. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center" style="top: 56%; left: 46%;">
        <div class="bq3d-turny" style="--from: -35deg; --to: 25deg; transform: rotateX(-10deg) rotateY(-35deg);">
            <div class="bq3d-paw" style="transform: none;">
                @for ($k = 0; $k < 7; $k++)
                    <div class="layer" style="--z: {{ $k * 0.18 }}rem; --b: {{ $k === 6 ? 1 : 0.55 + $k * 0.05 }};">
                        <span class="pad"></span>
                        @foreach ([['-4.3rem', '-4.1rem', '-18deg'], ['-2.2rem', '-5.8rem', '-6deg'], ['.2rem', '-5.8rem', '6deg'], ['2.3rem', '-4.1rem', '18deg']] as [$left, $top, $rot])
                            <span class="toe" style="left: {{ $left }}; top: {{ $top }}; rotate: {{ $rot }};"></span>
                        @endforeach
                    </div>
                @endfor
            </div>
        </div>
    </div>
    <div class="bq3d-center" style="top: 80%; left: 80%;">
        <div class="bq3d-bone">
            @include('site.partials.depth.box', ['box' => ['class' => 'shaft', 'w' => '5.4rem', 'h' => '1.2rem', 'd' => '1.2rem']])
            @foreach ([[-3.6, -1.4], [-3.6, -.2], [1.6, -1.4], [1.6, -.2]] as [$x, $y])
                <span class="bq3d-ball" style="left: {{ $x }}rem; top: {{ $y - 0.4 }}rem;"></span>
            @endforeach
        </div>
    </div>
    <span class="bq3d-heart bq3d-floaty" style="left: 8%; top: 14%;"></span>
    <span class="bq3d-heart bq3d-bobby" style="right: 10%; top: 8%; scale: .6; --delay: 1.3s;"></span>
</div>
