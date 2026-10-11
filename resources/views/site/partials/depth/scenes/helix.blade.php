{{-- "حلزون" — شريط DNA بيلف، وحواليه دواير نبض وصليب طبي عايم. --}}
<div class="bq3d-scene" aria-hidden="true">
    @foreach (['0s', '1s', '2s'] as $delay)
        <span class="bq3d-pulse" style="--delay: {{ $delay }};"></span>
    @endforeach
    <div class="bq3d-center">
        <div class="bq3d-helix">
            <div class="bq3d-helix-spin">
                @for ($i = 0; $i < 14; $i++)
                    <div class="bq3d-rung" style="--y: {{ ($i - 6.5) * 1.15 }}rem; --a: {{ $i * 28 }}deg;">
                        <span class="bar"></span>
                        <b class="dot dot--a"><span class="bq3d-ball"></span></b>
                        <b class="dot dot--b"><span class="bq3d-ball"></span></b>
                    </div>
                @endfor
            </div>
        </div>
    </div>
    <span class="bq3d-cross bq3d-bobby" style="left: 2%; top: 12%;"></span>
    <span class="bq3d-ball bq3d-floaty" style="right: 6%; bottom: 14%; width: 2.4rem; height: 2.4rem; --delay: 1s;"></span>
    <span class="bq3d-ball bq3d-bobby" style="left: 12%; bottom: 8%; width: 1.3rem; height: 1.3rem; --delay: 2s; --bq3d-ball: color-mix(in srgb, var(--site-primary) 45%, #fff);"></span>
</div>
