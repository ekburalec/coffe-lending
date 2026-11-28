<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/core/bootstrap.php';
$applicationLock = applicationLock(true);
echo 'Отправлено: ' . processOrderMail() . PHP_EOL;
