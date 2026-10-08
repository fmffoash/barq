// عدّاد طلبات الذكاء الاصطناعي (2026-10-08) — أي فورم عليه data-ai-run="create|follow_up|suggest"
// بيتبعت من هنا بدل الإرسال العادي: السيرفر بيجهّز الطلب (AiRunController::start)، والمتصفح
// بيستقبل الرد كلمة بكلمة ويعرض المرحلة والنسبة والوقت الباقي، وفي الآخر السيرفر بيطبّق الرد.
//
// ترتيب المحاولات: (1) Ollama مباشرة من المتصفح لو السيرفر قال إن ده ممكن، (2) بث عن طريق
// السيرفر، (3) السيرفر يستنى الرد كامل (العدّاد بالتقدير). لو حتى أول خطوة فشلت لأي سبب غير
// خطأ في الإدخال، الفورم بيتبعت بالطريقة العادية القديمة — مفيش حالة فؤاد يتعطّل فيها.

const STAGES = ['prepare', 'load', 'read', 'write', 'apply'];

const STAGE_TEXT = {
    prepare: 'بيجهّز الطلب...',
    load: 'بيحمّل نموذج الذكاء الاصطناعي في الذاكرة (بتحصل أول مرة بس)...',
    read: 'بيقرا طلبك والبيانات...',
    write: 'بيكتب الرد...',
    apply: 'بيطبّق النتيجة...',
};

const TITLES = {
    create: 'بيعمل موقعك',
    follow_up: 'بينفّذ طلبك',
    suggest: 'بيكتب محتوى الخانات',
};

const MODE_TEXT = {
    direct: 'الرد بيوصل من Ollama مباشرة',
    stream: 'الرد بيوصل عن طريق البرنامج',
    server: 'البرنامج مستني الرد كامل — العدّاد بالتقدير',
};

// وقت تقريبي لخطوة "تطبيق" على السيرفر (حفظ المشروع/المحتوى + فتح الصفحة).
const APPLY_MS = 1500;

class BeforeFirstByte extends Error {}

class Cancelled extends Error {}

function formatDuration(ms) {
    const total = Math.max(1, Math.round(ms / 1000));
    if (total < 60) return total + ' ثانية';
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;
    return minutes + ' دقيقة' + (seconds ? ' و' + seconds + ' ثانية' : '');
}

