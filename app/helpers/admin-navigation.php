<?php
declare(strict_types=1);

function adminNavigation(): array
{
    $groups = require APP_ROOT . '/config/admin-navigation.php';
    $path = requestPath();
    $section = is_string($_GET['section'] ?? null) ? $_GET['section'] : '';
    foreach ($groups as &$group) {
        $group['href'] ??= '/admin/edit?section=' . array_key_first($group['sections']);
        $group['active'] = $path === $group['href'] || ($path === '/admin/edit' && isset($group['sections'][$section]));
        $group['tabs'] = [];
        foreach ($group['sections'] as $key => $label) {
            $group['tabs'][] = ['title' => $label, 'href' => '/admin/edit?section=' . $key, 'active' => $key === $section];
        }
    }
    unset($group);
    return $groups;
}
