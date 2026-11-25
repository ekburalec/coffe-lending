import { initNotify } from './modules/notify.js';
import { initAdminReorder } from './modules/admin-reorder.js';

import { initAdminCharacterCount } from './modules/admin-character-count.js';

document.addEventListener('DOMContentLoaded', () => {
    initNotify();
    initAdminReorder();
    initAdminCharacterCount();
});
