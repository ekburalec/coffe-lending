<?php
declare(strict_types=1);

function ordersToErase(string $cutoff, array $erasedHashes): array
{
    $rows = database()->query('SELECT id,token,finished_at FROM orders WHERE anonymized=0')->fetchAll();
    return array_values(array_filter($rows, fn ($row) => ($row['finished_at'] !== null && $row['finished_at'] <= $cutoff) || isset($erasedHashes[hash('sha256', $row['token'])])));
}

function anonymizeOrderRows(array $rows): void
{
    $pdo = database(); $pdo->beginTransaction();
    try {
        foreach ($rows as $row) {
            $id = $row['id'];
            $pdo->prepare("UPDATE orders SET customer='{}',requested_at='',agreed_at='',next_contact='',anonymized=1,version=version+1,status=CASE WHEN finished_at IS NULL THEN 'cancelled' ELSE status END,finished_at=COALESCE(finished_at,?) WHERE id=?")->execute([date('Y-m-d H:i:s'), $id]);
            $pdo->prepare("UPDATE order_items SET comment='' WHERE order_id=?")->execute([$id]);
            $pdo->prepare('DELETE FROM order_events WHERE order_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM order_mail_queue WHERE order_id=?')->execute([$id]);
        }
        $pdo->commit();
    } catch (Throwable $error) { $pdo->rollBack(); throw $error; }
}
