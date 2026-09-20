{{--
    منتقي ألوان بصري بدل ما الأدمن يكتب JSON خام — كل لون له مربع ألوان (<input type="color">)
    ومربع نص هيكس متزامنين، والاتنين بيبنوا حقل colors_json المخفي تلقائي وقت أي تغيير
    (السكريبت المشترك في آخر الصفحة، شغّال بـ event delegation عشان يخدم أي عدد مجموعات
    في نفس الصفحة من غير تكرار كود). $colors: مصفوفة الألوان الحالية (أو null لقيم افتراضية).
--}}
@php
    $fieldName = $fieldName ?? 'colors_json';
    $defaults = ['primary' => '#f59e0b', 'background' => '#0b1220', 'surface' => '#111a2e', 'text' => '#f1f5f9', 'muted' => '#94a3b8'];
    $current = array_merge($defaults, array_filter($colors ?? []));
    $colorLabels = [
        'primary' => 'اللون الأساسي (أزرار وروابط)',
        'background' => 'خلفية الموقع',
        'surface' => 'خلفية الكروت',
        'text' => 'لون النص',
        'muted' => 'نص باهت (وصف/تفاصيل)',
    ];
@endphp

<div data-colors-group>
    <label class="mb-1.5 block text-sm font-medium text-slate-300">الألوان</label>

    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
        @foreach ($colorLabels as $key => $label)
            <div class="flex items-center gap-2.5 rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2">
                <input
                    type="color"
                    value="{{ $current[$key] }}"
                    data-color-key="{{ $key }}"
                    data-colors-sync
                    class="h-8 w-8 shrink-0 cursor-pointer rounded border-0 bg-transparent p-0"
                >
                <div class="flex min-w-0 flex-col">
                    <span class="truncate text-xs text-slate-400">{{ $label }}</span>
                    <input
                        type="text"
                        value="{{ $current[$key] }}"
                        dir="ltr"
                        data-color-key="{{ $key }}"
                        data-colors-sync
                        class="w-20 bg-transparent font-mono text-xs text-slate-200 outline-none"
                    >
                </div>
            </div>
        @endforeach
    </div>

    <input type="hidden" name="{{ $fieldName }}" data-colors-hidden value='{{ json_encode($current) }}'>
</div>

@once
    @push('scripts')
        <script>
            // منتقي الألوان — event delegation واحدة بتخدم أي عدد مجموعات ألوان في نفس
            // الصفحة (سواء نسخ قالب متعددة، أو تخصيص موقع مشروع واحد)، من غير تكرار سكريبت
            // لكل استخدام. اتنقلت هنا جوّه الجزئية نفسها (بدل ما تتكرر في كل صفحة بتستخدمها)
            // عشان أي صفحة تستخدم الجزئية دي تاخد السكريبت تلقائي معاها.
            document.addEventListener('input', function (event) {
                if (!event.target.matches('[data-colors-sync]')) {
                    return;
                }

                const group = event.target.closest('[data-colors-group]');
                if (!group) {
                    return;
                }

                const key = event.target.dataset.colorKey;
                group.querySelectorAll(`[data-color-key="${key}"]`).forEach((el) => {
                    if (el !== event.target) {
                        el.value = event.target.value;
                    }
                });

                const colors = {};
                group.querySelectorAll('input[type="color"][data-colors-sync]').forEach((el) => {
                    colors[el.dataset.colorKey] = el.value;
                });

                group.querySelector('[data-colors-hidden]').value = JSON.stringify(colors);
            });
        </script>
    @endpush
@endonce