// الرد JSON — بنعرض القيم النصية بس (من غير المفاتيح والأقواس) عشان فؤاد يشوف الكلام نفسه
// وهو بيتكتب. آخر نص لسه مقفلش بيتعرض لو واضح إنه قيمة (جاي بعد ":" أو "[").
export function readableText(json) {
    const parts = [];
    const re = /"((?:[^"\\]|\\.)*)("?)/g;
    let match;

    while ((match = re.exec(json))) {
        const closed = match[2] === '"';
        const after = json.slice(re.lastIndex).trimStart();
        const before = json.slice(0, match.index).trimEnd().slice(-1);

        if (closed && after.startsWith(':')) continue; // مفتاح
        if (!closed && before !== ':' && before !== '[') continue; // لسه مش معروف مفتاح ولا قيمة

        let value = match[1];
        try {
            value = JSON.parse('"' + value.replace(/\\$/, '') + '"');
        } catch {
            value = value.replace(/\\n/g, '\n').replace(/\\"/g, '"');
        }
        value = value.replace(/<[^>]*>/g, '').trim();
        // قيم داخلية زي اسم الفعل (update_content) أو مفتاح خانة — مالهاش معنى لفؤاد.
        if (value && !/^[a-z0-9_]+$/.test(value)) parts.push(value);
    }

    return parts.join(' · ');
}

// تقدير الوقت: بيبدأ بأرقام الجهاز المتسجّلة (AiStats) وبيصحّح نفسه من سرعة الكتابة الفعلية.
export class Eta {
    constructor(estimate, now = performance.now()) {
        this.est = {
            load_ms: Number(estimate?.load_ms) || 20000,
            prompt_tps: Math.max(Number(estimate?.prompt_tps) || 40, 1),
            eval_tps: Math.max(Number(estimate?.eval_tps) || 6, 0.5),
            eval_tokens: Math.max(Number(estimate?.eval_tokens) || 400, 20),
            prompt_tokens: Math.max(Number(estimate?.prompt_tokens) || 500, 1),
            loaded: estimate?.loaded ?? null,
        };
        this.t0 = now;
        this.firstTokenAt = null;
        this.tokens = 0;
        this.doneAt = null;
        this.serverOnly = false;
        this.maxRatio = 0;
    }

    loadMs() {
        if (this.est.loaded === true) return 0;
        return this.est.loaded === false ? this.est.load_ms : this.est.load_ms / 2;
    }

    readMs() {
        return (this.est.prompt_tokens / this.est.prompt_tps) * 1000;
    }

    token(now = performance.now()) {
        if (this.firstTokenAt === null) this.firstTokenAt = now;
        this.tokens++;
    }

    done(now = performance.now()) {
        this.doneAt = now;
    }

    liveTps(now) {
        const seconds = (now - this.firstTokenAt) / 1000;
        if (this.tokens < 8 || seconds <= 0) return this.est.eval_tps;
        const live = this.tokens / seconds;
        const weight = Math.min(this.tokens / 40, 1);
        return weight * live + (1 - weight) * this.est.eval_tps;
    }

    snapshot(now = performance.now()) {
        const elapsed = now - this.t0;
        const pre = this.loadMs() + this.readMs();
        const writeMs = (this.est.eval_tokens / this.est.eval_tps) * 1000;
        let stage;
        let remaining;
        let slow = false;

        if (this.doneAt !== null) {
            stage = 'apply';
            remaining = Math.max(APPLY_MS - (now - this.doneAt), 300);
        } else if (this.firstTokenAt !== null) {
            stage = 'write';
            const expected = Math.max(this.est.eval_tokens, this.tokens * 1.08 + 5);
            remaining = ((expected - this.tokens) / this.liveTps(now)) * 1000 + APPLY_MS;
        } else if (this.serverOnly && elapsed > pre) {
            stage = 'write';
            remaining = Math.max(pre + writeMs - elapsed, 3000) + APPLY_MS;
            slow = pre + writeMs < elapsed;
        } else {
            stage = elapsed < this.loadMs() ? 'load' : 'read';
            const left = pre - elapsed;
            slow = left < 0;
            remaining = Math.max(left, 2000) + writeMs + APPLY_MS;
        }

        // النسبة متنزلش لورا أبداً حتى لو التقدير اتصحّح لفوق.
        const ratio = Math.min(Math.max(elapsed / (elapsed + remaining), this.maxRatio), 0.99);
        this.maxRatio = ratio;

        return { stage, elapsed, remaining, ratio, slow };
    }
}

class Panel {
    constructor(root) {
        this.root = root;
        this.q = (name) => root.querySelector('[data-ai-' + name + ']');
        this.onCancel = null;
        this.onClose = null;
        this.q('cancel').addEventListener('click', () => this.onCancel && this.onCancel());
        this.q('ok').addEventListener('click', () => this.close());
    }

    open(kind) {
        this.q('title').textContent = TITLES[kind] || 'بيشتغل...';
        this.q('preview').dataset.show = 'false';
        this.q('preview').textContent = '';
        this.q('result').dataset.tone = '';
        this.q('result').textContent = '';
        this.q('mode').textContent = '';
        this.q('remaining').textContent = 'بيحسب الوقت...';
        this.q('elapsed').textContent = '';
        this.q('cancel').classList.remove('hidden');
        this.q('ok').classList.add('hidden');
        this.setPercent(0);
        this.setStage('prepare');
        this.root.dataset.open = 'true';
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.stopTicking();
        this.root.dataset.open = 'false';
        document.body.style.overflow = '';
        if (this.onClose) this.onClose();
    }

    setPercent(ratio) {
        const pct = Math.round(ratio * 100);
        this.q('percent').textContent = pct + '%';
        this.q('bar').style.width = pct + '%';
    }

    setStage(stage, slow = false) {
        const index = STAGES.indexOf(stage);
        this.root.querySelectorAll('[data-stage]').forEach((li) => {
            const i = STAGES.indexOf(li.dataset.stage);
            if (li.dataset.state === 'skipped' && i < index) return;
            li.dataset.state = i < index ? 'done' : i === index ? 'active' : 'waiting';
        });
        let text = STAGE_TEXT[stage] || '';
        if (slow && (stage === 'load' || stage === 'read')) text += ' (أبطأ من المعتاد شوية)';
        this.q('stage-label').textContent = text;
    }

    skipStage(stage) {
        const li = this.root.querySelector('[data-stage="' + stage + '"]');
        if (li) li.dataset.state = 'skipped';
    }

    setMode(mode) {
        this.q('mode').textContent = MODE_TEXT[mode] || '';
    }

    preview(text) {
        const box = this.q('preview');
        const readable = readableText(text);
        if (!readable) return;
        box.dataset.show = 'true';
        box.textContent = readable.length > 600 ? '…' + readable.slice(-600) : readable;
        box.scrollTop = box.scrollHeight;
    }

    startTicking(eta) {
        this.stopTicking();
        const tick = () => {
            const snap = eta.snapshot();
            this.setStage(snap.stage, snap.slow);
            this.setPercent(snap.ratio);
            this.q('remaining').textContent = snap.slow && snap.stage !== 'write'
                ? 'لسه شغال — ثواني ويبدأ يكتب'
                : 'باقي حوالي ' + formatDuration(snap.remaining);
            this.q('elapsed').textContent = 'عدّى ' + formatDuration(snap.elapsed);
        };
        tick();
        this.timer = setInterval(tick, 250);
    }

    stopTicking() {
        if (this.timer) clearInterval(this.timer);
        this.timer = null;
    }

    finish(outcome) {
        this.stopTicking();
        const ok = outcome.ok !== false;
        if (ok) {
            STAGES.forEach((s) => {
                const li = this.root.querySelector('[data-stage="' + s + '"]');
                if (li && li.dataset.state !== 'skipped') li.dataset.state = 'done';
            });
            this.setPercent(1);
            this.q('remaining').textContent = 'خلص';
            this.q('stage-label').textContent = 'خلص ✓';
        } else {
            this.q('stage-label').textContent = 'موقفش زي المتوقع';
            this.q('remaining').textContent = '';
        }
        this.q('result').textContent = outcome.reply || (ok ? 'تمام.' : 'حصلت مشكلة — جرّب تاني.');
        this.q('result').dataset.tone = ok ? 'ok' : 'error';
        this.q('cancel').classList.add('hidden');
        this.q('ok').classList.remove('hidden');
        this.q('ok').focus();
    }

    error(message) {
        this.finish({ ok: false, reply: message });
    }
}

function csrfToken(form) {
    return form.querySelector('input[name="_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content
        || '';
}

async function postJson(url, data, form, signal) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(form),
        },
        credentials: 'same-origin',
        body: JSON.stringify(data || {}),
        signal,
    });

    if (res.status === 419) throw new Error('الصفحة قديمة — حدّث الصفحة (F5) وجرّب تاني.');
    const json = await res.json().catch(() => null);
    if (!res.ok || !json) {
        throw new Error((json && json.message) || 'السيرفر رجّع خطأ (' + res.status + ') — جرّب تاني.');
    }
    return json;
}

