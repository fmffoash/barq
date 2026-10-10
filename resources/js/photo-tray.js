// صور مع الرسالة (2026-10-08) — فؤاد بيلزق كوبي جوجل مابس (الكلام + صور المكان) أو صور جاهزة
// (Ctrl+V / سحب / زرار)، والصور بتظهر كمصغّرات قبل الإرسال وبتتبعت مع الفورم:
// - ملفات (صور ملزوقة أو مرفوعة) ← <input type=file name="photos[]"> (DataTransfer)، فبتشتغل مع
//   الإرسال العادي ومع العدّاد (ai-run.js بيبعت new FormData(form)) بنفس الشكل.
// - صور جوجل مابس جوّه الكوبي (HTML) ← photo_urls[] — السيرفر بينزّلها (PhotoPoolService،
//   سيرفرات صور جوجل بس).
//
// [data-photo-tray] = مخزن صور كامل (صفحة الإنشاء). [data-paste-image] = صورة واحدة بتتحط في
// input موجود (شات التعديل: "حط الصورة دي في الهيرو").

const MAX_PHOTOS = 12;
const GOOGLE_IMAGE = /^https:\/\/([a-z0-9-]+\.)*(googleusercontent\.com|ggpht\.com)\//i;
const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

// صور المكان من الـHTML اللي جوجل مابس بيحطه في الكوبي — من غير الأيقونات الصغيرة.
export function googleImageUrls(html) {
    if (!html) return [];
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const urls = [];
    doc.querySelectorAll('img[src]').forEach((img) => {
        const src = img.getAttribute('src') || '';
        if (!GOOGLE_IMAGE.test(src)) return;
        const size = src.match(/=(?:s|w)(\d{1,4})(?:-|$)/);
        if (size && Number(size[1]) < 120) return;
        if (!urls.includes(src)) urls.push(src);
    });
    return urls;
}

function photosLabel(count) {
    if (count === 1) return 'صورة واحدة';
    if (count === 2) return 'صورتين';
    return count + (count <= 10 ? ' صور' : ' صورة');
}

function imageFiles(list) {
    return Array.from(list || []).filter((file) => IMAGE_TYPES.includes(file.type));
}

class PhotoTray {
    constructor(root) {
        this.root = root;
        this.form = root.closest('form');
        this.input = root.querySelector('[data-photo-input]');
        this.list = root.querySelector('[data-photo-list]');
        this.urlsBox = root.querySelector('[data-photo-urls]');
        this.hint = root.querySelector('[data-photo-hint]');
        // data-mode="pool": إضافة لمخزن الصور بس (صفحة المشروع) — مفيش "غلاف".
        this.poolOnly = root.dataset.mode === 'pool';
        this.items = [];

        root.querySelector('[data-photo-pick]')?.addEventListener('click', () => this.input.click());
        this.input.addEventListener('change', () => {
            // اختيار ملفات بالزرار بيستبدل محتوى الـinput — بنضيفهم للي موجود بدل ما يضيعوا.
            const picked = imageFiles(this.input.files);
            this.sync();
            this.addFiles(picked);
        });

        this.form.addEventListener('paste', (event) => this.onPaste(event));
        this.form.addEventListener('dragover', (event) => {
            if (Array.from(event.dataTransfer?.types || []).includes('Files')) {
                event.preventDefault();
                root.dataset.drag = 'true';
            }
        });
        this.form.addEventListener('dragleave', () => { root.dataset.drag = 'false'; });
        this.form.addEventListener('drop', (event) => {
            const files = imageFiles(event.dataTransfer?.files);
            root.dataset.drag = 'false';
            if (files.length) {
                event.preventDefault();
                this.addFiles(files);
            }
        });
    }

    onPaste(event) {
        const data = event.clipboardData;
        if (!data) return;

        const files = imageFiles(data.files);
        const urls = googleImageUrls(data.getData('text/html'));

        // صورة لوحدها (من غير كلام) — مفيش حاجة تتلزق في مربع الكتابة.
        if (files.length && !data.getData('text/plain')) event.preventDefault();

        this.addFiles(files);
        this.addUrls(urls);
    }

