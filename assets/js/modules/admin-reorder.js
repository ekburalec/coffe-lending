import { notify } from './notify.js';

/** Порядок товаров, навигации и вопросов: мышь, касание и клавиатура. */
export function initAdminReorder() {
    document.querySelectorAll('[data-reorder-list]').forEach(initList);
}

function initList(list) {
    const status = list.parentElement.querySelector('[data-reorder-status]');
    const rows = () => [...list.querySelectorAll('[data-reorder-id]')];
    const ids = () => rows().map((row) => row.dataset.reorderId);
    const handles = () => [...list.querySelectorAll('[data-reorder-handle]')];
    let active = null;
    let saving = false;
    let scrollFrame = null;

    if (rows().length < 2) return;
    list.classList.add('is-sortable');
    handles().forEach((handle) => { handle.hidden = false; });

    function reportPosition() {
        if (!active) return;
        status.textContent = list.dataset.messageMoving
            .replace('{название}', active.row.dataset.reorderName)
            .replace('{позиция}', rows().indexOf(active.row) + 1)
            .replace('{всего}', rows().length);
    }

    function start(handle, pointerId = null) {
        if (saving || active) return false;
        active = {
            handle, pointerId,
            row: handle.closest('[data-reorder-id]'),
            original: rows(),
            clientX: 0, clientY: 0,
        };
        active.row.classList.add('is-dragging');
        handle.setAttribute('aria-pressed', 'true');
        handle.focus({ preventScroll: true });
        if (pointerId !== null) handle.setPointerCapture(pointerId);
        reportPosition();
        return true;
    }

    function preserveFocus() {
        // Захват указателя возобновляется после перемещения узла в DOM.
        if (active.pointerId !== null) active.handle.setPointerCapture(active.pointerId);
        active.handle.focus({ preventScroll: true });
        reportPosition();
    }

    function movePointer() {
        if (!active) return;
        const target = document.elementFromPoint(active.clientX, active.clientY)?.closest('[data-reorder-id]');
        if (!target || target === active.row || target.parentElement !== list) return;
        const rectangle = target.getBoundingClientRect();
        const reference = active.clientY < rectangle.top + rectangle.height / 2 ? target : target.nextSibling;
        if (reference !== active.row && active.row.nextSibling !== reference) {
            list.insertBefore(active.row, reference);
            preserveFocus();
        }
    }

    function scrollWhileDragging() {
        if (!active || active.pointerId === null) return;
        const edge = 72;
        const step = 12;
        const direction = active.clientY < edge ? -1 : active.clientY > window.innerHeight - edge ? 1 : 0;
        if (direction) { window.scrollBy(0, direction * step); movePointer(); }
        scrollFrame = requestAnimationFrame(scrollWhileDragging);
    }

    async function finish(commit) {
        if (!active) return;
        const drag = active;
        active = null;
        cancelAnimationFrame(scrollFrame);
        if (drag.pointerId !== null && drag.handle.hasPointerCapture(drag.pointerId)) drag.handle.releasePointerCapture(drag.pointerId);
        drag.row.classList.remove('is-dragging');
        drag.handle.setAttribute('aria-pressed', 'false');
        if (!commit) {
            drag.original.forEach((row) => list.appendChild(row));
            drag.handle.focus({ preventScroll: true });
            status.textContent = list.dataset.messageCancelled;
            return;
        }
        const order = ids();
        if (order.join(',') === drag.original.map((row) => row.dataset.reorderId).join(',')) {
            status.textContent = '';
            return;
        }
        saving = true;
        list.classList.add('is-saving');
        list.setAttribute('aria-busy', 'true');
        handles().forEach((handle) => { handle.disabled = true; });
        status.textContent = list.dataset.messageSaving;
        try {
            const response = await fetch(list.dataset.reorderUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new URLSearchParams({
                    csrf: list.dataset.reorderCsrf,
                    category: list.dataset.reorderCategory,
                    section: list.dataset.reorderSection || '',
                    version: list.dataset.reorderVersion,
                    ids: JSON.stringify(order),
                }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || list.dataset.messageError);
            if (typeof result.version !== 'string') throw new Error(list.dataset.messageError);
            list.dataset.reorderVersion = result.version;
            status.textContent = list.dataset.messageSaved;
            notify(list.dataset.messageSaved, 'success');
        } catch (error) {
            drag.original.forEach((row) => list.appendChild(row));
            status.textContent = error.message || list.dataset.messageError;
            notify(status.textContent, 'error');
            console.error(error);
        } finally {
            saving = false;
            list.classList.remove('is-saving');
            list.setAttribute('aria-busy', 'false');
            handles().forEach((handle) => { handle.disabled = false; });
            drag.handle.focus({ preventScroll: true });
        }
    }

    list.addEventListener('pointerdown', (event) => {
        const handle = event.target.closest('[data-reorder-handle]');
        if (!handle || event.button !== 0 || !event.isPrimary || !start(handle, event.pointerId)) return;
        event.preventDefault();
        active.clientX = event.clientX;
        active.clientY = event.clientY;
        scrollFrame = requestAnimationFrame(scrollWhileDragging);
    });
    document.addEventListener('pointermove', (event) => {
        if (!active || event.pointerId !== active.pointerId) return;
        active.clientX = event.clientX;
        active.clientY = event.clientY;
        movePointer();
    });
    document.addEventListener('pointerup', (event) => {
        if (active && event.pointerId === active.pointerId) {
            finish(list.contains(document.elementFromPoint(event.clientX, event.clientY)));
        }
    });
    document.addEventListener('pointercancel', (event) => {
        if (active && event.pointerId === active.pointerId) finish(false);
    });
    window.addEventListener('blur', () => finish(false));
    list.addEventListener('keydown', (event) => {
        const handle = event.target.closest('[data-reorder-handle]');
        if (!handle || saving) return;
        if (event.key === 'Escape' && active) { event.preventDefault(); finish(false); return; }
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            if (active) finish(true);
            else start(handle);
            return;
        }
        if (!active || active.pointerId !== null || !['ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const current = rows();
        const index = current.indexOf(active.row);
        const targetIndex = event.key === 'Home' ? 0 : event.key === 'End' ? current.length - 1 : Math.max(0, Math.min(current.length - 1, index + (event.key === 'ArrowUp' ? -1 : 1)));
        if (targetIndex === index) return;
        list.insertBefore(active.row, targetIndex < index ? current[targetIndex] : current[targetIndex].nextSibling);
        preserveFocus();
    });
}
