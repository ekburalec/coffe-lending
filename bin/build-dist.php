<?php
declare(strict_types=1);

/**
 * Сборка дистрибутива для хостинга в папку dist/.
 * Правила — docs/specs/deploy.md.
 *
 * Запуск: php bin/build-dist.php
 */

const DIST_DIR_NAME = 'dist';
const DEPLOY_DIR_NAME = 'deploy';
const HASH_LENGTH = 10;
const CONFIG_FILE = 'config/deploy.php';

$projectRoot = dirname(__DIR__);
require __DIR__ . '/build-php.php';

try {
    buildPhpDist($projectRoot);
} catch (RuntimeException $e) {
    fwrite(STDERR, "Ошибка сборки: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

/**
 * Собирает подключённые на странице CSS/JS, ставит им ?v= и собирает ссылки на статику.
 */
function buildPage(string $root, string $pageRel, string $html, array &$built, array &$assets): string
{
    $pageDir = dirname($pageRel);

    $html = preg_replace_callback('/<link\b[^>]*>/i', function (array $m) use ($root, $pageRel, $pageDir, &$built) {
        $tag = $m[0];
        if (!preg_match('/\brel\s*=\s*["\']?stylesheet/i', $tag)) {
            return $tag;
        }
        return replaceBundledRef($tag, 'href', $root, $pageRel, $pageDir, $built, 'css');
    }, $html);

    $html = preg_replace_callback('/<script\b[^>]*\bsrc\s*=[^>]*>/i', function (array $m) use ($root, $pageRel, $pageDir, &$built) {
        return replaceBundledRef($m[0], 'src', $root, $pageRel, $pageDir, $built, 'js');
    }, $html);

    collectAssetsFromHtml($root, $pageRel, $html, $assets);

    return $html;
}

/**
 * Заменяет ссылку в теге на собранный файл с ?v=. Внешние ссылки не трогает.
 */
function replaceBundledRef(string $tag, string $attr, string $root, string $pageRel, string $pageDir, array &$built, string $type): string
{
    if (!preg_match('/\b' . $attr . '\s*=\s*(["\'])(.*?)\1/i', $tag, $m)) {
        return $tag;
    }
    $url = $m[2];
    if (!isLocalUrl($url)) {
        return $tag;
    }

    $rel = resolvePath($pageDir, stripQuery($url), $pageRel);
    if (!isset($built[$rel])) {
        $content = $type === 'css' ? bundleCss($root, $rel) : bundleJs($root, $rel);
        $built[$rel] = ['content' => $content, 'hash' => substr(md5($content), 0, HASH_LENGTH)];
    }

    $newUrl = stripQuery($url) . '?v=' . $built[$rel]['hash'];
    return str_replace($m[0], $attr . '=' . $m[1] . $newUrl . $m[1], $tag);
}

/* ---------- robots.txt и sitemap.xml ---------- */

/**
 * Настройки из config/deploy.php: site_url (https://домен без слэша в конце) и noindex.
 * Без site_url можно собрать только предварительную версию с noindex = true.
 */
function getDeployConfig(string $root): array
{
    $config = is_file($root . '/' . CONFIG_FILE) ? require $root . '/' . CONFIG_FILE : [];
    $siteUrl = rtrim(trim((string) ($config['site_url'] ?? '')), '/');
    $noindex = (bool) ($config['noindex'] ?? false);

    if ($siteUrl === '' && !$noindex) {
        throw new RuntimeException('не указан адрес сайта: заполни site_url в ' . CONFIG_FILE
            . ' (нужен для sitemap.xml, robots.txt, canonical и llms.txt) или включи noindex для предварительной сборки');
    }
    if ($siteUrl !== '' && !preg_match('~^https?://[^/\s?#]+$~i', $siteUrl)) {
        throw new RuntimeException('site_url в ' . CONFIG_FILE . " должен быть вида https://домен, без пути — сейчас «{$siteUrl}»");
    }
    return ['site_url' => $siteUrl, 'noindex' => $noindex];
}

/**
 * Предварительная сборка: запрет индексации на каждой странице.
 */
function injectNoindexMeta(string $html): string
{
    $html = preg_replace('/^[ \t]*<meta\b[^>]*\bname\s*=\s*["\']?robots\b[^>]*>\R?/mi', '', $html);
    return preg_replace('/(<meta\s+charset\s*=[^>]*>\R)/i', '$1    <meta name="robots" content="noindex, nofollow">' . "\n", $html, 1);
}

function renderNoindexRobots(): string
{
    return "# Предварительная версия сайта: индексация закрыта (noindex в " . CONFIG_FILE . ").\n"
        . "User-agent: *\nDisallow: /\n";
}

/**
 * Страница с <meta name="robots" content="noindex"> в sitemap не попадает.
 */
function isNoindexPage(string $html): bool
{
    if (!preg_match_all('/<meta\b[^>]*>/i', $html, $tags)) {
        return false;
    }
    foreach ($tags[0] as $tag) {
        if (preg_match('/\bname\s*=\s*["\']?robots\b/i', $tag) && preg_match('/\bcontent\s*=\s*["\'][^"\']*noindex/i', $tag)) {
            return true;
        }
    }
    return false;
}

/**
 * sitemap.xml: index.html — корень сайта, остальные страницы — по имени файла.
 */
function renderSitemap(string $siteUrl, array $pages): string
{
    ksort($pages);
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($pages as $pageRel => $lastmod) {
        $path = $pageRel === 'index.html' ? '/' : '/' . rawurlencode($pageRel);
        $xml .= "    <url>\n";
        $xml .= '        <loc>' . htmlspecialchars($siteUrl . $path, ENT_XML1) . "</loc>\n";
        $xml .= "        <lastmod>{$lastmod}</lastmod>\n";
        $xml .= "    </url>\n";
    }
    return $xml . "</urlset>\n";
}

/**
 * robots.txt: индексировать всё, путь к sitemap.xml.
 */
function renderRobots(string $siteUrl): string
{
    return "# Индексация открыта всем роботам, включая поисковые и ИИ-агенты\n"
        . "# (GPTBot, OAI-SearchBot, ClaudeBot, Claude-SearchBot, PerplexityBot, Google-Extended и др.).\n"
        . "# Краткое описание сайта для ИИ-агентов: {$siteUrl}/llms.txt\n"
        . "User-agent: *\nAllow: /\n\nSitemap: {$siteUrl}/sitemap.xml\n";
}

/* ---------- SEO-теги страниц ---------- */

/**
 * Абсолютный адрес страницы: index.html — корень сайта.
 */
function getPageUrl(string $siteUrl, string $pageRel): string
{
    $path = basename($pageRel) === 'index.html' ? dirname($pageRel) . '/' : $pageRel;
    return $siteUrl . '/' . ltrim(str_replace('./', '', $path), '/');
}

/**
 * Относительный адрес из страницы → абсолютный на домене сайта.
 */
function getAbsoluteUrl(string $siteUrl, string $pageRel, string $url): string
{
    if (!isLocalUrl($url)) {
        return $url;
    }
    $suffix = substr($url, strlen(stripQuery($url)));
    $path = stripQuery($url);
    if ($path === '' || $path === './' || $path === '.') {
        return getPageUrl($siteUrl, $pageRel) . $suffix;
    }
    if (str_starts_with($path, '/')) {
        return $siteUrl . $path . $suffix;
    }
    $rel = resolvePath(dirname($pageRel), $path, $pageRel);
    $isDir = str_ends_with($path, '/');
    return $siteUrl . '/' . $rel . ($isDir && $rel !== '' ? '/' : '') . $suffix;
}

/**
 * Добавляет canonical и og:url, делает абсолютными og:image / twitter:image и адреса в JSON-LD.
 */
function injectSeoTags(string $html, string $siteUrl, string $pageRel): string
{
    $pageUrl = getPageUrl($siteUrl, $pageRel);
    $extra = '';

    if (!preg_match('/<link\b[^>]*\brel\s*=\s*["\']?canonical\b/i', $html)) {
        $extra .= '    <link rel="canonical" href="' . htmlspecialchars($pageUrl) . "\">\n";
    }
    if (preg_match('/<meta\b[^>]*\bproperty\s*=\s*["\']og:/i', $html)
        && !preg_match('/<meta\b[^>]*\bproperty\s*=\s*["\']og:url["\']/i', $html)) {
        $extra .= '    <meta property="og:url" content="' . htmlspecialchars($pageUrl) . "\">\n";
    }
    if ($extra !== '') {
        $html = preg_replace('/^([ \t]*)<\/head>/mi', $extra . '$1</head>', $html, 1);
    }

    $html = preg_replace_callback(
        '/<meta\b[^>]*\b(?:property|name)\s*=\s*["\'](?:og:image|og:image:url|og:image:secure_url|twitter:image)["\'][^>]*>/i',
        function (array $m) use ($siteUrl, $pageRel) {
            return preg_replace_callback('/\bcontent\s*=\s*(["\'])(.*?)\1/i', fn ($c) =>
                'content=' . $c[1] . htmlspecialchars(getAbsoluteUrl($siteUrl, $pageRel, html_entity_decode($c[2]))) . $c[1], $m[0], 1);
        },
        $html
    );

    return preg_replace_callback(
        '/(<script\b[^>]*type\s*=\s*["\']application\/ld\+json["\'][^>]*>)(.*?)(<\/script>)/is',
        function (array $m) use ($siteUrl, $pageRel) {
            $data = json_decode($m[2], true);
            if (!is_array($data)) {
                throw new RuntimeException("{$pageRel}: невалидный JSON в <script type=\"application/ld+json\"> — " . json_last_error_msg());
            }
            $data = absolutizeJsonLd($data, $siteUrl, $pageRel);
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            return $m[1] . "\n" . str_replace('</', '<\/', $json) . "\n    " . $m[3];
        },
        $html
    );
}

/**
 * В JSON-LD адреса в полях url, image, logo, @id, sameAs, hasMap делаются абсолютными.
 */
function absolutizeJsonLd(array $data, string $siteUrl, string $pageRel): array
{
    $urlKeys = ['url', 'image', 'logo', '@id', 'sameAs', 'hasMap', 'contentUrl', 'thumbnailUrl'];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $isUrlList = in_array($key, $urlKeys, true) && array_is_list($value);
            $data[$key] = $isUrlList
                ? array_map(fn ($v) => is_string($v) ? getAbsoluteUrl($siteUrl, $pageRel, $v) : $v, $value)
                : absolutizeJsonLd($value, $siteUrl, $pageRel);
            if ($isUrlList) {
                foreach ($data[$key] as $i => $item) {
                    if (is_array($item)) {
                        $data[$key][$i] = absolutizeJsonLd($item, $siteUrl, $pageRel);
                    }
                }
            }
        } elseif (is_string($value) && in_array($key, $urlKeys, true)) {
            $data[$key] = getAbsoluteUrl($siteUrl, $pageRel, $value);
        }
    }
    return $data;
}

