# دليل نشر برق على السيرفر — خطوات فعلية، مش تلقائية

هذا الملف بيوثّق كل خطوة محتاجة تحصل فعلياً على سيرفر Hetzner (`138.199.220.217`، نفس
السيرفر اللي شغّال عليه Tafra ERP) عشان برق يبقى شغّال حي على `barq.tafraos.com`. **الجلسة
اللي كتبت الملف ده مالهاش أي وصول SSH فعلي للسيرفر** (صفر `ssh`/`scp` binary، صفر مفتاح
جوّه `~/.ssh/`) — يعني كل الخطوات دي لازم تتنفّذ يدوياً من شخص أو جلسة عندها وصول حقيقي
للسيرفر، مش تلقائي زي `tafra-deploy` بتاع مشروع الـ ERP.

⚠️ **قاعدة ثابتة تتطبّق على كل خطوة هنا:** صفر باسورد أو سر حقيقي يتكتب في أي ملف — أي
مكان فيه `__PLACEHOLDER__` لازم يتحط بإيد الشخص اللي بينفّذ مباشرة على السيرفر وقت الحاجة،
مش يتحط في هذا الملف ولا يتبعت في أي رسالة.

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
```

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

1. `curl -I https://barq.tafraos.com/up` → المفروض يرجع `200`.
2. افتح `https://barq.tafraos.com/login` في متصفح، وادخل بحساب الأدمن اللي عملته في خطوة 7.
3. اعمل قالب + مشروع تجريبي، واتأكد إنه بيظهر فعلاً على `{slug}.barq.tafraos.com`.
4. امسح المشروع/القالب التجريبيين بعد التأكد — أي بيانات تجربة لازم تتمسح فوراً (قاعدة
   ثابتة في `CLAUDE.md`).

## ملحوظة أخيرة

الملف ده بيوثّق **إيه اللي لازم يحصل**، مش تنفيذ فعلي حصل بالفعل. لو الجلسة اللي بتقرا الملف
ده معندهاش وصول SSH حقيقي لـ `138.199.220.217` (نفس القيد اللي كتب الملف ده من الأساس)،
الخطوات دي محتاجة تتنفّذ من شخص أو جلسة تانية عندها وصول فعلي — مش تتوهّم إنها اتنفّذت
لمجرد إنها موثّقة هنا.
