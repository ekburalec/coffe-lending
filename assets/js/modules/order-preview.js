import { notify } from './notify.js';

/** Локальный состав заказа. Цены, тарифы и тексты читаются из HTML. */
export function initOrderPreview() {
    const root = document.querySelector('[data-order-preview]');
    const cards = document.querySelectorAll('[data-product]');
    if (!root || !cards.length) return;

    const products = new Map();
    cards.forEach((card) => {
        products.set(card.dataset.product, {
            name: card.querySelector('[data-product-name]').textContent,
            price: Number(card.querySelector('[data-unit-price]').value),
            quantity: 0,
        });
    });

    const rows = [...root.querySelectorAll('[data-order-row]')];
    const empty = root.querySelector('[data-order-empty]');
    const more = root.querySelector('[data-order-more]');
    const controls = root.querySelector('[data-order-controls]');
    const summary = root.querySelector('[data-order-summary]');
    const subtotalOutput = root.querySelector('[data-order-subtotal]');
    const deliveryOutput = root.querySelector('[data-order-delivery]');
    const totalOutput = root.querySelector('[data-order-total]');
    const deliveryLabel = root.querySelector('[data-order-delivery-label]');
    const deliveryHint = root.querySelector('[data-order-delivery-hint]');
    const receivingOptions = [...root.querySelectorAll('[data-receiving]')];
    const counters = document.querySelectorAll('[data-order-counter]');
    const fee = Number(root.dataset.deliveryFee);
    const threshold = Number(root.dataset.freeDeliveryFrom);
    const formatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        maximumFractionDigits: 0,
    });

    function render() {
        let subtotal = 0;
        let count = 0;
        rows.forEach((row) => {
            const product = products.get(row.dataset.orderRow);
            row.hidden = product.quantity === 0;
            row.querySelector('[data-order-quantity]').textContent = product.quantity;
            row.querySelector('[data-order-line-total]').textContent = formatter.format(
                product.price * product.quantity,
            );
            subtotal += product.price * product.quantity;
            count += product.quantity;
        });
        const isDelivery = receivingOptions.some(
            (option) => option.checked && option.value === 'delivery',
        );
        const delivery = count && isDelivery && subtotal < threshold ? fee : 0;
        subtotalOutput.textContent = formatter.format(subtotal);
        deliveryOutput.textContent = formatter.format(delivery);
        totalOutput.textContent = formatter.format(subtotal + delivery);
        deliveryLabel.textContent = isDelivery
            ? root.dataset.labelDelivery
            : root.dataset.labelPickup;
        deliveryHint.textContent = !isDelivery
            ? root.dataset.hintPickup
            : !count
              ? root.dataset.hintEmpty
              : delivery
                ? root.dataset.hintPaid
                : root.dataset.hintFree;
        empty.hidden = count > 0;
        more.hidden = count === 0;
        if (summary) summary.hidden = count === 0;
        counters.forEach((counter) => {
            counter.textContent = count;
            counter.setAttribute(
                'aria-label',
                root.dataset.counterLabel.replace('{количество}', count),
            );
        });
    }

    function handleOrderClick(event) {
        const button = event.target.closest('button');
        if (!button) return;
        const action = ['Add', 'Increase', 'Decrease', 'Remove'].find(
            (name) => button.dataset[`order${name}`],
        );
        if (!action) return;
        const id = button.dataset[`order${action}`];
        const product = products.get(id);
        if (!product) return;
        const wasInOrder = root.contains(button);
        if (action === 'Remove') product.quantity = 0;
        else product.quantity = Math.max(0, product.quantity + (action === 'Decrease' ? -1 : 1));
        render();
        const message =
            product.quantity === 0
                ? root.dataset.messageRemoved
                : action === 'Add'
                  ? root.dataset.messageAdded
                  : root.dataset.messageChanged;
        notify(
            message.replace('{название}', product.name).replace('{количество}', product.quantity),
        );
        if (wasInOrder && product.quantity === 0) {
            const nextRow = rows.find((row) => !row.hidden);
            const nextControl = nextRow
                ? nextRow.querySelector('[data-order-decrease]')
                : empty.querySelector('[data-order-empty-link]');
            nextControl.focus({ preventScroll: true });
        }
    }

    document.querySelectorAll('[data-order-add]').forEach((button) => {
        button.hidden = false;
    });
    controls.hidden = false;
    document.addEventListener('click', handleOrderClick);
    receivingOptions.forEach((option) => option.addEventListener('change', render));
    render();
}
