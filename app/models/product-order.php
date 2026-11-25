<?php
declare(strict_types=1);

function productsInCategory(string $category): array
{
    return array_values(array_filter(listRecords('products'), fn (array $product): bool => $product['category'] === $category));
}

function saveProductOrder(array $ids): void
{
    $pdo = database();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare("UPDATE records SET sort_order = ? WHERE kind = 'products' AND id = ?");
        foreach ($ids as $position => $id) $query->execute([$position, $id]);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}
