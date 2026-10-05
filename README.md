# لوحة تحكم — أداة شخصية لبناء صفحات الهبوط

أداة خاصة لمستخدم واحد: قوالب جاهزة + محتوى (يدوي أو بالذكاء الاصطناعي المحلي Ollama) ← موقع
كامل قابل للمعاينة والتصدير كملفات ثابتة. Laravel 13 + Blade + Tailwind v4.

## التشغيل على جهازك

الدليل الكامل خطوة بخطوة: **[docs/LOCAL-SETUP.md](docs/LOCAL-SETUP.md)**. باختصار، بعد تسطيب
PHP 8.3+ و Composer و Node.js و Ollama:

| ويندوز (دبل كليك) | ماك / لينكس | |
|---|---|---|
| `local\setup.bat` | `./local/setup.sh` | تجهيز — مرة واحدة |
| `local\start.bat` | `./local/start.sh` | تشغيل على http://127.0.0.1:8010 |
| `local\update.bat` | `./local/update.sh` | تحديث لآخر كود |

`php artisan barq:doctor` بيفحص كل حاجة ويقول إيه الناقص.

## للمطوّرين

توثيق المعمار وقواعد الشغل كلها في [CLAUDE.md](CLAUDE.md). النشر على سيرفر (القديم):
[deploy/DEPLOYMENT.md](deploy/DEPLOYMENT.md). التستات: `php artisan test` (محتاج `npm run build` قبلها).
