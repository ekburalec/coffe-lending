import { notify } from './notify.js';

export function initAdminOrders() {
    const count = document.querySelector('[data-orders-count]');
    const board = document.querySelector('[data-order-board]');
    const print = document.querySelector('[data-order-print]');
    if (!count && !board && !print) return;
    if (print) print.addEventListener('click', () => window.print());
    if (count) {
        let previous = Number(count.dataset.count);
        async function refresh() {
            if (document.hidden) return;
            try {
                const response = await fetch('/admin/orders/count', { headers: { Accept: 'application/json' } });
                if (!response.ok || response.redirected) throw new Error('Не удалось обновить заказы. Проверьте вход и соединение.');
                const data = await response.json();
                if (data.count > previous) notify('Поступил новый заказ.');
                previous = data.count;
                count.textContent = `Новых заказов: ${data.count}. Обновлено ${data.time}`;
            } catch (error) { count.textContent = error.message; }
        }
        refresh();
        window.setInterval(refresh, 30000);
    }
    if (!board) return;
    let dragged = null;
    board.querySelectorAll('[data-order-card]').forEach((card) => {
        card.draggable = true;
        card.addEventListener('dragstart', (event) => { dragged = card; event.dataTransfer.setData('text/plain', card.dataset.orderCard); });
        card.addEventListener('dragend', () => { dragged = null; });
    });
    board.querySelectorAll('[data-order-column]').forEach((column) => {
        column.addEventListener('dragover', (event) => event.preventDefault());
        column.addEventListener('drop', async (event) => {
            event.preventDefault();
            const card = dragged;
            if (!card || card.parentElement === column || board.classList.contains('is-loading')) return;
            const form = new FormData();
            form.set('csrf', board.dataset.csrf); form.set('id', card.dataset.orderCard);
            form.set('version', card.dataset.version); form.set('status', column.dataset.orderColumn);
            board.classList.add('is-loading');
            board.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch('/admin/orders/move', {method: 'POST', body: form});
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Не удалось изменить статус.');
                window.location.reload();
            } catch (error) { notify(error.message); }
            finally { board.classList.remove('is-loading'); board.setAttribute('aria-busy', 'false'); }
        });
    });
}