    addFiles(files) {
        files.forEach((file) => {
            if (this.items.length >= MAX_PHOTOS) return;
            this.items.push({ kind: 'file', file, preview: URL.createObjectURL(file) });
        });
        this.sync();
    }

    addUrls(urls) {
        urls.forEach((url) => {
            if (this.items.length >= MAX_PHOTOS || this.items.some((i) => i.url === url)) return;
            this.items.push({ kind: 'url', url, preview: url });
        });
        this.sync();
    }

    remove(index) {
        const [item] = this.items.splice(index, 1);
        if (item && item.kind === 'file') URL.revokeObjectURL(item.preview);
        this.sync();
    }

    sync() {
        const transfer = new DataTransfer();
        this.items.filter((i) => i.kind === 'file').forEach((i) => transfer.items.add(i.file));
        this.input.files = transfer.files;

        this.urlsBox.replaceChildren(...this.items.filter((i) => i.kind === 'url').map((i) => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'photo_urls[]';
            hidden.value = i.url;
            return hidden;
        }));

        // الترتيب اللي بيتبعت: الملفات الأول وبعدين روابط جوجل (السيرفر بيحطهم بنفس الترتيب) —
        // فالعرض بنفس الترتيب عشان رقم "1 = الغلاف" يبقى صح.
        const ordered = [...this.items.filter((i) => i.kind === 'file'), ...this.items.filter((i) => i.kind === 'url')];
        this.list.replaceChildren(...ordered.map((item, position) => {
            const figure = document.createElement('figure');
            figure.className = 'relative h-16 w-16 overflow-hidden rounded-lg border border-slate-700 bg-slate-950';

            const img = document.createElement('img');
            img.src = item.preview;
            img.alt = '';
            img.referrerPolicy = 'no-referrer';
            img.className = 'h-full w-full object-cover';
            figure.appendChild(img);

            const badge = document.createElement('span');
            badge.className = 'absolute bottom-0 right-0 rounded-tl bg-slate-950/80 px-1 text-[10px] text-amber-300';
            badge.textContent = position === 0 && !this.poolOnly ? 'غلاف' : String(position + 1);
            figure.appendChild(badge);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-slate-950/80 text-xs leading-5 text-slate-200 hover:bg-red-600';
            remove.setAttribute('aria-label', 'شيل الصورة');
            remove.textContent = '×';
            remove.addEventListener('click', () => this.remove(this.items.indexOf(item)));
            figure.appendChild(remove);

            return figure;
        }));

        if (this.hint) {
            const count = this.items.length;
            this.hint.textContent = !count ? '' : this.poolOnly
                ? photosLabel(count) + ' جاهزة تتضاف لمخزن صور المشروع.'
                : photosLabel(count) + ' — هتتحط في الموقع بالترتيب (الأولى غلاف)، والزيادة بتفضل في مخزن صور المشروع.';
        }
    }
}

// شات التعديل: صورة ملزوقة بتتحط في input الصورة الموجود + مصغّر صغير جنبه.
function initPasteImage(form) {
    const input = form.querySelector('input[type="file"][name="image"]');
    if (!input) return;

    const preview = form.querySelector('[data-paste-preview]');

    const show = () => {
        if (!preview) return;
        const file = input.files && input.files[0];
        preview.replaceChildren();
        if (!file) return;
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = '';
        img.className = 'h-10 w-10 rounded object-cover';
        preview.appendChild(img);
    };

    input.addEventListener('change', show);
    form.addEventListener('paste', (event) => {
        const files = imageFiles(event.clipboardData?.files);
        if (!files.length) return;
        if (!event.clipboardData.getData('text/plain')) event.preventDefault();
        const transfer = new DataTransfer();
        transfer.items.add(files[0]);
        input.files = transfer.files;
        show();
    });
}

export function initPhotoTrays() {
    if (typeof DataTransfer === 'undefined') return;
    document.querySelectorAll('[data-photo-tray]').forEach((root) => new PhotoTray(root));
    document.querySelectorAll('form[data-paste-image]').forEach(initPasteImage);
}
