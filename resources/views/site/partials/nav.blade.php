{{--
    نافبار على شكل "pill" عائم — مشترك بين modern/gallery/bento (وأي تصميم تاني بيعمل
    @include('site.partials.nav')). الروابط وأسماءها جاهزة من SiteRenderer ($siteMeta['nav']،
    من غير أقسام الشات المضافة custom_*).
    - عرضه نفس عرض محتوى الصفحة (max-w-6xl) عشان الحواف تتطابق.
    - زرار "تواصل معنا" (CTA) على طول، فرابط قسم التواصل مش بيتكرر جوّه القايمة.
    - موبايل: قايمة ☰ من غير JS (site.partials.nav-menu)، واسم النشاط بيتقص بدل ما يلف سطرين.
--}}
@php
    $navLinks = collect($siteMeta['nav'] ?? []);
    $ctaKey = $contactAnchor ?? null;
    $menuLinks = $ctaKey ? $navLinks->reject(fn ($link) => $link['key'] === $ctaKey)->values() : $navLinks;
@endphp

@if ($navLinks->isNotEmpty())
    {{-- الحاوية بارتفاع ثابت (pt-3 + النافبار = 4.5rem تقريباً) وشفافة للدوس (pointer-events-none)
    — تصميم بهيرو صورة بعرض الشاشة (gallery) بيسحب الهيرو لفوق بنفس المقدار (-mt-[4.5rem])
    فالنافبار يطفو فوق الصورة بدل شريط فاضي بلون الخلفية فوقها. --}}
    <div class="pointer-events-none sticky top-0 z-30 px-4 pt-3 sm:px-8">
        <nav
            class="pointer-events-auto mx-auto flex max-w-6xl items-center justify-between gap-3 rounded-full border px-4 py-2.5 shadow-lg backdrop-blur-md sm:px-6"
            style="background-color: color-mix(in srgb, var(--site-surface) 88%, transparent); border-color: color-mix(in srgb, var(--site-text) 8%, transparent);"
        >
            <a href="#{{ $sections->first()['key'] }}" class="min-w-0 max-w-[55vw] truncate text-base font-extrabold sm:max-w-xs" style="color: var(--site-primary);">
                {{ $project->name }}
            </a>
            <ul class="hidden flex-wrap items-center gap-6 text-sm font-medium md:flex">
                @foreach ($menuLinks as $link)
                    <li>
                        <a href="#{{ $link['key'] }}" class="transition hover:opacity-70">{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>
            <div class="flex shrink-0 items-center gap-2">
                @if ($ctaKey)
                    <a
                        href="#{{ $ctaKey }}"
                        class="rounded-full px-4 py-2 text-xs font-bold shadow-sm transition hover:opacity-90 sm:text-sm"
                        style="background-color: var(--site-primary); color: var(--site-on-primary);"
                    >
                        تواصل معنا
                    </a>
                @endif
                @include('site.partials.nav-menu', ['menu' => ['links' => $menuLinks->all(), 'hideAt' => 'md']])
            </div>
        </nav>
    </div>
@endif