/**
 * SEO-минимум страницы. Нарушения — предупреждения: сборка не падает, но выводит список.
 */
function checkPageSeo(string $root, string $pageRel, string $html): array
{
    $warnings = [];
    $xpath = new DOMXPath(loadHtmlDocument($html));
    $add = function (string $text) use (&$warnings, $pageRel) {
        $warnings[] = "{$pageRel}: {$text}";
    };

    if (trim((string) $xpath->evaluate('string(/html/@lang)')) === '') {
        $add('нет атрибута lang у <html>');
    }

    $title = normalizeText((string) $xpath->evaluate('string(//head/title)'));
    if ($title === '') {
        $add('нет <title>');
    } elseif (mb_strlen($title) > 70) {
        $add('<title> длиннее 70 символов (' . mb_strlen($title) . ') — в выдаче обрежется');
    }

    $description = normalizeText(getMetaContent($xpath, 'name', 'description'));
    $length = mb_strlen($description);
    if ($description === '') {
        $add('нет meta description');
    } elseif ($length < 50 || $length > 160) {
        $add("meta description {$length} символов — лучше 50–160");
    }

    $h1Count = $xpath->query('//h1')->length;
    if ($h1Count !== 1) {
        $add("на странице {$h1Count} <h1>, нужен ровно один");
    }

    $noAlt = $xpath->query('//img[not(@alt)]')->length;
    if ($noAlt) {
        $add("{$noAlt} <img> без alt");
    }

    foreach (['og:title', 'og:description', 'og:image'] as $property) {
        if (getMetaContent($xpath, 'property', $property) === '') {
            $add("нет <meta property=\"{$property}\"> — превью ссылки в мессенджерах и соцсетях будет пустым");
        }
    }

    $ogImage = getMetaContent($xpath, 'property', 'og:image');
    if ($ogImage !== '') {
        $warnings = array_merge($warnings, checkOgImage($root, $pageRel, $ogImage));
    }

    if ($xpath->query('//script[@type="application/ld+json"]')->length === 0) {
        $add('нет структурированных данных JSON-LD (schema.org)');
    }

    return $warnings;
}

