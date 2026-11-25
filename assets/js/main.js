import { initNotify } from './modules/notify.js';
import { initOrderPreview } from './modules/order-preview.js';
import { initSiteNavigation } from './modules/site-navigation.js';
import { initFixedHeader } from './modules/fixed-header.js';
import { initAnnouncementBar } from './modules/announcement-bar.js';
import { initOrderDrawer } from './modules/order-drawer.js';

import { initReviewsCarousel } from './modules/reviews-carousel.js';

document.addEventListener('DOMContentLoaded', () => {
    initNotify();
    initOrderPreview();
    initSiteNavigation();
    initAnnouncementBar();
    initFixedHeader();
    initOrderDrawer();
    initReviewsCarousel();
});
