<?php
declare(strict_types=1);

function siteUrl(): string
{
    $deploy = require APP_ROOT . '/config/deploy.php';
    $url = rtrim($deploy['site_url'], '/');
    if ($url && (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true))) throw new RuntimeException('Invalid site_url');
    if (!$deploy['noindex'] && !$url) throw new RuntimeException('Public site requires site_url');
    return $url;
}

function absoluteAsset(string $path): string
{
    return siteUrl() ? siteUrl() . '/' . ltrim($path, '/') : $path;
}

function landingSeo(array $data): array
{
    $seo = getSettings('seo');
    $contacts = $data['contacts'];
    $days = ['1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '7' => 'Sunday'];
    $opening = [];
    foreach ($data['hours']['rows'] as $row) {
        if (!empty($row['closed'])) continue;
        $opening[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days[$row['id']], 'opens' => $row['opens'], 'closes' => $row['closes']];
    }
    $special = [];
    foreach (listRecords('exceptions', true) as $row) {
        $special[] = ['@type' => 'OpeningHoursSpecification', 'validFrom' => $row['date'], 'validThrough' => $row['date'], 'opens' => !empty($row['closed']) ? '00:00' : $row['opens'], 'closes' => !empty($row['closed']) ? '00:00' : $row['closes']];
    }
    $cafe = [
        '@type' => 'CafeOrCoffeeShop', 'name' => $contacts['name'], 'description' => !empty($seo['schema_description']) ? $seo['schema_description'] : $seo['description'],
        'url' => siteUrl() ? siteUrl() . '/' : './', 'image' => absoluteAsset($seo['image']),
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $contacts['city'], 'streetAddress' => $contacts['street'], 'addressCountry' => 'RU'],
        'openingHoursSpecification' => $opening, 'specialOpeningHoursSpecification' => $special,
    ];
    if ($contacts['phone'] !== '+7 (343) 000-00-00') $cafe['telephone'] = $contacts['phone'];
    if ($data['products']) {
        $prices = array_column($data['products'], 'price');
        $cafe['priceRange'] = rubles((int) min($prices)) . ' – ' . rubles((int) max($prices));
    }
    $menu = ['@type' => 'Menu', 'name' => 'Меню', 'hasMenuSection' => []];
    foreach ($data['categories'] as $category) {
        if (!$category['products']) continue;
        $section = ['@type' => 'MenuSection', 'name' => $category['name'], 'hasMenuItem' => []];
        foreach ($category['products'] as $product) $section['hasMenuItem'][] = [
            '@type' => 'MenuItem', 'name' => $product['name'], 'description' => $product['description'],
            'image' => absoluteAsset($product['image']), 'offers' => ['@type' => 'Offer', 'priceCurrency' => 'RUB', 'price' => $product['price'], 'availability' => !empty($product['available']) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'],
        ];
        $menu['hasMenuSection'][] = $section;
    }
    $cafe['hasMenu'] = $menu;
    $graph = [$cafe];
    if ($data['faq']) $graph[] = ['@type' => 'FAQPage', 'mainEntity' => array_map(fn (array $row): array => [
        '@type' => 'Question', 'name' => $row['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $row['answer']],
    ], $data['faq'])];
    $deploy = require APP_ROOT . '/config/deploy.php';
    $seo['image_alt'] = !empty($seo['image_alt']) ? $seo['image_alt'] : $seo['og_title'];
    $seo['noindex'] = $deploy['noindex'];
    $seo['canonical'] = siteUrl() ? siteUrl() . '/' : '';
    $seo['image_url'] = absoluteAsset($seo['image']);
    $seo['json_ld'] = json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    return $seo;
}
