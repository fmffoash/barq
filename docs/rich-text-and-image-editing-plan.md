# خطة: تنسيق نص زي وورد (Bold/Italic/تلوين جزء من الجملة) + تكبير/تحريك الصور

**طلب فؤاد (2026-09-22):** بعت صورة لشريط أدوات Word (خط، حجم، تخين/مايل/تحته خط، لون نص،
تظليل) وطلب نفس المستوى ده جوّه المحرر البصري المباشر (`site/live-edit`) — يقدر يعلّم **جزء
بس من الجملة** (كلمة وسط العنوان مثلاً) ويلوّنه/يخلّيه تخين لوحده من غير ما يأثر على باقي
النص. وطلب كمان إنه يقدر "يكبّر/يصغّر/يحرك" الصور من مكانها.

هذا الملف مكتوب عشان أي جلسة Claude تانية تقدر تنفّذه من غير ما تحتاج سياق زيادة من المحادثة
اللي اتكتب فيها — كل حاجة مذكورة هنا موجودة فعلياً في الكود دلوقتي (اتأكد منها وقت كتابة الخطة،
22 سبتمبر 2026)، اتأكد منها تاني بنفسك قبل ما تكتب أي سطر لو فيه شك.

## ⚠️ قواعد إجبارية قبل ما تبدأ (من CLAUDE.md الجذر، ومن التفاهم مع فؤاد)

1. **صفر مكتبة خارجية** — نفس باقي المشروع (`public/js/live-editor.js` كله JS خام بدون
   framework). استخدم `document.execCommand` لتنسيق النص الجزئي جوّه `contenteditable`
   (شوف "المرحلة 1" تحت) — ده مش أحدث API بس هو أبسط حل موثوق لتنسيق جزء من نص محدد بدون
   مكتبة، ومتاح في كل المتصفحات الحديثة لأغراض `contenteditable`.
2. **قاعدة "صفر `{!! !!}`" في CLAUDE.md الجذر بيتاخد استثناء ضيّق ومتعمّد هنا فقط** — راجع
   "الأمان: التطهير وقت الحفظ" تحت. أي استثناء تاني لازم يتقفل بنفس الطريقة (تطهير عند
   الكتابة، مش وقت العرض)، وممنوع تستخدم `{!! !!}` على أي قيمة متعرفش هي معدّاة على المطهّر
   ده قبل كده.
3. **ممنوع السيرفر الإنتاج** — إنت (الجلسة اللي بتنفّذ الخطة دي) بتشتغل على GitHub بس (branch
   + push). **متعملش أي SSH أو تعديل على `138.199.220.217` أبداً.** فؤاد بيدّي الخطة دي لجلسة
   Claude منفصلة، وجلسة تانية (اللي طلبت الخطة) هي اللي هتراجع وتنزل لايف بعد كده.
4. **مراحل منفصلة تماماً، كل واحدة قابلة للنشر لوحدها** — خلّص المرحلة 1 بالكامل (كود + اختبارات
   + `npm run build` ناجح محلي + push على branch منفصل) قبل ما تلمس أي حاجة في المرحلة 2.
   متبدأش المرحلة 2 إلا لو المرحلة 1 خلصت 100%. كل مرحلة PR/branch لوحدها.
5. **الفروع:**
   - المرحلة 1: branch اسمه `feature/rich-text-formatting` من `main`.
   - المرحلة 2: branch اسمه `feature/image-zoom-reposition`، لازم يتبني من `main` **بعد** ما
     المرحلة 1 تتدمج (يعني استنى تأكيد إن المرحلة 1 اتدمجت قبل ما تعمل branch المرحلة 2، عشان
     تتفادى تعارض في `live-editor.js`/`GeneratedSiteController.php` اللي الاتنين بيلمسوهم).
   - لو مش متأكد إن المرحلة 1 اتدمجت في `main` فعلاً، اعمل `git fetch && git log origin/main`
     وتأكد إن كوميتات المرحلة 1 موجودة هناك قبل ما تكمل.

---

## المرحلة 1: تنسيق جزء من النص (Bold/Italic/Underline/لون/تظليل/خط/حجم)

### الوضع الحالي (افهمه قبل ما تعدّل حاجة)

- خانات النص القابلة للتعديل (`slot_type` = `text`/`textarea`) بتتخزن كـ **نص عادي فقط** في
  `GeneratedSite.content_json[key]` — string واحد بلا أي تنسيق.
