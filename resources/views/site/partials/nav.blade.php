{{--
    نافبار على شكل "pill" عائم بدل الشريط المسطّح القديم — أقرب لشكل مواقع الوكالات/المنتجات
    الحديثة. مشترك بين modern وgallery. عنوان كل رابط بيتحدد من section_key نفسه: تسمية عربية
    معروفة لو الاسم شائع (hero/about/services/...)، وإلا الاسم نفسه بعد تنظيفه كخيار احتياطي.
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
    <div class="sticky top-4 z-20 px-4 sm:px-8">
        <nav
            class="mx-auto flex max-w-4xl items-center justify-between gap-4 rounded-full border border-white/5 px-5 py-3 shadow-lg backdrop-blur-md"
            style="background-color: color-mix(in srgb, var(--site-surface) 88%, transparent);"
        >
            <a href="#{{ $sections->first()['key'] }}" class="text-base font-extrabold" style="color: var(--site-primary);">
                {{ $project->name }}
            </a>
            <ul class="hidden flex-wrap items-center gap-6 text-sm font-medium sm:flex">
                @foreach ($navSections as $navSection)
                    <li>
                        <a href="#{{ $navSection['key'] }}" class="transition hover:opacity-70">
                            {{ $navLabel($navSection['key']) }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <a
                href="#{{ $sections->last()['key'] }}"
                class="shrink-0 rounded-full px-4 py-2 text-xs font-bold shadow-sm transition hover:opacity-90"
                style="background-color: var(--site-primary); color: var(--site-background);"
            >
                {{ $navLabel($sections->last()['key']) }}
            </a>
        </nav>
    </div>
@endif
