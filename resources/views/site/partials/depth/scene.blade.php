{{-- رسمة 3D بالمفتاح: ملف Blade لوحدها (الـ20 الأولى) أو وصف بيانات من SceneLibrary. --}}
@if (\App\Support\SceneLibrary::has($sceneKey))
    @include('site.partials.depth.scenes._spec', ['sceneKey' => $sceneKey])
@elseif (view()->exists('site.partials.depth.scenes.'.$sceneKey))
    @include('site.partials.depth.scenes.'.$sceneKey)
@endif
