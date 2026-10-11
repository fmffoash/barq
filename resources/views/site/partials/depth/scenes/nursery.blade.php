{{-- "مكعبات لعب" — 3 مكعبات حروف ملونة متكومة، وكورة بحر بتتنطط جنبهم. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center" style="top: 58%; left: 44%;">
        <div class="bq3d-turny" style="--from: -40deg; --to: -10deg; transform: rotateX(-12deg) rotateY(-40deg);">
            <div class="bq3d-toys" style="transform: rotateX(-8deg);">
                @foreach ([['أ', '#f43f5e', 'translate3d(-2.3rem, 2rem, 0) rotateY(8deg)'], ['ب', '#3b82f6', 'translate3d(2.3rem, 2rem, .4rem) rotateY(-10deg)'], ['ت', '#facc15', 'translate3d(0, -2.15rem, 0) rotateY(24deg)']] as [$letter, $color, $t])
                    <div class="bq3d-box bq3d-toy" style="--w: 4.2rem; --h: 4.2rem; --d: 4.2rem; --toy: {{ $color }}; transform: {{ $t }};">
                        <i class="f-front">{{ $letter }}</i><i class="f-back">{{ $letter }}</i><i class="f-right">{{ $letter }}</i><i class="f-left">{{ $letter }}</i><i class="f-top"></i><i class="f-bottom"></i>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <span class="bq3d-beachball" style="right: 2%; bottom: 14%;"></span>
    <span class="bq3d-ball bq3d-floaty" style="left: 6%; top: 12%; width: 1.3rem; height: 1.3rem; --bq3d-ball: #a3e635;"></span>
</div>
