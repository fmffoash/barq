{{-- رسمة 3D من صور جاهزة (Microsoft Fluent 3D Emoji — رخصة MIT، public/images/scenes3d) بدل الرسم بالكود
     (2026-10-13، فؤاد: الأشكال المرسومة بالكود "تعبانة"). صورة رئيسية كبيرة بتعوم وبتميل لما الماوس يقف عليها،
     وصور صغيرة حواليها على أعماق مختلفة (البعيدة مغبّشة شوية = عمق). $sceneKey = مفتاح من SceneLibrary::IMAGE_SCENES. --}}
@php $img = \App\Support\SceneLibrary::IMAGE_SCENES[$sceneKey]; @endphp
<div class="bq3d-scene bq3d-imgscene" aria-hidden="true">
    <span class="bq3d-img-glow"></span>
    <span class="bq3d-img-orbit"></span>
    <span class="bq3d-img-shadow"></span>
    @foreach ($img['satellites'] as $i => [$file, $size, $left, $top, $depth])
        <img src="/images/scenes3d/{{ $file }}" alt="" loading="lazy" class="bq3d-img-sat" style="width: {{ $size }}rem; left: {{ $left }}; top: {{ $top }}; --depth: {{ $depth }}; --delay: {{ $i * 0.9 }}s;">
    @endforeach
    <div class="bq3d-img-main">
        <img src="/images/scenes3d/{{ $img['main'] }}" alt="" class="bq3d-img-hero">
    </div>
</div>