/**
 * Картинка для превью: JPG или PNG, не меньше 1200×630, пропорции около 1,91:1.
 */
function checkOgImage(string $root, string $pageRel, string $ogImage): array
{
    $path = parse_url($ogImage, PHP_URL_PATH) ?: '';
    $file = $root . '/' . ltrim($path, '/');
    if (!is_file($file)) {
        return [];  // внешняя картинка — не проверяем
    }
    $warnings = [];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        $warnings[] = "{$pageRel}: og:image в формате {$ext} — не все соцсети и мессенджеры его показывают, лучше JPG или PNG";
    }
    $size = @getimagesize($file);
    if ($size) {
        [$width, $height] = $size;
        $ratio = $height > 0 ? $width / $height : 0;
        if ($width < 1200 || $ratio < 1.8 || $ratio > 2.0) {
            $warnings[] = "{$pageRel}: og:image {$width}×{$height} — нужна 1200×630 (1,91:1), иначе превью обрежется";
        }
    }
    return $warnings;
}

function getMetaContent(DOMXPath $xpath, string $attr, string $name): string
{
    $node = $xpath->query("//meta[@{$attr}='{$name}']/@content")->item(0);
    return $node ? trim($node->nodeValue) : '';
}

function printWarnings(array $warnings): void
{
    if (!$warnings) {
        echo 'SEO-проверка: замечаний нет' . PHP_EOL;
        return;
    }
    echo PHP_EOL . 'SEO-предупреждения (' . count($warnings) . '):' . PHP_EOL;
    foreach ($warnings as $warning) {
        echo '  ! ' . $warning . PHP_EOL;
    }
}

/* ---------- llms.txt для ИИ-агентов ---------- */

/**
 * llms.txt (llmstxt.org): название, описание, страницы и разделы со ссылками.
 */
function renderLlmsTxt(string $siteUrl, array $pages, bool $includeSitemap = true, string $notice = ''): string
{
    ksort($pages);
    $main = getPageSummary($siteUrl, 'index.html', $pages['index.html'] ?? reset($pages));

    $out = "# {$main['siteName']}\n\n";
    if ($main['description'] !== '') {
        $out .= "> {$main['description']}\n\n";
    }
    $out .= $notice . "Сайт: {$siteUrl}/\n\n";

    $out .= "## Страницы\n\n";
    foreach ($pages as $pageRel => $html) {
        $page = getPageSummary($siteUrl, $pageRel, $html);
        $out .= "- [{$page['title']}]({$page['url']})" . ($page['description'] !== '' ? ": {$page['description']}" : '') . "\n";
    }

    foreach ($pages as $pageRel => $html) {
        $page = getPageSummary($siteUrl, $pageRel, $html);
        if (!$page['sections']) {
            continue;
        }
        $out .= "\n## Разделы: {$page['title']}\n\n";
        foreach ($page['sections'] as $section) {
            $out .= "- [{$section['title']}]({$page['url']}#{$section['id']})\n";
        }
    }

    $out .= "\n## Подробнее\n\n";
    $out .= "- [Полный текст сайта]({$siteUrl}/llms-full.txt): весь текст страниц в Markdown\n";
    if ($includeSitemap) {
        $out .= "- [Карта сайта]({$siteUrl}/sitemap.xml)\n";
    }

    return $out;
}

