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
    if ($key === 'content' && $id !== '' && !$record) $record = visualContentRecords()[$id] ?? null;
    if ($key === 'social') $record = socialSettings();
    if ($id && !$record) {
        http_response_code(404);
        render('error', ['title' => 'Запись не найдена', 'message' => 'Вернитесь к списку.']);
        return;
    }
    if ($key === 'seo') {
        $record['schema_description'] ??= $record['description'];
        $record['image_alt'] ??= $record['og_title'];
    }
    $part = $key === 'seo' ? postText('section_part') : '';
    $definitions = $section['fields'];
    if (in_array($part, ['texts', 'preview'], true)) {
        $definitions = array_filter($definitions, fn (string $name): bool => $part === 'preview' ? in_array($name, ['image', 'image_alt'], true) : !in_array($name, ['image', 'image_alt'], true), ARRAY_FILTER_USE_KEY);
    }
    $editing = $singleton || $id !== '' || isset($_GET['new']);
    if (($section['fixed'] ?? false) && !$singleton && isset($_GET['new'])) redirect('/admin/edit?section=' . $key);
    $errors = [];
    $notice = isset($_GET['saved']) ? 'Изменения сохранены.' : '';
    $formattedContent = $key === 'content' && ($record['format'] ?? '') === 'inline';
    $recordVersion = $record && in_array($key, ['content', 'images'], true) ? visualRecordVersion($record) : '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrf()) {
            http_response_code(403);
            $errors['_form'] = 'Форма устарела. Обновите страницу.';
        } elseif ($recordVersion !== '' && isset($_POST['record_version']) && !hash_equals($recordVersion, postText('record_version'))) {
            http_response_code(409);
            $errors['_form'] = 'Текст или фото уже изменены в другой вкладке. Обновите страницу перед сохранением.';
        } elseif ($formattedContent) {
            $errors['_form'] = 'Этот текст содержит выделение. Откройте визуальный редактор, чтобы сохранить оформление.';
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
            [$data, $errors] = validateAdminFields($definitions, $record ?? []);
            if ($key === 'content') $data['format'] = 'text';
            $errors = array_merge($errors, validateAdminRecord($key, $data));
            if (!$errors) {
                foreach ($definitions as $name => $field) {
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
                if ($key === 'seo' && $part !== '') redirect('/admin/edit?section=seo&saved=1#seo-' . ($part === 'preview' ? 'preview' : 'texts'));
                if ($key === 'rating' && postText('return_to') === 'reviews') redirect('/admin/edit?section=reviews&saved=1#rating');
                if (in_array($key, ['reviews', 'faq'], true) && postText('return_to') === 'list') redirect('/admin/edit?section=' . $key . '&saved=1#record-' . $id);
                redirect('/admin/edit?section=' . $key . ($singleton ? '' : '&id=' . $id) . '&saved=1');
            }
            $record = $data;
        }
        if ($errors && !in_array(http_response_code(), [403, 409], true)) http_response_code(422);
    }
    $defaults = [];
    if (!$record && in_array($key, ['reviews', 'faq'], true)) {
        $positions = array_column(listRecords($key), 'sort_order');
        $defaults['sort_order'] = $positions ? max($positions) + 1 : 0;
    }
    $fields = adminFields($section['fields'], $record ?? $defaults, $errors);
    $socialGroups = [];
    if ($key === 'social') foreach (array_chunk($fields, 4) as $groupFields) $socialGroups[] = ['name' => $groupFields[0]['value'] ?: $groupFields[0]['default'], 'fields' => $groupFields];
    $seoTextFields = $key === 'seo' ? array_values(array_filter($fields, fn (array $field): bool => !in_array($field['name'], ['image', 'image_alt'], true))) : [];
    $seoImageFields = $key === 'seo' ? array_values(array_filter($fields, fn (array $field): bool => $field['name'] === 'image_alt')) : [];
    $seoPreview = $key === 'seo' ? seoPreviewData($record) : [];
    $ratingFields = $key === 'reviews' ? adminFields($sections['rating']['fields'], getSettings('rating'), [], 'rating') : [];
    $rows = [];
    if (!$singleton) foreach ($key === 'content' ? visualContentRecords() : listRecords($key) as $row) {
        $row['caption'] = $row['name'] ?? $row['question'] ?? $row['label'] ?? $row['text'] ?? $row['date'] ?? $row['id'];
        $row['preview'] = $key === 'faq' ? faqAnswer($row, getSettings('delivery'), openingHours()) : ($row['text'] ?? '');
        $row['preview'] = mb_strimwidth($row['preview'], 0, 340, '…', 'UTF-8');
        $rows[] = $row;
    }
    $listOrderVersion = in_array($key, ['navigation', 'faq'], true) ? productOrderVersion(array_column($rows, 'id')) : '';
    $productGroups = $key === 'products' && !$editing ? productOrderGroups($rows) : [];
    $recordCaption = $record['name'] ?? $record['question'] ?? $record['label'] ?? $record['date'] ?? $id;
    $formattedHtml = $formattedContent ? visualRecordHtml($record) : '';
    adminPage($section['title'], 'editor', compact('section', 'key', 'id', 'fields', 'rows', 'editing', 'errors', 'notice', 'singleton', 'recordCaption', 'productGroups', 'ratingFields', 'seoTextFields', 'seoImageFields', 'seoPreview', 'listOrderVersion', 'socialGroups', 'recordVersion', 'formattedContent', 'formattedHtml'));
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
