{{--
    زوايا الإطار المميّزة في تصميم "فريمد" (زخرفة بس، aria-hidden) — 4 زوايا بلون الأساسي فوق حد
    الإطار الرفيع. الحاوية لازم تبقى position:relative. $corner = مقاس الزاوية (كلاس Tailwind حرفي).
--}}
@php $fcSize = $corner ?? 'h-6 w-6'; @endphp
<span class="pointer-events-none absolute -right-px -top-px {{ $fcSize }} border-r-2 border-t-2" style="border-color: var(--site-primary);" aria-hidden="true"></span>
<span class="pointer-events-none absolute -left-px -top-px {{ $fcSize }} border-l-2 border-t-2" style="border-color: var(--site-primary);" aria-hidden="true"></span>
<span class="pointer-events-none absolute -bottom-px -right-px {{ $fcSize }} border-b-2 border-r-2" style="border-color: var(--site-primary);" aria-hidden="true"></span>
<span class="pointer-events-none absolute -bottom-px -left-px {{ $fcSize }} border-b-2 border-l-2" style="border-color: var(--site-primary);" aria-hidden="true"></span>
