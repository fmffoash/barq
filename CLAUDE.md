# برق (Barq) — دليل المشروع

أداة داخلية بتساعد فريقنا يبني مواقع بسيطة (ولاندنج بيدجز) للعملاء بسرعة — القوالب بتتبني
مرة واحدة، وبعدين أي مشروع جديد بياخد قالب + شوية بيانات (اسم/لوجو/ألوان/نصوص) ويطلع منه
موقع كامل على سب دومين خاص بيه تحت `barq.tafraos.com`.

**ملحوظة مهمة:** برق مشروع مستقل تماماً — كود منفصل بالكامل عن نظام Tafra ERP (اللي شغّال
على نفس السيرفر لاحقاً كـ subdomain تاني). صفر مشاركة كود أو داتابيز بينهم.

## الحالة الحالية (Phase 1-5 خلصوا كلهم — تفاصيل كل فيز في `ROADMAP.md`)
اللي شغّال فعلياً دلوقتي: أدمن واحد بيدخل ويعمل قوالب بخاناتها (slots) يدوياً أو من مكتبة
قوالب قابلة للبحث/الفلترة، يعمل منها مشروع، ويملّي الخانات يدوي أو بمساعدة اقتراح محتوى
بالذكاء الاصطناعي (Ollama محلي، صفر بيانات بتتبعت لأي API خارجي). الموقع الناتج (قوالب
`landing`) بيتولّد على طول على سب دومين خاص بيه، وقابل للتصدير كملفات HTML/CSS ثابتة
(zip) لو العميل عايز يستضيفه بنفسه بره برق. قوالب `wordpress` بقت شغّالة فعلياً كمان —
مشروع منها بيقدر يعمل site حقيقي على شبكة WordPress Multisite منفصلة (عن طريق
`docs/wordpress-mu-plugin.php` اللي بينتقل يدوي لشبكة الـ WordPress) ويتبعتله المحتوى نفسه
اللي بيتملّى بيه أي مشروع عادي — ولحد ما ده يحصل، زوّار السب دومين بتاعه بيشوفوا شاشة
"لسه بيتجهّز". **صفر deploy فعلي للسيرفر لسه** — كل الشغل ده محلي (SQLite) + مدفوع على
GitHub بس. فيه دلوقتي (Phase 6) خطوات ديبلوي جاهزة بالكامل في مجلد `deploy/` (كونفيج nginx
+ قالب `.env` إنتاج + دليل خطوة بخطوة) — بس ده توثيق/تجهيز، مش تنفيذ فعلي؛ لازم شخص أو جلسة
عندها وصول SSH حقيقي للسيرفر تنفّذها.

## التقنيات
Laravel 13 · PHP 8.3+ (القيد الفعلي في `composer.json`، مش 8.5 زي ما كان مكتوب هنا غلط —
8.5 جاية من الـ boilerplate العام بتاع Laravel Boost في `AGENTS.md`، مش من قيد المشروع
الحقيقي) · Blade · Tailwind CSS v4 (عن طريق `@tailwindcss/vite`) · SQLite محلي (هيتغيّر
لـ MySQL على السيرفر — خطوات التحويل والقالب الكامل لـ `.env` الإنتاج في
`deploy/DEPLOYMENT.md`) · صفر JS framework — كله Blade + Tailwind.

## هيكل المجلدات المهم
```
app/Models/               — Template, TemplateVariant, TemplateSlot, Project, GeneratedSite, UnsupportedRequest, User
app/Http/Controllers/     — TemplateController, TemplateVariantController, TemplateSlotController,
                             ProjectController, GeneratedSiteController, SiteController, DashboardController,
                             Auth/AuthController
app/Http/Middleware/      — DetectSite.php (بيحدد الموقع من السب دومين لطلبات العملاء)
app/Services/             — OllamaService (اقتراح محتوى بالذكاء الاصطناعي، Phase 2)،
                             SiteRenderer (بناء الأقسام/الألوان المشترك بين المعاينة والتصدير)،
                             SiteExportService (تصدير zip ثابت، Phase 4)،
                             WordPressService (توفير site + دفع محتوى على شبكة Multisite، Phase 5)
app/Console/Commands/     — CreateAdminUser.php (الأمر الوحيد لعمل/تحديث حساب الأدمن)
routes/web.php            — مسارات لوحة التحكم (login + dashboard + templates + projects)
routes/site.php           — مسارات المواقع المنشورة (تحت {siteSlug}.barq.tafraos.com بس)
routes/console.php        — أوامر الطرفية
config/barq.php           — إعدادات المشروع (BARQ_BASE_DOMAIN وغيرها)
config/services.php       — إعدادات Ollama + شبكة WordPress (network_url/shared_secret)
docs/wordpress-mu-plugin.php — الملف deliverable اللي بينتقل يدوي لشبكة WordPress (Phase 5)
deploy/                   — كونفيج nginx + قالب .env إنتاج + دليل النشر خطوة بخطوة (Phase 6،
                             توثيق/تجهيز بس — التنفيذ الفعلي محتاج وصول SSH حقيقي للسيرفر)
resources/views/site/     — الشِل والبارشيالز اللي بترندر الموقع المنشور فعلياً للعميل
resources/views/errors/   — 404.blade.php (نفس التصميم لمسارات لوحة التحكم والمواقع المنشورة)
tests/Feature/            — AuthenticationTest, TemplateManagementTest, ProjectManagementTest,
                             SiteRenderingTest, OllamaContentSuggestionTest, TemplateLibraryTest,
                             SiteExportTest, WordPressIntegrationTest
```

