{{-- "طرد" — كومة صناديق شحن، والصغير بيقع عليها ويتنطط، وتيكت سعر عايم. --}}
<div class="bq3d-scene" aria-hidden="true">
    <div class="bq3d-center" style="top: 56%; scale: 1.3;">
        <div class="bq3d-turny" style="--from: -40deg; --to: -10deg; transform: rotateX(-12deg) rotateY(-40deg);">
            <div class="bq3d-parcels" style="transform: none;">
                @include('site.partials.depth.box', ['box' => ['class' => 'bq3d-parcel', 'w' => '8rem', 'h' => '5.5rem', 'd' => '6rem', 'style' => 'transform: translateY(2.2rem);']])
                @include('site.partials.depth.box', ['box' => ['class' => 'bq3d-parcel', 'w' => '5.5rem', 'h' => '4rem', 'd' => '4.5rem', 'style' => 'transform: translateY(-2.55rem) rotateY(18deg);']])
                <div class="bq3d-drop">
                    @include('site.partials.depth.box', ['box' => ['class' => 'bq3d-parcel', 'w' => '3.6rem', 'h' => '2.8rem', 'd' => '3.2rem', 'style' => 'transform: translateY(-5.95rem) rotateY(-14deg);']])
                </div>
            </div>
        </div>
    </div>
    <span class="bq3d-tag bq3d-floaty" style="right: 4%; top: 10%;"></span>
    <span class="bq3d-ball bq3d-bobby" style="left: 6%; top: 22%; width: 1.4rem; height: 1.4rem; --delay: 1s;"></span>
    <span class="bq3d-ball bq3d-floaty" style="left: 14%; bottom: 10%; width: .9rem; height: .9rem; --delay: 2s; --bq3d-ball: color-mix(in srgb, var(--site-primary) 45%, #fff);"></span>
</div>