/**
 * llms-full.txt: весь текст страниц (main и footer) в Markdown.
 */
function renderLlmsFullTxt(string $siteUrl, array $pages): string
{
    ksort($pages);
    $parts = [];
    foreach ($pages as $pageRel => $html) {
        $page = getPageSummary($siteUrl, $pageRel, $html);
        $xpath = new DOMXPath(loadHtmlDocument($html));

        $part = "# {$page['title']}\n\nАдрес: {$page['url']}\n";
        if ($page['description'] !== '') {
            $part .= "\n> {$page['description']}\n";
        }
        foreach (['//main', '//body/footer'] as $query) {
            foreach ($xpath->query($query) as $node) {
                $blocks = [];
                renderMarkdownBlocks($node, $page['url'], $blocks);
                $part .= "\n" . joinMarkdownBlocks($blocks) . "\n";
            }
        }
        $parts[] = $part;
    }
    return implode("\n---\n\n", $parts);
}

/**
 * Название, описание, адрес и разделы (section[id] с заголовком h2) страницы.
 */
function getPageSummary(string $siteUrl, string $pageRel, string $html): array
{
    $xpath = new DOMXPath(loadHtmlDocument($html));
    $title = normalizeText((string) $xpath->evaluate('string(//head/title)'));
    $siteName = getMetaContent($xpath, 'property', 'og:site_name')
        ?: trim(explode(' — ', $title)[0]);

    $sections = [];
    foreach ($xpath->query('//main//section[@id]') as $section) {
        $heading = $xpath->query('.//h2', $section)->item(0);
        if ($heading) {
            $sections[] = ['id' => $section->getAttribute('id'), 'title' => normalizeText($heading->textContent)];
        }
    }

    return [
        'title' => $title ?: $pageRel,
        'siteName' => $siteName ?: $pageRel,
        'description' => normalizeText(getMetaContent($xpath, 'name', 'description')),
        'url' => getPageUrl($siteUrl, $pageRel),
        'sections' => $sections,
    ];
}

/**
 * Обходит DOM и собирает блоки Markdown: заголовки, абзацы, пункты списков.
 * Пропускает скрытое от людей (aria-hidden, hidden), svg, скрипты и кнопки-иконки.
 */
function renderMarkdownBlocks(DOMNode $node, string $pageUrl, array &$blocks): void
{
    $inline = '';
    $flush = function () use (&$inline, &$blocks) {
        $text = normalizeText($inline);
        if ($text !== '') {
            $blocks[] = $text;
        }
        $inline = '';
    };

    foreach ($node->childNodes as $child) {
        if ($child instanceof DOMText) {
            $inline .= $child->textContent;
            continue;
        }
        if (!$child instanceof DOMElement || isSkippedForMarkdown($child)) {
            continue;
        }
        $tag = strtolower($child->tagName);

        if (preg_match('/^h([1-6])$/', $tag, $m)) {
            $flush();
            $blocks[] = str_repeat('#', (int) $m[1]) . ' ' . renderMarkdownInline($child, $pageUrl);
        } elseif (in_array($tag, ['p', 'summary', 'figcaption', 'address', 'blockquote', 'dd'], true)) {
            $flush();
            $blocks[] = renderMarkdownInline($child, $pageUrl);
        } elseif ($tag === 'dt') {
            $flush();
            $blocks[] = '**' . renderMarkdownInline($child, $pageUrl) . '**';
        } elseif ($tag === 'li' && !(new DOMXPath($child->ownerDocument))->query('.//h2|.//h3|.//h4|.//p[2]', $child)->length) {
            $flush();
            $blocks[] = '- ' . renderMarkdownInline($child, $pageUrl);
        } elseif (in_array($tag, ['a', 'span', 'strong', 'em', 'b', 'i', 'small', 'time', 'mark', 'abbr', 'data', 'br'], true)) {
            $inline .= ' ' . renderMarkdownInlineElement($child, $pageUrl) . ' ';
        } elseif (!in_array($tag, ['img', 'picture', 'button', 'input', 'select', 'textarea', 'form', 'video', 'audio', 'iframe'], true)) {
            $flush();
            renderMarkdownBlocks($child, $pageUrl, $blocks);
        }
    }
    $flush();
}

function renderMarkdownInline(DOMNode $node, string $pageUrl): string
{
    $out = '';
    foreach ($node->childNodes as $child) {
        if ($child instanceof DOMText) {
            $out .= $child->textContent;
        } elseif ($child instanceof DOMElement) {
            $out .= renderMarkdownInlineElement($child, $pageUrl);
        }
    }
    return normalizeText($out);
}

/**
 * Один элемент внутри строки: ссылка → [текст](адрес), span и блочные — через пробел
 * (в вёрстке «14<span>дней</span>» визуально разделены), strong/em — слитно.
 */
