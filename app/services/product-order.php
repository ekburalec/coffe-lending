<?php
declare(strict_types=1);

function productOrderVersion(array $ids): string
{
    return hash('sha256', json_encode(array_values($ids), JSON_THROW_ON_ERROR));
}

function productOrderGroups(array $rows): array
{
    $groups = [];
    foreach (listRecords('categories') as $category) {
        $category['rows'] = array_values(array_filter($rows, fn (array $row): bool => $row['category'] === $category['id']));
        $category['version'] = productOrderVersion(array_column($category['rows'], 'id'));
        $groups[] = $category;
    }
    return $groups;
}

function nextProductOrder(string $category): int
{
    $positions = array_column(productsInCategory($category), 'sort_order');
    return $positions ? max($positions) + 1 : 0;
}
