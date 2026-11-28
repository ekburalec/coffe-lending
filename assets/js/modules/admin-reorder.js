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
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const motion = getComputedStyle(list);
    const duration = () => reducedMotion.matches ? 0 : parseFloat(motion.getPropertyValue('--admin-drag-duration'));
    const easing = motion.getPropertyValue('--ease-out').trim();
    const animations = new Map();

    function rearrange(change, stationary = active?.preview ? active.row : null) {
        const before = new Map(rows().map((row) => [row, row.getBoundingClientRect()]));
        animations.forEach((animation) => animation.cancel());
        animations.clear();
        change();
        rows().forEach((row) => {
            if (row === stationary) return;
            const previous = before.get(row);
            const current = row.getBoundingClientRect();
            const offset = previous.top - current.top;
            if (!offset || !duration()) return;
            const animation = row.animate([
                { transform: `translateY(${offset}px)` }, { transform: 'translateY(0)' },
            ], { duration: duration(), easing });
            animations.set(row, animation);
            animation.onfinish = () => animations.delete(row);
        });
    }

    function lift() {
        const rectangle = active.row.getBoundingClientRect();
        const preview = active.row.cloneNode(true);
        // Копия только визуальная: без повторных ID, JS-хуков и доступных контролов.
        [preview, ...preview.querySelectorAll('*')].forEach((element) => {
            element.removeAttribute('id');
            [...element.attributes].filter((attribute) => attribute.name.startsWith('data-'))
                .forEach((attribute) => element.removeAttribute(attribute.name));
        });
        preview.setAttribute('aria-hidden', 'true');
        preview.inert = true;
        preview.classList.add('is-drag-preview');
        list.appendChild(preview);
        active.preview = preview;
        active.rectangle = rectangle;
        active.row.classList.add('is-placeholder');
        list.classList.add('is-dragging');
        const frame = {
            left: `${rectangle.left}px`, top: `${rectangle.top}px`,
            width: `${rectangle.width}px`, height: `${rectangle.height}px`,
            transform: 'translate(0, 0)',
        };
        active.flight = preview.animate([frame, frame], { duration: 1, fill: 'both' });
    }

    function followPointer() {
        if (!active.preview) {
            if (Math.hypot(active.clientX - active.startX, active.clientY - active.startY) < 5) return;
            lift();
        }
        const rectangle = active.rectangle;
        const frame = {
            left: `${rectangle.left}px`, top: `${rectangle.top}px`,
            width: `${rectangle.width}px`, height: `${rectangle.height}px`,
            transform: `translate(${active.clientX - active.startX}px, ${active.clientY - active.startY}px)`,
        };
        active.flight.effect.setKeyframes([frame, frame]);
        movePointer();
    }

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
        if (!active?.preview) return;
        const bounds = list.getBoundingClientRect();
        if (active.clientX < bounds.left || active.clientX > bounds.right || active.clientY < bounds.top || active.clientY > bounds.bottom) return;
        // offsetTop не зависит от текущей FLIP-анимации соседей: нет дрожания порога.
        const others = rows().filter((row) => row !== active.row);
        const reference = others.find((row) => {
            const top = row.offsetTop + bounds.top;
            return active.clientY < top + row.offsetHeight / 2;
        }) || null;
        const current = rows();
        if (current[current.indexOf(active.row) + 1] !== reference && !(reference === null && current[current.length - 1] === active.row)) {
            rearrange(() => list.insertBefore(active.row, reference));
            preserveFocus();
        }
    }

    function scrollWhileDragging() {
        if (!active || active.pointerId === null) return;
        const edge = 72;
        const step = 12;
        const direction = !active.preview ? 0 : active.clientY < edge ? -1 : active.clientY > window.innerHeight - edge ? 1 : 0;
        const strength = direction < 0 ? (edge - active.clientY) / edge : (active.clientY - window.innerHeight + edge) / edge;
        if (direction) { window.scrollBy(0, direction * step * Math.min(1, strength)); followPointer(); }
        scrollFrame = requestAnimationFrame(scrollWhileDragging);
    }

    async function finish(commit) {
        if (!active) return;
        const drag = active;
        active = null;
        saving = true;
        cancelAnimationFrame(scrollFrame);
        if (drag.pointerId !== null && drag.handle.hasPointerCapture(drag.pointerId)) drag.handle.releasePointerCapture(drag.pointerId);
        drag.handle.setAttribute('aria-pressed', 'false');
        if (!commit) rearrange(() => drag.original.forEach((row) => list.appendChild(row)), drag.row);
        if (drag.preview) {
            const from = drag.preview.getBoundingClientRect();
            const to = drag.row.getBoundingClientRect();
            const landing = drag.preview.animate([
                { transform: `translate(${from.left - drag.rectangle.left}px, ${from.top - drag.rectangle.top}px)` },
                { transform: `translate(${to.left - drag.rectangle.left}px, ${to.top - drag.rectangle.top}px)` },
            ], { duration: duration(), easing, fill: 'forwards' });
            await landing.finished;
            drag.preview.remove();
            drag.flight.cancel();
            landing.cancel();
        }
        drag.row.classList.remove('is-dragging', 'is-placeholder');
        list.classList.remove('is-dragging');
        saving = false;
        if (!commit) {
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
            rearrange(() => drag.original.forEach((row) => list.appendChild(row)));
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
        active.startX = event.clientX;
        active.startY = event.clientY;
        scrollFrame = requestAnimationFrame(scrollWhileDragging);
    });
    document.addEventListener('pointermove', (event) => {
        if (!active || event.pointerId !== active.pointerId) return;
        active.clientX = event.clientX;
        active.clientY = event.clientY;
        followPointer();
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
        if (active && active.pointerId !== null) return;
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
        rearrange(() => list.insertBefore(active.row, targetIndex < index ? current[targetIndex] : current[targetIndex].nextSibling));
        preserveFocus();
    });
}
