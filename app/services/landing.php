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
    foreach ($faq as &$row) {
        $row['answer'] = match ($row['mode'] ?? 'manual') {
            'hours' => $hours['weekly'] . '. ' . $hours['today'] . '.',
            'delivery' => deliveryDescription($delivery),
            'timing' => (!empty($delivery['pickup_enabled']) ? 'Самовывоз: ' . $delivery['pickup_time'] . '. ' : '')
                . (!empty($delivery['delivery_enabled']) ? 'Доставка: ' . $delivery['delivery_time'] . '. ' : '')
                . 'В рабочие часы. Время ориентировочное. Состав на сайте не оформляет заказ.',
            default => $row['answer'],
        };
    }
    unset($row);
    return $faq;
}

function landingData(): array
{
    $contacts = getSettings('contacts');
    $delivery = getSettings('delivery');
    $hours = openingHours();
    $products = [];
    $categories = [];
    foreach (listRecords('categories', true) as $category) {
        $category['products'] = array_values(array_filter(listRecords('products', true), fn (array $product): bool => $product['category'] === $category['id']));
        foreach ($category['products'] as $product) $products[] = $product;
        $categories[] = $category;
    }
    $targets = ['#welcome', '#menu', '#occasions', '#space', '#delivery', '#faq', '#contacts'];
    foreach ($categories as $category) $targets[] = '#' . $category['id'];
    $navigation = array_values(array_filter(listRecords('navigation', true), fn (array $row): bool => in_array($row['target'], $targets, true)));
    $copy = array_column(listRecords('content'), 'text', 'id');
    $images = array_column(listRecords('images'), null, 'id');
    $faq = landingFaq($delivery, $hours);
    $announcements = array_column(activeAnnouncements(), 'text');
    array_unshift($announcements, $hours['special'] ? $hours['today'] : $hours['weekly']);
    $announcements[] = deliveryDescription($delivery);
    $reviews = listRecords('reviews', true);
    $rating = getSettings('rating');
    return compact('contacts', 'delivery', 'hours', 'products', 'categories', 'navigation', 'copy', 'images', 'faq', 'announcements', 'reviews', 'rating');
}