- بترندر بـ `{{ $value }}` (escaped، آمن) في **15 من 16 ملف layout** تحت
  `resources/views/site/layouts/*.blade.php` (كل واحد بيكرر نفس الـ`data-slot`/`{{ $value }}`
  بشكله)، ما عدا layout واحد (`classic.blade.php`) اللي بيستخدم partial مشترك
  `resources/views/site/partials/section.blade.php`. يعني **التعديل لازم يلمس كل الـ16 ملف**
  (أو على الأقل كل مكان فيه `{{ $value }}` لخانة `text`/`textarea`) — مش مكان واحد مركزي.
  دور على `grep -rn "data-slot=" resources/views/site/layouts/ resources/views/site/partials/`
  عشان تلاقي كل الأماكن بالظبط قبل ما تبدأ (فيه كمان
  `resources/views/site/partials/gallery-section.blade.php` و`modern-section.blade.php`
  مستخدمين من أكتر من layout).
- الحفظ بيمر بـ `GeneratedSiteController::update()` (`app/Http/Controllers/
  GeneratedSiteController.php`, نطاق `content.{key}` تقريباً سطر 151-167) — بياخد
  `content.{slotKey}` كـ string ويحطه في `$content[$key] = $value;` من غير أي تنقية.
- المحرر (`public/js/live-editor.js`) عنده بالفعل تولبار عائم لكل خانة نص (`buildToolbar()`)
  فيه لون + خط (dropdown مخصّص من `<div>`، شوف `.bq-toolbar-font-picker`). دلوقتي بيحفظ
  `active.el.innerText` بس (نص عادي، بيمسح أي تنسيق HTML لو حصل بالغلط).
- `commitActive()` (نفس الملف) بترجع الحفظ + عملت `reload()` بعد الحفظ لو
  `colorTouched`/`fontTouched` (إصلاح حديث، 22 سبتمبر 2026 — راجعه، منطقي إنه يتوسّع ليشمل
  `formatTouched` الجديد كمان).

### المطلوب

شريط الأدوات العائم (نفس التولبار الموجود، مش تولبار جديد) يتوسّع بزراير:
**تخين (B) / مايل (I) / تحته خط (U) / لون النص / تظليل (خلفية) / خط (موجود بالفعل) / حجم
الخط**، وكل واحد فيهم بيطبّق بس على الجزء المُحدّد (Selection) من النص جوّه العنصر، مش العنصر
كله — بالظبط زي الصورة اللي فؤاد بعتها من Word.

### التنفيذ التقني — الواجهة (public/js/live-editor.js + live-editor.css)

- استخدم `document.execCommand('bold')` / `'italic'` / `'underline'` / `'foreColor'` (مع
  `false, colorValue`) / `'hiliteColor'` (أو `'backColor'` كـ fallback لو `hiliteColor` مش
  مدعوم) / `'fontName'` / `'fontSize'` — كلهم بيشتغلوا على الـ Selection الحالي جوّه
  `contenteditable`.
- **مهم جداً:** أي زرار في التولبار بيضغط عليه المستخدم بيعمل `blur` على الـ`contenteditable`
  ويمسح الـ Selection قبل ما الكود يوصل لـ `execCommand` — ده باج كلاسيكي معروف. الحل: استخدم
  `mousedown` بدل `click` على كل زراير التولبار الجديدة، مع `event.preventDefault()` جوّه
  الـ handler (ده بيمنع الـ blur/فقدان الـ Selection قبل ما الأمر يتنفذ).
- زرار "تخين"/"مايل"/"تحته خط" toggle بسيط (زرار بيتفعّل/يتلغي، استخدم
  `document.queryCommandState('bold')` لو عايز تعكس الحالة الحالية في شكل الزرار — اختياري،
  مش إجباري للمرحلة دي).
- لون النص وتظليله: استخدم نفس `<input type="color">` الموجود بالفعل لتخصيص لون العنصر كله،
  بس اربطه بـ `execCommand('foreColor', false, value)` بدل ما يحفظ كخاصية عنصر كامل.
