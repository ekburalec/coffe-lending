<?php
declare(strict_types=1);

function uploadedMediaController(string $filename): void
{
    $path = appConfig('upload_dir') . '/' . $filename;
    if (!is_file($path)) {
        http_response_code(404);
        return;
    }
    header('Content-Type: ' . (str_ends_with($filename, '.jpg') ? 'image/jpeg' : 'image/webp'));
    header('Cache-Control: public, no-cache');
    readfile($path);
}

function discoveryController(): void
{
    $path = requestPath();
    $deploy = require APP_ROOT . '/config/deploy.php';
    $base = siteUrl();
    header('Cache-Control: no-cache');
    if ($path === '/robots.txt') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo $deploy['noindex'] ? "User-agent: *\nDisallow: /\n" : "User-agent: *\nDisallow: /admin\nSitemap: {$base}/sitemap.xml\n";
        return;
    }
    if ($path === '/sitemap.xml') {
        if ($deploy['noindex']) { http_response_code(404); return; }
        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>' . e($base . '/') . '</loc></url></urlset>';
        return;
    }
    header('Content-Type: text/plain; charset=UTF-8');
    $data = landingData();
    $seo = getSettings('seo');
    echo '# ' . $data['contacts']['name'] . "\n\n";
    if ($deploy['noindex']) echo "Индексация отключена; см. robots.txt и meta robots.\n\n";
    echo $seo['description'] . "\n\n";
    echo '- [Лендинг](' . ($base ?: '.') . "/)\n";
    if ($path !== '/llms-full.txt') { echo '- [Полное содержание](' . ($base ?: '.') . "/llms-full.txt)\n"; return; }
    echo "\n## Контакты\n\n" . $data['contacts']['city'] . ', ' . $data['contacts']['street'] . "\n" . $data['hours']['weekly'] . "\n" . $data['hours']['today'] . "\n\n";
    foreach ($data['categories'] as $category) {
        echo '## ' . $category['name'] . "\n\n";
        foreach ($category['products'] as $product) echo '- ' . $product['name'] . ', ' . $product['portion'] . ': ' . rubles((int) $product['price']) . '. ' . $product['description'] . (!empty($product['available']) ? '' : ' Нет в наличии.') . "\n";
        echo "\n";
    }
    echo "## Получение\n\n" . deliveryDescription($data['delivery']) . "\n\n";
    foreach ($data['faq'] as $row) echo '### ' . $row['question'] . "\n\n" . $row['answer'] . "\n\n";
    echo "На сайте только просмотр состава заказа, без отправки и оплаты.\n";
}

function manifestController(): void
{
    $manifest = json_decode(file_get_contents(APP_ROOT . '/manifest.webmanifest'), true, 512, JSON_THROW_ON_ERROR);
    $manifest['name'] = getSettings('contacts')['name'];
    $manifest['short_name'] = $manifest['name'];
    $manifest['description'] = getSettings('seo')['description'];
    header('Content-Type: application/manifest+json; charset=UTF-8');
    header('Cache-Control: no-cache');
    echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