function renderMarkdownInlineElement(DOMElement $element, string $pageUrl): string
{
    $tag = strtolower($element->tagName);
    if (isSkippedForMarkdown($element) || in_array($tag, ['img', 'button'], true)) {
        return '';
    }
    $text = renderMarkdownInline($element, $pageUrl);
    if ($tag === 'a' && $element->hasAttribute('href')) {
        $href = $element->getAttribute('href');
        $url = str_starts_with($href, '#') ? $pageUrl . $href : getAbsoluteUrlFromPage($pageUrl, $href);
        return $text !== '' ? " [{$text}]({$url}) " : '';
    }
    $isTight = in_array($tag, ['strong', 'em', 'b', 'i', 'small', 'time', 'mark', 'abbr', 'data', 'sup', 'sub'], true);
    return $isTight ? $text : " {$text} ";
}

/**
 * Ссылка из llms-full.txt: относительные адреса — от адреса страницы.
 */
function getAbsoluteUrlFromPage(string $pageUrl, string $href): string
{
    if (!isLocalUrl($href)) {
        return $href;
    }
    $origin = preg_match('~^https?://~i', $pageUrl)
        ? preg_replace('~^(https?://[^/]+).*$~', '$1', $pageUrl)
        : '.';
    if (str_starts_with($href, '/')) {
        return $origin . $href;
    }
    $baseDir = trim((string) parse_url($pageUrl, PHP_URL_PATH), '/');
    $baseDir = str_ends_with((string) parse_url($pageUrl, PHP_URL_PATH), '/') ? $baseDir : dirname($baseDir);
    return $origin . '/' . resolvePath($baseDir === '' ? '.' : $baseDir, stripQuery($href), $pageUrl)
        . substr($href, strlen(stripQuery($href)));
}

function isSkippedForMarkdown(DOMElement $element): bool
{
    $tag = strtolower($element->tagName);
    if (in_array($tag, ['script', 'style', 'svg', 'template', 'noscript', 'head'], true)) {
        return true;
    }
    if ($element->getAttribute('aria-hidden') === 'true' || $element->hasAttribute('hidden')) {
        return true;
    }
    // «(откроется в новой вкладке)» у ссылок — подсказка для скринридера, агенту не нужна
    $isSrOnly = preg_match('/(^|\s)sr-only(\s|$)/', $element->getAttribute('class')) === 1;
    return $isSrOnly && $element->parentNode instanceof DOMElement && strtolower($element->parentNode->tagName) === 'a';
}

function joinMarkdownBlocks(array $blocks): string
{
    $out = '';
    $previous = '';
    foreach ($blocks as $block) {
        if ($out !== '') {
            $out .= (str_starts_with($block, '- ') && str_starts_with($previous, '- ')) ? "\n" : "\n\n";
        }
        $out .= $block;
        $previous = $block;
    }
    return $out;
}

function loadHtmlDocument(string $html): DOMDocument
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return $document;
}

/**
 * Неразрывные пробелы → обычные, лишние пробелы схлопываются, пробел перед знаками препинания убирается.
 */
