{{--
    منتقي ترتيب/ظهور الأقسام — بديل عن كتابة sections_json خام كنص، بسحب وإفلات حقيقي
    (HTML5 Drag & Drop، بصفر مكتبة خارجية) بدل ما الأدمن يكتب مصفوفة JSON يدوي. كل قسم صف
    فيه تشيك بوكس (يظهر/يتخفي) ومقبض سحب (⠿) لإعادة الترتيب. أي تغيير (سحب أو تشييك) بيعيد
    بناء حقل مخفي بمصفوفة مفاتيح الأقسام المُفعّلة بالترتيب الحالي بالظبط.

    Props:
    - $sectionKeys: كل مفاتيح الأقسام الممكنة لهذا القالب (Collection أو array).
    - $currentOrder: مصفوفة المفاتيح المُفعّلة بترتيبها الحالي، أو null (يعني كل الأقسام
      بترتيبها الطبيعي — أول ما ظهرت في خانات القالب).
    - $fieldName: اسم الحقل المخفي الناتج (مثلاً "sections_json" أو "sections_override").
--}}
@php
    $allKeys = collect($sectionKeys ?? [])->values();
    $included = collect($currentOrder ?? $allKeys->all())->filter(fn ($key) => $allKeys->contains($key))->values();
    $excluded = $allKeys->diff($included)->values();
    $orderedRows = $included->merge($excluded);

    $friendlyLabels = [
        'hero' => 'هيرو (أول قسم في الصفحة)',
        'about' => 'من نحن',
        'services' => 'الخدمات',
        'gallery' => 'معرض الصور',
        'testimonials' => 'آراء العملاء',
        'contact' => 'تواصل',
    ];
@endphp

<div data-section-order-group data-field-name="{{ $fieldName }}">
    <ul class="space-y-1.5" data-section-order-list>
        @foreach ($orderedRows as $key)
            <li
                draggable="true"
                data-section-key="{{ $key }}"
                class="flex cursor-move items-center gap-2.5 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 transition data-[dragging=true]:opacity-40"
            >
                <span class="select-none text-slate-600" aria-hidden="true">⠿</span>
                <label class="flex flex-1 items-center gap-2 text-sm text-slate-200">
                    <input
                        type="checkbox"
                        data-section-toggle
                        @checked($included->contains($key))
                        class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-400 focus:ring-amber-400"
                    >
                    {{ $friendlyLabels[$key] ?? $key }}
                    <span class="font-mono text-xs text-slate-600" dir="ltr">({{ $key }})</span>
                </label>
            </li>
        @endforeach
    </ul>

    <input type="hidden" name="{{ $fieldName }}" data-section-order-hidden value="">
</div>

@once
    @push('scripts')
        <script>
            // منتقي ترتيب الأقسام — سحب وإفلات بـ HTML5 Drag & Drop خام (صفر مكتبة خارجية)،
            // event delegation واحدة بتخدم أي عدد منتقيات في نفس الصفحة (زي templates/show
            // اللي فيه واحد لكل نسخة قالب). كل تغيير (سحب أو تشييك) بيعيد كتابة الحقل المخفي
            // بمصفوفة مفاتيح الأقسام المفعّلة بترتيبها الحالي في الـ DOM بالظبط.
            (function () {
                let draggedRow = null;

                function syncHiddenField(group) {
                    const keys = Array.from(group.querySelectorAll('[data-section-key]'))
                        .filter((row) => row.querySelector('[data-section-toggle]').checked)
                        .map((row) => row.dataset.sectionKey);

                    group.querySelector('[data-section-order-hidden]').value = JSON.stringify(keys);
                }

                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('[data-section-order-group]').forEach(syncHiddenField);
                });

                document.addEventListener('change', function (event) {
                    if (!event.target.matches('[data-section-toggle]')) {
                        return;
                    }
                    syncHiddenField(event.target.closest('[data-section-order-group]'));
                });

                document.addEventListener('dragstart', function (event) {
                    const row = event.target.closest('[data-section-key]');
                    if (!row) {
                        return;
                    }
                    draggedRow = row;
                    row.dataset.dragging = 'true';
                    event.dataTransfer.effectAllowed = 'move';
                });

                document.addEventListener('dragend', function (event) {
                    const row = event.target.closest('[data-section-key]');
                    if (row) {
                        delete row.dataset.dragging;
                    }
                    draggedRow = null;
                });

                document.addEventListener('dragover', function (event) {
                    const row = event.target.closest('[data-section-key]');
                    if (!row || !draggedRow || row === draggedRow) {
                        return;
                    }
                    event.preventDefault();

                    const rect = row.getBoundingClientRect();
                    const isAfter = (event.clientY - rect.top) > rect.height / 2;
                    row.parentElement.insertBefore(draggedRow, isAfter ? row.nextSibling : row);
                });

                document.addEventListener('drop', function (event) {
                    const group = event.target.closest('[data-section-order-group]');
                    if (group) {
                        event.preventDefault();
                        syncHiddenField(group);
                    }
                });
            })();
        </script>
    @endpush
@endonce
