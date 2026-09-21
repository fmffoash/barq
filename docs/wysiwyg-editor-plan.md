# خطة: محرر بصري مباشر (WYSIWYG click-to-edit)

**طلب فؤاد (2026-09-21):** "التعديلات انا عايزك كاني فاتح مثلا برنامج الفوتوشوب الموقع مفتوح
بضغط علي اللون او الحته الي مش عاجباني واعدل فيها براحتي" — يعني بدل ما يروح لفورم تعبئة
المحتوى المنفصل (`projects/site-edit.blade.php`)، يفتح موقعه الحقيقي زي ما هو، ويدوس على أي
نص/لون/صورة فيه يعدلها في مكانها على طول (زي Notion/Framer/Webflow).

هذا الملف مكتوب عشان أي جلسة Claude تانية تقدر تبنيه من غير ما تحتاج سياق زيادة — كل حاجة
مذكورة هنا موجودة فعلياً في الكود دلوقتي، اتأكد منها قبل ما تكتب أي سطر.

## الأساس الموجود فعلاً (لا تعيد بناءه)

- كل عنصر محتوى في أي layout (`resources/views/site/layouts/*.blade.php`) بالفعل شايل
  `data-slot="{{ $item['slot']->key }}"` — ده اتضاف من قبل عشان `style_overrides_json`
  (تخصيص لون/خط خانة واحدة، شوف `SiteRenderer` وقسم "الخط العام + تخصيص خط/لون خانة واحدة"
  في `CLAUDE.md`). يعني **مفيش حاجة تتضاف في الـ Blade نفسه** — كل خانة قابلة للتحديد بـ
  `document.querySelectorAll('[data-slot]')` من غير أي تعديل في التصميمات.
