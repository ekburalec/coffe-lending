import { initNotify } from './modules/notify.js';
import { initAdminReorder } from './modules/admin-reorder.js';

document.addEventListener('DOMContentLoaded', () => {
    initNotify();
    initAdminReorder();
});
