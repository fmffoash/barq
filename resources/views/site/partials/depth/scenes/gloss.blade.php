{{-- "لمعة" — 3 زجاجات تجميل شفافة بأغطية دهبي بتلف، وقطرات لامعة عايمة، وهالة ضوء دايرة. --}}
<div class="bq3d-scene" aria-hidden="true">
    <span class="bq3d-ring-glow"></span>
    @foreach ([
        ['left' => '50%', 'top' => '50%', 'w' => '5.2rem', 'h' => '8.5rem', 'd' => '3.4rem', 'cap' => '2.2rem', 'from' => '-30deg', 'to' => '30deg', 'delay' => '0s'],
        ['left' => '22%', 'top' => '62%', 'w' => '3.6rem', 'h' => '5.4rem', 'd' => '3.6rem', 'cap' => '1.6rem', 'from' => '20deg', 'to' => '-35deg', 'delay' => '1.2s'],
        ['left' => '79%', 'top' => '60%', 'w' => '3rem', 'h' => '6.4rem', 'd' => '2.4rem', 'cap' => '1.8rem', 'from' => '-15deg', 'to' => '40deg', 'delay' => '2.1s'],
    ] as $b)
        <div class="bq3d-bottle bq3d-bobby" style="left: {{ $b['left'] }}; top: {{ $b['top'] }}; --delay: {{ $b['delay'] }};">
            <div class="bq3d-turny" style="--from: {{ $b['from'] }}; --to: {{ $b['to'] }}; --delay: {{ $b['delay'] }}; transform: rotateX(-12deg) rotateY({{ $b['from'] }});">
                @include('site.partials.depth.box', ['box' => ['class' => 'glass', 'w' => $b['w'], 'h' => $b['h'], 'd' => $b['d']]])
                @include('site.partials.depth.box', ['box' => ['class' => 'cap', 'w' => 'calc('.$b['w'].' * .5)', 'h' => $b['cap'], 'd' => 'calc('.$b['d'].' * .5)', 'style' => 'transform: translateY(calc(('.$b['h'].' + '.$b['cap'].') / -2));']])
            </div>
        </div>
    @endforeach
    @foreach ([['8%', '14%', '1.6rem', '0s'], ['88%', '18%', '1.1rem', '1s'], ['70%', '86%', '1.4rem', '2s'], ['30%', '8%', '.9rem', '1.6s']] as [$left, $top, $size, $delay])
        <span class="bq3d-ball bq3d-floaty" style="left: {{ $left }}; top: {{ $top }}; width: {{ $size }}; height: {{ $size }}; --delay: {{ $delay }}; --bq3d-ball: color-mix(in srgb, var(--site-primary) 60%, #fff);"></span>
    @endforeach
</div>