// بيقرا رد Ollama سطر بسطر (NDJSON). بيرجّع {text, metrics} أو {error, detail}، وبيرمي
// BeforeFirstByte لو الاتصال نفسه فشل قبل أي حاجة (عشان نجرّب الطريقة اللي بعدها).
async function readStream(url, init, eta, panel) {
    let res;
    try {
        res = await fetch(url, init);
    } catch (e) {
        if (e.name === 'AbortError') throw new Cancelled();
        throw new BeforeFirstByte(e.message);
    }

    if (!res.ok || !res.body) {
        const body = await res.json().catch(() => null);
        // Ollama نفسه رد بخطأ (النموذج مش موجود مثلاً) — ده رد حقيقي مش مشكلة اتصال.
        if (body && typeof body.error === 'string' && !body.message) {
            return { error: res.status === 404 && /not found/i.test(body.error) ? 'model_missing' : 'http_error', detail: body.error };
        }
        throw new BeforeFirstByte('HTTP ' + res.status);
    }

    const reader = res.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';
    let text = '';
    let gotAnything = false;

    const handle = (line) => {
        if (!line.trim()) return null;
        let chunk;
        try {
            chunk = JSON.parse(line);
        } catch {
            return null;
        }
        if (chunk.error) {
            return { error: chunk.kind || 'http_error', detail: String(chunk.error) };
        }
        if (typeof chunk.response === 'string' && chunk.response !== '') {
            text += chunk.response;
            eta.token();
            panel.preview(text);
        }
        if (chunk.done) {
            const { response, context, ...metrics } = chunk;
            return { text, metrics };
        }
        return null;
    };

    try {
        for (;;) {
            const { value, done } = await reader.read();
            if (done) break;
            gotAnything = true;
            buffer += decoder.decode(value, { stream: true });
            let newline;
            while ((newline = buffer.indexOf('\n')) !== -1) {
                const outcome = handle(buffer.slice(0, newline));
                buffer = buffer.slice(newline + 1);
                if (outcome) return outcome;
            }
        }
        const last = handle(buffer);
        if (last) return last;
    } catch (e) {
        if (e.name === 'AbortError') throw new Cancelled();
        if (!gotAnything) throw new BeforeFirstByte(e.message);
        return { error: 'down', detail: 'الاتصال اتقطع قبل ما الرد يخلص: ' + e.message };
    }

    if (!gotAnything) throw new BeforeFirstByte('empty response');
    return { error: 'down', detail: 'الاتصال اتقطع قبل ما الرد يخلص.' };
}

