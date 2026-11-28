import { initAdminOrders } from './modules/admin-orders.js';
import { initNotify } from './modules/notify.js';
import { initAdminReorder } from './modules/admin-reorder.js';
import { initAdminPasswordControls } from './modules/admin-password-controls.js';
import { initAdminPasswordStrength } from './modules/admin-password-strength.js';

import { initAdminCharacterCount } from './modules/admin-character-count.js';

document.addEventListener('DOMContentLoaded', () => {
    initNotify();
    initAdminOrders();
    initAdminReorder();
    initAdminCharacterCount();
    initAdminPasswordControls();
    initAdminPasswordStrength();
});
