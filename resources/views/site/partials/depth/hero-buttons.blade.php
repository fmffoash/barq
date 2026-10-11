{{-- أزرار هيرو تصميمات الـ3D (زرار بارز بحافة تحت + زرار شفاف لأول قسم). بيورّث متغيرات page. --}}
@foreach ($linkItems as $item)
    @include('site.partials.link-button', ['btn' => ['item' => $item, 'class' => 'rounded-full px-8 py-4 text-base font-bold transition hover:translate-y-0.5', 'style' => $solidButton]])
@endforeach
@if ($linkItems->isEmpty() && ($siteMeta['heroCta'] ?? null))
    @include('site.partials.link-button', ['btn' => $siteMeta['heroCta'] + ['class' => 'rounded-full px-8 py-4 text-base font-bold transition hover:translate-y-0.5', 'style' => $solidButton, 'icon' => 'arrow', 'iconClass' => 'order-last h-5 w-5']])
@endif
@if (! empty($siteMeta['nav']))
    <a href="#{{ $siteMeta['nav'][0]['key'] }}" class="rounded-full px-8 py-4 text-base font-bold transition hover:opacity-75" style="{{ $ghostButton }}">{{ $siteMeta['nav'][0]['label'] }}</a>
@endif
