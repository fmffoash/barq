# دليل نشر برق على السيرفر — خطوات فعلية، مش تلقائية

هذا الملف بيوثّق كل خطوة محتاجة تحصل فعلياً على سيرفر Hetzner (`138.199.220.217`، نفس
السيرفر اللي شغّال عليه Tafra ERP) عشان برق يبقى شغّال حي على `adamfoash.tafraos.com`. **الجلسة
اللي كتبت الملف ده مالهاش أي وصول SSH فعلي للسيرفر** (صفر `ssh`/`scp` binary، صفر مفتاح
جوّه `~/.ssh/`) — يعني كل الخطوات دي لازم تتنفّذ يدوياً من شخص أو جلسة عندها وصول حقيقي
للسيرفر، مش تلقائي زي `tafra-deploy` بتاع مشروع الـ ERP.

⚠️ **قاعدة ثابتة تتطبّق على كل خطوة هنا:** صفر باسورد أو سر حقيقي يتكتب في أي ملف — أي
مكان فيه `__PLACEHOLDER__` لازم يتحط بإيد الشخص اللي بينفّذ مباشرة على السيرفر وقت الحاجة،
مش يتحط في هذا الملف ولا يتبعت في أي رسالة.

## ⭐ الرجوع للسيرفر بعد فترة التشغيل المحلي (2026-10-10)

فؤاد شغّل اللوحة على جهازه فترة (docs/LOCAL-SETUP.md) ورجع للسيرفر لأن التشغيل المحلي محتاج
جهاز قوي. **مفيش بيانات مشاريع مهمة تتنقل** (كانت كلها تجارب)، فالسيرفر يبدأ نضيف. لو هتحتاج
تنقل بيانات من جهاز تاني بعد كده: `barq:export-data` هناك ← ارفع الـzip ← `php artisan
barq:import-data path.zip --force` هنا (استبدال كامل مش دمج) ← `chown` زي تحت.

**لو مجلد `/var/www/barq` لسه موجود على السيرفر** (تحديث عادي):

```bash
cd /var/www/barq
git fetch && git reset --hard origin/main
chown -R www-data:www-data storage bootstrap/cache public/images
npm ci && npm run build && chown -R www-data:www-data public/build
composer install --no-dev --optimize-autoloader --quiet && chown -R www-data:www-data vendor
# .env: راجع قسم "إعدادات برق الخاصة" في deploy/env.production.example وانسخ القيم الجديدة
# (OLLAMA_TIMEOUT/OLLAMA_NUM_CTX/OLLAMA_KEEP_ALIVE/OLLAMA_BROWSER_DIRECT، وAPP_URL الصح).
php artisan migrate --force
php artisan barq:seed-template-library      # آمن على المشاريع الموجودة، وبيحدّث الـ300 قالب
php artisan storage:link 2>/dev/null || true
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
cp deploy/nginx-barq.conf /etc/nginx/sites-enabled/barq && nginx -t
curl -s http://localhost:11434/api/ps      # لازم models:[] قبل الريلود (Ollama مش شغال دلوقتي)
systemctl reload nginx && systemctl reload php8.3-fpm
chown -R www-data:www-data storage bootstrap/cache
php artisan barq:doctor
```

**بعد الديبلوي:** افتح "⚙️ إعدادات الذكاء الاصطناعي" (`/settings/ai`) — لازم يبان Ollama "✓ شغال"
والنموذج "✓ متسطّب"، وبعدين دوس "⏱️ اختبار السرعة" مرة (أقل من دقيقة غالباً) عشان عدّاد الوقت
الباقي يبقى دقيق على السيرفر من أول طلب حقيقي.

**لو المجلد اتمسح** (اتشال برق من السيرفر وقت التشغيل المحلي): اتبع الخطوات من 4 لـ9 تحت كاملة
(قاعدة البيانات، clone، `.env` من `deploy/env.production.example`، migrate، storage:link،
`barq:create-admin`، `barq:seed-template-library`، الكاش والصلاحيات، nginx)، وبعدين خطوة 12
(Ollama) و`php artisan barq:doctor`.

