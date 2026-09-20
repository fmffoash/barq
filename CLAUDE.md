# لوحة تحكم — دليل المشروع (اسم الريبو الداخلي `barq`، مش اسم ظاهر لحد)

أداة شخصية خاصة بفؤاد بس (مش أداة فريق، ومش للعملاء) بتساعده يبني مواقع بسيطة (لاندنج
بيدجز) بسرعة — القوالب بتتبني مرة واحدة، وبعدين أي مشروع جديد بياخد قالب + شوية بيانات
(اسم/لوجو/ألوان/نصوص) ويطلع منه موقع كامل على سب دومين تحت `adamfoash.tafraos.com`.

**ملحوظة مهمة:** المشروع ده مستقل تماماً — كود منفصل بالكامل عن نظام Tafra ERP (اللي شغّال
على نفس السيرفر). صفر مشاركة كود أو داتابيز بينهم. وهو **مساحة شخصية لفؤاد وحده — صفر ظهور
لأي اسم "برق" في أي واجهة مستخدم، وصفر وصول لأي حد تاني غير فؤاد.**

## الحالة الحالية (2026-09-20 — لايف على السيرفر ومُختبر حي بالكامل)
**دلوقتي لايف على `https://adamfoash.tafraos.com`** (nginx + PHP 8.3-fpm + MySQL، نفس
سيرفر Tafra ERP بصفر تعارض)، أدمن واحد فقط (Foash) بيدخل ويعمل قوالب بخاناتها (slots)
يدوياً أو من مكتبة قوالب أصلية جاهزة (42 قالب × 14 فئة نشاط، `php artisan
barq:seed-template-library`)، يعمل منها مشروع، ويملّي الخانات يدوي أو بمساعدة اقتراح محتوى
بالذكاء الاصطناعي (Ollama محلي، صفر بيانات بتتبعت لأي API خارجي — النموذج نفسه لسه
مش متحمّل، فبيرجع fallback واضح "النموذج مش متاح" لحد ما فؤاد يرفع رامات/بروسيسور
السيرفر ويحمّله). الموقع الناتج (قوالب `landing`) بيتولّد على طول على سب دومين تاني تحت
`adamfoash.tafraos.com`، وقابل للتصدير كملفات HTML/CSS ثابتة (zip).

**⚠️ فجوة معروفة (2026-09-20):** مواقع المشاريع المنشورة (سب-سب-دومين زي
`مشروع.adamfoash.tafraos.com`) شغّالة صح على HTTP، بس بتفشل على HTTPS من برّه — لأن
شهادة Cloudflare المجانية (Universal SSL) بتغطي مستوى wildcard واحد بس (`*.tafraos.com`)
مش مستوى تاني جواه. الحل: تفعيل "Total TLS" (مجاني) من Cloudflare Dashboard → SSL/TLS →
Edge Certificates — قرار فؤاد ومحتاج دخول لوحة Cloudflare، مش حاجة ينفذها كلود. اللوحة
نفسها (adamfoash.tafraos.com) شغالة HTTPS تمام لأنها دومين مستوى واحد بس.

قوالب `wordpress` شغّالة فعلياً كمان — مشروع منها بيقدر يعمل site حقيقي على شبكة WordPress
Multisite منفصلة (عن طريق `docs/wordpress-mu-plugin.php` اللي بينتقل يدوي لشبكة الـ
WordPress) — دي مش متفعّلة فعلياً دلوقتي (مش محتاجة لاستخدام فؤاد الشخصي)، فزوّار أي سب
دومين من النوع ده بيشوفوا شاشة "لسه بيتجهّز" بس، ده سلوك متوقّع ومقصود.

قوالب `landing` بقى ليها 15 تصميم بصري مختلف فعلياً (`Template::LAYOUTS` — Phase 7/8، شوف
"التصميمات البصرية المتعددة" تحت)، ومعاها مكتبة قوالب أصلية جاهزة (`php artisan
barq:seed-template-library`) بتغطي 14 فئة نشاط شائعة × 3 قوالب لكل فئة (42 قالب بمحتوى عربي
افتراضي جاهز، موزّعة على الـ 15 تصميم). تغيير الألوان بقى بمنتقي ألوان بصري (`templates/
partials/color-picker.blade.php`) بدل كتابة JSON خام يدوي، ومعاه اختيار خط عام للموقع كله
(`TemplateVariant.font`، 6 خطوط عربي/لاتيني). كل خانة نص/فقرة/قايمة كمان ممكن تاخد لون/خط
تخصيصي خاص بيها بس (`GeneratedSite.style_overrides_json`، Phase 9) من صفحة تعبئة محتوى
المشروع نفسها.

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
app/Console/Commands/     — CreateAdminUser.php (عمل/تحديث حساب الأدمن)،
                             SeedTemplateLibrary.php (توليد/تحديث مكتبة القوالب الأصلية، Phase 7)
