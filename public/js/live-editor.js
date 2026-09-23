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

    // بيحط أي صندوق عائم (تولبار/شريط أدوات) جنب مستطيل هدف من غير ما يتراكب معاه خالص —
    // بيقيس حجم الصندوق الحقيقي (مش رقم ثابت مفترض زي 46px، ده كان سبب تغطية التولبار
    // للنص نفسه لما التولبار كبر بزراير المرحلة 1 — فؤاد اشتكى منها حياً 2026-09-23).
    // بيفضّل يحطه تحت الهدف؛ لو مفيش مساحة تحت، فوقه؛ ولو مفيش فوق ولا تحت (هدف طويل بارتفاع
    // الشاشة كلها)، يثبّته جوّه حدود الشاشة عمودياً. أفقياً بيتقص جوّه عرض الشاشة برضو.
    function positionElementNear(floatingEl, targetRect) {
        var margin = 8;
        var floatingRect = floatingEl.getBoundingClientRect();
        var height = floatingRect.height || 40;
        var width = floatingRect.width || 200;

        var top = targetRect.bottom + margin;
        if (top + height > window.innerHeight - margin) {
            var above = targetRect.top - height - margin;
            top = above >= margin ? above : Math.max(margin, window.innerHeight - height - margin);
        }

        var left = Math.max(margin, Math.min(targetRect.left, window.innerWidth - width - margin));

        floatingEl.style.top = Math.max(margin, top) + 'px';
        floatingEl.style.left = left + 'px';
    }

    function positionToolbar() {
        if (!active || !toolbarEl) {
            return;
        }
        positionElementNear(toolbarEl, active.el.getBoundingClientRect());
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
            positionElementNear(activeImage.controlsBar, rect);
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
        // وضع "ترتيب حر" (المرحلة 3) بيبدّل تفاعل الخانات بالكامل للسحب/التكبير — تعديل
        // النص العادي وفتح محرر الصورة (الأسطر تحت) بيتعطّلوا مؤقتاً لحد ما المستخدم يقفل
        // الوضع ده، وإلا كل دوس (حتى لو كان بداية سحب) هيفتح تعديل نص/صورة كمان بالغلط.
        if (freePositionMode) {
            return;
        }

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

    // ---------------------------------------------------------------------
    // ترتيب حر لأي عنصر (نقل/تكبير زي Canva/Wix — المرحلة 3، فؤاد أكّد صراحة: نص وأقسام
    // كمان، مش الصور بس — docs/rich-text-and-image-editing-plan.md). زرار "📐 ترتيب حر"
    // في الشريط العلوي بيبدّل وضع كامل: جوّاه، أي خانة (غير القوائم — شوف السبب تحت) بتتحرك
    // بالسحب المباشر بدل الدوس لتعديل النص/فتح محرر الصورة، ومعاها مقبض تكبير/تصغير عرض في
    // ركنها. برّه الوضع ده، كل حاجة بترجع تشتغل زي ما كانت بالظبط.
    //
    // ⚠️ إعادة تصميم (2026-09-24) بعد فيدباك فؤاد الحي على المرحلة 3: (1) مفيش حفظ تلقائي
    // لكل حركة لوحدها — كل تعديلات جلسة "الترتيب الحر" بتتجمّع محلياً بس (freePositionPending)
    // وبتتحفظ دفعة واحدة لما تدوس "💾 احفظ"، أو تتلغي بالكامل بـ"↺ تراجع عن كل حاجة" (بترجع
    // كل عنصر اتحرك لمكانه/حجمه الأصلي من غير ما تلمس السيرفر خالص — مفيش حاجة اتحفظت أصلاً).
    // (2) Escape وسط سحب/تكبير شغال بيلغي الحركة دي بس وترجع الحته لمكانها قبلها على طول.
    // (3) لما عنصرين فوق بعض، تقدر توصل للي تحت بالدوس تاني في نفس المكان بالظبط (مش لازم
    // تسحبه فوراً، أول دوسة بتختار اللي فوق زي العادي وبعدين بتلف على الباقي).
    // ---------------------------------------------------------------------
    var freePositionMode = false;
    var freePositionBtn = document.getElementById('bq-free-position-toggle');
    var freeDrag = null; // { el, key, offsetX, offsetY, widthPercent, startStyle }
    var freeResize = null; // { el, key, startClientX, startWidthPx, startStyle }
    var resizeHandleEl = null;
    var freePositionStatusBar = null;
    var freePositionOriginal = {}; // { [slotKey]: {posX, posY, width} } — قيمة الخانة وقت فتح وضع الترتيب الحر
    var freePositionPending = {}; // { [slotKey]: {posX?, posY?, width?} } — تعديلات الجلسة دي لسه مش محفوظة
    var freeClickCycle = { x: null, y: null, time: 0, index: -1 }; // لتدوير الاختيار بين عناصر متراكبة فوق بعض

    // خانات list بس مستبعدة — كل عناصر الـ<li>/<div> بتاعتها بيشاركوا نفس data-slot (مفتاح
    // واحد في style_overrides_json)، فأي موضع نحفظه هيتطبّق على كل عناصر القايمة مع بعض
    // ويرصّهم فوق بعض بالظبط — مفيش معنى "ترتيب حر" لعنصر بيتكرر مرات كتير بنفس المفتاح.
    // بتبقى معلّمة بصرياً (CSS) وقت وضع الترتيب الحر عشان يبقى واضح إنها مستبعدة عمداً، مش
    // باج (فؤاد سأل "فيه مربعات مش بتتحرك اصلا" — دي كانت السبب الأكيد لقوائم على الأقل).
    function isFreePositionEligible(el) {
        var key = el.dataset.slot;
        if (!key) {
            return false;
        }
        return config.slotTypes[key] !== 'list';
    }

    function freePositionSlots() {
        return Array.prototype.filter.call(document.querySelectorAll('[data-slot]'), isFreePositionEligible);
    }

    // المرجع الحقيقي لحساب نسب posX/posY/width لازم يكون نفس "containing block" اللي CSS
    // هيستخدمه فعلياً وقت الرندر (أقرب سلف بـposition غير static) — مش الـ<section> دايماً.
    // بعض التصميمات (gallery/duotone/signature...) فيها حاوية داخلية بـposition:relative
    // (لعمل z-index فوق صورة الهيرو) أضيق من الـsection نفسه؛ لو استخدمنا عرض/ارتفاع الـ
    // section هنا هتختلف النسبة المحسوبة هنا عن النسبة اللي CSS بيطبّقها فعلياً بعد ما
    // العنصر ياخد position:absolute، وهيبان اختلاف حقيقي بين مكان السحب ومكان العنصر بعد
    // الحفظ/الريلود. offsetParent بيرجّع بالظبط نفس الحاوية دي (مستقل عن position الحالي
    // بتاع العنصر نفسه)، فهو المرجع الصح دايماً.
    function sectionRectFor(el) {
        var container = el.offsetParent || el.closest('section') || el.parentElement;
        return container.getBoundingClientRect();
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function applyFreePositionPreview(el, leftPercent, topPercent, widthPercent) {
        el.style.position = 'absolute';
        if (leftPercent !== null) {
            el.style.left = leftPercent + '%';
        }
        if (topPercent !== null) {
            el.style.top = topPercent + '%';
        }
        if (widthPercent !== null) {
            el.style.width = widthPercent + '%';
        }
    }

    // عكس applyFreePositionPreview بالظبط — بيرجّع العنصر لوضعه الطبيعي جوّه الترتيب العادي
    // (مفيش أي override خالص)، مش بس يمسح قيمة معيّنة.
    function clearFreePositionPreview(el) {
        el.style.position = '';
        el.style.left = '';
        el.style.top = '';
        el.style.width = '';
    }

    // نسخة "الأصل" بتتاخد مرة واحدة بس لحظة ما وضع الترتيب الحر بيفتح — مرجع نرجعله لو
    // المستخدم دوس "تراجع عن كل حاجة"، بغض النظر عن كام حركة عملها وهو شغال.
    function snapshotFreePositionOriginals() {
        freePositionOriginal = {};
        freePositionSlots().forEach(function (el) {
            var key = el.dataset.slot;
            var override = config.styleOverrides[key] || {};
            freePositionOriginal[key] = {
                posX: override.posX != null ? override.posX : null,
                posY: override.posY != null ? override.posY : null,
                width: override.width != null ? override.width : null,
            };
        });
    }

    // بيسجّل التعديل محلياً بس — مفيش نداء save() هنا خالص دلوقتي (كان بيحفظ كل حركة لوحدها
    // فوراً قبل كده، وده اللي خلّى فؤاد يحس إنه "مفيش رجوع للخلف": أي حركة غلط كانت بتتخزن
    // على طول). الحفظ الفعلي بيحصل مرة واحدة بس لما يدوس "💾 احفظ" (saveFreePositionChanges).
    function commitFreePosition(el, leftPercent, topPercent, widthPercent) {
        var key = el.dataset.slot;
        var pending = freePositionPending[key] || {};
        if (leftPercent !== null) {
            pending.posX = Math.round(leftPercent * 100) / 100;
        }
        if (topPercent !== null) {
            pending.posY = Math.round(topPercent * 100) / 100;
        }
        if (widthPercent !== null) {
            pending.width = Math.round(widthPercent * 100) / 100;
        }
        freePositionPending[key] = pending;
        updateFreePositionStatusBar();
    }

    function positionResizeHandle(el) {
        if (!resizeHandleEl) {
            return;
        }
        var rect = el.getBoundingClientRect();
        resizeHandleEl.style.top = (rect.bottom - 7) + 'px';
        resizeHandleEl.style.left = (rect.right - 7) + 'px';
    }

    function showResizeHandleFor(el) {
        if (!resizeHandleEl) {
            resizeHandleEl = document.createElement('div');
            resizeHandleEl.className = 'bq-resize-handle';
            resizeHandleEl.addEventListener('mousedown', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var target = resizeHandleEl.dataset.targetSlot
                    ? document.querySelector('[data-slot="' + resizeHandleEl.dataset.targetSlot + '"]')
                    : null;
                if (!target) {
                    return;
                }
                var rect = target.getBoundingClientRect();
                freeResize = {
                    el: target,
                    key: target.dataset.slot,
                    startClientX: event.clientX,
                    startWidthPx: rect.width,
                    startStyle: { position: target.style.position, width: target.style.width },
                };
            });
            document.body.appendChild(resizeHandleEl);
        }
        resizeHandleEl.dataset.targetSlot = el.dataset.slot;
        resizeHandleEl.style.display = 'block';
        positionResizeHandle(el);
    }

    function hideResizeHandle() {
        if (resizeHandleEl) {
            resizeHandleEl.style.display = 'none';
        }
    }

    function onFreeSlotMouseEnter(event) {
        if (!freePositionMode || freeDrag || freeResize) {
            return;
        }
        showResizeHandleFor(event.currentTarget);
    }

    // بيحدّد أي عنصر فعلياً هنسحبه — مش بالضرورة event.currentTarget (ده دايماً أعلى عنصر
    // في نقطة الدوس بس، لأن المتصفح مابيبعتش mousedown غير للعنصر الظاهر فوق فعلاً). بنستخدم
    // elementsFromPoint نجيب كل العناصر المتراكبة في نفس النقطة دي، ولو المستخدم دوس تاني
    // في نفس المكان بالظبط (خلال ثانية ونص)، بنلف للعنصر اللي بعده في الترتيب (اللي تحت شوية).
    function pickFreePositionTarget(event) {
        var stack = document.elementsFromPoint(event.clientX, event.clientY).filter(function (node) {
            return node.hasAttribute && node.hasAttribute('data-slot') && isFreePositionEligible(node);
        });

        if (stack.length <= 1) {
            freeClickCycle = { x: event.clientX, y: event.clientY, time: Date.now(), index: 0 };
            return stack[0] || event.currentTarget;
        }

        var samePoint = freeClickCycle.x !== null
            && Math.abs(event.clientX - freeClickCycle.x) < 6
            && Math.abs(event.clientY - freeClickCycle.y) < 6
            && (Date.now() - freeClickCycle.time) < 1500;

        var index = samePoint ? (freeClickCycle.index + 1) % stack.length : 0;
        freeClickCycle = { x: event.clientX, y: event.clientY, time: Date.now(), index: index };

        toast('عنصر ' + (index + 1) + ' من ' + stack.length + ' متراكبين هنا — دوس تاني بنفس المكان عشان تلف على الباقي');

        return stack[index];
    }

    function onFreeSlotMouseDown(event) {
        if (!freePositionMode) {
            return;
        }
        // مقبض التكبير بتاعه مسك الحدث لوحده (mousedown مع stopPropagation فوق) — لو
        // وصلنا هنا يبقى دوس عادي على خانة، يعني نقل مش تكبير.
        event.preventDefault();
        var el = pickFreePositionTarget(event);
        var rect = el.getBoundingClientRect();
        var sectionRect = sectionRectFor(el);
        el.classList.add('bq-free-dragging');
        freeDrag = {
            el: el,
            key: el.dataset.slot,
            offsetX: event.clientX - rect.left,
            offsetY: event.clientY - rect.top,
            // العرض الحالي (قبل ما نحط position:absolute) — أي خانة أصلها من التصميم العادي
            // (زي w-full على الهيرو) لو خدت position:absolute من غير عرض صريح هتمتد لعرض
            // القسم كله بالظبط زي ما كانت (100%)، مش عرضها الحقيقي وقت السحب. تثبيت العرض
            // ده مع كل نقلة (مش بس أول مرة) بيمنع المشكلة دي، ومعندهوش أي ضرر لو اتكرر.
            widthPercent: clamp((rect.width / sectionRect.width) * 100, 5, 100),
            // حالة العنصر قبل السحب ده بالظبط — لو المستخدم دوس Escape وسط السحب، بنرجّعها
            // زي ما هي حرفياً من غير ما نلمس السيرفر خالص.
            startStyle: {
                position: el.style.position,
                left: el.style.left,
                top: el.style.top,
                width: el.style.width,
            },
        };
    }

    function restoreStyleFrom(el, startStyle) {
        el.style.position = startStyle.position || '';
        if ('left' in startStyle) {
            el.style.left = startStyle.left || '';
        }
        if ('top' in startStyle) {
            el.style.top = startStyle.top || '';
        }
        el.style.width = startStyle.width || '';
    }

    document.addEventListener('mousemove', function (event) {
        // بنعيد قياس حاوية الترتيب (offsetParent) في كل حركة فأر بدل ما نعتمد على القياس
        // المحفوظ وقت mousedown — أول ما العنصر ياخد position:absolute (أول تحريك في نفس
        // الجلسة) بيتشال من الـflow، وده ممكن يغيّر حجم حاويته لو كانت حاوية داخلية (زي
        // wrapper "relative z-10" في gallery/duotone) مقاسها معتمد على محتواها هي نفسها —
        // لو فضلنا نستخدم القياس القديم (قبل الشيل)، النسبة المئوية المحسوبة هتفضل بتنحرف عن
        // مكان الماوس الفعلي.
        if (freeDrag) {
            var sr = sectionRectFor(freeDrag.el);
            var leftPx = event.clientX - sr.left - freeDrag.offsetX;
            var topPx = event.clientY - sr.top - freeDrag.offsetY;
            var leftPercent = clamp((leftPx / sr.width) * 100, 0, 100);
            var topPercent = clamp((topPx / sr.height) * 100, 0, 100);
            applyFreePositionPreview(freeDrag.el, leftPercent, topPercent, null);
            positionResizeHandle(freeDrag.el);
        }
        if (freeResize) {
            var rsr = sectionRectFor(freeResize.el);
            var deltaX = event.clientX - freeResize.startClientX;
            var newWidthPx = Math.max(24, freeResize.startWidthPx + deltaX);
            var widthPercent = clamp((newWidthPx / rsr.width) * 100, 5, 100);
            applyFreePositionPreview(freeResize.el, null, null, widthPercent);
            positionResizeHandle(freeResize.el);
        }
    });

    document.addEventListener('mouseup', function () {
        if (freeDrag) {
            var el = freeDrag.el;
            el.classList.remove('bq-free-dragging');
            var rect = el.getBoundingClientRect();
            var sr = sectionRectFor(el);
            var leftPercent = clamp(((rect.left - sr.left) / sr.width) * 100, 0, 100);
            var topPercent = clamp(((rect.top - sr.top) / sr.height) * 100, 0, 100);
            applyFreePositionPreview(el, null, null, freeDrag.widthPercent);
            commitFreePosition(el, leftPercent, topPercent, freeDrag.widthPercent);
            freeDrag = null;
        }
        if (freeResize) {
            var rEl = freeResize.el;
            var rRect = rEl.getBoundingClientRect();
            var rsr = sectionRectFor(rEl);
            var widthPercent = clamp((rRect.width / rsr.width) * 100, 5, 100);
            commitFreePosition(rEl, null, null, widthPercent);
            freeResize = null;
        }
    });

    // Escape وسط سحب/تكبير شغال بيلغي الحركة دي بس، من غير ما يقفل وضع الترتيب الحر كله —
    // العنصر يرجع لمكانه قبل السحب ده بالظبط (مش الأصل من أول الجلسة، مجرد آخر خطوة).
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }
        if (freeDrag) {
            restoreStyleFrom(freeDrag.el, freeDrag.startStyle);
            freeDrag.el.classList.remove('bq-free-dragging');
            freeDrag = null;
        }
        if (freeResize) {
            restoreStyleFrom(freeResize.el, freeResize.startStyle);
            freeResize = null;
        }
        hideResizeHandle();
    });

    function buildFreePositionStatusBar() {
        var bar = document.createElement('div');
        bar.className = 'bq-toolbar bq-free-status-bar';

        var text = document.createElement('span');
        text.className = 'bq-free-status-text';
        bar.appendChild(text);

        var revertBtn = document.createElement('button');
        revertBtn.type = 'button';
        revertBtn.className = 'bq-toolbar-reset';
        revertBtn.textContent = '↺ تراجع عن كل حاجة';
        revertBtn.addEventListener('click', function () {
            revertAllFreePositionChanges();
        });
        bar.appendChild(revertBtn);

        var saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'bq-toolbar-done';
        saveBtn.textContent = '💾 احفظ التعديلات';
        saveBtn.addEventListener('click', function () {
            saveFreePositionChanges();
        });
        bar.appendChild(saveBtn);

        document.body.appendChild(bar);
        freePositionStatusBar = bar;
        updateFreePositionStatusBar();
    }

    function updateFreePositionStatusBar() {
        if (!freePositionStatusBar) {
            return;
        }
        var count = Object.keys(freePositionPending).length;
        freePositionStatusBar.querySelector('.bq-free-status-text').textContent = count === 0
            ? 'اسحب أي عنصر أو كبّره — هيتحفظ لما تدوس "احفظ" بس، تقدر تراجع أي وقت قبلها'
            : ('عندك ' + count + ' ' + (count === 1 ? 'تعديل' : 'تعديلات') + ' لسه مش محفوظ');
        var saveBtn = freePositionStatusBar.querySelector('.bq-toolbar-done');
        var revertBtn = freePositionStatusBar.querySelector('.bq-toolbar-reset');
        saveBtn.disabled = count === 0;
        revertBtn.disabled = count === 0;
    }

    function removeFreePositionStatusBar() {
        if (freePositionStatusBar) {
            freePositionStatusBar.remove();
            freePositionStatusBar = null;
        }
    }

    // بيرجّع كل خانة اتحركت/اتغيّر حجمها وقت الجلسة دي لحالتها الأصلية (قبل أي حركة) — مفيش
    // أي نداء سيرفر هنا خالص، لأن مفيش حاجة اتحفظت أصلاً (commitFreePosition بقى محلي بس).
    function revertAllFreePositionChanges() {
        Object.keys(freePositionPending).forEach(function (key) {
            var el = document.querySelector('[data-slot="' + key + '"]');
            if (!el) {
                return;
            }
            var original = freePositionOriginal[key] || { posX: null, posY: null, width: null };
            if (original.posX === null && original.posY === null && original.width === null) {
                clearFreePositionPreview(el);
            } else {
                applyFreePositionPreview(el, original.posX, original.posY, original.width);
            }
        });
        freePositionPending = {};
        updateFreePositionStatusBar();
        toast('✓ اتلغت كل التعديلات اللي لسه مش محفوظة');
    }

    function saveFreePositionChanges() {
        var entries = [];
        Object.keys(freePositionPending).forEach(function (key) {
            var change = freePositionPending[key];
            if ('posX' in change) {
                entries.push(['style[' + key + '][posX]', String(change.posX)]);
            }
            if ('posY' in change) {
                entries.push(['style[' + key + '][posY]', String(change.posY)]);
            }
            if ('width' in change) {
                entries.push(['style[' + key + '][width]', String(change.width)]);
            }
        });
        if (entries.length === 0) {
            return;
        }
        // ريلود بعد الحفظ (نفس منطق باقي التعديلات الشكلية في الملف ده) — الشكل النهائي
        // بيترندر من السيرفر (document.blade.php)، فمهم نتأكد إنه مطابق فعلاً للمعاينة.
        save(entries, '✓ اتحفظ الترتيب الحر').then(function (ok) {
            if (ok) {
                window.location.reload();
            }
        });
    }

    // حاويات ضيّقة (عمود نص/صورة محدود العرض جوّه هيرو، مش الـsection كله) بـposition:relative
    // قبل خانة قابلة للترتيب الحر — معلّمة بكلاس bq-free-position-boundary في الـlayout نفسه
    // (راجع gallery-section.blade.php). document.blade.php بيوسّعها بـCSS تلقائي **بس لو فيه
    // ترتيب حر محفوظ بالفعل** على خانة جواها — من غيرها أول سحب على خانة جديدة (لسه مفيهاش
    // تخصيص محفوظ) هيفضل يحس إنه "محصور في مساحة صغيرة" لحد أول حفظ+ريلود. الدالة دي بتوسّع
    // كل الحاويات دي فوراً وقت فتح وضع الترتيب الحر (مش بس اللي عندها تخصيص محفوظ خلاص)،
    // فالسحب بيحس صح من أول مرة، مش بعد الحفظ بس (فؤاد اشتكى منها حياً 2026-09-24).
    function setFreePositionBoundaryExpanded(el, expanded) {
        var props = ['position', 'inset', 'display', 'flexDirection', 'alignItems', 'justifyContent', 'maxWidth', 'margin'];
        var values = expanded
            ? ['absolute', '0', 'flex', 'column', 'center', 'center', 'none', '0']
            : ['', '', '', '', '', '', '', ''];
        props.forEach(function (prop, i) {
            el.style[prop] = values[i];
        });
    }

    function enterFreePositionMode() {
        freePositionMode = true;
        document.body.classList.add('bq-free-position-mode');
        if (freePositionBtn) {
            freePositionBtn.textContent = '✓ ترتيب حر شغال';
            freePositionBtn.classList.add('bq-active');
        }
        snapshotFreePositionOriginals();
        freePositionPending = {};
        buildFreePositionStatusBar();
        freePositionSlots().forEach(function (el) {
            el.addEventListener('mousedown', onFreeSlotMouseDown);
            el.addEventListener('mouseenter', onFreeSlotMouseEnter);
        });
        document.querySelectorAll('.bq-free-position-boundary').forEach(function (el) {
            setFreePositionBoundaryExpanded(el, true);
        });
    }

    function exitFreePositionMode() {
        freePositionMode = false;
        document.body.classList.remove('bq-free-position-mode');
        if (freePositionBtn) {
            freePositionBtn.textContent = '📐 ترتيب حر';
            freePositionBtn.classList.remove('bq-active');
        }
        hideResizeHandle();
        removeFreePositionStatusBar();
        freePositionSlots().forEach(function (el) {
            el.removeEventListener('mousedown', onFreeSlotMouseDown);
            el.removeEventListener('mouseenter', onFreeSlotMouseEnter);
        });
        // بنشيل التوسيع اللي عملناه بالـJS بس — لو فيه تخصيص محفوظ فعلاً على خانة جوّه
        // حاوية معيّنة، الـCSS المركزي (document.blade.php) هيفضل موسّعها زي ما هي، مستقل
        // تماماً عن الـinline style هنا.
        document.querySelectorAll('.bq-free-position-boundary').forEach(function (el) {
            setFreePositionBoundaryExpanded(el, false);
        });
    }

    function toggleFreePositionMode() {
        // اقفل أي تعديل نص/صورة شغال الأول — منع تعارض بين وضعين تفاعل مختلفين تماماً.
        if (active) {
            commitActive();
        }
        if (activeImage) {
            commitImageEditor();
        }

        if (!freePositionMode) {
            enterFreePositionMode();
            return;
        }

        var pendingCount = Object.keys(freePositionPending).length;
        if (pendingCount > 0) {
            var wantsSave = window.confirm(
                'عندك ' + pendingCount + ' تعديل لسه مش محفوظ في الترتيب الحر.\n\n'
                + '"موافق" = احفظ التعديلات دي.\n"إلغاء" = اتجاهلها وارجع للشكل الأصلي.'
            );
            if (wantsSave) {
                saveFreePositionChanges();
                return; // الريلود بعد الحفظ هيتولى قفل الوضع لوحده
            }
            revertAllFreePositionChanges();
        }
        exitFreePositionMode();
    }

    if (freePositionBtn) {
        freePositionBtn.addEventListener('click', toggleFreePositionMode);
    }

    window.addEventListener('resize', function () {
        if (freePositionMode && (freeDrag || freeResize)) {
            positionResizeHandle((freeDrag || freeResize).el);
        }
    });
})();
