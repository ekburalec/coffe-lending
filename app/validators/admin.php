<?php
declare(strict_types=1);

function adminSections(?array $collections = null): array
{
    $sections = require APP_ROOT . '/config/admin.php';
    if (isset($sections['products'])) {
        $sections['products']['fields']['category']['options'] = array_column($collections['categories'] ?? listRecords('categories'), 'name', 'id');
    }
    if (isset($sections['navigation'])) {
        $targets = ['#welcome' => 'Первый экран', '#menu' => 'Меню', '#occasions' => 'Поводы зайти', '#space' => 'О кофейне', '#delivery' => 'Самовывоз и доставка', '#reviews' => 'Отзывы гостей', '#faq' => 'FAQ', '#contacts' => 'Контакты'];
        foreach ($collections['categories'] ?? listRecords('categories', true) as $category) $targets['#' . $category['id']] = $category['name'];
        $sections['navigation']['fields']['target']['options'] = $targets;
    }
    foreach ($sections as $key => &$section) {
        if (!($section['singleton'] ?? false) && !($section['fixed'] ?? false)) {
            if ($key !== 'products') $section['fields']['sort_order'] = ['label' => 'Порядок', 'type' => 'number', 'min' => 0, 'max' => 100000, 'default' => 0];
            $section['fields']['visible'] = ['label' => 'Показывать на сайте', 'type' => 'checkbox', 'default' => 1];
        }
    }
    unset($section);
    return $sections;
}

function validateAdminFields(array $fields, array $previous, ?array $input = null, bool $allowUploads = true): array
{
    $input ??= $_POST;
    $data = $previous;
    $errors = [];
    foreach ($fields as $name => $field) {
        $raw = $input[$name] ?? '';
        $value = is_scalar($raw) ? trim(str_replace(["\r\n", "\r"], "\n", (string) $raw)) : '';
        if (!is_scalar($raw)) $errors[$name] = 'Некорректное значение.';
        $type = $field['type'] ?? 'text';
        if ($type === 'checkbox') $value = !empty($input[$name]) ? 1 : 0;
        if ($allowUploads && $type === 'image' && ($_FILES[$name . '_upload']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) continue;
        if (($field['required'] ?? false) && $value === '') $errors[$name] = 'Заполните поле.';
        elseif ($type === 'number' && ($value !== '' || ($field['required'] ?? false))) {
            $number = filter_var($value, FILTER_VALIDATE_INT);
            if ($number === false || $number < ($field['min'] ?? 0) || $number > ($field['max'] ?? 1000000)) $errors[$name] = 'Укажите целое число в допустимом диапазоне.';
            else $value = $number;
        } elseif ($type === 'select' && !array_key_exists($value, $field['options'] ?? [])) $errors[$name] = 'Выберите значение из списка.';
        elseif ($type === 'url' && $value !== '' && (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true))) $errors[$name] = 'Укажите полный адрес https:// или http://.';
        elseif ($type === 'image' && $value !== '' && !validImagePath($value)) $errors[$name] = 'Выберите существующую фотографию или загрузите новую.';
        elseif (mb_strlen((string) $value) > ($field['max'] ?? 5000) && $type !== 'number') $errors[$name] = 'Текст слишком длинный.';
        $data[$name] = $value;
    }
    return [$data, $errors];
}

function validateAdminRecord(string $kind, array $data, ?array $collections = null): array
{
    $errors = [];
    if ($kind === 'delivery' && empty($data['pickup_enabled']) && empty($data['delivery_enabled'])) $errors['pickup_enabled'] = 'Оставьте доступным хотя бы один способ получения.';
    if ($kind === 'seo' && !($collections === null && ($_FILES['image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) && !str_ends_with($data['image'] ?? '', '.jpg')) $errors['image'] = 'Для превью требуется JPG; загрузите новую фотографию.';
    if ($kind === 'faq' && ($data['mode'] ?? '') === 'manual' && trim($data['answer'] ?? '') === '') $errors['answer'] = 'Напишите ответ.';
    if ($kind === 'rating' && ($data['value'] ?? '') !== '') {
        if (!preg_match('/^[0-5](?:[.,][0-9])?$/D', $data['value']) || (float) str_replace(',', '.', $data['value']) > 5) $errors['value'] = 'Рейтинг от 0 до 5, максимум один знак после точки.';
        if (empty($data['source']) || (int) ($data['count'] ?? 0) < 1) $errors['source'] = 'Для публикации нужны источник и количество оценок.';
    }
    if ($kind === 'announcements') {
        foreach (['starts', 'ends'] as $name) {
            if (($data[$name] ?? '') === '') continue;
            $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $data[$name]);
            if (!$date || $date->format('Y-m-d\\TH:i') !== $data[$name]) $errors[$name] = 'Укажите дату и время.';
        }
        if (!empty($data['starts']) && !empty($data['ends']) && $data['ends'] < $data['starts']) $errors['ends'] = 'Окончание должно быть не раньше начала.';
    }
    if ($kind === 'products'  && !in_array($data['category'] ?? '', array_column($collections['categories'] ?? listRecords('categories'), 'id'), true)) $errors['category'] = 'Категория не существует.';
    if (in_array($kind, ['hours', 'exceptions'], true) && empty($data['closed'])) {
        foreach (['opens', 'closes'] as $name) if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $data[$name] ?? '')) $errors[$name] = 'Укажите время ЧЧ:ММ.';
        if (!$errors && $data['closes'] <= $data['opens']) $errors['closes'] = 'Закрытие должно быть позже открытия в тот же день.';
    }
    if ($kind === 'exceptions') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['date'] ?? '');
        if (!$date || $date->format('Y-m-d') !== ($data['date'] ?? '')) $errors['date'] = 'Укажите действительную дату.';
        foreach ($collections['exceptions'] ?? listRecords('exceptions') as $row) if ($row['date'] === ($data['date'] ?? '') && $row['id'] !== ($data['id'] ?? '')) $errors['date'] = 'Для этой даты уже есть запись.';
    }
    return $errors;
}