- حجم الخط: `execCommand('fontSize', false, N)` بياخد أرقام 1-7 بس (مقياس HTML قديم غريب،
  مش px) — البديل الأنضف: متستخدمش `fontSize` execCommand خالص، واعمل بدل منه: لف الـ
  Selection يدوياً في `<span style="font-size: Npx">` عن طريق
  `document.execCommand('fontSize', false, '7')` ثم استبدل كل `<font size="7">` بـ
  `<span style="font-size:...">` في الـ HTML الناتج (`document.querySelectorAll('font[size]')`
  → استبدلها بـ span). وثّق الحل اللي هتختاره في تعليق قصير في الكود لأي حد يراجعه بعدين.
- عند الحفظ (`commitActive()`): لو أي تنسيق اتغيّر (`formatTouched = true`)، ابعت
  `active.el.innerHTML` بدل `active.el.innerText` كقيمة `content[key]`. قارن `innerHTML`
  الأصلي بالجديد (مش `innerText`) عشان تعرف لو فيه تغيير فعلاً يستاهل حفظ.
- بعد الحفظ الناجح، لازم `reload()` (نفس منطق `styleTouched` الموجود) — التنسيق الجديد
  بيترندر من السيرفر بعد التطهير، مش هو نفس اللي كتبه المستخدم حرفياً (ممكن الرندر يختلف شوية
  عن التطهير)، فمهم يشوف الناتج الحقيقي بعد الحفظ.

### التنفيذ التقني — الأمان (تطهير وقت الحفظ، إجباري 100%)

**ده أهم جزء في المرحلة دي — أي تسريع أو تقصير هنا = ثغرة أمان حقيقية.**

1. اعمل كلاس جديد `app/Support/RichTextSanitizer.php` (أو `app/Services/RichTextSanitizer.php`
   — اتبع نمط تسمية المشروع، الخدمات في `app/Services/`) بميثود واحدة:
   `public static function clean(string $html): string`.
2. **استخدم `DOMDocument`، مش regex أو `strip_tags` بس** — `strip_tags`/regex سهل الالتفاف
   عليهم بمدخلات HTML مشوّهة عمداً. اعمل parse للـ HTML كـ fragment (لف المدخل في
   `<body>...</body>` قبل `loadHTML` مع `LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD`)، امشي
   على الشجرة (DOM tree) عقدة بعقدة، وابني output جديد من الصفر يحتوي بس على:
   - Tags مسموحة: `b`, `strong`, `i`, `em`, `u`, `span`, `br` — أي tag تاني (`script`, `img`,
     `a`, `iframe`, `svg`, ...) يتحوّل لنصه فقط (escaped)، مش يتشال بالكامل (عشان محتوى
     المستخدم النصي متتفقدش، بس الـ tag الخطر بس اللي بيتشال).
   - على `<span>` بس: خاصية `style` مسموحة، وتتفلتر بنفسها لسطر-سطر (split على `;`) — اسمح
     بس بـ: `color: #xxxxxx` (hex 6 خانات باستخدام نفس regex الموجود فعلاً
     `/^#[0-9a-fA-F]{6}$/` في `GeneratedSiteController::update()`), `background-color:
     #xxxxxx` (نفس القاعدة)، `font-family: var(--font-{key})` (لازم الـ key يكون موجود في
     `TemplateVariant::FONTS` بالظبط، نفس فحص `array_key_exists` الموجود حالياً)، و
     `font-size: Npx` أو `Nrem` (N رقم بين حدود منطقية، مثلاً 10-72px أو 0.6-4rem). أي خاصية
     CSS تانية (`background-image`, `position`, `behavior`, ...) أو قيمة فيها `url(`/
     `expression(`/`javascript:` تتشال بالكامل.
   - أي attribute تاني على أي tag (بما فيهم `on*`، `class`، `id`) يتشال بالكامل — الوحيد
     المسموح هو `style` على `span` وبس، بالقيود فوق.
3. اتأكد الدالة دي idempotent ومتقبلش تتفادى بمدخلات زي: `<script>alert(1)</script>`,
   `<img src=x onerror=alert(1)>`, `<span style="background:url(javascript:alert(1))">text
   </span>`, `<a href="javascript:alert(1)">click</a>`, `<svg onload=alert(1)>`, tags متداخلة
   أو غير مقفولة (`<b><i>text</b></i>`), UTF-8 مشوّه.