function normalizeText(string $text): string
{
    $text = str_replace("\u{00A0}", ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = preg_replace('/\s+([,.;:!?)])/u', '$1', $text);
    $text = preg_replace('/([(«])\s+/u', '$1', $text);
    return trim(preg_replace('/\s+»/u', '»', $text));
}

/* ---------- CSS ---------- */

/**
 * Встраивает все @import точки входа в один файл и переписывает url() под её папку.
 */
function bundleCss(string $root, string $entryRel): string
{
    $seen = [];
    $css = inlineCssFile($root, $entryRel, dirname($entryRel), $seen);
    return minifyCss($css);
}

function inlineCssFile(string $root, string $fileRel, string $outDir, array &$seen): string
{
    if (isset($seen[$fileRel])) {
        return '';
    }
    $seen[$fileRel] = true;

    $css = readProjectFile($root, $fileRel, $fileRel);
    $fileDir = dirname($fileRel);
    $children = [];

    $css = preg_replace_callback(
        '/^[ \t]*@import\s+(?:url\(\s*)?(["\']?)([^"\')\s]+)\1\s*\)?\s*([^;]*);/m',
        function (array $m) use ($root, $fileRel, $fileDir, $outDir, &$seen, &$children) {
            if (trim($m[3]) !== '') {
                throw new RuntimeException("{$fileRel}: @import с медиа-условием не поддерживается — {$m[0]}");
            }
            if (!isLocalUrl($m[2]) || str_starts_with($m[2], '/')) {
                throw new RuntimeException("{$fileRel}: @import только относительным путём — {$m[0]}");
            }
            $importRel = resolvePath($fileDir, $m[2], $fileRel);
            $placeholder = '/*__css_import_' . count($children) . '__*/';
            $children[$placeholder] = inlineCssFile($root, $importRel, $outDir, $seen);
            return $placeholder;
        },
        $css
    );

    // url() самого файла переписываются до вставки детей: у них пути уже переписаны.
    return strtr(rewriteCssUrls($css, $fileRel, $outDir), $children);
}

/**
 * Переписывает относительные url() из папки исходного файла в папку собранного.
 */
function rewriteCssUrls(string $css, string $fileRel, string $outDir): string
{
    $fileDir = dirname($fileRel);

    return mapCssUrls($css, function (string $url) use ($fileDir, $fileRel, $outDir) {
        if (!isLocalUrl($url) || str_starts_with($url, '/')) {
            return $url;
        }
        $target = resolvePath($fileDir, stripQuery($url), $fileRel);
        return relativePath($outDir, $target) . substr($url, strlen(stripQuery($url)));
    });
}

/**
 * Проходит по всем url(...) в CSS и заменяет адрес результатом $callback.
 * Содержимое кавычек читается целиком, поэтому url() внутри data:-URI не трогается.
 */
function mapCssUrls(string $css, callable $callback): string
{
    $out = '';
    $offset = 0;
    while (preg_match('/url\(\s*/i', $css, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $start = $m[0][1] + strlen($m[0][0]);
        $quote = in_array($css[$start] ?? '', ['"', "'"], true) ? $css[$start] : '';
        $valueStart = $start + strlen($quote);
        $valueEnd = $quote !== '' ? strpos($css, $quote, $valueStart) : strpos($css, ')', $valueStart);
        if ($valueEnd === false) {
            break;
        }
        $url = substr($css, $valueStart, $valueEnd - $valueStart);
        $out .= substr($css, $offset, $valueStart - $offset) . $callback($quote === '' ? trim($url) : $url);
        $offset = $valueEnd;
    }
    return $out . substr($css, $offset);
}

/**
 * Вырезает комментарии (с учётом строк), отступы и пустые строки.
 */
function minifyCss(string $css): string
{
    $out = '';
    $len = strlen($css);
    for ($i = 0; $i < $len; $i++) {
        $ch = $css[$i];
        if ($ch === '"' || $ch === "'") {
            $end = $i + 1;
            while ($end < $len && $css[$end] !== $ch) {
                $end += $css[$end] === '\\' ? 2 : 1;
            }
            $out .= substr($css, $i, $end - $i + 1);
            $i = $end;
            continue;
        }
        if ($ch === '/' && ($css[$i + 1] ?? '') === '*') {
            $end = strpos($css, '*/', $i + 2);
            $i = $end === false ? $len : $end + 1;
            continue;
        }
        $out .= $ch;
    }

    $lines = array_filter(array_map('trim', explode("\n", $out)), fn ($line) => $line !== '');
    return implode("\n", $lines) . "\n";
}

/* ---------- JS ---------- */

/**
 * Собирает граф ES-модулей от точки входа в один файл.
 */
function bundleJs(string $root, string $entryRel): string
{
    $modules = [];  // путь => ['code' => ..., 'exports' => [...], 'deps' => [...]]
    $order = [];
    $visiting = [];
    loadJsModule($root, $entryRel, $modules, $order, $visiting, [$entryRel]);

    $ids = [];
    foreach ($order as $index => $rel) {
        $ids[$rel] = '__module' . $index;
    }

    $out = "/* Сборка bin/build-dist.php из {$entryRel}. Не править руками. */\n";
    foreach ($order as $rel) {
        $module = $modules[$rel];
        $code = $module['code'];
        foreach ($module['imports'] as $placeholder => $import) {
            $code = str_replace($placeholder, renderJsImport($import, $ids[$import['from']]), $code);
        }

        $isEntry = $rel === $entryRel;
        $out .= "\n/* {$rel} */\n";
        $out .= $isEntry ? "(() => {\n" : "const {$ids[$rel]} = (() => {\n";
        $out .= rtrim($code) . "\n";
        if (!$isEntry) {
            $out .= 'return { ' . implode(', ', $module['exports']) . " };\n";
        }
        $out .= "})();\n";
    }

    return $out;
}

/**
 * Читает модуль, разбирает import/export, рекурсивно грузит зависимости (порядок — сначала зависимости).
 */
function loadJsModule(string $root, string $rel, array &$modules, array &$order, array &$visiting, array $chain): void
{
    if (isset($modules[$rel])) {
        return;
    }
    if (isset($visiting[$rel])) {
        throw new RuntimeException('циклический импорт: ' . implode(' → ', $chain));
    }
    $visiting[$rel] = true;

    $code = readProjectFile($root, $rel, $chain[count($chain) - 2] ?? $rel);
    $dir = dirname($rel);
    $imports = [];
    $exports = [];

    checkUnsupportedJs($code, $rel);

    // import { a, b as c } from './x.js';
    $code = preg_replace_callback('/^import\s*\{([^}]*)\}\s*from\s*(["\'])([^"\']+)\2\s*;?/m', function (array $m) use ($dir, $rel, &$imports) {
        $from = resolveJsImport($dir, $m[3], $rel, $m[0]);
        $placeholder = '/*__import_' . count($imports) . '__*/';
        $imports[$placeholder] = ['from' => $from, 'names' => parseImportNames($m[1], $rel)];
        return $placeholder;
    }, $code);

    // import './x.js';
    $code = preg_replace_callback('/^import\s*(["\'])([^"\']+)\1\s*;?/m', function (array $m) use ($dir, $rel, &$imports) {
        $from = resolveJsImport($dir, $m[2], $rel, $m[0]);
        $placeholder = '/*__import_' . count($imports) . '__*/';
        $imports[$placeholder] = ['from' => $from, 'names' => []];
        return $placeholder;
    }, $code);

    // export function / async function / const / class
    $code = preg_replace_callback('/^export\s+((?:async\s+)?function\s*\*?\s*|const\s+|class\s+)([A-Za-z_$][\w$]*)/m', function (array $m) use (&$exports) {
        $exports[] = $m[2];
        return $m[1] . $m[2];
    }, $code);

    if (preg_match('/^[ \t]*(import|export)\b/m', $code, $left, PREG_OFFSET_CAPTURE)) {
        $line = substr_count(substr($code, 0, $left[0][1]), "\n") + 1;
        throw new RuntimeException("{$rel}: неподдерживаемая форма {$left[1][0]} (примерно строка {$line}) — см. docs/specs/deploy.md");
    }

    foreach ($imports as $import) {
        loadJsModule($root, $import['from'], $modules, $order, $visiting, array_merge($chain, [$import['from']]));
    }

    unset($visiting[$rel]);
    $modules[$rel] = ['code' => $code, 'imports' => $imports, 'exports' => $exports];
    $order[] = $rel;

    foreach ($imports as $import) {
        foreach ($import['names'] as $name) {
            if (!in_array($name['imported'], $modules[$import['from']]['exports'], true)) {
                throw new RuntimeException("{$rel}: {$import['from']} не экспортирует {$name['imported']}");
            }
        }
    }
}

/**
 * Явные запреты, которые иначе дали бы сломанный билд.
 */
function checkUnsupportedJs(string $code, string $rel): void
{
    $rules = [
        '/^export\s+default\b/m' => 'export default',
        '/^export\s*\{/m' => 'export { … } / export … from',
        '/^export\s*\*/m' => 'export * from',
        '/^export\s+(let|var)\b/m' => 'export let / export var',
        '/^export\s+const\s*[\[{]/m' => 'деструктуризация в export const',
        '/^import\s*\*/m' => 'import * as',
        '/^import\s+[A-Za-z_$]/m' => 'import x from',
        '/\bimport\s*\(/' => 'import()',
        '/\bimport\.meta\b/' => 'import.meta',
    ];
    foreach ($rules as $pattern => $label) {
        if (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE)) {
            $line = substr_count(substr($code, 0, $m[0][1]), "\n") + 1;
            throw new RuntimeException("{$rel}:{$line}: не поддерживается {$label} — см. docs/specs/deploy.md");
        }
    }
}

function resolveJsImport(string $dir, string $spec, string $rel, string $statement): string
{
    if (!str_starts_with($spec, './') && !str_starts_with($spec, '../')) {
        throw new RuntimeException("{$rel}: импорт только относительным путём — {$statement}");
    }
    return resolvePath($dir, $spec, $rel);
}

/**
 * Разбирает "a, b as c" в список пар imported/local.
 */
function parseImportNames(string $list, string $rel): array
{
    $names = [];
    foreach (explode(',', $list) as $part) {
        $part = trim(preg_replace('/\s+/', ' ', $part));
        if ($part === '') {
            continue;
        }
        if (!preg_match('/^([A-Za-z_$][\w$]*)(?: as ([A-Za-z_$][\w$]*))?$/', $part, $m)) {
            throw new RuntimeException("{$rel}: не удалось разобрать импорт «{$part}»");
        }
        $names[] = ['imported' => $m[1], 'local' => $m[2] ?? $m[1]];
    }
    return $names;
}

function renderJsImport(array $import, string $moduleId): string
{
    if (!$import['names']) {
        return '';
    }
    $parts = array_map(
        fn ($n) => $n['imported'] === $n['local'] ? $n['local'] : "{$n['imported']}: {$n['local']}",
        $import['names']
    );
    return 'const { ' . implode(', ', $parts) . " } = {$moduleId};";
}

/* ---------- Статика ---------- */

/**
 * Ссылки на файлы из атрибутов HTML.
 */
function collectAssetsFromHtml(string $root, string $pageRel, string $html, array &$assets): void
{
    $pageDir = dirname($pageRel);
    preg_match_all('/\s(src|href|srcset|poster|data-src|data-srcset|content)\s*=\s*(["\'])(.*?)\2/is', $html, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $attr = strtolower($m[1]);
        $value = html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5);
        $urls = str_contains($attr, 'srcset')
            ? array_map(fn ($item) => preg_split('/\s+/', trim($item))[0], explode(',', $value))
            : [$value];

        foreach ($urls as $url) {
            if ($attr === 'content' && !preg_match('/^[\w.\/-]+\.\w{2,5}([?#].*)?$/', $url)) {
                continue;  // content у meta — обычно текст, а не путь
            }
            addAsset($root, $pageDir, $url, $pageRel, $assets);
        }
    }
}

