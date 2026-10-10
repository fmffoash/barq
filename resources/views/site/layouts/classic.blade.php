{{--
    "كلاسيك" — عمود واحد من كروت دائرية الحواف (هوية التصميم من الأول)، بس بقى صفحة كاملة:
    شريط علوي خفيف باسم النشاط (من غير نافبار "pill" — ده اللي بيميّزه عن modern)، هيرو بعنوان
    h1 وصورة جنب النص على الشاشات الكبيرة، معرض صور فسيفساء من غير خانات فاضية، كروت آراء
    حقيقية، وفوتر كامل. كل الكروت بنفس العرض (max-w-5xl).
--}}
@php
    $navLinks = $siteMeta['nav'] ?? [];
@endphp

@if ($sections->isNotEmpty())
    <header class="px-4 pt-5 sm:px-8">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4">
            <a href="#top" class="min-w-0 truncate text-lg font-extrabold" style="color: var(--site-primary);">{{ $project->name }}</a>
            <ul class="hidden items-center gap-6 text-sm font-medium md:flex" style="color: var(--site-muted);">
                @foreach ($navLinks as $link)
                    <li><a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
            @include('site.partials.nav-menu', ['menu' => ['links' => $navLinks, 'hideAt' => 'md']])
        </div>
    </header>
@endif

@forelse ($sections as $section)
    @include('site.partials.section', ['section' => $section])
@empty
    <div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p style="color: var(--site-muted);">الموقع لسه بيتجهّز، تعال زورنا تاني قريب.</p>
    </div>
@endforelse

@if ($sections->isNotEmpty())
    @include('site.partials.footer', ['footerTheme' => 'rounded'])
@endif
