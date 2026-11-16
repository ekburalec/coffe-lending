import { notify } from './notify.js';

export function initCart() {
  const dialog = document.querySelector('[data-cart-dialog]');
  const openButton = document.querySelector('[data-cart-open]');
  const template = document.getElementById('cart-item-template');
  const cards = Array.from(document.querySelectorAll('[data-product]'));
  if (!dialog || !openButton || !template || !cards.length) return;

  const list = dialog.querySelector('[data-cart-items]');
  const empty = dialog.querySelector('[data-cart-empty]');
  const content = dialog.querySelector('[data-cart-content]');
  const form = dialog.querySelector('[data-checkout]');
  const error = dialog.querySelector('[data-checkout-error]');
  const status = dialog.querySelector('[data-checkout-status]');
  const closeButton = dialog.querySelector('[data-cart-close]');
  const checkoutButton = dialog.querySelector('[data-cart-checkout-open]');
  const countLabel = document.querySelector('[data-cart-count]');
  const nameInput = form.querySelector('[data-checkout-name]');
  const phoneInput = form.querySelector('[data-checkout-phone]');
  const consent = form.querySelector('[data-checkout-consent]');
  const storageKey = 'coffe-go-cart-v1';
  const money = new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 });
  const catalog = new Map(cards.map(card => {
    const sizeSelect = card.querySelector('select[data-product-size]');
    const grindSelect = card.querySelector('select[data-product-grind]');
    const variants = sizeSelect
      ? Array.from(sizeSelect.options).map(option => ({ id: option.value, label: option.textContent, price: Number(option.dataset.price) }))
      : [{ id: 'default', label: card.dataset.productSize, price: Number(card.dataset.productPrice) }];
    return [card.dataset.productId, {
      name: card.dataset.productName,
      image: card.dataset.productImage,
      variants,
      grinds: grindSelect ? Array.from(grindSelect.options).map(option => option.value) : [''],
    }];
  }));

  function getProduct(item) {
    const product = catalog.get(item.id);
    const variant = product?.variants.find(option => option.id === item.size);
    if (!product || !variant || !product.grinds.includes(item.grind)) return null;
    return { ...product, variant };
  }

  function makeKey(item) {
    return JSON.stringify([item.id, item.size, item.grind]);
  }

  function readCart() {
    try {
      const stored = JSON.parse(localStorage.getItem(storageKey) || '[]');
      if (!Array.isArray(stored)) return [];
      const combined = new Map();
      stored.forEach(item => {
        if (!item || !getProduct(item) || !Number.isInteger(item.quantity) || item.quantity < 1) return;
        const key = makeKey(item);
        const previous = combined.get(key);
        combined.set(key, { id: item.id, size: item.size, grind: item.grind, quantity: Math.min(99, (previous?.quantity || 0) + item.quantity), key });
      });
      return Array.from(combined.values());
    } catch (storageError) {
      console.warn('Не удалось восстановить корзину.', storageError);
      return [];
    }
  }

  let items = readCart();

  function persist() {
    try {
      localStorage.setItem(storageKey, JSON.stringify(items.map(({ id, size, grind, quantity }) => ({ id, size, grind, quantity }))));
    } catch (storageError) {
      console.warn('Не удалось сохранить корзину.', storageError);
      notify('Корзина доступна в этой вкладке, но не сохранится после закрытия.');
    }
  }

  function resetFeedback() {
    error.hidden = true;
    error.textContent = '';
    status.hidden = true;
    status.textContent = '';
    [nameInput, phoneInput, consent].forEach(input => input.removeAttribute('aria-invalid'));
  }

  function showCart() {
    form.hidden = true;
    content.hidden = items.length === 0;
    empty.hidden = items.length > 0;
    resetFeedback();
  }

  function render(focusKey, focusAction) {
    const fragment = document.createDocumentFragment();
    let total = 0;
    let count = 0;
    items.forEach(item => {
      const product = getProduct(item);
      const node = template.content.firstElementChild.cloneNode(true);
      node.dataset.key = item.key;
      node.querySelector('[data-cart-name]').textContent = product.name;
      node.querySelector('[data-cart-variant]').textContent = [product.variant.label, item.grind].filter(Boolean).join(' · ');
      node.querySelector('[data-cart-price]').textContent = money.format(product.variant.price * item.quantity);
      node.querySelector('[data-cart-quantity]').textContent = item.quantity;
      const image = node.querySelector('[data-cart-image]');
      image.src = product.image || 'assets/images/product-placeholder.jpg';
      image.setAttribute('data-image-fallback', 'product');
      node.querySelectorAll('[data-cart-action]').forEach(button => {
        const action = button.dataset.cartAction;
        button.setAttribute('aria-label', (action === 'increase' ? 'Добавить: ' : action === 'decrease' ? 'Уменьшить количество: ' : 'Удалить: ') + product.name);
        if (action === 'increase') button.disabled = item.quantity >= 99;
      });
      fragment.append(node);
      total += product.variant.price * item.quantity;
      count += item.quantity;
    });
    list.replaceChildren(fragment);
    dialog.querySelector('[data-cart-total]').textContent = money.format(total);
    dialog.querySelector('[data-checkout-total]').textContent = money.format(total);
    countLabel.textContent = count;
    openButton.setAttribute('aria-label', 'Открыть корзину, товаров: ' + count);
    if (form.hidden) {
      content.hidden = items.length === 0;
      empty.hidden = items.length > 0;
    }
    if (focusKey) {
      const row = Array.from(list.children).find(node => node.dataset.key === focusKey);
      const control = row?.querySelector('[data-cart-action="' + focusAction + '"]');
      if (control && !control.disabled) control.focus();
      else closeButton.focus();
    }
  }

  function addProduct(card) {
    const id = card.dataset.productId;
    const size = card.querySelector('select[data-product-size]')?.value || 'default';
    const grind = card.querySelector('select[data-product-grind]')?.value || '';
    const item = { id, size, grind, quantity: 1 };
    item.key = makeKey(item);
    const existing = items.find(record => record.key === item.key);
    if (existing?.quantity >= 99) {
      notify('В корзине уже 99 единиц этого товара.');
      return;
    }
    if (existing) existing.quantity += 1;
    else items.push(item);
    persist();
    render();
    notify(catalog.get(id).name + ' — добавлено в корзину.');
  }

  openButton.disabled = false;
  cards.forEach(card => {
    const button = card.querySelector('[data-add-to-cart]');
    if (!button) return;
    button.disabled = false;
    button.addEventListener('click', () => addProduct(card));
  });

  openButton.addEventListener('click', () => {
    showCart();
    render();
    dialog.showModal();
    document.body.classList.add('is-cart-open');
  });
  dialog.addEventListener('close', () => document.body.classList.remove('is-cart-open'));
  closeButton.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target !== dialog) return;
    const rect = dialog.getBoundingClientRect();
    if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
  });
  dialog.querySelector('[data-cart-shop]').addEventListener('click', () => {
    dialog.close();
    window.location.hash = 'menu';
  });
  list.addEventListener('click', event => {
    const button = event.target.closest('[data-cart-action]');
    if (!button) return;
    const key = button.closest('[data-cart-item]').dataset.key;
    const item = items.find(record => record.key === key);
    if (!item) return;
    const action = button.dataset.cartAction;
    if (action === 'increase' && item.quantity < 99) item.quantity += 1;
    if (action === 'decrease') item.quantity -= 1;
    if (action === 'remove' || item.quantity < 1) items = items.filter(record => record.key !== key);
    persist();
    render(key, action);
  });
  checkoutButton.addEventListener('click', () => {
    if (!items.length) return;
    content.hidden = true;
    empty.hidden = true;
    form.hidden = false;
    resetFeedback();
    nameInput.focus();
  });
  dialog.querySelector('[data-checkout-back]').addEventListener('click', () => {
    showCart();
    checkoutButton.focus();
  });
  form.addEventListener('input', resetFeedback);
  form.addEventListener('change', resetFeedback);
  form.addEventListener('submit', event => {
    event.preventDefault();
    resetFeedback();
    const name = nameInput.value.trim();
    const phone = phoneInput.value.replace(/\D/g, '');
    let invalid;
    if (!name) {
      error.textContent = 'Укажите ваше имя.';
      invalid = nameInput;
    } else if (phone.length < 10 || phone.length > 15) {
      error.textContent = 'Укажите телефон с кодом города или оператора.';
      invalid = phoneInput;
    } else if (!consent.checked) {
      error.textContent = 'Для оформления необходимо согласие на обработку данных.';
      invalid = consent;
    }
    if (invalid) {
      error.hidden = false;
      invalid.setAttribute('aria-invalid', 'true');
      invalid.focus();
      return;
    }
    status.textContent = 'Заказ не отправлен: приём онлайн-заказов пока недоступен. Вы можете купить выбранные товары в кофейне — Екатеринбург, ул. Кофейная, 12.';
    status.hidden = false;
  });
  render();
}

