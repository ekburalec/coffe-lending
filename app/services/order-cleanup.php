<?php
declare(strict_types=1);

function cleanupOrderData(?DateTimeImmutable $now = null): int
{
    $now ??= new DateTimeImmutable();
    // Два календарных месяца с ограничением дня последним днём целевого месяца.
    $month = $now->modify('first day of this month')->modify('-2 months');
    $cutoff = $month->setDate((int) $month->format('Y'), (int) $month->format('m'), min((int) $now->format('d'), (int) $month->format('t')))->setTime((int) $now->format('H'), (int) $now->format('i'));
    $path = appConfig('data_dir') . '/order-erasure.json';
    $hashes = is_file($path) ? json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR) : [];
    if (!is_array($hashes)) throw new RuntimeException('Invalid erasure registry');
    $rows = ordersToErase($cutoff->format('Y-m-d H:i:s'), $hashes);
    if (!$rows) return 0;
    foreach ($rows as $row) $hashes[hash('sha256', $row['token'])] = $now->format('Y-m-d');
    if (file_put_contents($path . '.tmp', json_encode($hashes, JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Cannot save erasure registry');
    chmod($path . '.tmp', 0600);
    if (!rename($path . '.tmp', $path)) throw new RuntimeException('Cannot commit erasure registry');
    anonymizeOrderRows($rows);
    return count($rows);
}

function cleanupOrderBackups(): int
{
    $removed = 0; $cutoff = (new DateTimeImmutable())->modify('-2 months')->getTimestamp();
    foreach (glob(backupDirectory() . '/*') ?: [] as $file) {
        if (!preg_match('/^(?:backup|auto-backup)-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}\.(?:sqlite|zip)$/D', basename($file)) || filemtime($file) >= $cutoff) continue;
        $temporary = null;
        try {
            $path = $file;
            if (str_ends_with($file, '.zip')) {
                if (!class_exists('ZipArchive')) throw new RuntimeException('ZIP required for archive retention');
                $zip = new ZipArchive();
                if ($zip->open($file) !== true) throw new RuntimeException('Cannot inspect archive retention');
                $contents = $zip->getFromName('site.sqlite'); $zip->close();
                if ($contents === false) continue;
                $temporary = appConfig('data_dir') . '/retention-' . bin2hex(random_bytes(8)) . '.sqlite';
                file_put_contents($temporary, $contents); chmod($temporary, 0600); $path = $temporary;
            }
            $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('PRAGMA query_only=ON');
            $hasOrders = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='orders'")->fetchColumn();
            $sensitive = $hasOrders && $pdo->query('SELECT COUNT(*) FROM orders WHERE anonymized=0')->fetchColumn();
            $pdo = null;
            if ($sensitive && unlink($file)) $removed++;
        } finally { if ($temporary && is_file($temporary)) unlink($temporary); }
    }
    return $removed;
}
