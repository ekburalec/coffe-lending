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
        $field['hint_id'] = 'hint-' . $field['id'];
        $field['described_by'] = $field['error_id'] . (!empty($field['hint']) ? ' ' . $field['hint_id'] : '');
        $field['length'] = mb_strlen((string) $field['value'], 'UTF-8');
        $field['error'] = $errors[$name] ?? '';
        $fields[] = $field;
    }
    return $fields;
}
