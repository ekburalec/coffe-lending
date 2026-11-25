<?php
declare(strict_types=1);

function reorderProductsController(): void
{
    startAdminSession();
    if (!isset($_SESSION['admin'])) { productOrderResponse(401, ['message' => 'Сессия истекла. Войдите в админку снова.']); return; }
    if (!validCsrf()) { productOrderResponse(403, ['message' => 'Форма устарела. Обновите страницу.']); return; }
    $category = postText('category');
    $json = postText('ids');
    if (strlen($json) > 65536 || !findRecord('categories', $category)) { productOrderResponse(422, ['message' => 'Некорректная категория или список товаров.']); return; }
    try { $ids = json_decode($json, true, 512, JSON_THROW_ON_ERROR); }
    catch (JsonException) { productOrderResponse(422, ['message' => 'Некорректный список товаров.']); return; }
    if (!is_array($ids) || !array_is_list($ids) || count($ids) > 1000) { productOrderResponse(422, ['message' => 'Некорректный список товаров.']); return; }
    foreach ($ids as $id) if (!is_string($id) || !preg_match('/^[a-z0-9-]{1,80}$/D', $id)) { productOrderResponse(422, ['message' => 'Некорректный идентификатор товара.']); return; }
    $current = array_column(productsInCategory($category), 'id');
    $receivedSet = $ids;
    $currentSet = $current;
    sort($receivedSet); sort($currentSet);
    if ($receivedSet !== $currentSet || count(array_unique($ids)) !== count($ids)) { productOrderResponse(422, ['message' => 'Состав категории изменился. Обновите страницу и повторите.']); return; }
    if (!hash_equals(productOrderVersion($current), postText('version'))) { productOrderResponse(409, ['message' => 'Порядок уже изменён в другой вкладке. Обновите страницу и повторите.']); return; }
    try {
        saveProductOrder($ids);
        productOrderResponse(200, ['version' => productOrderVersion($ids)]);
    } catch (Throwable $error) {
        error_log((string) $error);
        productOrderResponse(500, ['message' => 'Не удалось сохранить порядок. Повторите позже.']);
    }
}

function productOrderResponse(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}
