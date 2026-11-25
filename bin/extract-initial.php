<?php
declare(strict_types=1);

// Одноразовый импорт исходной редакции; не запускается при публикации или обновлении.
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/core/bootstrap.php';
$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML('<?xml encoding="UTF-8">' . file_get_contents(APP_ROOT . '/config/initial.html'));
$xpath = new DOMXPath($doc);
function initialText(DOMXPath $xpath, string $query, ?DOMNode $node = null): string
{
    return preg_replace('/\s+/u', ' ', trim($xpath->query($query, $node)->item(0)?->textContent ?? ''));
}
$seed = ['records' => [], 'settings' => []];
foreach ($xpath->query('//*[@class="menu__category"]') as $index => $category) {
    $id = $category->getAttribute('id');
    $seed['records']['categories'][] = ['id' => $id, 'name' => initialText($xpath, './/h3', $category), 'sort_order' => $index, 'visible' => 1];
    foreach ($xpath->query('.//*[@data-product]', $category) as $position => $card) {
        $image = $xpath->query('.//img', $card)->item(0);
        $price = $xpath->query('.//*[@data-unit-price]', $card)->item(0);
        $seed['records']['products'][] = [
            'id' => $card->getAttribute('data-product'), 'category' => $id,
            'name' => initialText($xpath, './/*[@data-product-name]', $card),
            'description' => initialText($xpath, './/*[@class="product-card__description"]', $card),
            'portion' => initialText($xpath, './/*[@class="product-card__portion"]', $card),
            'ingredients' => initialText($xpath, './/details/p', $card), 'price' => (int) $price->getAttribute('value'),
            'image' => $image->getAttribute('src'), 'alt' => $image->getAttribute('alt'),
            'sort_order' => $position, 'visible' => 1, 'available' => 1,
        ];
    }
}
foreach ($xpath->query('//nav[@id="site-navigation"]//a') as $index => $link) {
    $seed['records']['navigation'][] = ['id' => 'nav-' . $index, 'name' => trim($link->textContent), 'target' => $link->getAttribute('href'), 'sort_order' => $index, 'visible' => 1];
}
foreach ($xpath->query('//*[@id="faq"]//details') as $index => $faq) {
    $seed['records']['faq'][] = ['id' => 'faq-' . $index, 'question' => initialText($xpath, './/summary', $faq), 'answer' => initialText($xpath, './/p', $faq), 'mode' => [0 => 'hours', 3 => 'delivery', 4 => 'timing'][$index] ?? 'manual', 'sort_order' => $index, 'visible' => 1];
}
// Тексты в существующих секциях без карточек, FAQ и вычисляемых условий.
$query = '//main//section//*[self::h1 or self::h2 or self::h3 or self::h4 or self::p or self::a][not(ancestor::*[@data-product])][not(ancestor::*[@id="faq"]//details)][not(ancestor::*[@class="delivery__panel"])][not(ancestor::noscript)][not(*)]';
// XPath 1.0: отдельная проверка предков нужна для FAQ.
$index = 0;
foreach ($xpath->query($query) as $node) {
    if ($xpath->query('ancestor::details', $node)->length || trim($node->textContent) === '') continue;
    $id = 'copy-' . $index++;
    $section = $xpath->query('ancestor::section', $node)->item(0)->getAttribute('id');
    $seed['records']['content'][] = ['id' => $id, 'label' => $section . ': ' . initialText($xpath, '.', $node), 'text' => initialText($xpath, '.', $node), 'visible' => 1];
    $node->setAttribute('data-content-key', $id);
}
foreach ($xpath->query('//main//section//img[not(ancestor::*[@data-product])]') as $index => $image) {
    $id = 'image-' . $index;
    $seed['records']['images'][] = ['id' => $id, 'name' => $image->getAttribute('alt'), 'image' => $image->getAttribute('src'), 'alt' => $image->getAttribute('alt'), 'visible' => 1];
    $image->setAttribute('data-image-key', $id);
}
$seed['settings'] = [
    'contacts' => ['name' => 'Вкусный повод', 'city' => 'Екатеринбург', 'street' => 'ул. Кофейная, 12', 'phone' => '+7 (343) 000-00-00', 'map_url' => ''],
    'delivery' => ['pickup_enabled' => 1, 'delivery_enabled' => 1, 'fee' => 150, 'threshold' => 1000, 'zone' => 'В радиусе 3 км от кофейни', 'pickup_time' => '15–20 минут', 'delivery_time' => '40–60 минут'],
    'seo' => ['title' => initialText($xpath, '//title'), 'description' => $xpath->query('//meta[@name="description"]')->item(0)->getAttribute('content'), 'og_title' => $xpath->query('//meta[@property="og:title"]')->item(0)->getAttribute('content'), 'og_description' => $xpath->query('//meta[@property="og:description"]')->item(0)->getAttribute('content'), 'image' => 'assets/img/og-image.jpg'],
    'rating' => ['value' => '', 'count' => 0, 'source' => ''],
];
foreach (range(1, 7) as $day) $seed['records']['hours'][] = ['id' => (string) $day, 'name' => ['','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота','Воскресенье'][$day], 'opens' => '08:00', 'closes' => '22:00', 'closed' => 0, 'visible' => 1, 'sort_order' => $day];
file_put_contents(APP_ROOT . '/config/seed.json', json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
// Аннотированный HTML понадобится этапу серверного вывода, не является публичным.
if (!is_dir(APP_ROOT . '/var')) mkdir(APP_ROOT . '/var', 0700, true);
file_put_contents(APP_ROOT . '/var/annotated.html', $doc->saveHTML());
echo 'Импортировано товаров: ' . count($seed['records']['products']) . "\n";
