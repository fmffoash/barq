{{--
    نافبار مشترك بين تصميمي modern وgallery — روابط تنقل سريعة لكل قسم (غير الهيرو، لأنه أول
    حاجة الزائر بيشوفها أصلاً). عنوان كل رابط بيتحدد من section_key نفسه: تسمية عربية معروفة
    لو الاسم شائع (hero/about/services/...)، وإلا الاسم نفسه بعد تنظيفه كخيار احتياطي — القالب
    ممكن يستخدم أي اسم قسم حر، فمفيش افتراض إن التسميات دي هتغطي كل الحالات.
--}}
@php
    $navSections = $sections->skip(1);

    $navLabel = fn (string $key) => match ($key) {
        'hero' => 'الرئيسية',
        'about' => 'من نحن',
        'services', 'menu' => 'خدماتنا',
        'gallery' => 'معرض الصور',
        'testimonials' => 'آراء العملاء',
        'pricing' => 'الأسعار',
        'faq' => 'الأسئلة الشائعة',
        'contact', 'cta' => 'تواصل معنا',
        default => \Illuminate\Support\Str::of($key)->replace(['_', '-'], ' ')->trim()->title()->toString(),
    };
@endphp

@if ($navSections->isNotEmpty())
    <nav class="sticky top-0 z-10 border-b border-white/5 backdrop-blur" style="background-color: color-mix(in srgb, var(--site-background) 85%, transparent);">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4 sm:px-10">
            <a href="#{{ $sections->first()['key'] }}" class="text-lg font-bold" style="color: var(--site-primary);">
                {{ $project->name }}
            </a>
            <ul class="flex flex-wrap items-center gap-5 text-sm font-medium">
                @foreach ($navSections as $navSection)
                    <li>
                        <a href="#{{ $navSection['key'] }}" class="transition hover:opacity-75">
                            {{ $navLabel($navSection['key']) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
@endif
