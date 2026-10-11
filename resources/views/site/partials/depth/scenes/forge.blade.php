{{-- "حديد" — دمبل 3D بيتشال ويلف، وخطوط سرعة بتجري ورا. --}}
<div class="bq3d-scene" aria-hidden="true">
    @foreach ([['18%', '9rem', '0s'], ['34%', '6rem', '.6s'], ['70%', '7.5rem', '1.1s'], ['84%', '5rem', '.3s']] as [$top, $width, $delay])
        <span class="bq3d-speed" style="top: {{ $top }}; right: 0; width: {{ $width }}; --delay: {{ $delay }};"></span>
    @endforeach
    <div class="bq3d-center">
        <div class="bq3d-dumbbell">
            @include('site.partials.depth.box', ['box' => ['class' => 'bar', 'w' => '16rem', 'h' => '1rem', 'd' => '1rem']])
            @foreach ([-1, 1] as $side)
                @foreach ([['4.6rem', '7rem', 80], ['5.6rem', '6rem', 70], ['6.5rem', '4.8rem', 62]] as [$x, $size, $light])
                    @for ($k = 0; $k < 8; $k++)
                        <i class="disc" style="--x: calc({{ $side }} * ({{ $x }} + {{ $k * 0.12 }}rem)); --s: {{ $size }}; --l: {{ $light }}%;"></i>
                    @endfor
                @endforeach
            @endforeach
        </div>
    </div>
</div>
