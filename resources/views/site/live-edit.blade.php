{{--
    نفس شِل الموقع الحقيقي بالظبط (site.document) — الفرق الوحيد $editable=true اللي
    بيحقن سكريبت/CSS المحرر البصري المباشر (docs/wysiwyg-editor-plan.md). المسار ده جوّه
    مجموعة auth (routes/web.php) — المسار العام (site.show) صفر تأثير عليه خالص.
--}}
@include('site.document', ['cssMode' => 'vite', 'editable' => true])
