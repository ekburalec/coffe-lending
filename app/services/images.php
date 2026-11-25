<?php
declare(strict_types=1);

function uploadedImage(array $file, bool $preview = false): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Файл не загружен или превышает лимит сервера.');
    if (!function_exists('imagewebp')) throw new RuntimeException('Для загрузки фотографий включите GD с WebP.');
    if (($file['size'] ?? 0) > 10 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) throw new InvalidArgumentException('Допустим файл размером до 10 МБ.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$mime]) || !in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) throw new InvalidArgumentException('Загрузите JPG, PNG или WebP.');
    $size = getimagesize($file['tmp_name']);
    if (!$size || $size[0] < 100 || $size[1] < 100 || $size[0] * $size[1] > 20000000) throw new InvalidArgumentException('Изображение: минимум 100×100, максимум 20 мегапикселей.');
    if ($preview && ($size[0] < 1200 || $size[1] < 630)) throw new InvalidArgumentException('Для превью нужен исходник не меньше 1200×630 пикселей.');
    $source = imagecreatefromstring(file_get_contents($file['tmp_name']));
    if (!$source) throw new InvalidArgumentException('Не удалось прочитать изображение.');
    $directory = appConfig('upload_dir');
    if (!is_dir($directory)) mkdir($directory, 0755, true);
    $base = bin2hex(random_bytes(16));
    $sizes = $preview ? [['', 1200, 630, 'jpg'], ['', 600, 315, 'webp'], ['@2x', 1200, 630, 'webp']] : [['', 720, 540, 'webp'], ['@2x', 1440, 1080, 'webp']];
    $written = [];
    try {
        foreach ($sizes as [$suffix, $width, $height, $format]) {
            $target = imagecreatetruecolor($width, $height);
            imagefill($target, 0, 0, imagecolorallocate($target, 247, 241, 232));
            $scale = max($width / $size[0], $height / $size[1]);
            $cropWidth = $width / $scale;
            $cropHeight = $height / $scale;
            imagecopyresampled($target, $source, 0, 0, (int) (($size[0] - $cropWidth) / 2), (int) (($size[1] - $cropHeight) / 2), $width, $height, (int) $cropWidth, (int) $cropHeight);
            $path = $directory . '/' . $base . $suffix . '.' . $format;
            $written[] = $path;
            $ok = $format === 'jpg' ? imagejpeg($target, $path, 90) : imagewebp($target, $path, 82);
            imagedestroy($target);
            if (!$ok) throw new RuntimeException('Не удалось сохранить фотографию.');
        }
    } catch (Throwable $error) {
        foreach ($written as $path) if (is_file($path)) unlink($path);
        throw $error;
    } finally {
        imagedestroy($source);
    }
    return 'assets/img/uploads/' . $base . ($preview ? '.jpg' : '.webp');
}

function validImagePath(string $path): bool
{
    if (!preg_match('~^assets/img/[a-zA-Z0-9_@/-]+\.(webp|jpg)$~D', $path)) return false;
    $file = str_starts_with($path, 'assets/img/uploads/')
        ? appConfig('upload_dir') . '/' . basename($path) : APP_ROOT . '/' . $path;
    return is_file($file);
}

function seoPreviewData(array $record): array
{
    $image = $record['image'];
    $one = str_replace('.jpg', '.webp', $image);
    $two = str_replace('.jpg', '@2x.webp', $image);
    // Старые пользовательские JPEG без миниатюр остаются доступны для просмотра.
    return [
        'image' => $image,
        'src' => assetUrl(validImagePath($one) ? $one : $image),
        'srcset_2x' => assetUrl(validImagePath($two) ? $two : $image),
        'alt' => !empty($record['image_alt']) ? $record['image_alt'] : $record['og_title'],
    ];
}

function generateOgPreviews(string $jpeg): void
{
    if (!function_exists('imagewebp')) throw new RuntimeException('Для превью требуется GD с WebP.');
    $source = imagecreatefromjpeg($jpeg);
    if (!$source) throw new RuntimeException('Не удалось прочитать JPG превью.');
    try {
        foreach ([['.webp', 600, 315], ['@2x.webp', 1200, 630]] as [$suffix, $width, $height]) {
            $target = imagecreatetruecolor($width, $height);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
            $ok = imagewebp($target, substr($jpeg, 0, -4) . $suffix, 82);
            imagedestroy($target);
            if (!$ok) throw new RuntimeException('Не удалось создать WebP превью.');
        }
    } finally { imagedestroy($source); }
}