4. **نادي الدالة دي في `GeneratedSiteController::update()`** بس لخانات `slot_type` = `text` أو
   `textarea` (نفس القائمة الموجودة فعلاً في فحص `style.*` سطر ~119) — `list`/`image`/`link`
   **تفضل زي ما هي بالضبط (escaped، `{{ }}`) — ممنوع تلمسهم في المرحلة دي**، معندهمش داعي
   لتنسيق جزئي أصلاً.
5. غيّر `{{ $value }}` لـ `{!! $value !!}` **بس** في الأماكن اللي بترندر خانة `text`/`textarea`
   جوّه الـ16 layout + الـ partials المشتركة (راجع الـ`grep` فوق) — واكتب تعليق قصير فوق كل
   واحدة بيوضّح إن القيمة دي معدّاة بالفعل على `RichTextSanitizer::clean()` وقت الحفظ (مرجع
   لملف الكلاس، مش شرح تفصيلي مكرر في كل مكان).

### الاختبارات (إجبارية قبل ما تعتبر المرحلة خلصت)

في `tests/Feature/LiveEditorTest.php` (الملف موجود بالفعل، أضف عليه):

1. تست وحدة (unit) لـ `RichTextSanitizer::clean()` لوحدها — كل الـ payloads الخبيثة المذكورة
   فوق، اتأكد الناتج **صفر** `<script`, `onerror=`, `javascript:`, `<img`, `<a`, أي حاجة خطرة،
   بس النص المقروء نفسه (بدون الأكواد الخطرة) لازم يفضل موجود.
2. تست feature: `PUT projects/{project}/site` بـ `content.{key}` = HTML شرعي (`<b>تخين</b> نص
   عادي <span style="color:#ff0000">أحمر</span>`) — اتأكد إنه يتخزن ويترندر صح في صفحة
   `site.show` (استخدم نفس نمط الاختبارات الموجودة في الملف).
3. تست feature: نفس الشيء بـ payload خبيث كامل — اتأكد الصفحة المُرندرة (`get()->assertSee` /
   `assertDontSee`) **مفيهاش** أي سكريبت قابل للتنفيذ.
4. شغّل الـ suite الكامل (`php artisan test`) بعد كل تعديل — صفر ريجريشن في تستات تانية
   (خصوصاً `SiteRenderingTest.php`/`SiteLayoutTest.php`/`SiteExportTest.php` اللي بترندر نفس
   الـ layouts).

### تعريف "المرحلة خلصت" (Definition of Done)

- [ ] `RichTextSanitizer` مكتوب + مختبر (يونيت تست) ضد كل الـ payloads الخبيثة المذكورة فوق.
- [ ] `GeneratedSiteController::update()` بيستخدمه لخانات text/textarea بس.
- [ ] كل الـ16 layout (+ الـ partials المشتركة) بترندر `{!! !!}` للخانات دي بس، مع تعليق قصير
      لكل واحدة.
- [ ] التولبار في `live-editor.js` فيه B/I/U/لون/تظليل/خط/حجم، بيشتغلوا على Selection جزئي
      فعلاً (اختبره يدوي: علّم كلمة وسط جملة، خلّيها تخين، اتأكد باقي الجملة متأثرتش).
      **متعرفش تتأكد من ده غير باختبار يدوي حقيقي في متصفح — الاختبارات الآلية مش هتغطي شكل
      الـ Selection نفسه.**
- [ ] الحفظ بيبعت `innerHTML` ويعمل `reload()` بعد النجاح.
- [ ] `npm run build` ناجح من غير أخطاء.
- [ ] `php artisan test` كامل، صفر ريجريشن.
- [ ] Push على `feature/rich-text-formatting`، PR لـ `main` (أو push للـ branch بس لو مفيش
      صلاحية فتح PR — اللي يراجع هيقرر).
- [ ] **استنى هنا. متبدأش المرحلة 2 إلا بعد تأكيد إن المرحلة دي اتدمجت.**

---

## المرحلة 2: تكبير/تصغير/تحريك الصور (جوّه إطارها الحالي)

### قرار نطاق مهم — اقرأه قبل ما تبدأ

فؤاد طلب "تكبير/تصغير/تحريك" بشكل عام، وسأله Claude (اللي كتب الخطة دي) سؤال محدد: هل قصده
الصور بس ولا أي عنصر في الصفحة (نص/قسم كامل) — فؤاد اختار "أي عنصر"، **لكن** بعد شرح إن ده
معناه إعادة بناء نظام الـ16 تصميم من نموذج "ترتيب ثابت" لنموذج "موضع حر" (زي Canva/Wix)، وده
خطر حقيقي على شكل التصميمات الجاهزة (خصوصاً على الموبايل) ومجهود كبير جداً.

