{{-- "أبراج" — مدينة أيزومتري صغيرة: أبراج بتتبني دور دور، وشجر، والأرضية بتلف ببطء. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center">
        <div class="bq3d-city">
            @foreach ([
                ['x' => '1.5rem', 'y' => '1.5rem', 'w' => '4.5rem', 'd' => '4.5rem', 'h' => '9rem', 'l' => 72, 'delay' => '0s'],
                ['x' => '7.5rem', 'y' => '1.5rem', 'w' => '3rem', 'd' => '3rem', 'h' => '6rem', 'l' => 55, 'delay' => '.6s'],
                ['x' => '7.5rem', 'y' => '6rem', 'w' => '6rem', 'd' => '3rem', 'h' => '4rem', 'l' => 85, 'delay' => '1.2s'],
                ['x' => '1.5rem', 'y' => '9rem', 'w' => '3rem', 'd' => '4.5rem', 'h' => '7rem', 'l' => 62, 'delay' => '1.8s'],
                ['x' => '9rem', 'y' => '10.5rem', 'w' => '3rem', 'd' => '3rem', 'h' => '11rem', 'l' => 48, 'delay' => '2.4s'],
            ] as $t)
                <div class="bq3d-tower" style="--x: {{ $t['x'] }}; --y: {{ $t['y'] }}; --w: {{ $t['w'] }}; --dd: {{ $t['d'] }}; --h: {{ $t['h'] }}; --l: {{ $t['l'] }}%; --delay: {{ $t['delay'] }};">
                    <i class="roof"></i><i class="s1"></i><i class="s2"></i><i class="s3"></i><i class="s4"></i>
                </div>
            @endforeach
            @foreach ([['6rem', '10rem'], ['13rem', '2rem'], ['5.6rem', '13.2rem'], ['13.4rem', '7.4rem']] as [$x, $y])
                <div class="bq3d-tree" style="--x: {{ $x }}; --y: {{ $y }};"><span class="bq3d-ball"></span></div>
            @endforeach
        </div>
    </div>
</div>
