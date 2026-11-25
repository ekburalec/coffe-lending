<?php
declare(strict_types=1);

function reorderRecordsController(): void
{
    startAdminSession();
    if (!isset($_SESSION['admin'])) { productOrderResponse(401, ['message' => 'Сессия истекла. Войдите в админку снова.']); return; }
    if (!validCsrf()) { productOrderResponse(403, ['message' => 'Форма устарела. Обновите страницу.']); return; }
    $kind = postText('section');
    $json = postText('ids');
    if (!in_array($kind, ['navigation', 'faq'], true) || strlen($json) > 65536) { productOrderResponse(422, ['message' => 'Некорректный раздел или список.']); return; }
    try { $ids = json_decode($json, true, 512, JSON_THROW_ON_ERROR); }
    catch (JsonException) { productOrderResponse(422, ['message' => 'Некорректный список.']); return; }
    if (!is_array($ids) || !array_is_list($ids) || count($ids) > 1000) { productOrderResponse(422, ['message' => 'Некорректный список.']); return; }
    foreach ($ids as $id) if (!is_string($id) || !preg_match('/^[a-z0-9-]{1,80}$/D', $id)) { productOrderResponse(422, ['message' => 'Некорректный идентификатор.']); return; }
    $current = array_column(listRecords($kind), 'id');
    $receivedSet = $ids;
    $currentSet = $current;
    sort($receivedSet); sort($currentSet);
    if ($receivedSet !== $currentSet || count(array_unique($ids)) !== count($ids)) { productOrderResponse(422, ['message' => 'Состав списка изменился. Обновите страницу и повторите.']); return; }
    if (!hash_equals(productOrderVersion($current), postText('version'))) { productOrderResponse(409, ['message' => 'Порядок уже изменён в другой вкладке. Обновите страницу и повторите.']); return; }
    try {
        saveRecordOrder($kind, $ids);
        productOrderResponse(200, ['version' => productOrderVersion($ids)]);
    } catch (Throwable $error) {
        error_log((string) $error);
        productOrderResponse(500, ['message' => 'Не удалось сохранить порядок. Повторите позже.']);
    }
}
