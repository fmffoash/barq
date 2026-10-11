{{-- "مكعب" — مكعب كود (أوجهه نوافذ فيها سطور كود) بيلف، و3 مكعبات صغيرة بتشقلب حواليه. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center">
        <div class="bq3d-cube-spin" style="position: absolute; transform: rotateX(-24deg) rotateY(35deg);">
            @include('site.partials.depth.box', ['box' => ['class' => 'bq3d-codecube', 'w' => '8.5rem', 'h' => '8.5rem', 'd' => '8.5rem']])
        </div>
    </div>
    @foreach ([['12%', '14%', '2.4rem', '0s'], ['82%', '22%', '1.8rem', '1.5s'], ['76%', '80%', '2.8rem', '.8s'], ['14%', '78%', '1.5rem', '2.2s']] as [$left, $top, $size, $delay])
        <div class="bq3d-floaty" style="position: absolute; left: {{ $left }}; top: {{ $top }}; --delay: {{ $delay }};">
            <div class="bq3d-cubelet-spin" style="position: absolute; --delay: {{ $delay }};">
                @include('site.partials.depth.box', ['box' => ['class' => 'bq3d-cubelet', 'w' => $size, 'h' => $size, 'd' => $size]])
            </div>
        </div>
    @endforeach
</div>
