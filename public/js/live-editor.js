// المحرر البصري المباشر (WYSIWYG click-to-edit) — docs/wysiwyg-editor-plan.md.
// صفر مكتبة خارجية عمداً (زي باقي سكريبتات المشروع). النص/الفقرة بس دلوقتي (أولوية
// الخطة) — القوائم/الروابط/الصور لسه بتترندر عادي، تعديلهم لسه من فورم "تعبئة المحتوى".
(function () {
    'use strict';

    var configEl = document.getElementById('live-editor-config');
    if (!configEl) {
        return;
    }

    var config = JSON.parse(configEl.textContent);

    // ---------- Toast ----------
    var toastContainer = document.getElementById('bq-toast-container');

    function toast(message, isError) {
        if (!toastContainer) {
            return;
        }
        var el = document.createElement('div');
        el.className = 'bq-toast' + (isError ? ' bq-toast-error' : '');
        el.textContent = message;
        toastContainer.appendChild(el);
        requestAnimationFrame(function () {
            el.classList.add('bq-toast-show');
        });
        setTimeout(function () {
            el.classList.remove('bq-toast-show');
            setTimeout(function () {
                el.remove();
            }, 250);
        }, 2600);
    }

    // ---------- Save ----------
    function save(entries, successMessage) {
        var formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('_token', config.csrfToken);
        entries.forEach(function (entry) {
            formData.append(entry[0], entry[1]);
        });

        return fetch(config.saveUrl, {
            method: 'POST',
            body: formData,
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('save failed: ' + res.status);
                }
                toast(successMessage || '✓ اتحفظ');
                return true;
            })
            .catch(function () {
                toast('حصل خطأ وقت الحفظ — حاول تاني', true);
                return false;
            });
    }

    // ---------- Text/textarea click-to-edit ----------
    var editableSlots = Object.keys(config.slotTypes).filter(function (key) {
        var type = config.slotTypes[key];
        return type === 'text' || type === 'textarea';
    });

    document.querySelectorAll('[data-slot]').forEach(function (el) {
        if (editableSlots.indexOf(el.dataset.slot) !== -1) {
            el.setAttribute('data-bq-editable', '1');
        }
    });

    var active = null; // { el, key, type, originalText, originalHTML, colorTouched, fontTouched, formatTouched, pendingColor, pendingFont, savedRange }
    var toolbarEl = null;

    // ---------- تنسيق نص جزئي (Bold/Italic/Underline/لون/تظليل/خط/حجم — Word-style) ----------
    // docs/rich-text-and-image-editing-plan.md، المرحلة 1. الحفظ نفسه (content.{key} كـ
    // innerHTML) بيتطهّر سيرفر-سايد في GeneratedSiteController::update() عن طريق
    // RichTextSanitizer — أي وسم غير b/strong/i/em/u/span/br بيتحول لنصه بس وقت الحفظ.
    //
    // باج معروف: أي عنصر بياخد focus (زرار عادي، <input type="color">، <select>) بيعمل blur
    // على الـcontenteditable ويمسح الـSelection قبل ما نوصل لـexecCommand. الحل هنا عام لكل
    // أدوات التنسيق مع بعض: بنسجّل آخر Selection صالحة جوّه active.el باستمرار (selectionchange)
    // وبنرجّعها (focus + addRange) قبل أي execCommand مباشرة — مش بس وقت الحاجة، عشان يشتغل
    // مع الزراير العادية (mousedown+preventDefault بيمنع الـblur أصلاً) وأدوات الفورم الأصلية
    // (color/select، اللي preventDefault هيمنع الـpicker من الفتح خالص لو استخدمناه معاها).
    function saveSelectionIfInsideActive() {
        if (!active) {
            return;
        }
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) {
            return;
        }
        var range = sel.getRangeAt(0);
        if (active.el.contains(range.commonAncestorContainer)) {
            active.savedRange = range.cloneRange();
        }
    }

    document.addEventListener('selectionchange', saveSelectionIfInsideActive);

    function restoreSelection() {
        if (!active) {
            return;
        }
        // execCommand('styleWithCSS') بقى true افتراضياً في متصفحات كتير حديثة — يعني
        // bold/italic/underline بيطلعوا <span style="font-weight/font-style/text-decoration">
        // بدل <b>/<i>/<u> (اتأكدنا منها فعلياً وقت التطوير)، وده مش خصائص مسموحة في
        // RichTextSanitizer فهتتشال بالكامل وقت الحفظ. تثبيته على false هنا (قبل أي
        // execCommand) بيضمن الوسوم القديمة (b/i/u/font) اللي المطهّر فعلاً بيقبلها أو
        // بيحوّلها (font عن طريق normalizeLegacyFontTags فوق).
        try {
            document.execCommand('styleWithCSS', false, false);
        } catch (e) {
            // مفيش حاجة نعملها — بعض المتصفحات القديمة أصلاً مبتفهمش الأمر ده وده الوضع
            // الافتراضي بتاعها زي ما هو.
        }
        active.el.focus();
        var sel = window.getSelection();
        sel.removeAllRanges();
        if (active.savedRange) {
            sel.addRange(active.savedRange);
        }
    }

    function markFormatted() {
        active.formatTouched = true;
    }

    function applyToggleCommand(command) {
        restoreSelection();
        document.execCommand(command);
        markFormatted();
        updateFormatButtonStates();
    }

    // execCommand('foreColor'/'hiliteColor'/'fontName'/'fontSize') بتولّد وسوم
    // <font color=""/face=""/size=""> جوّه المتصفح (اتأكدنا فعلياً وقت التطوير) — <font>
    // مش وسم مسموح في RichTextSanitizer (b/strong/i/em/u/span/br بس)، فهيتحول لنصه بس وأي
    // تنسيق هيضيع وقت الحفظ لو سبناه زي ما هو. الحل: نسيب execCommand يعمل الـwrap الصعب
    // (حوالين أي Selection معقد/متداخل)، وبعدين نحوّل أي <font> ناتج لـ<span style="...">
    // يعدّي صح من المطهّر. foreColor/hiliteColor بيحطوا القيمة الحقيقية في attribute
    // color مباشرة فبنقرأها زي ما هي؛ fontName/fontSize بنبعتلهم قيمة "marker" ثابتة
    // (face="bq-font-marker"، size="7") عشان نلاقيها ونستبدلها بالقيمة الحقيقية المطلوبة.
    function normalizeLegacyFontTags(root, markerFontKey, markerSizePx) {
        root.querySelectorAll('font').forEach(function (fontEl) {
            var declarations = [];

            var color = fontEl.getAttribute('color');
            if (color) {
                declarations.push('color: ' + color);
            }

            if (markerFontKey && fontEl.getAttribute('face') === 'bq-font-marker') {
                declarations.push('font-family: var(--font-' + markerFontKey + ')');
            }

            if (markerSizePx && fontEl.getAttribute('size') === '7') {
                declarations.push('font-size: ' + markerSizePx + 'px');
            }

            var span = document.createElement('span');
            if (declarations.length > 0) {
                span.setAttribute('style', declarations.join('; '));
            }
            while (fontEl.firstChild) {
                span.appendChild(fontEl.firstChild);
            }
            fontEl.parentNode.replaceChild(span, fontEl);
        });
    }

    function applyForeColor(color) {
        restoreSelection();
        document.execCommand('foreColor', false, color);
        normalizeLegacyFontTags(active.el, null, null);
        markFormatted();
    }

    function applyHighlight(color) {
        restoreSelection();
        var applied = false;
        try {
            applied = document.execCommand('hiliteColor', false, color);
        } catch (e) {
            applied = false;
        }
        if (!applied) {
            try {
                document.execCommand('backColor', false, color);
            } catch (e) {
                // مفيش fallback تاني — لو الاتنين فشلوا، ببساطة مفيش تظليل اتطبّق.
            }
        }
        normalizeLegacyFontTags(active.el, null, null);
        markFormatted();
    }

    function applyFontFamily(fontKey) {
        restoreSelection();
        document.execCommand('fontName', false, 'bq-font-marker');
        normalizeLegacyFontTags(active.el, fontKey, null);
        markFormatted();
    }

    // execCommand('fontSize', false, N) بياخد مقياس HTML قديم (1-7)، مش px.
    function applyFontSize(px) {
        restoreSelection();
        document.execCommand('fontSize', false, '7');
        normalizeLegacyFontTags(active.el, null, px);
        markFormatted();
    }

    function updateFormatButtonStates() {
        if (!toolbarEl) {
            return;
        }
        ['bold', 'italic', 'underline'].forEach(function (command) {
            var btn = toolbarEl.querySelector('[data-format-command="' + command + '"]');
            if (!btn) {
                return;
            }
            var isActive = false;
            try {
                isActive = document.queryCommandState(command);
            } catch (e) {
                isActive = false;
            }
            btn.classList.toggle('bq-toolbar-format-active', isActive);
        });
    }

    function hexFromComputedColor(computed) {
        var match = /^rgba?\((\d+),\s*(\d+),\s*(\d+)/.exec(computed || '');
        if (!match) {
            return '#f1f5f9';
        }
        var toHex = function (n) {
            var h = parseInt(n, 10).toString(16);
            return h.length === 1 ? '0' + h : h;
        };
        return '#' + toHex(match[1]) + toHex(match[2]) + toHex(match[3]);
    }

    function positionToolbar() {
        if (!active || !toolbarEl) {
            return;
        }
        var rect = active.el.getBoundingClientRect();
        var top = rect.top - 46;
        if (top < 48) {
            top = rect.bottom + 8;
        }
        toolbarEl.style.top = Math.max(0, top) + 'px';
        toolbarEl.style.left = Math.max(8, rect.left) + 'px';
    }

    // زراير تخين/مايل/تحته خط — toggle بسيط، بيطبّق بس على الـSelection الحالي (Word-style).
    // mousedown+preventDefault (مش click) عشان نمنع الـblur اللي بيمسح الـSelection قبل ما
    // نوصل لـexecCommand أصلاً.
    function buildFormatButton(label, command) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'bq-toolbar-format-btn bq-toolbar-format-btn-' + command;
        btn.textContent = label;
        btn.setAttribute('data-format-command', command);
        btn.setAttribute('title', label);
        btn.addEventListener('mousedown', function (event) {
            event.preventDefault();
            applyToggleCommand(command);
        });

        return btn;
    }

    // منتقي لون/تظليل جزئي (Word-style) — <input type="color"> عادي، بس محتاج نحفظ الـ
    // Selection وقت mousedown (قبل ما الـpicker يفتح ويسرق الـfocus) عشان نرجّعها وقت
    // input (لحظة اختيار اللون فعلياً).
    function buildInlineColorInput(label, onApply) {
        var wrapLabel = document.createElement('label');
        wrapLabel.textContent = label;
        var input = document.createElement('input');
        input.type = 'color';
        input.value = '#f1f5f9';
        input.addEventListener('mousedown', saveSelectionIfInsideActive);
        input.addEventListener('input', function () {
            onApply(input.value);
        });
        wrapLabel.appendChild(input);

        return wrapLabel;
    }

    // حجم الخط الجزئي — قايمة أحجام جاهزة (px). <select> عادي هنا آمن (الحجم رقم بس، مفيهوش
    // مشكلة "font-family جوّه option متتجاهلش" اللي خلّتنا نتجنّب select لمنتقي الخط، شوف
    // تعليق Phase 16 فوق).
    function buildFontSizeSelect() {
        var wrapLabel = document.createElement('label');
        wrapLabel.textContent = 'حجم';
        var select = document.createElement('select');
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'حجم الخط';
        select.appendChild(placeholder);
        [12, 14, 16, 18, 20, 24, 28, 32, 40, 48, 64].forEach(function (px) {
            var opt = document.createElement('option');
            opt.value = String(px);
            opt.textContent = px + 'px';
            select.appendChild(opt);
        });
        select.addEventListener('mousedown', saveSelectionIfInsideActive);
        select.addEventListener('change', function () {
            if (select.value) {
                applyFontSize(Number(select.value));
            }
            select.value = '';
        });
        wrapLabel.appendChild(select);

        return wrapLabel;
    }

    // خط الجزء المحدد — نفس فكرة منتقي الخط الحالي (div بدل select، شوف تعليق Phase 16)، بس
    // بيطبّق بـapplyFontFamily على الـSelection بدل ما يحفظ كتخصيص للخانة كلها.
    function buildInlineFontPicker() {
        var wrapLabel = document.createElement('label');
        wrapLabel.textContent = 'خط الجزء';

        var picker = document.createElement('div');
        picker.className = 'bq-toolbar-font-picker';

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'bq-toolbar-font-toggle';
        toggle.textContent = 'اختر خط';

        var list = document.createElement('div');
        list.className = 'bq-toolbar-font-list';
        list.hidden = true;

        Object.keys(config.fonts).forEach(function (key) {
            var opt = document.createElement('div');
            opt.className = 'bq-toolbar-font-option';
            opt.textContent = config.fonts[key];
            opt.style.fontFamily = 'var(--font-' + key + ')';
            opt.addEventListener('mousedown', saveSelectionIfInsideActive);
            opt.addEventListener('click', function () {
                applyFontFamily(key);
                list.hidden = true;
            });
            list.appendChild(opt);
        });

        toggle.addEventListener('mousedown', saveSelectionIfInsideActive);
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            list.hidden = !list.hidden;
        });

        picker.appendChild(toggle);
        picker.appendChild(list);
        wrapLabel.appendChild(picker);

        return wrapLabel;
    }

    function buildToolbar() {
        var override = config.styleOverrides[active.key] || {};
        var el = document.createElement('div');
        el.className = 'bq-toolbar';

        // ---- تنسيق الجزء المحدد من النص (Word-style، المرحلة 1) ----
        el.appendChild(buildFormatButton('B', 'bold'));
        el.appendChild(buildFormatButton('I', 'italic'));
        el.appendChild(buildFormatButton('U', 'underline'));
        el.appendChild(buildInlineColorInput('لون النص', applyForeColor));
        el.appendChild(buildInlineColorInput('تظليل', applyHighlight));
        el.appendChild(buildInlineFontPicker());
        el.appendChild(buildFontSizeSelect());

        var divider = document.createElement('span');
        divider.className = 'bq-toolbar-divider';
        el.appendChild(divider);

        // ---- تخصيص الخانة كلها (زي ما كان، Phase 8/16 — style_overrides_json) ----
        var colorLabel = document.createElement('label');
        colorLabel.textContent = 'لون';
        var colorInput = document.createElement('input');
        colorInput.type = 'color';
        colorInput.value = override.color || hexFromComputedColor(getComputedStyle(active.el).color);
        colorInput.addEventListener('input', function () {
            active.colorTouched = true;
            active.pendingColor = colorInput.value;
        });
        colorLabel.appendChild(colorInput);

        // منتقي خط مخصّص (Phase 16، 2026-09-21) — <select> عادي هنا استبدلناه لأن قايمة
        // <option> المفتوحة بترندرها واجهة نظام التشغيل نفسها في متصفحات كتير، وأي CSS
        // بنحطه على font-family جوّه <option> بيتجاهل بالكامل (فؤاد أكّد حياً إنها مش
        // بتظهر). البديل: عناصر <div> عادية إحنا بنتحكم فيها بالكامل، مضمون تحترم أي style.
        var fontLabel = document.createElement('label');
        fontLabel.textContent = 'الخط';

        var fontPicker = document.createElement('div');
        fontPicker.className = 'bq-toolbar-font-picker';

        var fontToggle = document.createElement('button');
        fontToggle.type = 'button';
        fontToggle.className = 'bq-toolbar-font-toggle';
        var currentFontLabel = override.font ? (config.fonts[override.font] || override.font) : '— الخط العام —';
        fontToggle.textContent = currentFontLabel;
        fontToggle.style.fontFamily = override.font ? ('var(--font-' + override.font + ')') : '';

        var fontList = document.createElement('div');
        fontList.className = 'bq-toolbar-font-list';
        fontList.hidden = true;

        function addFontOption(value, text) {
            var opt = document.createElement('div');
            opt.className = 'bq-toolbar-font-option';
            opt.textContent = text;
            if (value) opt.style.fontFamily = 'var(--font-' + value + ')';
            opt.addEventListener('click', function () {
                active.fontTouched = true;
                active.pendingFont = value;
                fontToggle.textContent = text;
                fontToggle.style.fontFamily = value ? ('var(--font-' + value + ')') : '';
                fontList.hidden = true;
            });
            fontList.appendChild(opt);
        }

        addFontOption('', '— الخط العام —');
        Object.keys(config.fonts).forEach(function (key) {
            addFontOption(key, config.fonts[key]);
        });

        fontToggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            fontList.hidden = !fontList.hidden;
        });

        fontPicker.appendChild(fontToggle);
        fontPicker.appendChild(fontList);
        fontLabel.appendChild(fontPicker);

        var resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'bq-toolbar-reset';
        resetBtn.textContent = '✕ إزالة التخصيص';
        resetBtn.addEventListener('click', function () {
            active.colorTouched = true;
            active.fontTouched = true;
            active.pendingColor = '';
            active.pendingFont = '';
            commitActive();
        });

        var doneBtn = document.createElement('button');
        doneBtn.type = 'button';
        doneBtn.className = 'bq-toolbar-done';
        doneBtn.textContent = 'تم';
        doneBtn.addEventListener('click', function () {
            commitActive();
        });

        el.appendChild(colorLabel);
        el.appendChild(fontLabel);
        el.appendChild(resetBtn);
        el.appendChild(doneBtn);
        document.body.appendChild(el);
        toolbarEl = el;
        positionToolbar();
        updateFormatButtonStates();
    }

    function startEditing(el) {
        if (active) {
            commitActive();
        }

        var key = el.dataset.slot;
        active = {
            el: el,
            key: key,
            type: config.slotTypes[key],
            originalText: el.innerText,
            originalHTML: el.innerHTML,
            colorTouched: false,
            fontTouched: false,
            formatTouched: false,
            pendingColor: '',
            pendingFont: '',
            savedRange: null,
        };

        el.setAttribute('contenteditable', 'true');
        el.classList.add('bq-editing');
        el.focus();

        var range = document.createRange();
        range.selectNodeContents(el);
        range.collapse(false);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);

        buildToolbar();
    }

    function closeActive(save) {
        if (!active) {
            return;
        }
        active.el.removeAttribute('contenteditable');
        active.el.classList.remove('bq-editing');
        if (!save) {
            // بيرجّع innerHTML الأصلي (مش innerText) عشان لو تنسيق اتطبّق (Bold/لون/...)
            // وبعدين المستخدم عمل Escape، أي تنسيق اتضاف يتلغي هو كمان مش النص بس.
            active.el.innerHTML = active.originalHTML;
        }
        if (toolbarEl) {
            toolbarEl.remove();
            toolbarEl = null;
        }
        active = null;
    }

    function commitActive() {
        if (!active) {
            return;
        }

        var entries = [];
        // تنسيق جزئي (Bold/لون/خط/حجم — Word-style، المرحلة 1) بيبعت innerHTML بدل نص عادي،
        // عشان الوسوم المسموحة (b/i/u/span...) تتخزن وتترندر — المقارنة هنا بـinnerHTML مش
        // innerText برضه عشان نلاقي أي تغيير تنسيق حتى لو النص الظاهري نفسه متغيرش.
        if (active.formatTouched) {
            var newHTML = active.el.innerHTML.trim();
            if (newHTML !== active.originalHTML.trim()) {
                entries.push(['content[' + active.key + ']', newHTML]);
            }
        } else {
            var newText = active.el.innerText.trim();
            if (newText !== active.originalText.trim()) {
                entries.push(['content[' + active.key + ']', newText]);
            }
        }
        // تغيير لون/خط الخانة كلها أو تنسيق جزء منها بيتخزن صح في السيرفر فوراً، بس شكله
        // الفعلي (زي أي CSS تاني في الصفحة، أو أي HTML اتطهّر شوية عن اللي كتبه المستخدم
        // حرفياً) بيتحدد وقت الـ render — من غير ريلود الصفحة هيفضل شكله زي ما كان قبل
        // الحفظ حتى لو الحفظ نجح 100%. عشان كده لازم ريلود هنا تحديداً.
        var styleTouched = active.colorTouched || active.fontTouched || active.formatTouched;
        if (active.colorTouched) {
            entries.push(['style[' + active.key + '][color]', active.pendingColor]);
        }
        if (active.fontTouched) {
            entries.push(['style[' + active.key + '][font]', active.pendingFont]);
        }

        closeActive(true);

        if (entries.length === 0) {
            return;
        }

        save(entries).then(function (ok) {
            if (ok && styleTouched) {
                window.location.reload();
            }
        });
    }

    // ---------- تكبير/تصغير/تحريك الصورة جوّه إطارها الثابت (المرحلة 2) ----------
    // docs/rich-text-and-image-editing-plan.md — منفصل تماماً عن startEditing/buildToolbar
    // (مخصصة للنص)، الفرق في التفاعل (سحب حر + سلايدر، مش contenteditable) كبير كفاية إنه
    // يستاهل مسار واضح لوحده. كل خانة صورة (slot_type=image) في أي قالب — مش مقصور على
    // مفتاح بعينه زي hero_image، شوف توضيح النطاق في ملف الخطة.
    var imageEditableSlots = Object.keys(config.slotTypes).filter(function (key) {
        return config.slotTypes[key] === 'image';
    });

    document.querySelectorAll('[data-slot]').forEach(function (el) {
        if (el.tagName === 'IMG' && imageEditableSlots.indexOf(el.dataset.slot) !== -1) {
            el.setAttribute('data-bq-image-editable', '1');
        }
    });

    var activeImage = null; // { el, key, zoom, position, originalTransform, originalObjectPosition, dragBar, controlsBar }
    var imageDragging = false;

    function parsePosition(position) {
        var m = /^(\d{1,3})% (\d{1,3})%$/.exec(position || '');

        return m ? { x: Number(m[1]), y: Number(m[2]) } : { x: 50, y: 50 };
    }

    function applyImagePreview() {
        if (!activeImage) {
            return;
        }
        // معاينة فورية على العنصر الحقيقي مباشرة (نفس روح المعاينة الفورية لألوان التصميم) —
        // نفس CSS بالظبط اللي هيتولّد سيرفر-سايد بعد الحفظ (site/document.blade.php).
        activeImage.el.style.transform = 'scale(' + activeImage.zoom + ')';
        activeImage.el.style.objectPosition = activeImage.position.x + '% ' + activeImage.position.y + '%';
    }

    function positionImageControls() {
        if (!activeImage) {
            return;
        }
        // بنستخدم مستطيل الحاوية (parentElement) مش الصورة نفسها — الصورة بعد transform:
        // scale() (المرحلة 2) بيرجّع getBoundingClientRect بتاعها المساحة البصرية الموسّعة
        // بعد التكبير (ممكن تبقى أضعاف حجم الشاشة)، وده بيخلي شريط الأدوات يترندر برّه الشاشة
        // خالص. الحاوية (overflow-hidden) دايماً بتفضل بنفس حجمها المرئي الثابت بغض النظر عن
        // قيمة الزوم.
        var rect = activeImage.el.parentElement.getBoundingClientRect();
        if (activeImage.dragBar) {
            activeImage.dragBar.style.top = rect.top + 'px';
            activeImage.dragBar.style.left = rect.left + 'px';
            activeImage.dragBar.style.width = rect.width + 'px';
            activeImage.dragBar.style.height = rect.height + 'px';
        }
        if (activeImage.controlsBar) {
            // زي positionToolbar بالظبط (تنسيق النص) — لو تحت الصورة برّه الشاشة (صورة
            // بارتفاع كبير قريب من ارتفاع الشاشة كله)، جرّب فوقها؛ ولو فوقها كمان برّه
            // الشاشة (صورة بتاخد الشاشة كلها تقريباً)، ثبّته جوّه حدود الشاشة أياً كان.
            var top = rect.bottom + 8;
            if (top > window.innerHeight - 60) {
                top = rect.top - 46;
            }
            top = Math.max(4, Math.min(top, window.innerHeight - 52));
            activeImage.controlsBar.style.top = top + 'px';
            activeImage.controlsBar.style.left = Math.max(8, rect.left) + 'px';
        }
    }

    function closeImageEditor(keepAppliedStyle) {
        if (!activeImage) {
            return;
        }
        if (!keepAppliedStyle) {
            activeImage.el.style.transform = activeImage.originalTransform;
            activeImage.el.style.objectPosition = activeImage.originalObjectPosition;
        }
        if (activeImage.dragBar) {
            activeImage.dragBar.remove();
        }
        if (activeImage.controlsBar) {
            activeImage.controlsBar.remove();
        }
        document.removeEventListener('scroll', positionImageControls, true);
        window.removeEventListener('resize', positionImageControls);
        activeImage = null;
    }

    function commitImageEditor() {
        if (!activeImage) {
            return;
        }
        var key = activeImage.key;
        var entries = [
            ['style[' + key + '][zoom]', String(activeImage.zoom)],
            ['style[' + key + '][position]', activeImage.position.x + '% ' + activeImage.position.y + '%'],
        ];
        closeImageEditor(true);
        // نفس سبب الريلود بتاع تنسيق النص/الخط: الـzoom/position النهائي بيتحسم في CSS
        // السيرفر (site/document.blade.php)، مش المعاينة اللحظية هنا.
        save(entries, '✓ اتحفظت الصورة').then(function (ok) {
            if (ok) {
                window.location.reload();
            }
        });
    }

    function resetImageEditor() {
        if (!activeImage) {
            return;
        }
        var key = activeImage.key;
        var entries = [
            ['style[' + key + '][zoom]', ''],
            ['style[' + key + '][position]', ''],
        ];
        closeImageEditor(false);
        save(entries, '✓ اتشالت تخصيصات الصورة').then(function (ok) {
            if (ok) {
                window.location.reload();
            }
        });
    }

    function openImageEditor(el) {
        if (active) {
            commitActive();
        }
        if (activeImage) {
            if (activeImage.el === el) {
                return;
            }
            commitImageEditor();
        }

        var key = el.dataset.slot;
        var override = config.styleOverrides[key] || {};
        var zoom = Number(override.zoom) || 1;

        activeImage = {
            el: el,
            key: key,
            zoom: zoom,
            position: parsePosition(override.position),
            originalTransform: el.style.transform,
            originalObjectPosition: el.style.objectPosition,
            dragBar: null,
            controlsBar: null,
        };

        applyImagePreview();

        // سطح السحب — عنصر شفاف فوق الصورة بالظبط (نفس مكانها/حجمها)، بيمسك mousedown
        // للتحريك (object-position) بس، منفصل عن شريط الأدوات (السلايدر/الزراير) عشان
        // مايحصلش تعارض بين "دوس عشان تسحب" و"دوس على زرار".
        var dragBar = document.createElement('div');
        dragBar.className = 'bq-image-drag-bar';
        dragBar.textContent = '🖐 اسحب لتحريك الصورة';
        dragBar.addEventListener('mousedown', function (event) {
            event.preventDefault();
            imageDragging = true;
            updateImagePositionFromEvent(event);
        });
        document.body.appendChild(dragBar);
        activeImage.dragBar = dragBar;

        var controlsBar = document.createElement('div');
        controlsBar.className = 'bq-toolbar';

        var zoomLabel = document.createElement('label');
        zoomLabel.textContent = 'زوم';
        var zoomInput = document.createElement('input');
        zoomInput.type = 'range';
        zoomInput.min = '1';
        zoomInput.max = '3';
        zoomInput.step = '0.1';
        zoomInput.value = String(zoom);
        zoomInput.className = 'bq-image-zoom-range';
        zoomInput.addEventListener('input', function () {
            activeImage.zoom = Number(zoomInput.value);
            applyImagePreview();
        });
        zoomLabel.appendChild(zoomInput);

        var resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'bq-toolbar-reset';
        resetBtn.textContent = '✕ إعادة الضبط';
        resetBtn.addEventListener('click', resetImageEditor);

        var doneBtn = document.createElement('button');
        doneBtn.type = 'button';
        doneBtn.className = 'bq-toolbar-done';
        doneBtn.textContent = 'تم';
        doneBtn.addEventListener('click', commitImageEditor);

        controlsBar.appendChild(zoomLabel);
        controlsBar.appendChild(resetBtn);
        controlsBar.appendChild(doneBtn);
        document.body.appendChild(controlsBar);
        activeImage.controlsBar = controlsBar;

        positionImageControls();
        document.addEventListener('scroll', positionImageControls, true);
        window.addEventListener('resize', positionImageControls);
    }

    function updateImagePositionFromEvent(event) {
        if (!activeImage) {
            return;
        }
        // نفس سبب استخدام مستطيل الحاوية في positionImageControls: الصورة المكبّرة
        // transform بتاعها بيوسّع getBoundingClientRect بتاعها، فحساب نسبة السحب منها بيدّي
        // حركة أبطأ من المتوقع (الماوس محتاج يتحرك مسافة أكبر بكتير من الفعلية). object-
        // position أصلاً بيتحسب نسبة لمساحة العنصر الأصلية (قبل transform)، اللي هي نفس
        // مساحة الحاوية بالظبط (الصورة h-full w-full).
        var rect = activeImage.el.parentElement.getBoundingClientRect();
        var x = Math.round(Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100)));
        var y = Math.round(Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100)));
        activeImage.position = { x: x, y: y };
        applyImagePreview();
    }

    // مسجّلين مرة واحدة بس (مش جوّه openImageEditor) عشان مايتكررش تسجيل listeners في
    // الـdocument كل مرة المستخدم يفتح صورة جديدة.
    document.addEventListener('mousemove', function (event) {
        if (imageDragging) {
            updateImagePositionFromEvent(event);
        }
    });
    document.addEventListener('mouseup', function () {
        imageDragging = false;
    });

    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-bq-editable="1"]');
        if (target) {
            if (active && active.el === target) {
                return;
            }
            event.preventDefault();
            startEditing(target);
            return;
        }

        var imageTarget = event.target.closest('[data-bq-image-editable="1"]');
        if (imageTarget) {
            event.preventDefault();
            openImageEditor(imageTarget);
            return;
        }

        // دوس بره الخانة النشطة وبره التولبار بتاعها → احفظ واقفل.
        if (active && !event.target.closest('.bq-toolbar')) {
            commitActive();
        }

        if (activeImage && !event.target.closest('.bq-image-drag-bar') && !event.target.closest('.bq-toolbar')) {
            commitImageEditor();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && activeImage) {
            closeImageEditor(false);
        }

        if (!active) {
            return;
        }
        if (event.key === 'Escape') {
            closeActive(false);
            return;
        }
        // خانة "text" سطر واحد بس (عنوان) — Enter بيحفظ بدل ما يعمل سطر جديد.
        if (event.key === 'Enter' && active.type === 'text') {
            event.preventDefault();
            commitActive();
        }
    });

    document.addEventListener('paste', function (event) {
        if (!active || event.target !== active.el) {
            return;
        }
        event.preventDefault();
        var text = (event.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, text);
    });

    window.addEventListener('scroll', function () {
        if (active) {
            positionToolbar();
        }
    }, true);

    // ---------- زرار/درج "تصميم الموقع" (ألوان/خط/ترتيب أقسام) ----------
    var fab = document.getElementById('bq-design-fab');
    var drawer = document.getElementById('bq-design-drawer');
    var drawerClose = document.getElementById('bq-design-drawer-close');
    var designForm = document.getElementById('bq-design-form');

    function openDrawer() {
        drawer.setAttribute('data-open', '1');
        drawer.setAttribute('aria-hidden', 'false');
    }

    function closeDrawer() {
        drawer.removeAttribute('data-open');
        drawer.setAttribute('aria-hidden', 'true');
    }

    if (fab && drawer) {
        fab.addEventListener('click', openDrawer);
        drawerClose.addEventListener('click', closeDrawer);
    }

    // معاينة حية فورية لتغيير الألوان (قبل الحفظ حتى) — نفس روح "زي الفوتوشوب".
    document.addEventListener('input', function (event) {
        if (!event.target.matches('[data-colors-sync][type="color"]')) {
            return;
        }
        var key = event.target.dataset.colorKey;
        document.body.style.setProperty('--site-' + key, event.target.value);
    });

    if (designForm) {
        designForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var entries = [];
            new FormData(designForm).forEach(function (value, key) {
                entries.push([key, value]);
            });
            // نفس سبب الريلود في commitActive: الخط العام/تخينه/ميله/حجمه وتبديل القالب
            // كلهم بيتحسموا وقت render السيرفر بس — من غير ريلود التعديل بيتخزن صح في
            // الداتا بيز بس شكله على الشاشة مش بيتغيّر خالص (ده كان سبب شكوى "أي تعديل
            // بعمله مبيتعدلش"، 22 سبتمبر 2026).
            save(entries, '✓ اتحفظ التصميم').then(function (ok) {
                closeDrawer();
                if (ok) {
                    setTimeout(function () {
                        window.location.reload();
                    }, 400);
                }
            });
        });
    }
})();
