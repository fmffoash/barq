{{--
    زرار رابط موحّد لكل التصميمات. كل الإعدادات جوّه مصفوفة واحدة $btn — @include بيورّث كل
    متغيرات الصفحة اللي حواليه (زي $item من آخر @foreach)، فأي اسم عام هنا كان ممكن "يتسرّب"
    من غير قصد (حصل فعلاً: زرار احتياطي طلع بنص "الوصف المختصر").
      ['item' => $item]                        خانة رابط حقيقية من SiteRenderer (data-slot عليها)
      ['href' => '#contact', 'label' => '...'] زرار احتياطي (من غير data-slot)
      'class' / 'style'                        الشكل من التصميم نفسه
      'icon' (اختياري)                         افتراضياً واتساب/تليفون/إيميل حسب نوع الرابط، false = من غير
      'iconClass' (اختياري)
    بيضمن: target="_blank" لروابط http(s) بس (tel:/mailto:/#قسم مايفتحوش تاب فاضي)، وdata-slot
    على الخانة الحقيقية بس (المحرر المباشر — ترتيب حر).
--}}
@php
    $lbItem = $btn['item'] ?? null;
    $lbHref = $lbItem ? trim((string) $lbItem['value']) : (string) ($btn['href'] ?? '#');
    $lbLabel = $lbItem ? ($lbItem['label'] ?? $lbItem['slot']->label()) : (string) ($btn['label'] ?? '');
    $lbType = $lbItem['linkType'] ?? \App\Services\SiteRenderer::linkType($lbHref);
    $lbIcon = array_key_exists('icon', $btn)
        ? $btn['icon']
        : match ($lbType) { 'whatsapp' => 'whatsapp', 'phone' => 'phone', 'email' => 'mail', default => null };
@endphp
<a
    @if ($lbItem) data-slot="{{ $lbItem['slot']->key }}" @endif
    href="{{ $lbHref }}"
    @if (\App\Services\SiteRenderer::isExternal($lbHref)) target="_blank" rel="noopener" @endif
    class="inline-flex items-center justify-center gap-2 {{ $btn['class'] ?? '' }}"
    @if (filled($btn['style'] ?? null)) style="{{ $btn['style'] }}" @endif
>@if ($lbIcon)@include('site.partials.icon', ['name' => $lbIcon, 'class' => $btn['iconClass'] ?? 'h-5 w-5 shrink-0'])@endif<span>{{ $lbLabel }}</span></a>
