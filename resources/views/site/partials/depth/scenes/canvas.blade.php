{{-- "لوحات" — 4 لوحات فنية متعلقة في الهوا على أعماق مختلفة، بتلف كأنك ماشي في معرض. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center">
        <div class="bq3d-frames">
            @foreach ([
                ['w' => '7rem', 'h' => '8.8rem', 'x' => '-3.4rem', 'y' => '-1rem', 'z' => '-3rem', 'art' => 'linear-gradient(160deg, var(--site-primary), color-mix(in srgb, var(--site-primary) 30%, #111) 70%), radial-gradient(circle at 70% 30%, #fde68a 0 18%, transparent 19%)'],
                ['w' => '6rem', 'h' => '6rem', 'x' => '3.6rem', 'y' => '-3.4rem', 'z' => '1rem', 'art' => 'radial-gradient(circle at 50% 55%, color-mix(in srgb, var(--site-primary) 70%, #fff) 0 30%, transparent 31%), linear-gradient(to bottom, #fef3c7, #fcd34d)'],
                ['w' => '6.6rem', 'h' => '4.6rem', 'x' => '3rem', 'y' => '3.4rem', 'z' => '3.6rem', 'art' => 'linear-gradient(to top, color-mix(in srgb, var(--site-primary) 60%, #0f172a) 0 35%, transparent 35%), conic-gradient(from 200deg at 30% 70%, #60a5fa, #a78bfa, #f472b6, #60a5fa)'],
                ['w' => '4.4rem', 'h' => '5.6rem', 'x' => '-4.2rem', 'y' => '4.2rem', 'z' => '5rem', 'art' => 'repeating-linear-gradient(45deg, var(--site-primary) 0 .5rem, color-mix(in srgb, var(--site-primary) 30%, #fff) .5rem 1rem)'],
            ] as $f)
                <span class="bq3d-art" style="--w: {{ $f['w'] }}; --h: {{ $f['h'] }}; --x: {{ $f['x'] }}; --y: {{ $f['y'] }}; --z: {{ $f['z'] }}; --art: {{ $f['art'] }};"></span>
            @endforeach
        </div>
    </div>
    <span class="bq3d-ball bq3d-bobby" style="right: 4%; top: 6%; width: 1.4rem; height: 1.4rem;"></span>
</div>
