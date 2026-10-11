{{-- "طوب" — برج طوب بيتبني: 8 طبقات متعاشقة، الطبقة اللي فوق بتنزل طوبة طوبة، وطوبة بارزة من النص. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center" style="top: 54%; scale: 1.15;">
        <div class="bq3d-turny" style="--from: 20deg; --to: 55deg; transform: rotateX(-12deg) rotateY(20deg);">
            <div class="bq3d-jenga" style="transform: rotateX(-12deg);">
                @for ($layer = 0; $layer < 8; $layer++)
                    <div class="bq3d-layer" style="transform: translateY({{ 5 - $layer * 1.45 }}rem) rotateY({{ $layer % 2 ? 90 : 0 }}deg);">
                        @foreach ([-2.45, 0, 2.45] as $k => $z)
                            @php
                                $sticksOut = $layer === 3 && $k === 1;
                                $top = $layer === 7;
                            @endphp
                            <div @class(['bq3d-brick-drop' => $top]) style="--delay: {{ $k * 0.35 }}s;">
                                @include('site.partials.depth.box', ['box' => ['class' => 'bq3d-brick', 'w' => '7.3rem', 'h' => '1.35rem', 'd' => '2.3rem', 'style' => 'transform: translateZ('.$z.'rem)'.($sticksOut ? ' translateX(2.6rem)' : '').'; --wood: '.(60 + (($layer * 3 + $k) % 4) * 8).'%;']])
                            </div>
                        @endforeach
                    </div>
                @endfor
            </div>
        </div>
    </div>
    <span class="bq3d-ball bq3d-floaty" style="right: 8%; top: 12%; width: 1.4rem; height: 1.4rem;"></span>
</div>
