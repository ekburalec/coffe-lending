<?php
declare(strict_types=1);

function visualEditorController(): void
{
    requireAdmin();
    redirect('/');
}

function visualSessionController(): void
{
    startAdminSession();
    if (!isset($_SESSION['admin'])) { productOrderResponse(401, ['message' => 'Сессия истекла. Войдите в админку в другой вкладке и повторите сохранение.']); return; }
    productOrderResponse(200, ['csrf' => $_SESSION['csrf']]);
}

function visualPreviewController(): void
{
    requireAdmin();
    redirect('/');
}

function saveVisualEditorController(): void
{
    startAdminSession();
    if (!isset($_SESSION['admin'])) { productOrderResponse(401, ['message' => 'Сессия истекла. Правки остались здесь; войдите в админку в другой вкладке.']); return; }
    if (!validCsrf()) { productOrderResponse(403, ['message' => 'Форма устарела. Скопируйте правки перед обновлением редактора.']); return; }
    $created = [];
    try {
        $json = postText('changes');
        if (strlen($json) > 500000) throw new InvalidArgumentException('Слишком большой набор изменений.');
        $input = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($input)) throw new InvalidArgumentException('Некорректные изменения.');
        $changes = prepareVisualChanges($input);
        $changes = attachVisualImages($changes, $created);
        saveVisualRecords($changes);
    } catch (DomainException $error) {
        removeVisualUploads($created);
        productOrderResponse(409, ['message' => $error->getMessage()]); return;
    } catch (InvalidArgumentException | JsonException $error) {
        removeVisualUploads($created);
        productOrderResponse(422, ['message' => $error instanceof JsonException ? 'Некорректные изменения.' : $error->getMessage()]); return;
    } catch (Throwable $error) {
        removeVisualUploads($created);
        error_log((string) $error);
        productOrderResponse(500, ['message' => 'Изменения не сохранены. Проверьте подключение и повторите.']); return;
    }
    productOrderResponse(200, ['message' => 'Изменения опубликованы.', 'state' => visualEditorState()]);
}
