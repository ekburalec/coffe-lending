<?php
declare(strict_types=1);

function rubles(int $amount): string
{
    return number_format($amount, 0, '.', ' ') . ' ₽';
}

function deliveryDescription(array $delivery): string
{
    $parts = [];
    if (!empty($delivery['pickup_enabled'])) $parts[] = 'Самовывоз без доплаты';
    if (!empty($delivery['delivery_enabled'])) {
        $parts[] = $delivery['zone'] . '. Доставка ' . rubles((int) $delivery['fee'])
            . ', бесплатно от ' . rubles((int) $delivery['threshold']);
    }
    return implode('. ', $parts) . '.';
}

function activeAnnouncements(?DateTimeImmutable $now = null): array
{
    $now ??= new DateTimeImmutable('now', new DateTimeZone(appConfig('timezone')));
    $minute = $now->setTimezone(new DateTimeZone(appConfig('timezone')))->format('Y-m-d\TH:i');
    return array_values(array_filter(listRecords('announcements', true), fn (array $row): bool =>
        (empty($row['starts']) || $row['starts'] <= $minute) && (empty($row['ends']) || $row['ends'] >= $minute)));
}

function landingFaq(array $delivery, array $hours): array
{
    $faq = listRecords('faq', true);
    foreach ($faq as &$row) $row['answer'] = faqAnswer($row, $delivery, $hours);
    unset($row);
    return $faq;
}

function faqAnswer(array $row, array $delivery, array $hours): string
{
    $answer = match ($row['mode'] ?? 'manual') {
        'hours' => $hours['weekly'] . '. ' . $hours['today'] . '.',
        'delivery' => deliveryDescription($delivery),
        'timing' => (!empty($delivery['pickup_enabled']) ? 'Самовывоз: ' . $delivery['pickup_time'] . '. ' : '')
            . (!empty($delivery['delivery_enabled']) ? 'Доставка: ' . $delivery['delivery_time'] . '. ' : '')
            . 'В рабочие часы. Время ориентировочное. Состав на сайте не оформляет заказ.',
        default => $row['answer'],
    };
    if (($row['mode'] ?? 'manual') !== 'manual' && !empty($row['prefix'])) $answer = $row['prefix'] . ' ' . $answer;
    return $answer;
}

function landingData(): array
{
    $contacts = getSettings('contacts');
    $socialValues = socialSettings($contacts);
    $socialLinks = [];
    foreach (socialNetworkDefaults() as $key => $network) {
        $url = $socialValues[$key . '_url'];
        $socialLinks[] = ['name' => $socialValues[$key . '_name'], 'label' => $socialValues[$key . '_label'], 'url' => $url ?: '#contacts', 'placeholder' => $url === ''];
    }
    $missingSocial = count(array_filter($socialLinks, fn (array $social): bool => $social['placeholder']));
    $socialNotice = $missingSocial === count($socialLinks) ? 'Ссылки на сообщества пока не добавлены.' : ($missingSocial ? 'Не все ссылки на сообщества добавлены.' : '');
    $delivery = getSettings('delivery');
    $hours = openingHours();
    $products = [];
    $categories = [];
    foreach (listRecords('categories', true) as $category) {
        $category['products'] = array_values(array_filter(listRecords('products', true), fn (array $product): bool => $product['category'] === $category['id']));
        foreach ($category['products'] as $product) $products[] = $product;
        $categories[] = $category;
    }
    $reviews = listRecords('reviews', true);
    $faq = landingFaq($delivery, $hours);
    $targets = ['#welcome', '#menu', '#occasions', '#space', '#delivery', '#reviews', '#contacts'];
    if ($faq) $targets[] = '#faq';
    foreach ($categories as $category) $targets[] = '#' . $category['id'];
    $navigation = array_values(array_filter(listRecords('navigation', true), fn (array $row): bool => in_array($row['target'], $targets, true)));
    $copy = array_column(listRecords('content'), 'text', 'id');
    $images = array_column(listRecords('images'), null, 'id');
    $occasionPanels = [];
    foreach ([['coffee', 21, '#coffee'], ['tea', 24, '#tea'], ['meeting', 27, '#space']] as [$kind, $textIndex, $target]) {
        $choices = array_values(array_filter($products, fn (array $product): bool => $product['category'] === $kind && !empty($product['available'])));
        $illustrated = array_values(array_filter($choices, fn (array $product): bool => !str_contains($product['image'], '/placeholders/')));
        $product = $illustrated[0] ?? $choices[0] ?? null;
        $occasionPanels[] = ['id' => $kind, 'title_key' => 'copy-' . $textIndex, 'body_key' => 'copy-' . ($textIndex + 1), 'link_key' => 'copy-' . ($textIndex + 2), 'target' => in_array($target, ['#coffee', '#tea'], true) && !in_array(substr($target, 1), array_column($categories, 'id'), true) ? '#menu' : $target, 'product' => $product, 'image' => $product ?? $images['image-1']];
    }
    $announcements = array_column(activeAnnouncements(), 'text');
    array_unshift($announcements, $hours['special'] ? $hours['today'] : $hours['weekly']);
    $announcements[] = deliveryDescription($delivery);
    $rating = getSettings('rating');
    return compact('contacts', 'delivery', 'hours', 'products', 'categories', 'navigation', 'copy', 'images', 'faq', 'announcements', 'reviews', 'rating', 'occasionPanels', 'socialLinks', 'socialNotice');
}
