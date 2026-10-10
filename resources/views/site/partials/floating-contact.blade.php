{{--
    زرار واتساب/اتصال عائم ثابت في ركن الشاشة — أكبر مكسب للزوار اللي فاتحين الموقع من
    موبايل (لينك من واتساب/فيسبوك). $action من SiteRenderer::contactAction(): رابط التواصل لو
    واتساب/تليفون، وإلا رابط زرار الهيرو. مش بيترندر في المحرر المباشر (document.blade.php).
    $raised: فوق شريط معاينة القالب (templates.preview) عشان مايتغطّاش.
    الحلقة النابضة بتقف لو الزائر طالب تقليل الحركة (motion-safe).
--}}
@php
    $isWhatsapp = $action['type'] === 'whatsapp';
@endphp
<a
    href="{{ $action['href'] }}"
    @if (\App\Services\SiteRenderer::isExternal($action['href'])) target="_blank" rel="noopener" @endif
    aria-label="{{ $action['label'] }}"
    title="{{ $action['label'] }}"
    class="group fixed start-5 z-40 flex h-14 w-14 items-center justify-center rounded-full shadow-xl transition hover:scale-105 sm:start-6 {{ ($raised ?? false) ? 'bottom-24' : 'bottom-5 sm:bottom-6' }}"
    style="{{ $isWhatsapp ? 'background-color: #25D366; color: #ffffff;' : 'background-color: var(--site-primary); color: var(--site-on-primary);' }}"
>
    <span class="absolute inset-0 rounded-full opacity-40 motion-safe:animate-ping" style="background-color: inherit; animation-duration: 2.4s;" aria-hidden="true"></span>
    @include('site.partials.icon', ['name' => $isWhatsapp ? 'whatsapp' : 'phone', 'class' => 'relative h-7 w-7'])
</a>
