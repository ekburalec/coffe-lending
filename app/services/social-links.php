<?php
declare(strict_types=1);

function socialNetworkDefaults(): array
{
    return ['vk' => ['name' => 'ВКонтакте', 'label' => 'VK'], 'ok' => ['name' => 'Одноклассники', 'label' => 'OK'], 'telegram' => ['name' => 'Telegram', 'label' => 'TG'], 'whatsapp' => ['name' => 'WhatsApp', 'label' => 'WA'], 'rutube' => ['name' => 'RUTUBE', 'label' => 'RT']];
}

function socialSettings(?array $contacts = null): array
{
    $saved = getSettings('social');
    $contacts ??= getSettings('contacts');
    $values = [];
    foreach (socialNetworkDefaults() as $key => $network) {
        $values[$key . '_name'] = $saved[$key . '_name'] ?? $network['name'];
        $values[$key . '_label'] = $saved[$key . '_label'] ?? $network['label'];
        $values[$key . '_visible'] = $saved[$key . '_visible'] ?? 1;
        // Старые URL сохраняются до первого сохранения отдельного раздела.
        $values[$key . '_url'] = $saved[$key . '_url'] ?? $contacts[$key . '_url'] ?? '';
    }
    return $values;
}