**حدود رفع الصور في pool برق** (`/etc/php/8.3/fpm/pool.d/barq.conf`، مش متتبّع في الريبو) — لازم
تطابق `client_max_body_size 64M` في nginx وتحقّق التطبيق (8 ميجا للصورة، لحد 10 صور مرة واحدة):

```
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 64M
php_admin_value[max_file_uploads] = 20
```

بعد تعديلها: `curl -s http://localhost:11434/api/ps` (لازم `models:[]`) ← `systemctl reload php8.3-fpm`.

**الذكاء الاصطناعي على السيرفر:** المتصفح مش بيقدر يكلّم Ollama مباشرة هنا (مش ظاهر برّه)، فالرد
بيعدّي من السيرفر نفسه كلمة بكلمة (`AiRunController::stream`) مع نبضة "لسه شغال" كل 15 ثانية —
Cloudflare بيقطع أي رد ساكت 100 ثانية (خطأ 524)، والنموذج على المعالج ممكن ياخد أكتر من كده قبل
أول كلمة. `fastcgi_read_timeout 600s` في nginx أمان إضافي. لو ظهر 524 رغم كده، راجع إن
`X-Accel-Buffering: no` واصل (ممكن تأكد بـ`curl -N` على `/ai/runs/{id}/stream` من جوّه السيرفر).

## 0) الفرق عن مشروع Tafra ERP الموجود بالفعل

برق مستقل تماماً — كود منفصل، قاعدة بيانات منفصلة (`barq` مش `erp`)، مجلد منفصل
(`/var/www/barq` مش `/var/www/erp`)، كونفيج nginx منفصل (`sites-enabled/barq`). صفر تعديل
على أي حاجة خاصة بالـ ERP مطلوب في أي خطوة هنا.

## 1) المتطلبات على السيرفر

السيرفر أصلاً شغّال عليه Tafra ERP بـ PHP 8.3 + MySQL + nginx — نفس الإصدارات دي كافية
لبرق (`composer.json` بيطلب `"php": "^8.3"` بالظبط، مش 8.5 زي ما كان مكتوب غلط قبل كده في
`CLAUDE.md`). المطلوب إضافي:

- **Node.js + npm** (لبناء أصول Tailwind/Vite) — لو مش متسطّب أصلاً للـ ERP.
- **قاعدة بيانات MySQL جديدة ومستخدم جديد** مخصّصين لبرق بس (خطوة 4).
- **شهادة SSL wildcard** تغطي `barq.tafraos.com` و`*.barq.tafraos.com` مع بعض (خطوة 3) —
  ده مختلف عن شهادة `tafraos.com` الموجودة، لأنها مبتغطيش `*.barq.tafraos.com`.

## 2) DNS

الدومين `tafraos.com` على Cloudflare بالفعل (نفس اللي الـ ERP شغّال عليه). لازم يتضاف على
نفس الـ zone:

| النوع | الاسم | القيمة | Proxy status |
|---|---|---|---|
| A | `barq` | `138.199.220.217` | مفعّل (السحابة البرتقالي) |
| A | `*.barq` | `138.199.220.217` | مفعّل (السحابة البرتقالي) |

الـ proxy لازم يكون مفعّل (مش DNS-only) عشان شهادة Cloudflare Origin CA في الخطوة الجاية
تشتغل — الشهادة دي بتتوثّق بس بين Cloudflare وسيرفرنا، مش مباشرة مع المتصفح.

## 3) شهادة SSL — Cloudflare Origin CA (نفس أسلوب الـ ERP، مش Let's Encrypt)

كونفيج الـ ERP الموجود (`/etc/ssl/tafra/tafra.crt`) شكله بيقول إنه شهادة Cloudflare Origin
CA مش Let's Encrypt عادية — ده الأنسب هنا كمان، لأن Let's Encrypt العادي (HTTP-01) مبيدعمش
wildcard أصلاً؛ محتاج DNS-01 challenge أعقد. Origin CA بتدّي wildcard SAN بسهولة من نفس
الداشبورد:

1. Cloudflare Dashboard → الدومين `tafraos.com` → **SSL/TLS → Origin Server** → **Create
   Certificate**.
