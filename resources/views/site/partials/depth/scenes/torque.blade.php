{{-- "عجلة" — كاوتش برنجة بيلف، وترس ورا بيلف عكسه، وخطوط سرعة. --}}
<div class="bq3d-scene" aria-hidden="true">
    @foreach ([['22%', '8rem', '0s'], ['44%', '5.5rem', '.5s'], ['66%', '7rem', '1s'], ['82%', '4.5rem', '.3s']] as [$top, $width, $delay])
        <span class="bq3d-speed" style="top: {{ $top }}; right: -1rem; width: {{ $width }}; --delay: {{ $delay }};"></span>
    @endforeach
    <div class="bq3d-center">
        <div class="bq3d-garage">
            <div style="position: absolute; transform: translate(-4.2rem, -4.4rem) translateZ(-3rem);">
                <div class="bq3d-gear">
                    @for ($k = 0; $k < 4; $k++)
                        <i @class(['face' => $k === 3]) style="--z: {{ $k * 0.25 }}rem;"></i>
                    @endfor
                </div>
            </div>
            <div class="bq3d-wheel">
                @for ($k = 0; $k < 10; $k++)
                    <i @class(['face' => $k === 0 || $k === 9]) style="--z: {{ ($k - 4.5) * 0.3 }}rem;"></i>
                @endfor
            </div>
        </div>
    </div>
</div>