**القرار:** المرحلة دي (2) بتغطي **الصور بس**، جوّه المساحة (frame) اللي التصميم مخصصها لها
أصلاً — مش نقل الصورة لمكان تاني في الصفحة. ده بيدّي فؤاد تحكم حقيقي (تكبير/تصغير/اختيار الجزء
اللي بيبان من الصورة) من غير ما يكسر أي تصميم من الـ16. **توسيع الموضوع لعناصر تانية (نص كامل،
قسم كامل، نقل حر لأي مكان) = مرحلة 3 منفصلة تماماً، ممنوع تبدأ فيها أو حتى تخطط لها من غير
موافقة صريحة جديدة من فؤاد** — لأنها هتحتاج إعادة تصميم فعلي لكل الـ16 layout، مش إضافة صغيرة.

### الفكرة التقنية

مش "اسحب الصورة لأي حتة في الصفحة" — ده "زوم + تحريك جوّه المكان بتاعها" (بالظبط زي أداة تغيير
صورة الغلاف في فيسبوك/انستجرام): حجم الإطار (frame) نفسه ثابت زي ما التصميم حدده (`max-h-[...]`
وغيره في كل layout)، بس الصورة جوّاه تقدر:
- تتكبر/تصغر (zoom، مضاعف من 1.0 لـ 3.0 مثلاً).
- تتحرك يمين/شمال/فوق/تحت جوّه الإطار (`object-position`، نسبة مئوية 0-100 لكل محور).

### تغييرات البيانات

- `GeneratedSite.style_overrides_json[slotKey]` بالفعل موجود وبيحمل `color`/`font` لأي خانة
  (`app/Models/GeneratedSite.php::styleFor()`). وسّعه ليشمل خانات `image` كمان بمفتاحين جداد:
  `zoom` (float، 1.0-3.0) و`position` (string، `"X% Y%"` — زي قيمة CSS `object-position`
  مباشرة، كل رقم بين 0-100).
- **ملحوظة مهمة:** الفحص الحالي في `GeneratedSiteController::update()` (سطر ~118) بيقصر
  `style.*` على `slot_type` في `['text', 'textarea', 'list']` — `image` مش موجودة في القايمة
  دي خالص. لازم تضيف مسار validation منفصل لـ `style.{key}.zoom`/`style.{key}.position` بس
  لخانات `image`، بنفس مبدأ الـ `has()` الموجود (عشان يفضل partial-safe ومايمسحش تخصيص خانة
  صورة تانية في نداء AJAX لخانة واحدة).
- Validation: `zoom` رقم بين 1.0 و3.0 (`round(..., 2)`)، `position` لازم يطابق
  `/^\d{1,3}%\s\d{1,3}%$/` مع كل رقم فيها ≤ 100 (نفس فلسفة regex الألوان الموجودة — ارفض أي
  قيمة مش مطابقة تماماً بدل ما تحاول "تنضفها").

### الرندر (site/document.blade.php + كل layout بيعرض صورة)

- `document.blade.php` عنده بالفعل آلية مركزية لـ`$slotStyles` (`[data-slot="key"] { color:
  ...; font-family: ... }`، سطور 51-64 في الملف وقت كتابة الخطة دي) — **دي نفسها الآلية
  المطلوب توسيعها**، مش حاجة جديدة. ضيف جوّه نفس الـ`@foreach`:
  ```blade
  @if (filled($style['zoom'] ?? null) || filled($style['position'] ?? null))
      [data-slot="{{ $slotKey }}"] {
          @if (filled($style['position'] ?? null))
              object-position: {{ $style['position'] }} !important;
          @endif
          @if (filled($style['zoom'] ?? null))
              transform: scale({{ $style['zoom'] }});
          @endif
      }
  @endif
  ```
  (لاحظ: `object-position` بيشتغل بس لو الصورة عندها `object-fit: cover` — راجع كل الـ16
  layout اتأكد كل `<img>` لخانة صورة عندها `object-cover` بالفعل، الأغلبية عندها زي ما شفنا في
  `site/partials/section.blade.php`. لو `transform: scale()` بيكسر الإطار المدوّر الحواف
  (`overflow` مش `hidden` على الحاوي)، ضيف `overflow-hidden` على حاوية الصورة في أي layout
  ناقصها — تأكد بعين حقيقية في المتصفح مش بس بالقراءة).
