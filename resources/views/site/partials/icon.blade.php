{{--
    أيقونات SVG صغيرة مدمجة (صفر طلب خارجي، بتشتغل في التصدير الثابت من غير إنترنت) —
    @include('site.partials.icon', ['name' => 'whatsapp', 'class' => 'h-5 w-5']).
    زخرفية بس (aria-hidden) — أي معنى لازم يتكتب نص جنبها أو aria-label على الزرار نفسه.
    خط الرسم: currentColor (بتاخد لون النص اللي حواليها).
--}}
@php $class = $class ?? 'h-5 w-5'; @endphp
@switch($name)
    @case('whatsapp')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.75-.72 2-1.41.25-.69.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.5h-.01a9.4 9.4 0 0 1-4.8-1.31l-.34-.2-3.56.93.95-3.47-.22-.36a9.4 9.4 0 0 1-1.44-5.01c0-5.2 4.23-9.43 9.43-9.43a9.37 9.37 0 0 1 9.42 9.44c0 5.2-4.23 9.42-9.43 9.42m8.02-17.44A11.26 11.26 0 0 0 12.04.75C5.8.75.72 5.83.72 12.08c0 2 .52 3.95 1.51 5.66L.62 23.63l6.02-1.58a11.3 11.3 0 0 0 5.41 1.38h.01c6.24 0 11.32-5.08 11.33-11.33 0-3.02-1.18-5.87-3.32-8.01"/></svg>
        @break
    @case('phone')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        @break
    @case('mail')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
        @break
    @case('star')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.94 5.96 6.56.95-4.75 4.63 1.12 6.54L12 17.5l-5.87 3.08 1.12-6.54L2.5 9.41l6.56-.95z"/></svg>
        @break
    @case('quote')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 17.5c0-4.6 2.3-8.4 6.6-10.5l.9 1.5c-2.3 1.4-3.6 3.2-3.8 5.3H20a2 2 0 0 1 2 2V20a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2zM2 17.5C2 12.9 4.3 9.1 8.6 7l.9 1.5c-2.3 1.4-3.6 3.2-3.8 5.3H8a2 2 0 0 1 2 2V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z"/></svg>
        @break
    @case('menu')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        @break
    @case('close')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        @break
    @case('arrow')
        {{-- سهم "لقدّام" في RTL (بيشاور شمال). --}}
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        @break
    @case('arrow-up')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
        @break
    @case('check')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
        @break
    @case('sparkles')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 2.5l1.6 4.9 4.9 1.6-4.9 1.6L10 15.5l-1.6-4.9L3.5 9l4.9-1.6zM18 13l.9 2.6 2.6.9-2.6.9L18 20l-.9-2.6-2.6-.9 2.6-.9z"/></svg>
        @break
    @case('clock')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        @break
    @case('google')
        <svg class="{{ $class }}" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.6 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.76h3.57c2.08-1.92 3.31-4.74 3.31-8.09"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.76c-.98.66-2.24 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23"/><path fill="#FBBC05" d="M5.84 14.11a6.6 6.6 0 0 1 0-4.22V7.05H2.18a11 11 0 0 0 0 9.9z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15A10.6 10.6 0 0 0 12 1 11 11 0 0 0 2.18 7.05l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38"/></svg>
        @break
@endswitch
