<?php
declare(strict_types=1);

function adminEditorController(): void
{
    requireAdmin();
    $sections = adminSections();
    $key = is_string($_GET['section'] ?? null) ? $_GET['section'] : '';
    $section = $sections[$key] ?? null;
    if (!$section) {
        http_response_code(404);
        render('error', ['title' => 'Раздел не найден', 'message' => 'Выберите раздел в админке.']);
        return;
    }
    $singleton = $section['singleton'] ?? false;
    $id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
    $record = $singleton ? getSettings($key) : ($id ? findRecord($key, $id) : null);
    if ($id && !$record) {
        http_response_code(404);
        render('error', ['title' => 'Запись не найдена', 'message' => 'Вернитесь к списку.']);
        return;
    }
    $editing = $singleton || $id !== '' || isset($_GET['new']);
    if (($section['fixed'] ?? false) && !$singleton && isset($_GET['new'])) redirect('/admin/edit?section=' . $key);
    $errors = [];
    $notice = isset($_GET['saved']) ? 'Изменения сохранены.' : '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrf()) {
            http_response_code(403);
            $errors['_form'] = 'Форма устарела. Обновите страницу.';
        } elseif (postText('action') === 'delete') {
            $errors = deleteAdminRecord($key, $id, $section);
            if (!$errors) redirect('/admin/edit?section=' . $key . '&saved=1');
        } else {
            [$data, $errors] = validateAdminFields($section['fields'], $record ?? []);
            $errors = array_merge($errors, validateAdminRecord($key, $data));
            if (!$errors) {
                foreach ($section['fields'] as $name => $field) {
                    if (($field['type'] ?? '') !== 'image') continue;
                    $file = $_FILES[$name . '_upload'] ?? ['error' => UPLOAD_ERR_NO_FILE];
                    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        try { $data[$name] = uploadedImage($file, $key === 'seo'); }
                        catch (InvalidArgumentException $error) { $errors[$name] = $error->getMessage(); }
                        catch (RuntimeException $error) { error_log((string) $error); $errors[$name] = 'Загрузка недоступна. Проверьте GD и права каталога на сервере.'; }
                    }
                }
            }
            if (!$errors) {
                if ($singleton) saveSettings($key, $data);
                else {
                    $id = $id ?: 'item-' . bin2hex(random_bytes(8));
                    saveRecord($key, $id, $data);
                }
                redirect('/admin/edit?section=' . $key . ($singleton ? '' : '&id=' . $id) . '&saved=1');
            }
            $record = $data;
        }
        if ($errors && http_response_code() !== 403) http_response_code(422);
    }
    $fields = [];
    foreach ($section['fields'] as $name => $field) {
        $field['name'] = $name;
        $field['type'] = $field['type'] ?? 'text';
        $field['value'] = $record[$name] ?? $field['default'] ?? '';
        $field['error'] = $errors[$name] ?? '';
        $fields[] = $field;
    }
    $rows = [];
    if (!$singleton) foreach (listRecords($key) as $row) {
        $row['caption'] = $row['name'] ?? $row['question'] ?? $row['label'] ?? $row['text'] ?? $row['date'] ?? $row['id'];
        $rows[] = $row;
    }
    adminPage($section['title'], 'editor', compact('section', 'key', 'id', 'fields', 'rows', 'editing', 'errors', 'notice', 'singleton'));
}

function deleteAdminRecord(string $kind, string $id, array $section): array
{
    if (!$id || ($section['fixed'] ?? false) || ($section['singleton'] ?? false)) return ['_form' => 'Эту запись нельзя удалить.'];
    if (postText('confirm_delete') !== '1') return ['_form' => 'Для удаления отметьте подтверждение. Можно просто скрыть запись.'];
    if ($kind === 'categories') foreach (listRecords('products') as $product) {
        if ($product['category'] === $id) return ['_form' => 'Сначала перенесите или удалите товары этой категории.'];
    }
    deleteRecord($kind, $id);
    return [];
}
