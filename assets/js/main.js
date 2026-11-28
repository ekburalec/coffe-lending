import { initNotify } from './modules/notify.js';
import { initVisualEditor } from './modules/visual-editor.js';
import { initOrderPreview } from './modules/order-preview.js';
import { initSiteNavigation } from './modules/site-navigation.js';
import { initFixedHeader } from './modules/fixed-header.js';
import { initAnnouncementBar } from './modules/announcement-bar.js';
import { initOrderDrawer } from './modules/order-drawer.js';

import { initReviewsCarousel } from './modules/reviews-carousel.js';
import { initOccasions } from './modules/occasions.js';
import { initSocialLinks } from './modules/social-links.js';

document.addEventListener('DOMContentLoaded', () => {
    initNotify();
    initVisualEditor();
    initOrderPreview();
    initSiteNavigation();
    initAnnouncementBar();
    initFixedHeader();
    initOrderDrawer();
    initReviewsCarousel();
    initOccasions();
    initSocialLinks();
});
