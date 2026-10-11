{{-- أسطوانة 3D من شرايح (CSS: .bq3d-cyl). $cyl: r (رقم بالـrem)، h (مقاس CSS)، n (عدد الشرايح)، class، style،
     caps (true = غطا فوق وتحت). كل شريحة ليها --l (إضاءة حسب زاويتها) عشان تبان مدوّرة. --}}
@php
    $cylN = $cyl['n'] ?? 18;
    $cylR = $cyl['r'];
    $cylW = round(2 * M_PI * $cylR / $cylN * 1.04, 3);
@endphp
<div class="bq3d-cyl {{ $cyl['class'] ?? '' }}" style="--r: {{ $cylR }}rem; --h: {{ $cyl['h'] }}; --sw: {{ $cylW }}rem; {{ $cyl['style'] ?? '' }}">
    @for ($i = 0; $i < $cylN; $i++)
        @php $a = round($i * 360 / $cylN, 2); @endphp
        <i class="strip" style="--a: {{ $a }}deg; --l: {{ (int) round(60 + 32 * cos(deg2rad($a + 40))) }}%;"></i>
    @endfor
    @if ($cyl['caps'] ?? true)
        <i class="cap cap-top"></i><i class="cap cap-bottom"></i>
    @endif
</div>
