<?php
declare(strict_types=1);

/**
 * Рендер картинки превью ссылки (og:image) из HTML-шаблона в JPG 1200×630.
 * Шаблон и описание — docs/design/og-image/.
 *
 * Запуск: php bin/build-og-image.php
 * Нужен Google Chrome (или Chromium); путь можно задать переменной окружения CHROME_PATH.
 */

const OG_TEMPLATE = 'docs/design/og-image/og-image.html';
const OG_OUTPUT = 'assets/img/og-image.jpg';
const OG_WIDTH = 1200;
const OG_HEIGHT = 630;
const OG_JPEG_QUALITY = 86;

$projectRoot = dirname(__DIR__);

try {
    buildOgImage($projectRoot);
} catch (RuntimeException $e) {
    fwrite(STDERR, 'Ошибка: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

function buildOgImage(string $root): void
{
    $template = $root . '/' . OG_TEMPLATE;
    if (!is_file($template)) {
        throw new RuntimeException('нет шаблона ' . OG_TEMPLATE);
    }
    if (!function_exists('imagecreatefrompng')) {
        throw new RuntimeException('в PHP нет расширения GD');
    }

    $png = tempnam(sys_get_temp_dir(), 'og') . '.png';
    renderTemplateToPng(findChrome(), $template, $png);

    $image = @imagecreatefrompng($png);
    @unlink($png);
    if (!$image || imagesx($image) !== OG_WIDTH || imagesy($image) !== OG_HEIGHT) {
        throw new RuntimeException('Chrome вернул снимок не того размера');
    }

    $output = $root . '/' . OG_OUTPUT;
    imageinterlace($image, true);  // progressive JPEG
    imagejpeg($image, $output, OG_JPEG_QUALITY);
    imagedestroy($image);

    printf("Готово: %s (%d×%d, %s КБ)%s", OG_OUTPUT, OG_WIDTH, OG_HEIGHT, round(filesize($output) / 1024, 1), PHP_EOL);
}

/**
 * Снимок шаблона headless-браузером. Шрифты и картинка грузятся по относительным путям из file://.
 */
function renderTemplateToPng(string $chrome, string $template, string $png): void
{
    $profile = sys_get_temp_dir() . '/og-chrome-' . getmypid();
    $command = implode(' ', [
        escapeshellarg($chrome),
        '--headless=new',
        '--disable-gpu',
        '--hide-scrollbars',
        '--allow-file-access-from-files',
        '--force-device-scale-factor=1',
        '--default-background-color=FFFFFFFF',
        '--virtual-time-budget=5000',
        '--window-size=' . OG_WIDTH . ',' . OG_HEIGHT,
        '--user-data-dir=' . escapeshellarg($profile),
        '--screenshot=' . escapeshellarg($png),
        escapeshellarg('file://' . $template),
        '2>&1',
    ]);
    exec($command, $output, $code);
    exec('rm -rf ' . escapeshellarg($profile));

    if ($code !== 0 || !is_file($png) || filesize($png) === 0) {
        throw new RuntimeException("не удалось сделать снимок шаблона:\n" . implode("\n", $output));
    }
}

function findChrome(): string
{
    $candidates = array_filter([
        getenv('CHROME_PATH') ?: null,
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        '/Applications/Chromium.app/Contents/MacOS/Chromium',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
    ]);
    foreach ($candidates as $path) {
        if (is_file($path) && is_executable($path)) {
            return $path;
        }
    }
    throw new RuntimeException('не найден Chrome или Chromium; укажи путь в CHROME_PATH');
}
