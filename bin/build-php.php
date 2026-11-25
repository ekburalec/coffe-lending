<?php
declare(strict_types=1);

function buildPhpDist(string $root): void
{
    $config = getDeployConfig($root);
    $preview = $root . '/var/build-preview-' . bin2hex(random_bytes(6));
    mkdir($preview, 0700, true);
    try {
        $env = array_merge(getenv(), ['APP_DATA_DIR' => $preview, 'APP_UPLOAD_DIR' => $preview . '/media']);
        runBuildProcess([PHP_BINARY, $root . '/bin/migrate.php'], $root, $env);
        $html = runBuildProcess([PHP_BINARY, '-r', 'require "app/core/bootstrap.php"; $data = landingData(); $data["seo"] = landingSeo($data); render("landing", $data);'], $root, $env);
        $warnings = checkPageSeo($root, 'index.php', $html);
        if ($warnings) throw new RuntimeException(implode("\n", $warnings));
        $dist = $root . '/dist';
        removeDirectory($dist, $root);
        mkdir($dist, 0755, true);
        $assets = [];
        $versions = [];
        foreach (['assets/css/main.css' => 'css', 'assets/js/main.js' => 'js', 'assets/js/admin.js' => 'js'] as $entry => $type) {
            $content = $type === 'css' ? bundleCss($root, $entry) : bundleJs($root, $entry);
            writeDistFile($dist, $entry, $content);
            $versions[$entry] = $entry . '?v=' . substr(md5($content), 0, HASH_LENGTH);
            collectAssetsFromText($root, $entry, $content, $assets);
        }
        // HTTP-маршруты не являются статическими файлами.
        $html = preg_replace('~<link\b[^>]*href="/llms\.txt"[^>]*>~', '', $html);
        collectAssetsFromHtml($root, 'index.php', $html, $assets);
        // При заданном домене OG может быть абсолютным; исходное изображение всё равно нужно.
        $seed = json_decode(file_get_contents($root . '/config/seed.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach (array_merge($seed['records']['products'], $seed['records']['images'], [$seed['settings']['seo']]) as $record) {
            $assets[$record['image']] = 'seed';
            if (str_ends_with($record['image'], '.jpg')) {
                foreach (['.webp', '@2x.webp'] as $suffix) {
                    $previewAsset = substr($record['image'], 0, -4) . $suffix;
                    if (is_file($root . '/' . $previewAsset)) $assets[$previewAsset] = 'SEO preview';
                }
            }
            if (str_ends_with($record['image'], '.webp')) $assets[str_replace('.webp', '@2x.webp', $record['image'])] = 'seed';
        }
        copyAssets($root, $dist, $assets);
        foreach (['app', 'templates'] as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) if ($file->isFile()) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                writeDistFile($dist, $relative, file_get_contents($file->getPathname()));
            }
        }
        foreach (['index.php', 'router.php', 'manifest.webmanifest', 'bin/migrate.php', 'bin/create-admin.php', 'bin/doctor.php', 'config/app.php', 'config/admin.php', 'config/admin-navigation.php', 'config/routes.php', 'config/deploy.php', 'config/schema.sql', 'config/seed.json'] as $relative) {
            writeDistFile($dist, $relative, file_get_contents($root . '/' . $relative));
        }
        writeDistFile($dist, 'config/assets.php', "<?php\nreturn " . var_export($versions, true) . ";\n");
        writeDistFile($dist, 'config/release.php', "<?php\nreturn true;\n");
        copyDeployFiles($root, $dist);
        foreach (['app', 'templates', 'config', 'bin'] as $private) writeDistFile($dist, $private . '/.htaccess', "Require all denied\n");
        printReport($dist);
        echo "PHP-дистрибутив готов. Рабочая БД, аккаунты, копии и загрузки в него не включены.\n";
        if ($config['noindex']) echo "Предварительный режим: noindex; домен не задан или ещё не опубликован.\n";
    } finally {
        foreach (glob($preview . '/*') ?: [] as $file) if (is_file($file)) unlink($file);
        rmdir($preview);
    }
}

function runBuildProcess(array $command, string $root, array $env): string
{
    $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $root, $env);
    if (!is_resource($process)) throw new RuntimeException('Не удалось запустить PHP для проверки сборки.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException('Проверка PHP-сборки: ' . $error);
    return $output;
}