/**
 * Ссылки на файлы из собранного CSS/JS и из скопированных SVG.
 */
function collectAssetsFromText(string $root, string $fileRel, string $text, array &$assets): void
{
    $dir = dirname($fileRel);
    $ext = strtolower(pathinfo($fileRel, PATHINFO_EXTENSION));

    if ($ext === 'css' || $ext === 'svg') {
        mapCssUrls($text, function (string $url) use ($root, $dir, $fileRel, &$assets) {
            addAsset($root, $dir, $url, $fileRel, $assets);
            return $url;
        });
    }
    if ($ext === 'svg') {
        preg_match_all('/\s(?:xlink:)?href\s*=\s*(["\'])(.*?)\1/i', $text, $matches);
        foreach ($matches[2] as $url) {
            addAsset($root, $dir, $url, $fileRel, $assets);
        }
    }
    if ($ext === 'webmanifest') {
        preg_match_all('/"src"\s*:\s*"([^"]+)"/', $text, $matches);
        foreach ($matches[1] as $url) {
            addAsset($root, $dir, $url, $fileRel, $assets);
        }
    }
    if ($ext === 'js') {
        preg_match_all('/(["\'`])(assets\/[^"\'`\s]+\.(?:webp|avif|png|jpe?g|gif|svg|ico|woff2?|mp4|webm|pdf))\1/i', $text, $matches);
        foreach ($matches[2] as $url) {
            addAsset($root, '.', $url, $fileRel, $assets);
        }
    }
}

