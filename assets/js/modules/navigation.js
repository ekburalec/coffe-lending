export function initNavigation() {
  const toggle = document.querySelector('[data-nav-toggle]');
  const navigation = document.querySelector('[data-navigation]');
  if (!toggle || !navigation) return;

  const mobile = window.matchMedia('(max-width: 1023px)');
  toggle.hidden = false;

  function setOpen(open, returnFocus = false) {
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
    toggle.classList.toggle('is-open', open);
    navigation.hidden = mobile.matches && !open;
    if (returnFocus) toggle.focus();
  }

  setOpen(false);
  toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
  mobile.addEventListener('change', () => setOpen(false));
  navigation.addEventListener('click', (event) => {
    if (event.target.closest('a') && mobile.matches) setOpen(false);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && mobile.matches && toggle.getAttribute('aria-expanded') === 'true') {
      setOpen(false, true);
    }
  });
  document.addEventListener('click', (event) => {
    if (mobile.matches && !navigation.contains(event.target) && !toggle.contains(event.target)) {
      setOpen(false);
    }
  });
}