function setButtons(form, busy) {
    form.querySelectorAll('[data-ai-submit], button[type="submit"]').forEach((btn) => {
        if (busy) {
            btn.dataset.originalText ??= btn.textContent;
            btn.disabled = true;
        } else {
            btn.disabled = false;
            if (btn.dataset.originalText) btn.textContent = btn.dataset.originalText;
        }
    });
}

// الإرسال العادي القديم (من غير عدّاد) — آخر حل.
function classicSubmit(form) {
    form.dataset.aiRunBypass = 'true';
    setButtons(form, true);
    const btn = form.querySelector('[data-ai-submit]');
    if (btn) btn.textContent = 'بيفكر...';
    HTMLFormElement.prototype.submit.call(form);
}

async function runForm(form, panel) {
    const kind = form.dataset.aiRun;
    const data = new FormData(form);
    data.set('kind', kind);
    if (form.dataset.aiProject) data.set('project_id', form.dataset.aiProject);

    const controller = new AbortController();
    let cfg = null;
    let cancelled = false;

    setButtons(form, true);
    panel.open(kind);
    panel.onClose = () => setButtons(form, false);
    panel.onCancel = () => {
        cancelled = true;
        controller.abort();
        if (cfg && cfg.cancel_url) postJson(cfg.cancel_url, {}, form).catch(() => {});
        panel.close();
    };

    let res;
    try {
        res = await fetch(form.dataset.aiRunUrl, {
            method: 'POST',
            body: data,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: controller.signal,
        });
    } catch (e) {
        if (cancelled) return;
        panel.close();
        classicSubmit(form);
        return;
    }

    if (res.status === 422) {
        const json = await res.json().catch(() => ({}));
        const messages = Object.values(json.errors || {}).flat();
        panel.error(messages.length ? messages.join('\n') : json.message || 'البيانات مش مكتملة.');
        return;
    }
    if (res.status === 419) {
        panel.error('الصفحة قديمة — حدّث الصفحة (F5) وجرّب تاني.');
        return;
    }
    if (!res.ok) {
        panel.close();
        classicSubmit(form);
        return;
    }

    cfg = await res.json();
    if (cancelled) return;

    if (cfg.done) {
        return done(cfg, panel);
    }

    const eta = new Eta(cfg.estimate);
    if (cfg.estimate && cfg.estimate.loaded === true) panel.skipStage('load');
    panel.startTicking(eta);

    try {
        let outcome = null;

        if (cfg.direct) {
            panel.setMode('direct');
            try {
                outcome = await readStream(cfg.direct.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(cfg.direct.body),
                    signal: controller.signal,
                }, eta, panel);
            } catch (e) {
                if (!(e instanceof BeforeFirstByte)) throw e;
            }
        }

        if (!outcome) {
            panel.setMode('stream');
            try {
                outcome = await readStream(cfg.stream_url, {
                    method: 'POST',
                    headers: { Accept: 'application/x-ndjson', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken(form) },
                    credentials: 'same-origin',
                    signal: controller.signal,
                }, eta, panel);
            } catch (e) {
                if (!(e instanceof BeforeFirstByte)) throw e;
            }
        }

        let result;
        if (outcome) {
            eta.done();
            result = await postJson(cfg.complete_url, outcome, form, controller.signal);
        } else {
            panel.setMode('server');
            eta.serverOnly = true;
            result = await postJson(cfg.server_url, {}, form, controller.signal);
        }

        if (cancelled) return;
        done(result, panel);
    } catch (e) {
        if (cancelled || e instanceof Cancelled || e.name === 'AbortError') return;
        panel.error(e.message || 'حصلت مشكلة — جرّب تاني.');
    }
}

function done(result, panel) {
    panel.finish(result);
    if (result.ok === false) {
        // الفشل نفسه اتسجّل (في الشات مثلاً) — يبان بعد ما فؤاد يقرا الرسالة ويقفلها.
        if (result.reload) panel.onClose = () => window.location.reload();
        return;
    }

    // نجاح: نروح للصفحة الجديدة (مشروع اتعمل) أو نحدّث نفس الصفحة عشان التعديل يبان.
    let gone = false;
    const go = () => {
        if (gone) return;
        gone = true;
        if (result.redirect) window.location.assign(result.redirect);
        else window.location.reload();
    };
    panel.onClose = go;
    setTimeout(go, 1400);
}

export function initAiRuns() {
    const root = document.getElementById('ai-run-panel');
    if (!root) return;

    const supported = 'fetch' in window && 'ReadableStream' in window && 'AbortController' in window && 'TextDecoder' in window;
    const panel = new Panel(root);

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-ai-run]');
        if (!form || event.defaultPrevented || form.dataset.aiRunBypass === 'true') return;

        if (!supported) {
            const btn = form.querySelector('[data-ai-submit]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'بيفكر...';
            }
            return;
        }

        event.preventDefault();
        runForm(form, panel);
    });
}