2. Hostnames: `barq.tafraos.com` و `*.barq.tafraos.com` (الاتنين مع بعض في نفس الشهادة).
3. صلاحية 15 سنة (الافتراضي) كافية.
4. احفظ الـ certificate والـ private key على السيرفر:
   ```bash
   mkdir -p /etc/ssl/barq
   # الصق محتوى الـ certificate هنا:
   nano /etc/ssl/barq/barq.crt
   # الصق محتوى الـ private key هنا:
   nano /etc/ssl/barq/barq.key
   chmod 600 /etc/ssl/barq/barq.key
   ```
5. تأكد إن SSL/TLS mode بتاع الـ zone على **Full (strict)** — نفس اللي الـ ERP شغّال عليه
   أصلاً (لو مش كده، الزوار هياخدوا خطأ اتصال).

## 4) قاعدة البيانات — MySQL مستقلة تماماً

```sql
CREATE DATABASE barq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'barq'@'localhost' IDENTIFIED BY '__DB_PASSWORD__';
GRANT ALL PRIVILEGES ON barq.* TO 'barq'@'localhost';
FLUSH PRIVILEGES;
```

اختار باسورد قوي فعلي مكان `__DB_PASSWORD__` وقت التنفيذ — متسجّلوش في أي ملف.

## 5) تجهيز الكود على السيرفر

```bash
cd /var/www
git clone https://github.com/fmffoash/barq.git barq
cd barq
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## 6) ملف `.env`

```bash
cp deploy/env.production.example .env
nano .env   # اكتب DB_PASSWORD الحقيقي، وأي إعدادات بريد فعلية لو محتاجها
php artisan key:generate --force
```

راجع `deploy/env.production.example` — كل قيمة فيها شرح جوّه الكومنت اللي فوقها (الـ
Ollama والـ WordPress Multisite اختياريين وآمن تسيبهم فاضيين لحد ما تحتاجهم فعلاً).

## 7) قاعدة البيانات + التخزين + حساب الأدمن

```bash
php artisan migrate --force
php artisan storage:link
```

`storage:link` ضروري — `GeneratedSiteController` بيخزّن صور المواقع (لوجو/صور الأقسام) على
`storage/app/public/site-images`، ولازم اللينك الرمزي ده عشان الزوّار يقدروا يشوفوها فعلياً
على `/storage/...`.

```bash
php artisan barq:create-admin
```

الأمر ده تفاعلي (`$this->ask()`/`$this->secret()`) — اكتب الاسم/الإيميل/الباسورد مباشرة
وقت التنفيذ، صفر تسجيل لأي حاجة منهم في أي ملف.

```bash
php artisan barq:seed-template-library
```

بيولّد مكتبة القوالب الأصلية — 20 فئة نشاط × 15 قالب موزّعة على الـ 16 تصميم
البصري (300 قالب توتال) بمحتوى عربي افتراضي جاهز، من غير ما تحتاج تعمل أي قالب يدوي بإيدك
الأول. الأمر idempotent بالكامل — آمن تشغّله تاني وقت ما تحدّث الكود لاحقاً (هيحدّث
القوالب الموجودة بدل ما يكرّرها).

## 8) الكاش والصلاحيات

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data /var/www/barq/storage /var/www/barq/bootstrap/cache
```

## 9) nginx

```bash
cp deploy/nginx-barq.conf /etc/nginx/sites-enabled/barq
nginx -t
systemctl reload nginx
```

## 10) الجدولة (cron)

مفيش أي scheduled command متسجّل فعلياً في `routes/console.php` لحد دلوقتي (`preview_cleanup_days`
موجود في الكونفيج بس صفر أمر تنضيف بيستخدمه حالياً) — يعني الـ cron مش ضروري النهارده. لو
حابب تضيفه استباقياً على أي حال (آمن وصفر تأثير حتى لو مفيش scheduled tasks):

```
* * * * * cd /var/www/barq && php artisan schedule:run >> /dev/null 2>&1
```

## 11) الطابور (queue worker)

مفيش أي `ShouldQueue` job في الكود لحد دلوقتي (`QUEUE_CONNECTION=database` معرّف بس مش
مستخدَم فعلياً) — **مفيش داعي لـ systemd service خاص بـ `queue:work` دلوقتي**. لو مستقبلاً
اتضافت jobs (زي إعادة محاولة push المحتوى لشبكة WordPress في الخلفية)، وقتها محتاج service
جديد يشبه `tafra-queue.service` الموجود للـ ERP، بس مستقل بالكامل (اسم/مسار مختلفين).

