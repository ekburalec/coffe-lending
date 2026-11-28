import { notify } from './notify.js';

/** Верхняя панель редактирует сам лендинг в текущем документе. */
export function initVisualEditor() {
    const root = document.querySelector('[data-visual-editor]');
    if (!root) return;
    const saveButton = root.querySelector('[data-visual-save]');
    const cancelButton = root.querySelector('[data-visual-cancel]');
    const count = root.querySelector('[data-visual-count]');
    const message = root.querySelector('[data-visual-message]');
    const selectionLabel = root.querySelector('[data-visual-selection]');
    const managedLink = root.querySelector('[data-visual-managed-link]');
    const loginLink = root.querySelector('[data-visual-login]');
    const controls = root.querySelector('[data-visual-controls]');
    const formatButtons = [...root.querySelectorAll('[data-visual-format]')];
    const photo = root.querySelector('[data-visual-photo]');
    const photoForm = root.querySelector('[data-visual-photo-form]');
    const altInput = root.querySelector('[data-visual-photo-alt]');
    const fileInput = root.querySelector('[data-visual-photo-file]');
    const photoPreview = root.querySelector('[data-visual-photo-preview]');
    const photoError = root.querySelector('[data-visual-photo-error]');
    const leave = root.querySelector('[data-visual-leave]');
    let state = JSON.parse(root.dataset.state);
    let textElements = [];
    let imageElements = [];
    let managedElements = [];
    let mirrors = [];
    let activeText = null;
    let savedRange = null;
    let editing = true;
    let saving = false;
    let leaveTarget = null;
    let leaveAction = null;
    let photoKey = null;
    let photoTrigger = null;
    let draftFile = null;
    let draftUrl = null;
    let photoReading = false;
    let photoReadVersion = 0;
    const textChanges = new Map();
    const imageChanges = new Map();
    const urls = new Set();
    const originalAttributes = new Map();

    function showMessage(text, error = false) {
        message.textContent = text;
        message.hidden = !text;
        message.classList.toggle('is-error', error);
        if (error) notify(text, 'error');
    }

    function dirty() { return textChanges.size + imageChanges.size; }

    function updateControls() {
        saveButton.disabled = saving || !dirty();
        cancelButton.disabled = saving || !dirty();
        count.textContent = saving ? 'Сохраняем…' : dirty() ? `Изменено элементов: ${dirty()}` : 'Нет изменений';
        root.setAttribute('aria-busy', String(saving));
        formatButtons.forEach(button => { button.disabled = saving || !editing || !activeText; });
        textElements.forEach(element => {
            element.contentEditable = String(editing && !saving);
            element.classList.toggle('is-changed', textChanges.has(element.dataset.visualText));
        });
        imageElements.forEach(element => element.classList.toggle('is-changed', imageChanges.has(element.dataset.visualImage)));
    }

    // Нормализация клиентского DOM нужна для чистой вставки и сравнения правок.
    // Окончательная проверка и очистка всегда выполняются сервером.
    function safeHtml(html) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const output = document.createElement('div');
        function append(source, target) {
            for (const node of source.childNodes) {
                if (node.nodeType === Node.TEXT_NODE) {
                    target.append(document.createTextNode(node.textContent));
                    continue;
                }
                if (node.nodeType !== Node.ELEMENT_NODE) continue;
                const name = node.tagName.toLowerCase();
                if (['script', 'style', 'iframe', 'object', 'svg', 'math', 'template'].includes(name)) continue;
                const tag = name === 'b' ? 'strong' : name === 'i' ? 'em' : name;
                if (['strong', 'em', 'br'].includes(tag)) {
                    const child = document.createElement(tag);
                    if (tag !== 'br') append(node, child);
                    target.append(child);
                } else {
                    append(node, target);
                    if (['p', 'div', 'li'].includes(tag)) target.append(document.createElement('br'));
                }
            }
        }
        append(wrapper, output);
        return output.innerHTML.replace(/(?:<br>)+$/, '').trim();
    }

    function canonicalizeState() {
        Object.values(state.texts).forEach(item => { item.html = safeHtml(item.html); });
    }

    function rememberAttributes(element) {
        originalAttributes.set(element, Object.fromEntries(['tabindex', 'role', 'aria-label', 'aria-multiline', 'title'].map(name => [name, element.getAttribute(name)])));
    }

    function restoreAttributes(element) {
        for (const [name, value] of Object.entries(originalAttributes.get(element) || {})) {
            if (value === null) element.removeAttribute(name);
            else element.setAttribute(name, value);
        }
    }

    function setMode() {
        document.body.classList.toggle('is-viewing', !editing);
        controls.hidden = !editing;
        textElements.forEach(element => {
            if (editing) {
                element.tabIndex = 0;
                element.setAttribute('role', 'textbox');
                element.setAttribute('aria-label', state.texts[element.dataset.visualText].label);
                element.setAttribute('aria-multiline', 'true');
                element.title = `Редактировать: ${state.texts[element.dataset.visualText].label}`;
            } else restoreAttributes(element);
        });
        imageElements.forEach(element => {
            if (editing) {
                element.tabIndex = 0;
                element.setAttribute('role', 'button');
                element.setAttribute('aria-label', `Изменить фотографию: ${state.images[element.dataset.visualImage].label}`);
                element.title = `Изменить фото и описание: ${state.images[element.dataset.visualImage].label}`;
            } else restoreAttributes(element);
        });
        managedElements.forEach(element => {
            if (editing) {
                element.tabIndex = 0;
                element.title = `Изменяется в разделе «${element.dataset.visualManaged}»`;
            }
            else restoreAttributes(element);
        });
        const modeToggle = root.querySelector('[data-visual-mode-toggle]');
        modeToggle.setAttribute('aria-pressed', String(editing));
        modeToggle.setAttribute('aria-expanded', String(editing));
        updateControls();
    }

    function selectText(element) {
        activeText = element;
        selectionLabel.textContent = state.texts[element.dataset.visualText].label;
        managedLink.hidden = true;
        updateControls();
    }

    function updateText(element) {
        const id = element.dataset.visualText;
        const html = safeHtml(element.innerHTML);
        if (html === state.texts[id].html) textChanges.delete(id);
        else textChanges.set(id, {kind: 'content', id, html, version: state.texts[id].version});
        for (const other of [...textElements, ...mirrors]) {
            if (other !== element && (other.dataset.visualText || other.dataset.visualMirror) === id) other.innerHTML = html;
        }
        updateControls();
    }

    function format(command) {
        if (!editing || saving || !activeText || !savedRange) return;
        const selection = window.getSelection();
        activeText.focus();
        selection.removeAllRanges();
        selection.addRange(savedRange);
        document.execCommand(command);
        updateText(activeText);
        if (selection.rangeCount) savedRange = selection.getRangeAt(0).cloneRange();
    }

    function showManaged(element, keepSelection = false) {
        if (!keepSelection) { activeText = null; savedRange = null; }
        selectionLabel.textContent = `Изменяется в разделе «${element.dataset.visualManaged}»`;
        managedLink.href = element.dataset.visualHref;
        managedLink.hidden = false;
        updateControls();
    }

    function releaseUrl(url) {
        if (!url || !urls.has(url)) return;
        URL.revokeObjectURL(url);
        urls.delete(url);
    }

    function renderImages() {
        for (const image of imageElements) {
            const key = image.dataset.visualImage;
            const pending = imageChanges.get(key);
            image.alt = pending ? pending.alt : state.images[key].alt;
            image.src = pending?.url || state.images[key].src;
            if (pending?.file) image.removeAttribute('srcset');
            else image.srcset = `${state.images[key].src} 1x, ${state.images[key].src.replace(/\.webp$/, '@2x.webp')} 2x`;
        }
    }

    function openPhoto(element) {
        if (saving) return;
        activeText = null;
        savedRange = null;
        photoKey = element.dataset.visualImage;
        photoTrigger = element;
        const pending = imageChanges.get(photoKey);
        draftFile = pending?.file || null;
        draftUrl = pending?.url || null;
        altInput.value = pending ? pending.alt : state.images[photoKey].alt;
        fileInput.value = '';
        fileInput.removeAttribute('aria-invalid');
        photoError.textContent = '';
        photoPreview.src = draftUrl || state.images[photoKey].src;
        photoPreview.removeAttribute('srcset');
        root.querySelector('[data-visual-photo-label]').textContent = state.images[photoKey].label;
        photo.showModal();
        altInput.focus();
        updateControls();
    }

    function resetChanges() {
        textChanges.clear();
        imageChanges.clear();
        [...urls].forEach(releaseUrl);
        for (const element of [...textElements, ...mirrors]) {
            const id = element.dataset.visualText || element.dataset.visualMirror;
            element.innerHTML = state.texts[id].html;
        }
        renderImages();
        updateControls();
        showMessage('Несохранённые изменения отменены.');
    }

    async function fetchJson(url, options) {
        const response = await fetch(url, options);
        if (!response.ok) {
            const data = await response.json();
            if (response.status === 401) loginLink.hidden = false;
            throw new Error(data.message || 'Не удалось выполнить действие. Правки остались в редакторе.');
        }
        return await response.json();
    }

    async function save() {
        if (saving) return false;
        if (!dirty()) return true;
        for (const change of textChanges.values()) {
            const plain = document.createElement('div');
            plain.innerHTML = change.html;
            if (!plain.textContent.trim() || plain.textContent.length > 5000) {
                showMessage(`Заполните текст «${state.texts[change.id].label}», не более 5000 символов.`, true);
                return false;
            }
        }
        saving = true;
        updateControls();
        showMessage('Сохраняем изменения…');
        try {
            // Повторный вход в другой вкладке восстанавливает CSRF без потери ввода.
            const session = await fetchJson('/admin/visual-editor/session', {cache: 'no-store'});
            const logoutCsrf = document.querySelector('form[action="/admin/logout"] input[name="csrf"]');
            if (logoutCsrf) logoutCsrf.value = session.csrf;
            const form = new FormData();
            form.append('csrf', session.csrf);
            const changes = [...textChanges.values()];
            for (const [id, change] of imageChanges) {
                changes.push({kind: 'images', id, alt: change.alt, version: state.images[id].version});
                if (change.file) form.append(`photo_${id}`, change.file);
            }
            form.append('changes', JSON.stringify(changes));
            const result = await fetchJson('/admin/visual-editor/save', {method: 'POST', body: form});
            state = result.state;
            canonicalizeState();
            textChanges.clear();
            imageChanges.clear();
            [...urls].forEach(releaseUrl);
            for (const element of [...textElements, ...mirrors]) {
                const id = element.dataset.visualText || element.dataset.visualMirror;
                element.innerHTML = state.texts[id].html;
            }
            renderImages();
            loginLink.hidden = true;
            showMessage(result.message);
            notify(result.message, 'success');
            return true;
        } catch (error) {
            console.error('Visual editor save failed:', error);
            showMessage(error instanceof TypeError ? 'Нет связи с сервером. Правки остались в редакторе; повторите сохранение.' : error.message, true);
            return false;
        } finally {
            saving = false;
            updateControls();
        }
    }

    function navigate(href) {
        if (saving) return;
        if (!dirty()) { window.location.assign(href); return; }
        leaveTarget = href;
        leaveAction = () => window.location.assign(leaveTarget);
        leave.showModal();
    }

    function pageClick(event) {
        if (root.contains(event.target)) return;
        if (!editing) {
            const external = event.target.closest('a[href]');
            if (external && external.target !== '_blank' && !external.getAttribute('href').startsWith('#')) {
                event.preventDefault();
                navigate(external.href);
            }
            return;
        }
        const text = event.target.closest('[data-visual-text]');
        if (text) {
            event.preventDefault();
            event.stopImmediatePropagation();
            selectText(text);
            if (document.activeElement !== text) text.focus();
            return;
        }
        const image = event.target.closest('[data-visual-image]');
        const managed = event.target.closest('[data-visual-managed]');
        if (image || managed) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (image) openPhoto(image);
            else { showManaged(managed); navigate(managed.dataset.visualHref); }
        }
    }

    function initPage() {
        try {
            loginLink.hidden = true;
            showMessage('');
            document.body.classList.add('visual-editor__page');
            textElements = [...document.querySelectorAll('[data-visual-text]')].filter(element => state.texts[element.dataset.visualText]);
            imageElements = [...document.querySelectorAll('[data-visual-image]')].filter(element => state.images[element.dataset.visualImage]);
            managedElements = [...document.querySelectorAll('[data-visual-managed]')];
            mirrors = [...document.querySelectorAll('[data-visual-mirror]')];
            for (const element of textElements) {
                rememberAttributes(element);
                element.classList.add('visual-editor__text');
            }
            for (const element of imageElements) {
                rememberAttributes(element);
                element.classList.add('visual-editor__image');
            }
            for (const element of managedElements) {
                rememberAttributes(element);
                element.classList.add('visual-editor__managed');
            }
            for (const element of [...textElements, ...mirrors]) {
                const id = element.dataset.visualText || element.dataset.visualMirror;
                element.innerHTML = textChanges.get(id)?.html || state.texts[id].html;
            }
            renderImages();
            document.addEventListener('click', pageClick, true);
            document.addEventListener('focusin', event => {
                if (!editing) return;
                const text = event.target.closest('[data-visual-text]');
                const managed = event.target.closest('[data-visual-managed]');
                if (text) selectText(text);
                else if (managed) showManaged(managed);
            });
            document.addEventListener('pointerover', event => {
                const managed = event.target.closest('[data-visual-managed]');
                if (editing && managed && !event.target.closest('[data-visual-text]')) showManaged(managed, true);
            });
            document.addEventListener('input', event => {
                const element = event.target.closest('[data-visual-text]');
                if (element) updateText(element);
            });
            document.addEventListener('selectionchange', () => {
                const selection = window.getSelection();
                if (!selection.rangeCount || !activeText) return;
                const range = selection.getRangeAt(0);
                if (activeText.contains(range.commonAncestorContainer)) savedRange = range.cloneRange();
            });
            document.addEventListener('paste', event => {
                if (!editing || !event.target.closest('[data-visual-text]')) return;
                event.preventDefault();
                document.execCommand('insertText', false, event.clipboardData.getData('text/plain'));
            });
            document.addEventListener('drop', event => {
                if (editing && event.target.closest('[data-visual-text]')) event.preventDefault();
            });
            document.addEventListener('keydown', event => {
                const text = event.target.closest('[data-visual-text]');
                if (editing && text && (event.ctrlKey || event.metaKey) && ['b', 'i'].includes(event.key.toLowerCase())) {
                    event.preventDefault();
                    format(event.key.toLowerCase() === 'b' ? 'bold' : 'italic');
                }
                if (!editing || !['Enter', ' '].includes(event.key) || text) return;
                const image = event.target.closest('[data-visual-image]');
                const managed = event.target.closest('[data-visual-managed]');
                if (image || managed) {
                    event.preventDefault();
                    if (image) openPhoto(image);
                    else navigate(managed.dataset.visualHref);
                }
            }, true);
            setMode();
            const key = window.location.hash.slice(1);
            const target = textElements.find(element => element.dataset.visualText === key);
            if (target) { target.scrollIntoView({block: 'center'}); target.focus(); }
        } catch (error) {
            console.error('Visual editor initialization failed:', error);
            showMessage('Не удалось включить редактирование. Обновите страницу.', true);
        }
    }

    canonicalizeState();
    initPage();
    saveButton.addEventListener('click', save);
    cancelButton.addEventListener('click', resetChanges);
    formatButtons.forEach(button => {
        button.addEventListener('pointerdown', event => event.preventDefault());
        button.addEventListener('click', () => format(button.dataset.visualFormat));
    });
    root.querySelector('[data-visual-mode-toggle]').addEventListener('click', () => {
        if (saving) return;
        editing = !editing;
        setMode();
    });
    fileInput.addEventListener('change', async () => {
        photoError.textContent = '';
        fileInput.removeAttribute('aria-invalid');
        const readVersion = ++photoReadVersion;
        photoReading = false;
        photoForm.querySelector('[type="submit"]').disabled = false;
        const file = fileInput.files[0];
        if (!file) return;
        if (file.size > 10 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            fileInput.value = '';
            photoError.textContent = 'Выберите JPG, PNG или WebP размером до 10 МБ.';
            fileInput.setAttribute('aria-invalid', 'true');
            return;
        }
        const url = URL.createObjectURL(file);
        urls.add(url);
        photoReading = true;
        photoForm.querySelector('[type="submit"]').disabled = true;
        const probe = new Image();
        probe.src = url;
        try {
            await probe.decode();
            if (readVersion !== photoReadVersion || !photo.open) { releaseUrl(url); return; }
            if (probe.width < 100 || probe.height < 100 || probe.width * probe.height > 20000000) throw new Error('Изображение: минимум 100×100, максимум 20 мегапикселей.');
            if (draftUrl !== imageChanges.get(photoKey)?.url) releaseUrl(draftUrl);
            draftFile = file;
            draftUrl = url;
            photoPreview.src = url;
        } catch (error) {
            releaseUrl(url);
            if (readVersion !== photoReadVersion) return;
            fileInput.value = '';
            photoError.textContent = error instanceof DOMException ? 'Не удалось прочитать изображение. Выберите другой файл.' : error.message;
            fileInput.setAttribute('aria-invalid', 'true');
        } finally {
            if (readVersion === photoReadVersion) {
                photoReading = false;
                photoForm.querySelector('[type="submit"]').disabled = false;
            }
        }
    });
    photoForm.addEventListener('submit', event => {
        event.preventDefault();
        if (photoReading) return;
        const alt = altInput.value.trim();
        if (!alt || alt.length > 300) { altInput.focus(); return; }
        const previous = imageChanges.get(photoKey);
        if (previous?.url !== draftUrl) releaseUrl(previous?.url);
        if (alt === state.images[photoKey].alt && !draftFile) imageChanges.delete(photoKey);
        else imageChanges.set(photoKey, {alt, file: draftFile, url: draftUrl});
        renderImages();
        photo.close();
        updateControls();
        showMessage('Фотография подготовлена. Нажмите «Сохранить», чтобы опубликовать изменения.');
    });
    root.querySelector('[data-visual-photo-close]').addEventListener('click', () => photo.close());
    photo.addEventListener('close', () => {
        photoReadVersion++;
        photoReading = false;
        photoForm.querySelector('[type="submit"]').disabled = false;
        if (draftUrl !== imageChanges.get(photoKey)?.url) releaseUrl(draftUrl);
        photoTrigger?.focus();
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.getAttribute('href').startsWith('#')) return;
        if (dirty() || saving) { event.preventDefault(); navigate(link.href); }
    });
    document.addEventListener('submit', event => {
        if (event.target.getAttribute('action') !== '/admin/logout' || (!dirty() && !saving)) return;
        event.preventDefault();
        if (saving) return;
        leaveAction = () => event.target.submit();
        leave.showModal();
    });
    root.querySelector('[data-visual-leave-stay]').addEventListener('click', () => leave.close());
    root.querySelector('[data-visual-leave-discard]').addEventListener('click', () => {
        resetChanges();
        leaveAction();
    });
    root.querySelector('[data-visual-leave-save]').addEventListener('click', async () => {
        leave.close();
        if (await save()) leaveAction();
    });
    window.addEventListener('beforeunload', event => {
        if (dirty()) { event.preventDefault(); event.returnValue = ''; }
    });
    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); if (!photo.open && !leave.open) save(); }
    });
}