## المعمار: التوجيه بالدومين (Domain Routing)
كل موقع منشور بيتفتح على `{project.slug}.barq.tafraos.com` — ده مختلف تماماً عن لوحة
التحكم اللي بتفتح على الدومين العادي (`localhost:8000` محلياً، أو دومين لوحة التحكم على
السيرفر). الفصل ده بيتم في `bootstrap/app.php`، جوه الـ `then:` closure اللي بيسجّل
`routes/site.php` بس تحت `Route::domain('{siteSlug}.'.config('barq.base_domain'))`:

```php
then: function (): void {
    Route::domain('{siteSlug}.'.config('barq.base_domain'))
        ->group(base_path('routes/site.php'));
},
```

`{siteSlug}` بتتحط تلقائي من أول جزء في الدومين (زي أي route parameter عادي)، وبتوصل
لـ `DetectSite` middleware اللي بيدوّر على `GeneratedSite` بنفس الـ slug ده، ولو لقاه
بيحطه في `$request->attributes` عشان `SiteController::show()` يستخدمه. لو مش لاقي حاجة،
404 عادي — ونفس الـ 404 ده بيظهر لو الموقع `archived` (بس `draft`/`published` الاتنين
بيترندروا عادي، القرار ده متعمّد — أي موقع اتعمل بيبان على طول حتى قبل ما يتنشر رسمياً).

**قاعدة دائمة:** أي مسار جديد خاص بلوحة التحكم يروح `routes/web.php`. أي مسار خاص بعرض
الموقع المنشور للعميل النهائي يروح `routes/site.php`. الاتنين ملفات منفصلة تماماً وبيتسجّلوا
بطريقتين مختلفتين — الخلط بينهم غلط معماري.

### رندر الموقع المنشور
`SiteController::show()` بيجمّع أقسام الموقع (`sections`) من ترتيب `TemplateVariant.sections_json`
لو موجود، وإلا من أول ظهور طبيعي للأقسام في خانات القالب (`TemplateSlot`). كل خانة جوّه
القسم بترندر بشكل مختلف حسب `slot_type` بتاعها (`text`/`textarea`/`image`/`list`/`link`) —
البارشيال العام `site/partials/section.blade.php` بيتعامل مع الأنواع دي كلها بمكان واحد
(صفر بارشيال منفصل لكل قسم أو نوع). الخانات الفاضية بتتفلتر، والأقسام اللي كل خاناتها فاضية
بتختفي بالكامل من الرندر. الألوان (`TemplateVariant.colors_json`) بتتحط كـ CSS custom
properties على الـ `<body>` (زي `--site-primary`) — ده أسلوب متعمّد بدل كلاسات Tailwind
ثابتة، عشان الألوان بتتغيّر لكل مشروع وقت التشغيل (runtime)، مش وقت الـ build.

قوالب من نوع `kind = 'wordpress'` بتاخد شاشة "لسه بيتجهّز" (coming-soon) بدل الرندر العادي
**لحد ما يتعملهم site فعلي على شبكة WordPress Multisite** (Phase 5 — `WordPressService` +
`docs/wordpress-mu-plugin.php`) — بعد كده الزائر بيتحوّل تلقائي (`redirect()->away(...)`)
لموقعه الحقيقي هناك، مش بيشوف رندر محلي خالص.

## نظام الأدمن (مستخدم واحد بس)
**صفر تسجيل حسابات جديدة وصفر استرجاع باسورد** — النظام مبني على افتراض إن فيه أدمن واحد
بس. الحساب بيتعمل أو يتحدّث عن طريق أمر طرفية تفاعلي:

```bash
php artisan barq:create-admin
```

الأمر ده بياخد الاسم/الإيميل/الباسورد (بتأكيد) بشكل تفاعلي (`$this->ask()` / `$this->secret()`)،
ومستحيل الباسورد يتسجّل في أي ملف أو لوج — ده قرار أمان متعمّد ومكتوب في تعليق الكود نفسه.
الدخول والخروج عاديين (`AuthController::store()`/`destroy()`) — صفر صفحة تسجيل، صفر رابط
"نسيت الباسورد".

**⚠️ قاعدة إجبارية للمشروع كله:** صفر باسورد حقيقي (أو حتى تجريبي) يتكتب في أي ملف من ملفات
المشروع أو أي ميموري — لو محتاج باسورد تجربة وقت التطوير، اسأل في الشات وقتها ومتسجلوش.

## قواعد الشغل
- **البيئة:** الـ working directory بترجع لـ `/home/user/tafra-erp` بعد كل أمر Bash — أي
  أمر خاص ببرق لازم يتسبق بـ `cd /home/user/barq &&` صراحةً.
- **Edit/Write:** لازم تقرا الملف بأداة `Read` الأول قبل أي تعديل — قراءته بـ `cat`/`grep`
  في Bash مش كفاية وهيرمي error.
- **متعدّلش بـ PowerShell على ملفات فيها عربي** — بتعمل mojibake (تشوّه الترميز).
- **صفر تعطيل لفحص TLS أو `HTTPS_PROXY`** لحل مشاكل الشبكة/البروكسي.
- **أي بيانات تجريبية (tinker rows وغيرها)** لازم تتمسح فوراً بعد التأكد اليدوي منها —
  الاستثناء الوحيد هو الـ test suite نفسه (`RefreshDatabase` بينضّف لوحده تلقائي).
- **كل تعديل يتأكد إنه شغال قبل ما تكمّل** — `php -l` على أي ملف PHP اتغيّر، وتشغيل الـ
  test suite كامل (`php artisan test`) بعد أي تغيير مؤثر.
