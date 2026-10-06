<?php

// راوتر سيرفر PHP المدمج للتشغيل المحلي (local/start) — نفس راوتر Laravel الداخلي
// (vendor/laravel/framework/.../Foundation/resources/server.php) بالظبط في المنطق، بس
// إحنا بنشغّل `php -S` بنفسنا بدل `php artisan serve` عشان نقدر نمرّر إعدادات php.ini (`-d`):
// حد رفع الصور (PHP افتراضياً 2 ميجا والتطبيق بيقبل لحد 8) ووقت تنفيذ مفتوح لردود الذكاء
// الاصطناعي الطويلة — artisan serve مبيعدّيش `-d` للسيرفر اللي بيشغّله.

$publicPath = dirname(__DIR__).'/public';

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
