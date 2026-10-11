{{-- "كرة أرضية" — كرة من خطوط طول وعرض بتلف، وطيارة بتدور حواليها في مدار مايل. --}}
<div class="bq3d-scene" aria-hidden="true">
    <span class="bq3d-globe-core"></span>
    <div class="bq3d-center">
        <div class="bq3d-globe" style="transform: rotateZ(-18deg);">
            <div class="bq3d-globe-spin" style="position: absolute;">
                @for ($i = 0; $i < 6; $i++)
                    <i class="meridian" style="--a: {{ $i * 30 }}deg;"></i>
                @endfor
                @foreach ([[0, 11], [-2.75, 9.5], [2.75, 9.5], [-4.6, 5.6], [4.6, 5.6]] as [$y, $size])
                    <i class="parallel" style="--y: {{ $y }}rem; --s: {{ $size }}rem;"></i>
                @endforeach
            </div>
        </div>
        <div class="bq3d-orbit">
            <span class="bq3d-orbit-ring"></span>
            <div class="bq3d-orbit-spin">
                <span class="bq3d-plane"></span>
            </div>
        </div>
    </div>
    <span class="bq3d-ball bq3d-floaty" style="left: 6%; top: 10%; width: 1.2rem; height: 1.2rem;"></span>
    <span class="bq3d-ball bq3d-bobby" style="right: 8%; bottom: 12%; width: 1.6rem; height: 1.6rem; --delay: 1s; --bq3d-ball: color-mix(in srgb, var(--site-primary) 45%, #fff);"></span>
</div>
