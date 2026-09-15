<?php

// إعدادات برق الخاصة (منفصلة عن config/app.php) — الدومين اللي عليه لوحة التحكم،
// واللاحقة اللي بتتبني عليها روابط معاينة المواقع الناتجة، وإعدادات نموذج الذكاء الاصطناعي المحلي.

return [

    // الدومين اللي لو الطلب جاي عليه بالظبط، النظام يعرض لوحة التحكم (مش موقع عميل).
    'admin_host' => env('BARQ_ADMIN_HOST', 'localhost'),

    // اللاحقة الأساسية لمواقع المعاينة — الموقع الناتج بيظهر على {slug}.{base_domain}.
    'base_domain' => env('BARQ_BASE_DOMAIN', 'barq.tafraos.com'),

    // نموذج الذكاء الاصطناعي المحلي (Ollama) — هيتفعّل في المرحلة التانية من الخطة.
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:8b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
    ],

    // بعد كام يوم تتعلّم أي نسخة معاينة اتعملت ومحدش اعتمدها (للتنضيف الدوري لاحقًا).
    'preview_cleanup_days' => (int) env('BARQ_PREVIEW_CLEANUP_DAYS', 30),
];