routes/web.php            — مسارات لوحة التحكم (login + dashboard + templates + projects)
routes/site.php           — مسارات المواقع المنشورة (تحت {siteSlug}.barq.tafraos.com بس)
routes/console.php        — أوامر الطرفية
config/barq.php           — إعدادات المشروع (BARQ_BASE_DOMAIN وغيرها)
config/services.php       — إعدادات Ollama + شبكة WordPress (network_url/shared_secret)
docs/wordpress-mu-plugin.php — الملف deliverable اللي بينتقل يدوي لشبكة WordPress (Phase 5)
deploy/                   — كونفيج nginx + قالب .env إنتاج + دليل النشر خطوة بخطوة (Phase 6،
                             توثيق/تجهيز بس — التنفيذ الفعلي محتاج وصول SSH حقيقي للسيرفر)
resources/views/site/     — الشِل والبارشيالز اللي بترندر الموقع المنشور فعلياً للعميل
                             (layouts/*.blade.php — الـ 15 تصميم بصري، Phase 7/8)
resources/views/templates/partials/color-picker.blade.php — منتقي ألوان بصري (بدل JSON خام)
resources/views/errors/   — 404.blade.php (نفس التصميم لمسارات لوحة التحكم والمواقع المنشورة)
tests/Feature/            — AuthenticationTest, TemplateManagementTest, ProjectManagementTest,
                             SiteRenderingTest, OllamaContentSuggestionTest, TemplateLibraryTest,
                             SiteExportTest, WordPressIntegrationTest, SiteLayoutTest (Phase 7)
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
لو موجود، وإلا من أول ظهور طبيعي للأقسام في خانات القالب (`TemplateSlot`، مرتّبة بـ
`sort_order` ثم `id` كفاصل ثانوي — `Template::slots()` — عشان ترتيب الأقسام يبقى مضمون حتى
لما أكتر من خانة من أقسام مختلفة عندها نفس `sort_order`). كل خانة جوّه القسم بترندر بشكل
مختلف حسب `slot_type` بتاعها (`text`/`textarea`/`image`/`list`/`link`). الخانات الفاضية
بتتفلتر، والأقسام اللي كل خاناتها فاضية بتختفي بالكامل من الرندر. الألوان
(`TemplateVariant.colors_json`) بتتحط كـ CSS custom properties على الـ `<body>` (زي
`--site-primary`) — ده أسلوب متعمّد بدل كلاسات Tailwind ثابتة، عشان الألوان بتتغيّر لكل
مشروع وقت التشغيل (runtime)، مش وقت الـ build.

### التصميمات البصرية المتعددة (Phase 7)
كل قالب `landing` بيختار `layout` واحد من `Template::LAYOUTS` (15 تصميم: `classic`/`modern`/
`gallery`/`split`/`magazine`/`bento`/`minimal`/`bold`/`glass`/`timeline`/`stack`/`diagonal`/
`framed`/`neon`/`duotone`) — الاختلاف بينهم **بس** في شكل العرض، صفر تأثير على بنية الخانات أو اقتراح المحتوى
بالذكاء الاصطناعي (`OllamaService` وباقي النظام بيشتغلوا على `TemplateSlot` نفسه أياً كان الـ
layout). `SiteController`/`SiteExportService` الاتنين بيرندروا عن طريق شِل واحد مشترك
(`site/document.blade.php`) بياخد `layout` من `SiteRenderer::render()` ويعمل
`@include('site.layouts.'.$layout)` — نفس الشِل مستخدم في المعاينة الحية (`@vite`) والتصدير
الثابت (رابط CSS نسبي)، الفرق بس في `$cssMode`. الاسم العربي المعروض لكل تصميم مركزي في
`Template::layoutLabel()` (مستخدم في `templates/index.blade.php` و`templates/show.blade.php`
عشان مايتكررش الـ match في أكتر من فيو).

كل تصميم غير "classic" (اللي هو نفس السلوك القديم بالحرف، عمود واحد بسيط زي زمان) بيحتاج يعرف
شكل كل قسم (هيرو/جاليري/قايمة/cta/نص عادي) — ده بيتحسب في `SiteRenderer::classifySection()`
**من موقع القسم وأنواع خاناته**، مش من اسم القسم (`section_key`) نفسه، عشان يشتغل مع أي قالب
أياً كان تسميات أقسامه (بتاع المكتبة أو أي قالب الأدمن يعمله يدوي):
- أول قسم في الترتيب دايماً `hero`.
- أي قسم فيه خانة `image` بيبقى `gallery`.
- آخر قسم لو فيه خانة `link` بيبقى `cta`.
- قسم فيه خانة `list` بيبقى `list`.
- غير كده `text` (نص عادي).

كل ملفات التصميمات جوّه `resources/views/site/layouts/` — `classic.blade.php` بيستخدم
`site/partials/section.blade.php` القديم (بس دلوقتي بياخد لمسة "hero/cta" برضه بدل ما يفضل
مسطّح تماماً)، و`modern`/`gallery` ليهم بارشيالز خاصة بيهم (`modern-section.blade.php`/
`gallery-section.blade.php`) بيتسويتشوا على `$section['kind']`. باقي الـ 10 تصميمات
(split/magazine/bento/minimal/bold/glass/timeline/stack/diagonal/framed) كل واحد فيهم ملف
واحد self-contained (نافبار + كل حالات الأقسام + فوتر جوّه نفس الملف) بدل التقسيم لبارشيال
منفصل — أسهل مراجعة/تعديل لتصميم واحد من غير ما تقفز بين ملفات. `nav.blade.php` (نافبار "pill" عائم) و`footer.blade.php` مشتركين بس بين التصميمات اللي شكلها
قريب من بعض (modern/gallery/bento/timeline/stack/diagonal)، والباقي (split/magazine/minimal/
bold/glass/framed) عنده نافبار/فوتر خاص بيه مبني جوّه ملفه نفسه (مسطّح/بحدود رفيعة/بتباعد
حروف... إلخ) عشان يفضل متسق مع هوية التصميم — "classic" لوحده من غير نافبار خالص. آخر
اتنين اتضافوا (`neon` — عناوين/حدود متوهّجة بـ `text-shadow`/`box-shadow`، `duotone` — صور
بتأثير ثنائي اللون عن طريق `mix-blend-mode`) نفس نمط الملف الواحد self-contained.

### الخط العام + تخصيص خط/لون خانة واحدة (Phase 9)
6 خطوط متاحة (`TemplateVariant::FONTS`: `cairo`/`tajawal`/`almarai`/`ibm-plex-arabic`/
`poppins`/`inter`، كل واحد باكدج `@fontsource/*` مستضاف محلياً + متعرّف كـ `--font-{key}` في
`resources/css/app.css`). **مستويين من التخصيص، منفصلين تماماً:**
- **الخط العام للموقع كله** — `TemplateVariant.font` (زي الألوان بالظبط، عمود على نفس الجدول).
  `SiteRenderer` بيرجّعه كـ `font`، و`site/document.blade.php` بيحطه `font-family: var(--font-
  {key})` على الـ `<body>` (بدل الكلاس الثابت `font-[Cairo]` اللي كان موجود قبل كده).
- **لون/خط خانة واحدة بس** (نص/فقرة/قايمة بس — صفر معنى للصور أو تسمية زرار الرابط) —
  `GeneratedSite.style_overrides_json` (JSON: `{"hero_title": {"color": "#ff0000", "font":
  "tajawal"}}`)، بيتقرا عن طريق `GeneratedSite::styleFor($slotKey)`. بدل ما نلمس كل تصميم من
  الـ 15 بمنطق `style=` شرطي، `SiteRenderer` بيحط `'style' => $site->styleFor($slot->key)` على
  كل `$item`، وكل تصميم بس بيحط خاصية `data-slot="{{ $item['slot']->key }}"` على العنصر اللي
  بيعرض النص (بدون أي منطق ستايل جوّه التصميم نفسه). `site/document.blade.php` بيجمع كل
  التخصيصات الموجودة فعلاً ويولّدلها `<style>` block واحد بـ attribute selector
  (`[data-slot="..."] { color: ... !important; font-family: ... !important; }`) — فالتخصيص
  رندر بحت (CSS)، صفر تأثير على `content_json` أو أي منطق تاني.
  **الواجهة الإدارية:** فورم تعبئة محتوى المشروع (`projects/site-edit.blade.php`) فيه
  `<details>` "تخصيص لون/خط الخانة دي بس" تحت كل خانة نص/فقرة/قايمة — checkbox بيفعّل مربع
  لون (مربع لون `disabled` مالوش قيمة في الفورم أصلاً وقت الإرسال، وده اللي بيخلي "مفيش
  تخصيص" يترسل صح)، وdropdown اختيار خط بقيمة "— الخط العام —" كخيار افتراضي يعني "امسح
  التخصيص". `GeneratedSiteController::update()` بيبني `style_overrides_json` من `style[{key}]
  [color]`/`style[{key}][font]`، وبيمسح مفتاح أي خانة القيمتين بتوعها فاضيين (مفيش تراكم
  JSON فاضي).

### مكتبة القوالب الأصلية (Phase 7)
```bash
php artisan barq:seed-template-library
```
بيولّد (أو يحدّث — الأمر idempotent بالكامل عن طريق `updateOrCreate` على كل مستوى) 14 فئة نشاط
شائعة (مطاعم، عيادات، صالونات، جيم، عقارات، متاجر، تعليم، استشارات، مقاولات، صيانة سيارات،
فعاليات، سياحة، ستارت أب، بورتفوليو) × 3 قوالب لكل فئة (توزيع تلقائي على الـ 3 تصميمات) = 42
قالب. كل قوالب المكتبة دي **تصميم ومحتوى أصلي اتكتب خصيصاً للمشروع** (موثّق في
`license_note` بتاع كل قالب) — مفيش أي قالب أو تصميم منسوخ من مصدر خارجي، عشان صفر مخاطرة
ترخيص. بنية الخانات موحّدة لكل القوالب (6 أقسام: hero/about/services/gallery/testimonials/contact)
لكن التسميات والمحتوى الافتراضي (`TemplateSlot.default_value`) بيجي من بيانات الفئة نفسها —
خانات الروابط (`hero_cta`/`contact_link`) عمداً من غير `default_value` عشان محدش يشوف رابط
وهمي بالغلط.

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
