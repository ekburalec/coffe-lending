<?php
declare(strict_types=1);

function listRecords(string $kind, bool $publicOnly = false): array
{
    $query = database()->prepare('SELECT * FROM records WHERE kind = ? ORDER BY sort_order, id');
    $query->execute([$kind]);
    $records = [];
    foreach ($query as $row) {
        $data = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
        $record = array_merge($data, ['id' => $row['id'], 'sort_order' => (int) $row['sort_order'], 'visible' => (int) $row['visible']]);
        if (!$publicOnly || $record['visible']) $records[] = $record;
    }
    return $records;
}

function findRecord(string $kind, string $id): ?array
{
    foreach (listRecords($kind) as $record) {
        if ($record['id'] === $id) return $record;
    }
    return null;
}

function saveRecord(string $kind, string $id, array $data): void
{
    $sort = (int) ($data['sort_order'] ?? 0);
    $visible = (int) ($data['visible'] ?? 1);
    unset($data['id'], $data['sort_order'], $data['visible']);
    $query = database()->prepare('INSERT INTO records(kind, id, payload, sort_order, visible) VALUES(?, ?, ?, ?, ?)
        ON CONFLICT(kind, id) DO UPDATE SET payload = excluded.payload, sort_order = excluded.sort_order, visible = excluded.visible');
    $query->execute([$kind, $id, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $sort, $visible]);
}

function deleteRecord(string $kind, string $id): void
{
    $query = database()->prepare('DELETE FROM records WHERE kind = ? AND id = ?');
    $query->execute([$kind, $id]);
}

function getSettings(string $group): array
{
    $query = database()->prepare('SELECT payload FROM settings WHERE name = ?');
    $query->execute([$group]);
    $json = $query->fetchColumn();
    return $json === false ? [] : json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}

function saveSettings(string $group, array $data): void
{
    $query = database()->prepare('INSERT INTO settings(name, payload) VALUES(?, ?) ON CONFLICT(name) DO UPDATE SET payload=excluded.payload');
    $query->execute([$group, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
}
