<?php
declare(strict_types=1);

/**
 * Настройки сборки дистрибутива (bin/build-dist.php). Правила — docs/specs/deploy.md.
 */

return [
    // Полный адрес сайта без слэша в конце, например 'https://example.ru'.
    // Нужен для sitemap.xml, robots.txt, canonical, превью ссылки и llms.txt.
    'site_url' => '',

    // Предварительная сборка, закрытая от поисковиков: robots.txt «Disallow: /»,
    // <meta name="robots" content="noindex, nofollow"> на страницах, без sitemap.xml.
    // llms.txt и llms-full.txt создаются и в предварительной сборке.
    // Можно без site_url. Для боевого запуска — false.
    'noindex' => true,
];