function addAsset(string $root, string $baseDir, string $url, string $from, array &$assets): void
{
    $url = trim($url);
    if ($url === '' || !isLocalUrl($url)) {
        return;
    }
    $path = stripQuery($url);
    if ($path === '' || str_ends_with($path, '/')) {
        return;
    }
    $rel = str_starts_with($path, '/') ? ltrim($path, '/') : resolvePath($baseDir, $path, $from);
    if (!is_file($root . '/' . $rel)) {
        throw new RuntimeException("{$from}: ссылка на несуществующий файл {$url}");
    }
    $assets[$rel] ??= $from;
}

/**
 * Копирует используемую статику; SVG и webmanifest дополнительно проверяются на свои ссылки.
 */
function copyAssets(string $root, string $distDir, array $assets): void
{
    $copied = [];
    while ($pending = array_diff_key($assets, $copied)) {
        foreach ($pending as $rel => $from) {
            $copied[$rel] = true;
            if (is_file($distDir . '/' . $rel)) {
                continue;  // уже собран (CSS/JS)
            }
            $target = $distDir . '/' . $rel;
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }
            copy($root . '/' . $rel, $target);

            if (in_array(strtolower(pathinfo($rel, PATHINFO_EXTENSION)), ['svg', 'webmanifest'], true)) {
                collectAssetsFromText($root, $rel, (string) file_get_contents($root . '/' . $rel), $assets);
            }
        }
    }
}

/**
 * Всё из deploy/ (включая .htaccess) — в корень dist/.
 */
function copyDeployFiles(string $root, string $distDir): void
{
    $deployDir = $root . '/' . DEPLOY_DIR_NAME;
    if (!is_dir($deployDir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($deployDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->getFilename() === '.DS_Store') {
            continue;
        }
        $target = $distDir . '/' . substr($item->getPathname(), strlen($deployDir) + 1);
        if ($item->isDir()) {
            is_dir($target) || mkdir($target, 0755, true);
        } else {
            copy($item->getPathname(), $target);
        }
    }
}

/* ---------- Пути и файлы ---------- */

function isLocalUrl(string $url): bool
{
    return !preg_match('#^([a-z][a-z0-9+.-]*:|//|\#)#i', $url);
}

function stripQuery(string $url): string
{
    return preg_split('/[?#]/', $url, 2)[0];
}

/**
 * Склеивает путь относительно папки и нормализует его. Выход за корень проекта — ошибка.
 */
function resolvePath(string $baseDir, string $path, string $from): string
{
    $full = ($baseDir === '.' || $baseDir === '') ? $path : $baseDir . '/' . $path;
    $parts = [];
    foreach (explode('/', $full) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            if (!$parts) {
                throw new RuntimeException("{$from}: путь {$path} ведёт за пределы проекта");
            }
            array_pop($parts);
            continue;
        }
        $parts[] = $part;
    }
    $rel = implode('/', $parts);
    if (str_starts_with($rel, DIST_DIR_NAME . '/') || str_starts_with($rel, 'docs/')) {
        throw new RuntimeException("{$from}: ссылка на служебную папку {$rel}");
    }
    return $rel;
}

function relativePath(string $fromDir, string $toFile): string
{
    $from = ($fromDir === '.' || $fromDir === '') ? [] : explode('/', $fromDir);
    $to = explode('/', $toFile);
    while ($from && $to && $from[0] === $to[0]) {
        array_shift($from);
        array_shift($to);
    }
    return str_repeat('../', count($from)) . implode('/', $to);
}

function readProjectFile(string $root, string $rel, string $from): string
{
    $path = $root . '/' . $rel;
    if (!is_file($path)) {
        throw new RuntimeException("{$from}: файл не найден — {$rel}");
    }
    return (string) file_get_contents($path);
}

function writeDistFile(string $distDir, string $rel, string $content): void
{
    $target = $distDir . '/' . $rel;
    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0755, true);
    }
    file_put_contents($target, $content);
}

/**
 * Удаляет папку сборки. Разрешено только для dist/ внутри проекта.
 */
function removeDirectory(string $dir, string $root): void
{
    if (!is_dir($dir)) {
        return;
    }
    if (realpath($dir) !== realpath($root) . DIRECTORY_SEPARATOR . DIST_DIR_NAME) {
        throw new RuntimeException("отказ удалять {$dir}: это не папка сборки");
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

function printReport(string $distDir): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($distDir, FilesystemIterator::SKIP_DOTS)
    );
    $files = [];
    $total = 0;
    foreach ($iterator as $item) {
        $rel = substr($item->getPathname(), strlen($distDir) + 1);
        $files[$rel] = $item->getSize();
        $total += $item->getSize();
    }
    ksort($files);

    echo 'Собрано в ' . DIST_DIR_NAME . '/:' . PHP_EOL;
    foreach ($files as $rel => $size) {
        printf("  %-50s %8s%s", $rel, formatSize($size), PHP_EOL);
    }
    printf("Файлов: %d, всего %s%s", count($files), formatSize($total), PHP_EOL);
}

function formatSize(int $bytes): string
{
    return $bytes >= 1024 ? round($bytes / 1024, 1) . ' КБ' : $bytes . ' Б';
}
