{{-- صندوق 3D بـ6 أوجه (CSS في app.css: .bq3d-box). $box: w/h/d (مقاسات CSS)، class، style. --}}
<div class="bq3d-box {{ $box['class'] ?? '' }}" style="--w: {{ $box['w'] }}; --h: {{ $box['h'] }}; --d: {{ $box['d'] }}; {{ $box['style'] ?? '' }}">
    <i class="f-front"></i><i class="f-back"></i><i class="f-right"></i><i class="f-left"></i><i class="f-top"></i><i class="f-bottom"></i>
</div>
