{{-- شريط عائم فوق معاينة القالب (TemplateController::preview) — ستايل inline بس عشان ميتأثرش
بألوان/خط القالب نفسه ولا يحتاج كلاسات Tailwind جديدة. المسافة الفاضية عشان الشريط ميغطّيش آخر الفوتر. --}}
<div aria-hidden="true" style="height: 80px;"></div>
<div
    dir="rtl"
    style="position: fixed; inset-inline: 0; bottom: 18px; z-index: 2147483000; display: flex; justify-content: center; pointer-events: none; font-family: var(--font-cairo), system-ui, sans-serif;"
>
    <div style="pointer-events: auto; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 999px; background: rgba(2, 6, 23, 0.88); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35); color: #e2e8f0; font-size: 14px; backdrop-filter: blur(6px);">
        <span style="padding-inline: 6px; color: #94a3b8;">معاينة: <strong style="color: #f1f5f9;">{{ $template->name }}</strong></span>
        @if ($template->is_active)
            <a
                href="{{ route('projects.create', ['template' => $template->id]) }}"
                style="border-radius: 999px; background: #fbbf24; padding: 7px 14px; color: #0f172a; font-weight: 700; text-decoration: none;"
            >استخدم القالب ده</a>
        @endif
        <a
            href="{{ route('templates.index') }}"
            style="border-radius: 999px; border: 1px solid #334155; padding: 6px 12px; color: #cbd5e1; text-decoration: none;"
        >رجوع للقوالب</a>
    </div>
</div>
