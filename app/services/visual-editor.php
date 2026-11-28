<?php
declare(strict_types=1);

function visualEditorConfig(): array
{
    static $config;
    return $config ??= require APP_ROOT . '/config/visual-editor.php';
}

function visualContentRecords(): array
{
    $seed = json_decode(file_get_contents(APP_ROOT . '/config/seed.json'), true, 512, JSON_THROW_ON_ERROR);
    $defaults = array_column($seed['records']['content'], null, 'id');
    return array_replace($defaults, array_column(listRecords('content'), null, 'id'));
}

function visualRecordVersion(array $record): string
{
    return hash('sha256', json_encode($record, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

function visualTypography(string $text): string
{
    $text = preg_replace('/(?<![\p{L}\p{N}])([авикосуя]|во|до|за|из|ко|на|не|ни|но|об|от|по|со|то|уже)((?:<\/(?:strong|em)>)*?)[ \t]+(?=\S)/iu', '$1$2' . "\u{00A0}", $text);
    return preg_replace('/(\d)((?:<\/(?:strong|em)>)*?)[ \t]+(?=(?:<(?:strong|em)>)*(?:₽|%|кг|мг|г|мл|л|км|см|мм|м|минут[а-я]*|час[а-я]*|дн[а-я]*)(?!\p{L}))/iu', '$1$2' . "\u{00A0}", $text);
}

/** Читает фрагмент DOM и выводит только текст, strong/em/br без атрибутов. */
function cleanVisualHtml(string $html, bool $typography = true): string
{
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    try {
        $document->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $body = $document->getElementsByTagName('body')->item(0);
        $walk = function (DOMNode $node) use (&$walk): string {
            if ($node instanceof DOMText) return e($node->nodeValue);
            if (!$node instanceof DOMElement) return '';
            $tag = strtolower($node->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'svg', 'math', 'template'], true)) return '';
            if ($tag === 'br') return '<br>';
            $result = '';
            foreach ($node->childNodes as $child) $result .= $walk($child);
            $tag = match ($tag) { 'b' => 'strong', 'i' => 'em', default => $tag };
            if (in_array($tag, ['strong', 'em'], true)) return '<' . $tag . '>' . $result . '</' . $tag . '>';
            return $result . (in_array($tag, ['p', 'div', 'li'], true) ? '<br>' : '');
        };
        $result = '';
        if ($body) foreach ($body->childNodes as $node) $result .= $walk($node);
        $result = preg_replace('/(?:<br>)+$/', '', trim($result));
        return $typography ? visualTypography($result) : $result;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

function visualRecordHtml(array $record): string
{
    return ($record['format'] ?? 'text') === 'inline' ? cleanVisualHtml($record['text'], false) : e($record['text']);
}

function visualPlainText(string $html): string
{
    return html_entity_decode(strip_tags(str_replace('<br>', "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function visualTextAttributes(string $id, bool $preview): string
{
    return $preview && in_array($id, visualEditorConfig()['texts'], true) ? 'data-visual-text="' . e($id) . '"' : '';
}

function visualImageAttributes(string $id, bool $preview): string
{
    return $preview && in_array($id, visualEditorConfig()['images'], true) ? 'data-visual-image="' . e($id) . '"' : '';
}

function visualManagedAttributes(string $section, bool $preview, string $id = ''): string
{
    if (!$preview) return '';
    $names = ['products' => 'Меню кофейни', 'categories' => 'Категории меню', 'contacts' => 'Контакты и часы', 'hours' => 'Часы работы', 'delivery' => 'Самовывоз и доставка', 'navigation' => 'Навигация', 'announcements' => 'Объявления', 'reviews' => 'Отзывы', 'rating' => 'Рейтинг', 'faq' => 'Вопросы и ответы', 'social' => 'Соцсети'];
    $href = '/admin/edit?section=' . $section . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    return 'data-visual-managed="' . e($names[$section]) . '" data-visual-href="' . e($href) . '"';
}

function visualEditorState(): array
{
    $texts = [];
    foreach (visualContentRecords() as $id => $record) {
        if (!in_array($id, visualEditorConfig()['texts'], true)) continue;
        $texts[$id] = ['html' => visualRecordHtml($record), 'label' => visualEditorConfig()['labels'][$id], 'version' => visualRecordVersion($record)];
    }
    $images = [];
    foreach (listRecords('images') as $record) if (in_array($record['id'], visualEditorConfig()['images'], true)) {
        $images[$record['id']] = ['src' => assetUrl($record['image']), 'alt' => $record['alt'], 'label' => $record['name'], 'version' => visualRecordVersion($record)];
    }
    return compact('texts', 'images');
}

/** Валидация всего пакета до записи и обработки файлов. */
function prepareVisualChanges(array $input): array
{
    if (!$input || !array_is_list($input) || count($input) > 60) throw new InvalidArgumentException('Некорректный список изменений.');
    $content = visualContentRecords();
    $images = array_column(listRecords('images'), null, 'id');
    $seen = [];
    $changes = [];
    foreach ($input as $item) {
        if (!is_array($item) || !is_string($item['kind'] ?? null) || !is_string($item['id'] ?? null)) throw new InvalidArgumentException('Некорректная запись.');
        $kind = $item['kind'];
        $id = $item['id'];
        $allowed = $kind === 'content' ? visualEditorConfig()['texts'] : ($kind === 'images' ? visualEditorConfig()['images'] : []);
        if (!in_array($id, $allowed, true) || isset($seen[$kind . '/' . $id])) throw new InvalidArgumentException('Этот элемент нельзя менять в визуальном редакторе.');
        $seen[$kind . '/' . $id] = true;
        $record = ($kind === 'content' ? $content : $images)[$id] ?? null;
        if (!$record) throw new InvalidArgumentException('Элемент больше не существует. Обновите редактор.');
        if (!is_string($item['version'] ?? null) || !hash_equals(visualRecordVersion($record), $item['version'])) throw new DomainException('Этот текст или фото уже изменены в другой вкладке. Скопируйте свои правки перед обновлением редактора.');
        if ($kind === 'content') {
            if (!is_string($item['html'] ?? null) || strlen($item['html']) > 40000) throw new InvalidArgumentException('Текст слишком длинный.');
            $html = cleanVisualHtml($item['html']);
            $plain = visualPlainText($html);
            if (trim(str_replace("\u{00A0}", ' ', $plain)) === '' || mb_strlen($plain) > 5000) throw new InvalidArgumentException('Заполните текст, не более 5000 символов.');
            $record['text'] = $html;
            $record['format'] = 'inline';
        } else {
            if (!is_string($item['alt'] ?? null)) throw new InvalidArgumentException('Укажите описание фотографии.');
            $record['alt'] = trim($item['alt']);
            if ($record['alt'] === '' || mb_strlen($record['alt']) > 300) throw new InvalidArgumentException('Описание фотографии: от 1 до 300 символов.');
        }
        $changes[] = compact('kind', 'id', 'record');
    }
    return $changes;
}

/** Не доверяем загруженным именам и не публикуем пути до общей транзакции. */
function attachVisualImages(array $changes, array &$created): array
{
    $expected = [];
    foreach ($changes as &$change) {
        if ($change['kind'] !== 'images') continue;
        $name = 'photo_' . $change['id'];
        $expected[] = $name;
        if (isset($_FILES[$name]) && ($_FILES[$name]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $path = uploadedImage($_FILES[$name]);
            $created[] = $path;
            $change['record']['image'] = $path;
        }
    }
    unset($change);
    foreach (array_keys($_FILES) as $name) if (!in_array($name, $expected, true)) throw new InvalidArgumentException('Недопустимая фотография.');
    return $changes;
}

function removeVisualUploads(array $paths): void
{
    foreach ($paths as $path) foreach ([$path, str_replace('.webp', '@2x.webp', $path)] as $version) {
        $file = appConfig('upload_dir') . '/' . basename($version);
        if (is_file($file) && !unlink($file)) error_log('Cannot remove failed visual upload: ' . basename($file));
    }
}
