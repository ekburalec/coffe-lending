export function initHeaderScroll() {
  const header = document.querySelector('[data-site-header]');
  if (!header) return;

  document.body.classList.add('is-header-fixed');

  function updateHeader() {
    header.classList.toggle('is-scrolled', window.scrollY > 24);
  }

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });
}
