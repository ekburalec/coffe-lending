import { initNotify } from './modules/notify.js';
import { initAdminReorder } from './modules/admin-reorder.js';
import { initAdminPasswordControls } from './modules/admin-password-controls.js';

import { initAdminCharacterCount } from './modules/admin-character-count.js';

document.addEventListener('DOMContentLoaded', () => {
    initNotify();
    initAdminReorder();
    initAdminCharacterCount();
    initAdminPasswordControls();
});
