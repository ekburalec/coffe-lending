<?php
declare(strict_types=1);

function backupsController(): void
{
    requireAdmin();
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrf()) {
            http_response_code(403);
            $error = 'Форма устарела. Обновите страницу.';
        } else {
            try {
                $action = postText('action');
                if (in_array($action, ['export-db', 'export-full'], true)) {
                    downloadBackup(createBackup($action === 'export-full'));
                    return;
                }
                if ($action === 'download') {
                    $name = postText('filename');
                    if (!preg_match('/^(?:backup|auto-backup)-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}\.(?:sqlite|zip)$/D', $name) || !is_file(backupDirectory() . '/' . $name)) throw new InvalidArgumentException('Копия не найдена.');
                    downloadBackup(backupDirectory() . '/' . $name);
                    return;
                }
                if ($action !== 'restore' || postText('confirm_restore') !== '1') throw new InvalidArgumentException('Подтвердите замену текущих данных.');
                restoreBackup($_FILES['backup'] ?? []);
                redirect('/admin/login?restored=1');
            } catch (Throwable $exception) {
                error_log((string) $exception);
                http_response_code(422);
                $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Не удалось выполнить операцию. Проверьте ZIP, свободное место и журнал сервера.';
            }
        }
    }
    $copies = array_map('basename', glob(backupDirectory() . '/backup-*') ?: []);
    rsort($copies);
    $automaticCopies = [];
    foreach (automaticBackups() as $path) {
        $automaticCopies[] = ['name' => basename($path), 'date' => date('d.m.Y H:i', filemtime($path)), 'size' => number_format(filesize($path) / 1024 / 1024, 2, ',', ' ') . ' МБ'];
    }
    adminPage('Резервные копии', 'backups', compact('error', 'copies', 'automaticCopies'));
}

function downloadBackup(string $path): void
{
    header('Content-Type: ' . (str_ends_with($path, '.zip') ? 'application/zip' : 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
}
