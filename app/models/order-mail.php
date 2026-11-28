<?php
declare(strict_types=1);

function orderMailRows(int $id): array
{
    $query = database()->prepare('SELECT * FROM order_mail_queue WHERE order_id=?'); $query->execute([$id]); return $query->fetchAll();
}

function retryOrderMail(int $id): void
{
    database()->prepare("UPDATE order_mail_queue SET state='pending',attempts=0,next_attempt=0,last_error='' WHERE order_id=? AND state='failed'")->execute([$id]);
}

function pendingOrderMail(): array
{
    $query = database()->prepare("SELECT * FROM order_mail_queue WHERE state='pending' AND next_attempt<=? ORDER BY id LIMIT 10");
    $query->execute([time()]);
    return $query->fetchAll();
}

function discardOrderMail(int $id): void
{
    database()->prepare('DELETE FROM order_mail_queue WHERE id=?')->execute([$id]);
}

function completeOrderMail(int $id): void
{
    database()->prepare("UPDATE order_mail_queue SET state='sent',attempts=attempts+1,last_error='' WHERE id=?")->execute([$id]);
}

function failOrderMail(int $id, int $attempts): void
{
    database()->prepare('UPDATE order_mail_queue SET state=?,attempts=?,next_attempt=?,last_error=? WHERE id=?')
        ->execute([$attempts >= 5 ? 'failed' : 'pending', $attempts, time() + min(3600, 60 * 2 ** $attempts), 'SMTP недоступен или отклонил письмо', $id]);
}
