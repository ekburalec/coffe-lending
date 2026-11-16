import { initNavigation } from './modules/navigation.js';
import { initHeaderScroll } from './modules/header-scroll.js';
import { initMenuFilters } from './modules/menu-filters.js';
import { initNotifications } from './modules/notify.js';
import { initNewsletter } from './modules/newsletter.js';
import { initCart } from './modules/cart.js';
import { initImageFallbacks } from './modules/image-fallbacks.js';

document.addEventListener('DOMContentLoaded', () => {
  initHeaderScroll();
  initNavigation();
  initMenuFilters();
  initNotifications();
  initNewsletter();
  initCart();
  initImageFallbacks();
});

