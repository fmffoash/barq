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

    var active = null; // { el, key, type, originalText, colorTouched, fontTouched, pendingColor, pendingFont }
    var toolbarEl = null;

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

    function buildToolbar() {
        var override = config.styleOverrides[active.key] || {};
        var el = document.createElement('div');
        el.className = 'bq-toolbar';

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
            colorTouched: false,
            fontTouched: false,
            pendingColor: '',
            pendingFont: '',
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
            active.el.innerText = active.originalText;
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
        var newText = active.el.innerText.trim();
        if (newText !== active.originalText.trim()) {
            entries.push(['content[' + active.key + ']', newText]);
        }
        // تغيير لون/خط الخانة بيتخزن صح في السيرفر فوراً، بس شكله الفعلي (زي أي CSS تاني
        // في الصفحة) بيتحدد وقت الـ render — من غير ريلود الصفحة هيفضل شكله زي ما كان
        // قبل الحفظ حتى لو الحفظ نجح 100%. عشان كده لازم ريلود هنا تحديداً (مش للنص لوحده،
        // ده بيبان فوراً من contenteditable نفسه من غير ما يحتاج ريلود).
        var styleTouched = active.colorTouched || active.fontTouched;
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

        // دوس بره الخانة النشطة وبره التولبار بتاعها → احفظ واقفل.
        if (active && !event.target.closest('.bq-toolbar')) {
            commitActive();
        }
    });

    document.addEventListener('keydown', function (event) {
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
