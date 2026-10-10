{{--
    قايمة موبايل من غير أي JavaScript — <details>/<summary>: دوسة على ☰ بتفتح لوحة الروابط،
    ودوسة تانية (أو على أي مكان برّه اللوحة — طبقة شفافة جوّه الـsummary) بتقفلها. بتشتغل في
    التصدير الثابت كمان. كل الإعدادات في مصفوفة $menu (عشان مفيش متغير صفحة يتسرّب جوّه @include):
      'links'      => SiteRenderer siteMeta['nav'] ([key, label])
      'hideAt'     => 'sm' (الافتراضي) أو 'md' — الشاشة اللي القايمة الكاملة بتظهر من عندها
      'sharp'      => حواف حادة (split/bold/magazine/minimal)
      'panelStyle' => خلفية/لون اللوحة لو التصميم عايز غير الـsurface
--}}
@php
    $nmSharp = $menu['sharp'] ?? false;
    $nmLinks = $menu['links'] ?? [];
@endphp
@if (! empty($nmLinks))
    <details class="group relative {{ ($menu['hideAt'] ?? 'sm') === 'md' ? 'md:hidden' : 'sm:hidden' }}">
        <summary
            class="relative z-20 flex h-10 w-10 cursor-pointer items-center justify-center {{ $nmSharp ? '' : 'rounded-full' }}"
            style="background-color: color-mix(in srgb, var(--site-text) 8%, transparent);"
            aria-label="القايمة"
        >
            <span class="group-open:hidden">@include('site.partials.icon', ['name' => 'menu', 'class' => 'h-5 w-5'])</span>
            <span class="hidden group-open:block">@include('site.partials.icon', ['name' => 'close', 'class' => 'h-5 w-5'])</span>
            {{-- طبقة شفافة على الشاشة كلها وقت ما القايمة مفتوحة: دوسة برّه اللوحة = دوسة على
            الـsummary نفسه = قفل. --}}
            <span class="fixed inset-0 hidden cursor-default group-open:block" aria-hidden="true"></span>
        </summary>
        <ul
            class="absolute end-0 top-full z-30 mt-3 flex w-56 flex-col gap-1 p-2 text-base font-semibold shadow-2xl {{ $nmSharp ? '' : 'rounded-2xl' }}"
            style="{{ $menu['panelStyle'] ?? 'background-color: var(--site-surface); color: var(--site-text); box-shadow: 0 0 0 1px color-mix(in srgb, var(--site-text) 10%, transparent), 0 20px 40px -12px rgb(0 0 0 / .35);' }}"
        >
            @foreach ($nmLinks as $nmLink)
                <li>
                    <a href="#{{ $nmLink['key'] }}" class="block px-4 py-3 transition hover:opacity-70 {{ $nmSharp ? '' : 'rounded-xl' }}">{{ $nmLink['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </details>
@endif
