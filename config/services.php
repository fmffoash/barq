<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // نموذج الذكاء الاصطناعي المحلي (Ollama) اللي بيقترح محتوى الخانات — Phase 2.
    // صفر بيانات بتتبعت لأي خدمة خارجية، كله بيشتغل محلي على السيرفر نفسه.
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:8b'),
        // كان متحط في .env من الأول (OLLAMA_TIMEOUT=120) بس مش متوصّل بحاجة — OllamaService
        // كان بيستخدم Http::timeout(60) ثابت بدل ما يقرا القيمة دي (2026-09-20).
        // التشغيل المحلي (2026-10-06): أقل قيمة فعلية 600 ثانية مهما كان مكتوب هنا (OllamaService::timeout)
        // — ملفات .env القديمة فيها 180 وده كان بيقطع الرد على جهاز من غير كارت شاشة.
        'timeout' => (int) env('OLLAMA_TIMEOUT', 900),
        // مساحة القراية (بالتوكنز): Ollama افتراضياً 4096 بس على أي جهاز من غير كارت شاشة كبير، وأي
        // طلب أطول كان بيتقص من أوله (التعليمات نفسها) من غير أي رسالة. صفحة الإعدادات تقدر تغيّرها.
        'num_ctx' => (int) env('OLLAMA_NUM_CTX', 8192),
        // النموذج بيفضل محمّل في الرام المدة دي بعد آخر استخدام (على السيرفر كانت 5 دقايق عشان الرام
        // ضيقة) — على الجهاز الشخصي نص ساعة بتوفّر تحميله من الديسك (10-30 ثانية) مع كل رسالة.
        'keep_alive' => env('OLLAMA_KEEP_ALIVE', '30m'),
        // المتصفح يستقبل الرد من Ollama مباشرة (auto = لو الصفحة وOllama على نفس الجهاز) ولا
        // عن طريق السيرفر — شوف OllamaService::browserDirect(). false = عن طريق السيرفر دايماً.
        'browser_direct' => env('OLLAMA_BROWSER_DIRECT', 'auto'),
    ],

    // شبكة WordPress Multisite اللي بنعمل عليها المواقع من نوع "ووردبريس" — Phase 5.
    // network_url بيشاور على شبكة الـ Multisite (فيها mu-plugin بيعرّض REST endpoint خاص
    // تحت namespace اسمه barq/v1)، وshared_secret ده توكن ثابت بنبعته كـ Bearer في كل نداء
    // عشان الـ plugin يتأكد إن النداء جاي من برق فعلاً مش من حد تاني.
    'wordpress' => [
        'network_url' => env('WORDPRESS_NETWORK_URL'),
        'shared_secret' => env('WORDPRESS_SHARED_SECRET'),
    ],

];