## 12) الذكاء الاصطناعي (Ollama) — اختياري تماماً

لو عايز ميزة اقتراح المحتوى بالذكاء الاصطناعي تشتغل فعلياً في الإنتاج، لازم Ollama متسطّب
ومشغّل على نفس السيرفر:

```bash
curl -fsSL https://ollama.com/install.sh | sh
ollama pull qwen3:8b
ollama --version   # لو هتجرّب نموذج من الأحدث (qwen3.5/gemma4) لازم 0.31.2 أو أحدث
```

إعداداته في `.env` (شرحها في `deploy/env.production.example`): `OLLAMA_NUM_CTX=6144`،
`OLLAMA_KEEP_ALIVE=5m` (الرام مشتركة مع Tafra ERP)، `OLLAMA_BROWSER_DIRECT=off`.

⚠️ ده استهلاك رام وقرص حقيقي على سيرفر أصلاً شغّال عليه Tafra ERP — قرّر بوعي هل السيرفر
مستحمل الحمل الإضافي ده قبل ما تشغّله، مش افتراض إنه هيمشي لوحده. لو قررت تأجيلها، سيبها
زي ما هي — `OllamaService` بيتعامل مع فشل الاتصال بأمان (الميزة بترجع تفشل برسالة واضحة،
صفر كسر لباقي النظام).

## 13) شبكة WordPress Multisite (Phase 5) — بنية تحتية منفصلة، لسه مبنيتش

قوالب `kind=wordpress` محتاجة شبكة WordPress Multisite فعلية شغّالة في مكان تاني (مش على
نفس مجلد برق) عشان `WordPressService` يقدر يوفّر مواقع عليها فعلاً — ده مش جزء من ديبلوي
برق نفسه، هو تبعية خارجية. لحد ما الشبكة دي تتجهّز، سيب `WORDPRESS_NETWORK_URL`/
`WORDPRESS_SHARED_SECRET` فاضيين في `.env` — زوّار أي موقع بقالب WordPress هيشوفوا شاشة
"لسه بيتجهّز" بدل خطأ، وده سلوك متعمّد وآمن.

## 14) اختبار الدخان (smoke test)

بعد كل الخطوات فوق:

1. `curl -I https://adamfoash.tafraos.com/up` → المفروض يرجع `200`.
2. افتح `https://adamfoash.tafraos.com/login` في متصفح، وادخل بحساب الأدمن اللي عملته في خطوة 7.
3. افتح `/templates` وتأكد إن الـ 300 قالب من `barq:seed-template-library` ظاهرين، وإن الخطوط
   (Cairo/Tajawal/...) بتظهر صح مش fallback نظام (لو الخط مش ظاهر، غالباً `npm run build`
   في خطوة 5 معملش أو `public/build` مش متزامن مع الكود الحالي).
4. اعمل مشروع من أي قالب من المكتبة (أو قالب يدوي جديد)، واتأكد إنه بيظهر فعلاً على
   `https://adamfoash.tafraos.com/site/{slug}` بالتصميم والألوان الصح.
5. امسح أي مشروع تجريبي زيادة عن مكتبة القوالب بعد التأكد — أي بيانات تجربة لازم تتمسح فوراً
   (قاعدة ثابتة في `CLAUDE.md`). قوالب المكتبة نفسها (الـ 300) مش بيانات تجربة — سيبها زي
   ما هي، دي جزء من المنتج.

## ملحوظة أخيرة

الملف ده بيوثّق **إيه اللي لازم يحصل**، مش تنفيذ فعلي حصل بالفعل. لو الجلسة اللي بتقرا الملف
ده معندهاش وصول SSH حقيقي لـ `138.199.220.217` (نفس القيد اللي كتب الملف ده من الأساس)،
الخطوات دي محتاجة تتنفّذ من شخص أو جلسة تانية عندها وصول فعلي — مش تتوهّم إنها اتنفّذت
لمجرد إنها موثّقة هنا.
