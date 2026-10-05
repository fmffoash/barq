# لوحة تحكم — أداة شخصية لبناء صفحات الهبوط

أداة خاصة لمستخدم واحد: قوالب جاهزة + محتوى (يدوي أو بالذكاء الاصطناعي المحلي Ollama) ← موقع
كامل قابل للمعاينة والتصدير كملفات ثابتة. Laravel 13 + Blade + Tailwind v4.

## التشغيل على جهازك

**ويندوز:** نزّل `local/install-windows.bat` ودوس عليه دبل كليك — بيسطّب كل حاجة لوحده
(PHP/Node/Composer نسخة خاصة بالتطبيق + Ollama والموديل) وبيحط أيقونة **"لوحة المواقع"** على
سطح المكتب. **ماك/لينكس:** `./local/setup.sh` مرة واحدة، وبعدين `./local/start.sh`.

الدليل الكامل خطوة بخطوة: **[docs/LOCAL-SETUP.md](docs/LOCAL-SETUP.md)** — وفحص الصحة:
`local\doctor.bat` (ويندوز) أو `php artisan barq:doctor`.

## للمطوّرين

توثيق المعمار وقواعد الشغل كلها في [CLAUDE.md](CLAUDE.md). النشر على سيرفر (القديم):
[deploy/DEPLOYMENT.md](deploy/DEPLOYMENT.md). التستات: `php artisan test` (محتاج `npm run build` قبلها).
