<?php
declare(strict_types=1);

function adminSections(): array
{
    $sections = require APP_ROOT . '/config/admin.php';
    if (isset($sections['products'])) {
        $sections['products']['fields']['category']['options'] = array_column(listRecords('categories'), 'name', 'id');
    }
    foreach ($sections as &$section) {
        if (!($section['singleton'] ?? false) && !($section['fixed'] ?? false)) {
            $section['fields']['sort_order'] = ['label' => 'Порядок', 'type' => 'number', 'min' => 0, 'max' => 100000, 'default' => 0];
            $section['fields']['visible'] = ['label' => 'Показывать на сайте', 'type' => 'checkbox', 'default' => 1];
        }
    }
    unset($section);
    return $sections;
}

function validateAdminFields(array $fields, array $previous): array
{
    $data = $previous;
    $errors = [];
    foreach ($fields as $name => $field) {
        $value = postText($name);
        $type = $field['type'] ?? 'text';
        if ($type === 'checkbox') $value = isset($_POST[$name]) ? 1 : 0;
        if ($type === 'image' && ($_FILES[$name . '_upload']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) continue;
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

function validateAdminRecord(string $kind, array $data): array
{
    $errors = [];
    if ($kind === 'products' && !findRecord('categories', $data['category'] ?? '')) $errors['category'] = 'Категория не существует.';
    return $errors;
}
