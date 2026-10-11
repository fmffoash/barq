{{-- "قهوة" — فنجان 3D بيتمايل، بخار طالع منه، وحبوب بن عايمة حواليه. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center" style="top: 56%;">
        <div class="bq3d-cup">
            <div class="saucer"></div>
            <div class="base"></div>
            @for ($i = 0; $i < 18; $i++)
                @php $angle = $i * 20; @endphp
                <i class="strip" style="--a: {{ $angle }}deg; --l: {{ (int) round(62 + 30 * cos(deg2rad($angle + 40))) }}%;"></i>
            @endfor
            <div class="rim"></div>
            <div class="handle"></div>
        </div>
    </div>
    @foreach ([['38%', '0s'], ['48%', '1.2s'], ['58%', '2.4s']] as [$left, $delay])
        <span class="bq3d-steam" style="left: {{ $left }}; top: 2%; --delay: {{ $delay }};"></span>
    @endforeach
    @foreach ([['6%', '18%', '0s'], ['84%', '12%', '1.5s'], ['10%', '72%', '3s'], ['86%', '70%', '.8s']] as [$left, $top, $delay])
        <span class="bq3d-bean bq3d-floaty" style="left: {{ $left }}; top: {{ $top }}; --delay: {{ $delay }};"></span>
    @endforeach
</div>
