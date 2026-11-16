export function initMenuFilters() {
  const filters = document.querySelector('[data-menu-filters]');
  const items = Array.from(document.querySelectorAll('[data-menu-item]'));
  const status = document.querySelector('[data-menu-status]');
  if (!filters || !items.length) return;

  const buttons = Array.from(filters.querySelectorAll('[data-menu-filter]'));

  function selectCategory(category, announce) {
    let count = 0;
    items.forEach((item) => {
      const visible = category === 'all' || (category === 'featured'
        ? item.hasAttribute('data-featured')
        : item.dataset.category === category);
      item.hidden = !visible;
      if (visible) count += 1;
    });
    buttons.forEach((button) => {
      const selected = button.dataset.menuFilter === category;
      button.setAttribute('aria-pressed', String(selected));
      button.classList.toggle('is-active', selected);
    });
    if (announce && status) status.textContent = 'Позиций в выбранной категории: ' + count;
  }

  filters.hidden = false;
  selectCategory('featured', false);
  filters.addEventListener('click', (event) => {
    const button = event.target.closest('[data-menu-filter]');
    if (button) selectCategory(button.dataset.menuFilter, true);
  });
}

