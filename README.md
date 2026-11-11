# Шаблон HTML-лендинга

Заготовка для статических лендингов: HTML + чистый CSS (BEM, переменные) + нативный JS
(ES modules), сборка дистрибутива для обычного хостинга одной командой, правила для
AI-агентов. Правила работы — [AGENTS.md](AGENTS.md), документация — [docs/](docs/).

## Что нужно

- PHP 8+ в командной строке с расширениями GD и DOM — для сборки и локального сервера
  (на хостинг PHP не нужен).
- Google Chrome или Chromium — только для `bin/build-og-image.php`.

## Как начать новый проект

1. Скопировать папку шаблона под именем проекта (без `dist/`), при желании — `git init`.
2. Заполнить вместе с агентом, в этом порядке:
   1. [docs/product/product.md](docs/product/product.md) — бриф (последний вопрос — по какой структуре строим лендинг);
   2. [docs/design/direction.md](docs/design/direction.md) + референсы в [docs/design/refs/](docs/design/refs/);
   3. [docs/product/landing-content.md](docs/product/landing-content.md) — структура и тексты.
3. Заменить заглушки каркаса:

| Что | Где |
|-----|-----|
| `<title>`, description, Open Graph, JSON-LD, `apple-mobile-web-app-title`, `theme-color` | `index.html`, `<head>` |
| Тексты и секции страницы | `index.html` (по `landing-content.md`) |
| Палитра, шрифты, отступы, радиусы | `assets/css/base/vars.css` (по `direction.md`), шрифты — `assets/fonts/` + `@font-face` в `base.css` |
| Знак и PNG-иконки | `assets/icons/` — [docs/modules/web-app.md](docs/modules/web-app.md) |
| Имя, описание, цвета веб-приложения | `manifest.webmanifest` |
| Картинка превью ссылки | `docs/design/og-image/og-image.html` → `php bin/build-og-image.php` |
| Адрес сайта, режим noindex | `config/deploy.php` |

После замены иконок или манифеста обновить `?v=` в ссылках (первые 10 символов md5 файла):

```bash
md5 -q assets/icons/favicon.svg | cut -c1-10
```

## Локальный просмотр

Конфигурации — в `.claude/launch.json`: `landing` — исходники на http://localhost:8090,
`landing-dist` — собранный `dist/` на http://localhost:8092. Вручную:

```bash
php -S localhost:8090
```

## Сборка для хостинга

```bash
php bin/build-dist.php
```

Результат — папка `dist/`: её содержимое (вместе со скрытым `.htaccess`) выгружается в корень
сайта. Подробности, SEO-проверки и чек-лист публикации — [docs/specs/deploy.md](docs/specs/deploy.md).

## Что в шаблоне

```
AGENTS.md, CLAUDE.md     ← правила для AI-агентов (CLAUDE.md подключает AGENTS.md)
index.html               ← каркас страницы: шапка, первый экран, раздел, финальный CTA, подвал
manifest.webmanifest     ← веб-приложение «На экран Домой»
assets/css/              ← base (vars, reset, base), blocks (container, skip-link, btn, section, notify),
                           sections (site-header, hero, site-footer), utils (sr-only)
assets/js/               ← main.js + modules/notify.js
assets/icons/            ← знак-заглушка и PNG-иконки
assets/img/og-image.jpg  ← картинка превью-заглушка
bin/                     ← build-dist.php, build-og-image.php
config/deploy.php        ← site_url и noindex
deploy/.htaccess         ← кеширование и сжатие на хостинге
docs/                    ← продукт, дизайн, спеки по слоям, модули, правила агента
```
