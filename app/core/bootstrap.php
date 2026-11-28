<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__, 2));
$appConfig = require APP_ROOT . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);
ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/database.php';
require_once APP_ROOT . '/app/models/content.php';

set_exception_handler(function (Throwable $error): void {
    error_log((string) $error);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Ошибка приложения. Проверьте журнал PHP.\n");
        exit(1);
    }
    http_response_code(500);
    render('error', ['title' => 'Временная ошибка', 'message' => 'Не удалось выполнить действие. Попробуйте позже.']);
});

require_once APP_ROOT . '/app/models/auth.php';
require_once APP_ROOT . '/app/services/passwords.php';
require_once APP_ROOT . '/app/services/pwned-passwords.php';
require_once APP_ROOT . '/app/services/auth.php';
require_once APP_ROOT . '/app/controllers/auth.php';

require_once APP_ROOT . '/app/services/images.php';
require_once APP_ROOT . '/app/services/social-links.php';
require_once APP_ROOT . '/app/validators/admin.php';
require_once APP_ROOT . '/app/controllers/admin.php';

require_once APP_ROOT . '/app/services/opening-hours.php';

require_once APP_ROOT . '/app/services/landing.php';
require_once APP_ROOT . '/app/controllers/landing.php';

require_once APP_ROOT . '/app/services/seo.php';

require_once APP_ROOT . '/app/services/backups.php';
require_once APP_ROOT . '/app/controllers/backups.php';

require_once APP_ROOT . '/app/controllers/discovery.php';

require_once APP_ROOT . '/app/helpers/admin-navigation.php';

require_once APP_ROOT . '/app/models/product-order.php';
require_once APP_ROOT . '/app/services/product-order.php';
require_once APP_ROOT . '/app/controllers/product-order.php';
require_once APP_ROOT . '/app/controllers/record-order.php';

require_once APP_ROOT . '/app/helpers/admin-fields.php';

require_once APP_ROOT . '/app/models/visual-editor.php';
require_once APP_ROOT . '/app/services/visual-editor.php';
require_once APP_ROOT . '/app/controllers/visual-editor.php';

require_once APP_ROOT . '/app/models/orders.php';
require_once APP_ROOT . '/app/models/order-backups.php';
require_once APP_ROOT . '/app/models/order-mail.php';
require_once APP_ROOT . '/app/models/order-retention.php';
require_once APP_ROOT . '/app/services/orders.php';
require_once APP_ROOT . '/app/validators/orders.php';

require_once APP_ROOT . '/app/services/order-mail.php';
require_once APP_ROOT . '/app/services/order-cleanup.php';
require_once APP_ROOT . '/app/controllers/orders.php';
require_once APP_ROOT . '/app/controllers/admin-orders.php';

require_once APP_ROOT . '/app/controllers/order-mail.php';
