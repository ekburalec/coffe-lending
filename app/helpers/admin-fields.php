<?php
declare(strict_types=1);

function adminFields(array $definitions, array $values, array $errors = [], string $prefix = 'field'): array
{
    $fields = [];
    foreach ($definitions as $name => $field) {
        $field['name'] = $name;
        $field['id'] = $prefix . '-' . $name;
        $field['error_id'] = 'error-' . $prefix . '-' . $name;
        $field['type'] = $field['type'] ?? 'text';
        $field['value'] = $values[$name] ?? $field['default'] ?? '';
        $field['error'] = $errors[$name] ?? '';
        $fields[] = $field;
    }
    return $fields;
}
