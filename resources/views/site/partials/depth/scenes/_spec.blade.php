{{-- رسمة من SceneLibrary (وصف بيانات بدل ملف Blade لكل رسمة). $sceneKey = مفتاح الرسمة. --}}
@php
    $spec = \App\Support\SceneLibrary::spec($sceneKey);
    [$tiltX, $tiltY] = $spec['tilt'] ?? [-14, -28];
    $mainAnim = match ($spec['anim'] ?? 'turn') { 'spin' => 'bq3d-spinny', 'float' => '', default => 'bq3d-turny' };
    $floaties = \App\Support\SceneLibrary::floaties($spec['floaties'] ?? 'balls');
@endphp
<div class="bq3d-scene" aria-hidden="true">
    @foreach ($floaties as $f)
        <span class="{{ $f['class'] }}" style="{{ $f['style'] }}"></span>
    @endforeach
    <div class="bq3d-center @if (($spec['anim'] ?? 'turn') === 'float') bq3d-bobby @endif" style="top: {{ $spec['top'] ?? '54%' }}; left: {{ $spec['left'] ?? '50%' }}; scale: {{ $spec['scale'] ?? 1 }};">
        <div class="bq3d-part" style="transform: rotateX({{ $tiltX + 10 }}deg);">
            <div class="bq3d-part {{ $mainAnim }}" style="--from: {{ $tiltY - 22 }}deg; --to: {{ $tiltY + 22 }}deg; transform: rotateY({{ $tiltY }}deg);">
                @foreach ($spec['parts'] as $part)
                    @include('site.partials.depth.scenes._part', ['part' => $part])
                @endforeach
            </div>
        </div>
    </div>
</div>