- `SiteRenderer::render()` لازم يبعت `zoom`/`position` جوّه `$item['style']` (نفس مصدر
  `$slotStyles` — راجع `app/Services/SiteRenderer.php` سطر ~43 `'style' =>
  $site->styleFor($slot->key)`).

### الواجهة (المحرر البصري)

- خانة الصورة مش `contenteditable` زي النص، فمحتاجة تفاعل مختلف تماماً. لما المستخدم يدوس على
  صورة جوّه `live-edit`، افتح overlay بسيط فوق الصورة نفسها (مش drawer جانبي منفصل) فيه:
  - Slider للزوم (`<input type="range" min="1" max="3" step="0.1">`).
  - منطقة السحب: `mousedown` على الصورة → `mousemove` بيحسب الفرق وبيحدّث `object-position`
    مباشرة كـ CSS مباشر على العنصر (معاينة فورية زي ما فيه بالفعل للألوان) → `mouseup` بيثبّت
    القيمة النهائية.
  - زرار "تم" يحفظ (`style[key][zoom]`, `style[key][position]`) بنفس آلية `save()` الموجودة،
    وبعد النجاح `reload()` (نفس منطق `styleTouched`).
  - زرار "إعادة الضبط" يمسح التخصيص (يبعت قيم فاضية، الكونترولر هيمسحهم من الـ JSON).
- ابنيها كدالة جديدة منفصلة في `live-editor.js` (مثلاً `openImageEditor(el)`)، منفصلة تماماً عن
  `startEditing()`/`buildToolbar()` الحالية (اللي مخصصة للنص بس) — متحاولش "تشارك" الكود بين
  الاتنين، الفرق في التفاعل كبير كفاية إنه يستاهل مسار منفصل واضح.

### الاختبارات

- تست feature: حفظ `style.{key}.zoom`/`position` لخانة صورة، اتأكد اتخزن واترندر في CSS الصفحة
  صح (`assertSee` على قيمة الـ `object-position`/`scale` في الـ HTML الناتج).
- تست feature: قيم خارج الحدود (`zoom=99`, `position="200% 200%"`, `position="<script>"`) —
  اتأكد بترفض/بتتجاهل، مش بتتخزن زي ما هي.
- شغّل الـ suite الكامل تاني.

### تعريف "المرحلة خلصت"

- [ ] `style_overrides_json` بيقبل `zoom`/`position` لخانات `image` بس، بعد validation صارم.
- [ ] CSS بيترندر صح في `document.blade.php` عن طريق نفس آلية `$slotStyles` الموجودة.
- [ ] واجهة overlay التحكم في الصورة شغالة (زوم + سحب + حفظ + إعادة ضبط) في `live-editor.js`.
- [ ] اختبار يدوي حقيقي: كبّر/صغّر/حرّك صورة هيرو حقيقية، احفظ، اعمل reload، اتأكد الشكل
      محفوظ صح ومفيش كسر في الإطار المدوّر أو الـ layout حواليها.
- [ ] `npm run build` ناجح، `php artisan test` كامل بصفر ريجريشن.
- [ ] Push على `feature/image-zoom-reposition`.

---

## مرحلة 3 (مستقبلية — غير مخطط لها بعد، لا تبدأ فيها)

تحريك/تكبير حر لأي عنصر في الصفحة (نص كامل، قسم كامل) لأي مكان — زي برنامج تصميم كامل
(Canva/Wix). دي مش امتداد بسيط لمرحلة 2، دي إعادة تصميم فعلية لنموذج الرندر بتاع الـ16 تصميم
(من "ترتيب Flexbox/Grid ثابت" لـ"مواضع حرة X/Y محفوظة لكل خانة"). **لازم جلسة تخطيط منفصلة
تماماً** تراجع كل الـ16 layout واحد واحد وتقرر إزاي هيتوافق النموذجين مع بعض (هل كل layout
بيبقى له "وضع حر" اختياري؟ هل في حد أدنى/أقصى لمنع كسر الشكل على الموبايل؟) قبل ما أي كود يتكتب.
متبدأش فيها من غير رجوع لفؤاد أول.
