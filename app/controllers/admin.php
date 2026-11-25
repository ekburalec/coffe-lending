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
        } elseif (postText('action') === 'toggle_visibility') {
            if (!$record || !in_array($key, ['reviews', 'faq'], true)) $errors['_form'] = 'Эту запись нельзя скрыть.';
            else {
                $record['visible'] = $record['visible'] ? 0 : 1;
                saveRecord($key, $id, $record);
                redirect('/admin/edit?section=' . $key . '&saved=1#record-' . $id);
            }
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
                    if ($key === 'products' && (!$record || $record['category'] !== $data['category'])) $data['sort_order'] = nextProductOrder($data['category']);
                    $id = $id ?: 'item-' . bin2hex(random_bytes(8));
                    saveRecord($key, $id, $data);
                }
                if ($key === 'rating' && postText('return_to') === 'reviews') redirect('/admin/edit?section=reviews&saved=1#rating');
                if (in_array($key, ['reviews', 'faq'], true) && postText('return_to') === 'list') redirect('/admin/edit?section=' . $key . '&saved=1#record-' . $id);
                redirect('/admin/edit?section=' . $key . ($singleton ? '' : '&id=' . $id) . '&saved=1');
            }
            $record = $data;
        }
        if ($errors && http_response_code() !== 403) http_response_code(422);
    }
    $defaults = [];
    if (!$record && in_array($key, ['reviews', 'faq'], true)) {
        $positions = array_column(listRecords($key), 'sort_order');
        $defaults['sort_order'] = $positions ? max($positions) + 1 : 0;
    }
    $fields = adminFields($section['fields'], $record ?? $defaults, $errors);
    $ratingFields = $key === 'reviews' ? adminFields($sections['rating']['fields'], getSettings('rating'), [], 'rating') : [];
    $rows = [];
    if (!$singleton) foreach (listRecords($key) as $row) {
        $row['caption'] = $row['name'] ?? $row['question'] ?? $row['label'] ?? $row['text'] ?? $row['date'] ?? $row['id'];
        $row['preview'] = $key === 'faq' ? faqAnswer($row, getSettings('delivery'), openingHours()) : ($row['text'] ?? '');
        $row['preview'] = mb_strimwidth($row['preview'], 0, 340, '…', 'UTF-8');
        $rows[] = $row;
    }
    $productGroups = $key === 'products' && !$editing ? productOrderGroups($rows) : [];
    $recordCaption = $record['name'] ?? $record['question'] ?? $record['label'] ?? $record['date'] ?? $id;
    adminPage($section['title'], 'editor', compact('section', 'key', 'id', 'fields', 'rows', 'editing', 'errors', 'notice', 'singleton', 'recordCaption', 'productGroups', 'ratingFields'));
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
