{{-- رسمة 3D بالمفتاح: صور 3D جاهزة (IMAGE_SCENES، الأولوية)، أو ملف Blade (الـ20 الأولى)، أو وصف بيانات من SceneLibrary. --}}
@if (isset(\App\Support\SceneLibrary::IMAGE_SCENES[$sceneKey]))
    @include('site.partials.depth.scenes._image', ['sceneKey' => $sceneKey])
@elseif (\App\Support\SceneLibrary::has($sceneKey))
    @include('site.partials.depth.scenes._spec', ['sceneKey' => $sceneKey])
@elseif (view()->exists('site.partials.depth.scenes.'.$sceneKey))
    @include('site.partials.depth.scenes.'.$sceneKey)
@endif