- الحفظ الفعلي بيمر بـ `GeneratedSiteController::update()` (route: `PUT
  projects/{project}/site` باسم `projects.site.update`) واللي بياخد:
  - `content.{slotKey}` — نص الخانة. **للـ list: ده string واحد بسطور متعددة (`\n`
    مفصول)، مش array** — الكونترولر بيعمل `explode("\n", $value)` بنفسه (شوف
    `GeneratedSiteController::update()` سطر ~93). لو بنيت محرر list تفاعلي (زرار +/×)،
    اجمع القيم في textarea مخفي بسطر لكل عنصر قبل الإرسال، متبعتش array مباشر.
  - `content_files.{slotKey}` — ملف صورة حقيقي (multipart، مش رابط/URL) لخانات النوع
    `image`.
  - `style.{slotKey}.color` / `style.{slotKey}.font` — تخصيص الخانة دي بس.
  - تخصيصات على مستوى الموقع كله: `use_custom_colors` (boolean) + `colors_override` (JSON
    string) / `font_override` (string) / `use_custom_sections` (boolean) +
    `sections_override` (JSON string) — شوف `GeneratedSiteController::designOverrides()`.

  **⚠️ ملحوظتين مهمتين قبل ما تبني الحفظ التدريجي (خانة واحدة في كل نداء AJAX):**
  1. **`style.*` مش partial-safe دلوقتي** — الكونترولر بيلف على *كل* خانات القالب، ولو
     `style.{key}.color`/`style.{key}.font` مش موجودين في الـ request خالص بيعتبرهم "امسح
     التخصيص" (`unset($styleOverrides[$key])`) — عكس `content.*` اللي فعلاً partial (بيتفحّص
     `$request->has("content.{$key}")` قبل ما يلمسه). يعني لو المحرر الجديد بعت تعديل لون
     خانة واحدة بس من غير باقي الخانات، هيمسح تخصيصات لون/خط كل الخانات التانية بالغلط.
     **الحل الصح:** ضيف نفس فحص `has()` على `style.*` جوه `update()` (سطر الـ loop بتاع
     الـ style overrides) قبل ما تبني أي حفظ تدريجي — تعديل بسيط وآمن ومتوافق مع الفورم
     القديم (اللي أصلاً بيبعت كل الخانات مع بعض فمش هيتأثر).
  2. **`colors_override`/`sections_override` بيترجعوا `null` (يعني "ارجع لتصميم القالب
     الافتراضي") لو `use_custom_colors`/`use_custom_sections` مش `true`** — أي نداء AJAX
     بيغيّر لون عام لازم يبعت `use_custom_colors=1` مع الـ JSON الكامل (كل الألوان مش بس
     اللي اتغيّر)، وبرضه لترتيب الأقسام.
- منتقي الألوان (`resources/views/templates/partials/color-picker.blade.php`) ومنتقي ترتيب
  الأقسام (`partials/section-order-picker.blade.php`) موجودين وشغالين — المطلوب إعادة
  استخدامهم كـ overlay/drawer، مش إعادة بناءهم من الصفر.

## ⚠️ قيد أمان إجباري — اقرأه قبل ما تبدأ

المسار العام اللي بيعرض الموقع المنشور (`/site/{siteSlug}`، بدون auth middleware — أي حد
معاه الرابط يفتحه) **لازم يفضل زي ما هو بالظبط**. ممنوع تمنعاً باتاً إضافة أي query parameter
(زي `?edit=1`) على المسار العام ده يفعّل وضع التعديل — ده هيبقى ثغرة (أي حد يعرف الرابط يقدر
يجرب يعدل الموقع). وضع التعديل المباشر **لازم يبقى على route منفصل تماماً** جوه `routes/
web.php` (تحت نفس الـ middleware group بتاع `auth` الموجود فعلاً)، مثلاً:

```php
Route::get('projects/{project}/site/live-edit', [GeneratedSiteController::class, 'liveEdit'])
    ->name('projects.site.live-edit');
```

الـ controller method ده بيرندر نفس الموقع (استخدم `SiteRenderer::render($project->site)` -
راجع إزاي `SiteController::show()` بيستخدمها بالظبط) لكن بيلفه في شِل بيحمّل سكريبت/CSS وضع
التعديل، ومحمي بنفس الـ auth/ownership اللي على `projects.site.edit` النهاردة.

## الخطة التقنية

1. **Controller + route جديد** (`GeneratedSiteController::liveEdit()` أو مشابه): يرندر نفس
   الموقع (نفس `SiteRenderer` output) لكن بيحقن قبل `</body>` سكريبت/CSS وضع التعديل (ملف JS
   واحد بسيط، `public/js/live-editor.js` أو مشابه — صفر مكتبة خارجية، JS خام زي باقي
   المشروع).

2. **وضع hover + click على `[data-slot]`:**
   - hover: `outline` خفيف + أيقونة قلم صغيرة تظهر فوق العنصر.
   - click على نص (`slot_type` = text/textarea): يخلي العنصر `contenteditable="true"`،
     يظهر شريط أدوات عائم صغير جنبه (مربع لون + dropdown خط — نفس القيم المتاحة في
     `TemplateVariant::FONTS`)، والحفظ بيحصل `onblur` (أو زرار "تم" صغير) بـ `fetch()` على
     `projects.site.update` بنفس شكل الـ payload الحالي (`content[{slot}]`,
     `style[{slot}][color]`, `style[{slot}][font]`).
   - click على list (`slot_type` = list): كل عنصر في القايمة بيبقى قابل للتعديل مكانه +
     زرار "+" لإضافة عنصر و"×" صغير لحذف عنصر — عند الحفظ اجمعهم في نص واحد بسطر لكل عنصر
     (`\n` مفصول) وابعته كـ `content.{slot}` (مش array — شوف الملحوظة فوق).
   - click على link (`slot_type` = link): popover صغير فيه input واحد للرابط، يتبعت كـ
     `content.{slot}` عادي (رابط الـ CTA/التواصل نص عادي زي أي خانة text في الكونترولر).
   - click على image (`slot_type` = image): overlay بسيط لرفع صورة جديدة كملف حقيقي
     (`content_files.{slot}`، `multipart/form-data` — مش JSON/`fetch` عادي، لازم
     `FormData`) — استخدم نفس أسلوب رفع الصور الموجود في `projects/site-edit.blade.php`
     حالياً، لا تعيد اختراعه.

3. **زرار "تصميم الموقع" عائم** (زاوية الشاشة، مكانه ثابت): بيفتح drawer/overlay فيه نفس
   منتقي الألوان + منتقي الخط العام + ترتيب الأقسام الموجودين فعلاً، وبيحفظ على نفس
   `colors_override_json`/`font_override`/`sections_override_json`.

4. **كل حفظ AJAX** (`fetch` + CSRF token من `<meta name="csrf-token">` أو `document.querySelector`
   على input الفورم الحالي) — صفر إعادة تحميل صفحة، مع Toast بسيط "✓ اتحفظ" بعد كل تعديل
   ناجح، ورسالة خطأ واضحة لو فشل الحفظ (زي فقدان الاتصال بالإنترنت لحظياً).

5. **نقطة الدخول:** زرار جديد "عدّل الموقع بصرياً" في `projects/show.blade.php` جنب زرار
   "تعبئة المحتوى" الحالي، بيودي لـ `route('projects.site.live-edit', $project)`. الفورم
   القديم (`projects.site.edit`) يفضل موجود زي ما هو — دي إضافة مش استبدال، لحد ما فؤاد
   يجرب الاتنين ويقرر.

## اختبارات لازم تتضاف

- `auth` middleware بيمنع زائر مش مسجّل دخول من فتح `projects.site.live-edit`.
- مالك تاني (لو حصل مستقبلاً — دلوقتي أدمن واحد بس بس التست بيوثّق النية) مايقدرش يعدل
  مشروع مش بتاعه.
- حفظ نص خانة عن طريق الـ endpoint الجديد فعلاً بيغيّر `content_json` في الداتابيز (نفس
  تست موجود على `projects.site.update` ممكن تتقلّد بس من خلال الـ route الجديد لو الكونترولر
  method مختلفة).
- الرابط العام `/site/{slug}` **يفضل** يرجع نفس الرندر القديم بالظبط من غير أي أثر لوضع
  التعديل (يعني مفيش سكريبت التعديل بيتحمّل على الرابط العام خالص).

## ملاحظة أخيرة

الأولوية: نص + لون + خط أولاً (أكتر حاجة فؤاد هيستخدمها)، بعد كده صور، بعد كده قوائم/روابط.
لو الوقت ضيّق، سلّم نسخة أولى بالنص/اللون/الخط بس وسيب الباقي لمرحلة تانية — مش لازم كل حاجة
تتسلم مرة واحدة.
