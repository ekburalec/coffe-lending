<?php
declare(strict_types=1);

function saveVisualRecords(array $changes): void
{
    $pdo = database();
    $pdo->beginTransaction();
    try {
        foreach ($changes as $change) saveRecord($change['kind'], $change['id'], $change['record']);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}
