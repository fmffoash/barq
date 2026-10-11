{{-- "عطر" — زجاجة عطر بأوجه (منشور 8 أضلاع) برقبة دهبي وغطا كروي، ورذاذ طالع منها. --}}
<div class="bq3d-scene" aria-hidden="true">
    <span class="bq3d-ring-glow" style="opacity: .7;"></span>
    @foreach (['0s', '.8s', '1.6s', '2.3s'] as $delay)
        <span class="bq3d-mist" style="left: 40%; top: 22%; --delay: {{ $delay }};"></span>
    @endforeach
    <div class="bq3d-center" style="top: 58%;">
        <div class="bq3d-turny" style="--from: -35deg; --to: 35deg; transform: rotateX(-12deg) rotateY(-35deg);">
            <div class="bq3d-perfume" style="transform: rotateX(-4deg);">
                @include('site.partials.depth.cylinder', ['cyl' => ['class' => 'flask', 'r' => 3.4, 'h' => '6.6rem', 'n' => 8]])
                @include('site.partials.depth.cylinder', ['cyl' => ['class' => 'neck', 'r' => 0.9, 'h' => '1.1rem', 'n' => 12, 'style' => 'transform: translateY(-3.85rem);']])
                <span class="bq3d-ball" style="left: -1.5rem; top: -7.3rem; width: 3rem; height: 3rem; --bq3d-ball: color-mix(in srgb, var(--site-primary) 50%, #f6e7b0);"></span>
            </div>
        </div>
    </div>
    @foreach ([['8%', '70%', '1rem', '0s'], ['84%', '14%', '1.3rem', '1.2s']] as [$left, $top, $size, $delay])
        <span class="bq3d-ball bq3d-floaty" style="left: {{ $left }}; top: {{ $top }}; width: {{ $size }}; height: {{ $size }}; --delay: {{ $delay }}; --bq3d-ball: color-mix(in srgb, var(--site-primary) 60%, #fff);"></span>
    @endforeach
</div>
