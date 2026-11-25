> Активная архитектура: PHP-лендинг и админка, SQLite/PDO, шаблоны .tpl.
> Применяются docs/specs/php.md и docs/app/admin-spec.md. Исходная статическая
> редакция находится в истории Git; шаблонные инструкции о создании нового проекта
> относятся к исходной заготовке. CSS, BEM и нативный JS сохранены.

# AGENTS.md

Инструкции для любого AI-агента (Claude Code, Codex, Cursor и др.), который работает
с этим проектом. Прочитай этот файл целиком перед началом любой задачи.

Это единственный источник правил для агентов. `CLAUDE.md` только подключает этот файл —
правила меняются здесь, а не там. Ссылки ниже ведут на документы в `docs/`: открывай
нужные перед работой, сами по себе они не подгружаются.

## О проекте

Это лендинг кофейни «Вкусный повод» на PHP, SQLite, чистом HTML/CSS и нативном JS.
Проект создан из статического шаблона; PHP-фаза и админка согласованы пользователем.
В шаблоне есть документация (`docs/`), инструменты сборки (`bin/`) и пустой рабочий каркас
страницы. Документация — источник правды о стеке, архитектуре и стиле кода. Если задача
противоречит документации — остановись и спроси пользователя, а не «улучшай» правила молча.

Серверная часть реализована по [docs/specs/php.md](docs/specs/php.md): один
администратор, управление контентом, SQLite. Заказ и оплата в неё не входят.

### Если проект только что создан из шаблона

Прежде чем писать код, проверь, заполнены ли эти файлы. Если там заготовка — заполни их
вместе с пользователем (сначала спека, потом код):

1. [docs/product/product.md](docs/product/product.md) — название, идея, аудитория (JTBD),
   зачем покупают, УТП, доказательства, возражения, цены, целевое действие, структура
   лендинга (последний вопрос брифа), части системы.
2. [docs/design/direction.md](docs/design/direction.md) — референсы, впечатление, палитра,
   типографика, геометрия, плотность, что не используем. Референсы — в [docs/design/refs/](docs/design/refs/).
3. [docs/product/landing-content.md](docs/product/landing-content.md) — структура лендинга
   и тексты блоков (после product.md, до вёрстки).

Затем — заменить в каркасе заглушки: `<title>`, description, Open Graph и JSON-LD
в `index.html`, имя и цвета в `manifest.webmanifest`, знак в `assets/icons/favicon.svg`,
токены в `assets/css/base/vars.css`, шаблон картинки превью `docs/design/og-image/og-image.html`.
Полный список — в [README.md](README.md).

## Стек (не менять без явного решения пользователя)

| Слой      | Технологии                                                                        |
|-----------|-----------------------------------------------------------------------------------|
| Страницы  | PHP-фронт-контроллер `index.php`, серверные шаблоны `templates/*.tpl` |
| Разметка  | HTML5, семантика, доступность WCAG 2.1 AA                                          |
| Стили     | Чистый CSS, BEM, `@import`, CSS-переменные. Без SCSS/Tailwind/CSS-in-JS/фреймворков |
| JS        | Нативный ES2020+, ES modules. Без jQuery, фреймворков, TypeScript, сборщиков       |
| Сборка    | Только собственные скрипты на PHP CLI: `bin/build-dist.php` (дистрибутив для хостинга в `dist/`), `bin/build-og-image.php` (картинка превью ссылки) |
| Картинки  | webp с retina-версией `@2x`, `og:image` — JPG; иконки и логотипы — SVG              |
| Сервер    | PHP 8.2+, SQLite/PDO, GD/WebP и ZIP по [docs/specs/php.md](docs/specs/php.md) |

PHP используется в приложении, сборке и локальном сервере (`php -S localhost:8090 router.php`).
На хостинг выгружается PHP-дистрибутив без рабочих данных.

## Как работать (обязательно)

Полные правила: [docs/ai-rules/ai-rules.md](docs/ai-rules/ai-rules.md) и
[docs/ai-rules/change-policy.md](docs/ai-rules/change-policy.md).

- Сначала прочитай документацию, относящуюся к задаче (карта ниже), потом пиши код.
- Работай только в рамках текущей задачи и текущей фазы. Одна задача = один завершённый
  логический результат.
- Не меняй больше одной подсистемы за шаг без явной причины.
- Не смешивай рефакторинг и новую функциональность. Не трогай несвязанный код.
- Не переписывай рабочий код целиком, не добавляй абстракции, не меняй стек и стиль кода.
- Сначала минимально рабочая версия, потом улучшения.
- Перед большим рефакторингом или крупными изменениями — backup/commit.

### Формат ответа

1. Краткий план
2. Изменяемые файлы
3. Реализация
4. Что проверить вручную
5. Следующий логичный шаг

## Карта документации

Читай файл, когда задача затрагивает соответствующую область.

| Файл | Когда читать |
|------|--------------|
| [README.md](README.md) | Старт нового проекта из шаблона: что заменить, как запустить и собрать |
| [docs/product/product.md](docs/product/product.md) | Любая задача — понимание продукта и сценариев |
| [docs/product/specs.md](docs/product/specs.md) | Структура лендинга, смыслы, оффер, тексты, новые блоки |
| [docs/product/landing-content.md](docs/product/landing-content.md) | Утверждённая структура и тексты блоков лендинга — перед вёрсткой любого блока |
| [docs/product/dod-global.md](docs/product/dod-global.md) | Перед сдачей **любой** задачи — общий Definition of Done |
| [docs/design/direction.md](docs/design/direction.md) | Любая вёрстка и стили |
| [docs/design/refs/](docs/design/refs/) | Визуальные референсы к дизайн-направлению |
| [docs/design/images.md](docs/design/images.md) | Фото и сгенерированная графика: бриф на каждую картинку, размеры 1x/2x |
| [docs/ai-rules/ai-rules.md](docs/ai-rules/ai-rules.md) | Всегда — правила поведения агента |
| [docs/ai-rules/change-policy.md](docs/ai-rules/change-policy.md) | Всегда — как дробить изменения |
| [docs/specs/html.md](docs/specs/html.md) | Разметка, формы, изображения, a11y, ARIA, `<head>` страницы |
| [docs/specs/css.md](docs/specs/css.md) | Любые стили, структура CSS, `main.css` |
| [docs/specs/css-component-vars.md](docs/specs/css-component-vars.md) | Цвета и состояния компонентов, контекстное переопределение |
| [docs/specs/theming.md](docs/specs/theming.md) | Тёмная тема, `.dark-mode`, тёмные секции |
| [docs/specs/js.md](docs/specs/js.md) | Любой JS, модули, формы, fetch, уведомления |
| [docs/specs/deploy.md](docs/specs/deploy.md) | Подготовка к публикации: сборка `dist/`, версии `?v=`, SEO, кеширование в `.htaccess` |
| [docs/specs/php.md](docs/specs/php.md) | Любой серверный PHP-код и админка |

## Смыслы, структура и тексты лендинга — [docs/product/specs.md](docs/product/specs.md)

Обязательно прочитай этот файл, если задача касается структуры лендинга, его содержания,
смыслов, текстов или нового блока. Как применять:

- **Content-first.** Сначала смысл и текст блока, потом вёрстка. Порядок работы: бриф
  ([product.md](docs/product/product.md)) → структура
  ([landing-content.md](docs/product/landing-content.md)) → референсы
  ([direction.md](docs/design/direction.md), [refs/](docs/design/refs/)) → вёрстка.
  Если текста для блока нет — сначала предложи текст, не верстай блок с «рыбой».
- **Оффер** выводи из JTBD: какую задачу человек решает, когда ищет продукт. Проблемы,
  желания и выгоды клиента сопоставляй с тем, что даёт продукт (Value Proposition Canvas).
- **Заголовки** проверяй по формуле 4U: полезность, уникальность, ультраконкретность, срочность.
- **Структура** — не один скелет на все случаи. Последний вопрос брифа: предложи 2–3 структуры
  из каталога в specs.md (или свой гибрид) под этот проект, отметь рекомендуемую, пользователь
  выбирает. Блоки идут по скелету выбранной структуры. От проблемы (PAS) строй, только если
  клиент покупает, чтобы убрать проблему; продукты удовольствия (кафе, ресторан, магазин,
  подарки) — от продукта и впечатления (витрина, история бренда).
  Порядок подачи внутри блока — AIDA.
- **Возражения** собирай в матрицу «почему не купят» и закрывай блоками FAQ, гарантий и кейсов.
- **Тексты** — в информационном стиле: без штампов, только факты. Проверяй по этому критерию
  каждый текст, который сгенерировал сам. Факты, цифры, отзывы и кейсы бери из `product.md`
  или у пользователя — не выдумывай. Если пользователь разрешил демо-данные — помечай их
  **[демо]** в `product.md`.
- **Первый экран** проходит тест 5 секунд: понятно, что это, для кого и что делать дальше.

В ответе на такие задачи укажи, какую роль в структуре играет блок, и отметь в «Что проверить
вручную», прошёл ли заголовок 4U, а первый экран — тест 5 секунд.

## Ключевые правила по слоям (кратко)

Это выжимка. Детали и примеры — в спеках по ссылкам.

### Структура проекта

```
index.php                ← фронт-контроллер, лендинг и админка
app/                     ← контроллеры, модели, сервисы и валидаторы
templates/               ← шаблоны .tpl
var/                     ← локальные приватные данные, не в git
manifest.webmanifest     ← веб-приложение «На экран Домой»
assets/css/              ← main.css (точка входа) + base/ blocks/ sections/ utils/
assets/js/               ← main.js (точка входа) + modules/
assets/img/              ← растровая графика (webp 1x + @2x, og-image.jpg)
assets/icons/            ← favicon.svg, иконки веб-приложения, sprite.svg
assets/fonts/            ← локальные шрифты woff2
bin/                     ← скрипты сборки (PHP CLI)
config/deploy.php        ← адрес сайта и режим noindex для сборки
deploy/                  ← файлы в корень хостинга как есть (.htaccess и т. п.)
docs/                    ← документация
dist/                    ← результат сборки, не в git, руками не правится
```

### HTML — [docs/specs/html.md](docs/specs/html.md)

- `<!DOCTYPE html>`, `lang`, `UTF-8`, `viewport`, `description`, уникальный `<title>` на каждой странице.
- В `<head>` публичной страницы: Open Graph, JSON-LD schema.org, фавиконка, манифест — адреса
  формируются PHP, домен берётся из config/deploy.php.
- Семантика: один `<main>`, один `<h1>`, заголовки без пропуска уровней, у `<section>` есть
  заголовок и `id`.
- Формы: `<label for>` у каждого поля, `autocomplete`, `aria-describedby` для ошибок, `type="submit"`.
- `<img>`: всегда `alt`, `width`, `height`; растровая графика — всегда с retina:
  `src` на 1x + `srcset="name.webp 1x, name@2x.webp 2x"`.
- `<button>` — для действий, `<a href>` — для навигации. Ссылки на чужие сайты и сервисы — всегда
  `target="_blank"` + `rel="noopener noreferrer"` + скрытое «(откроется в новой вкладке)».
- `<strong>`/`<em>`, а не `<b>`/`<i>`.
- Без inline-стилей и inline-JS. JS-хуки — `data-*` или `js-*`, не классы оформления. ID не для стилей.

### CSS — [docs/specs/css.md](docs/specs/css.md)

- Структура `assets/css/`: `base/` (vars, reset, base) → `blocks/` → `sections/` → `utils/` — в этом порядке импорта.
- Только BEM (`block__element--modifier`), строчные, через дефис, по смыслу. Один блок/секция = один файл.
- Новый файл подключается только через `@import url('./…');` в `main.css`.
- Простые селекторы по классам, низкая специфичность, flex/grid, без хаков.
- Цвета, отступы, шрифты, радиусы, тени — только через переменные из `vars.css`.
- Адаптив обязателен (от 320px), медиа-запросы рядом с блоком.
- Состояния форм явные: hover, focus, active, disabled, error, success, loading.

### Переменные компонентов и темы — [css-component-vars.md](docs/specs/css-component-vars.md), [theming.md](docs/specs/theming.md)

- Дефолты переменных компонента — в `:root`, тёмные значения — в `.dark-mode`, отдельным
  именованным блоком в `assets/css/base/vars.css`. Для всех компонентов без исключения,
  включая кнопки. Других файлов токенов нет.
- Компонент только **читает** переменные, никогда не объявляет их в своём селекторе.
- Контекст (тёмная секция, `.dark-mode` и т. п.) переопределяет переменные на своём уровне.
- Именование: `--{класс-компонента}-{состояние?}-{свойство}` (`--btn-primary-hover-bg`).
- Тема переключается классом `.dark-mode` на `<body>` (или на контейнере). Никаких `.logo--dark`.

### JS — [docs/specs/js.md](docs/specs/js.md)

- `assets/js/main.js` — только импорты и вызовы `init*()` внутри `DOMContentLoaded`.
- Каждая фича — свой модуль в `assets/js/modules/` (kebab-case), экспортирует одну `init*`,
  в начале проверяет наличие своих элементов.
- Только `const`/`let`, `async/await`, `fetch` в `try/catch` с проверкой `response.ok`.
- Классы состояния — `is-*`; показ/скрытие — атрибутом `hidden`.
- Уведомления — только через `notify.js`; `alert/confirm/prompt` запрещены. Ошибки не глушить.
- Контент и факты — в HTML, а не генерируются JS: страница читается без JS.
- Синтаксис `import`/`export` — только формы, которые понимает сборка (раздел «Сборка» в js.md):
  нельзя `export default`, `import * as`, `import()` и др.

## Разработка и сборка

- Локальный просмотр исходников — конфигурация `landing` в `.claude/launch.json`
  (`php -S localhost:8090 router.php`). Страница грузит `main.css` с цепочкой `@import` и `main.js`
  с модулями как есть — без сборки.
- Публикация — [docs/specs/deploy.md](docs/specs/deploy.md): `php bin/build-dist.php`
  собирает PHP-дистрибутив `dist/`, CSS/JS объединяются с `?v=` по содержимому.
  Рабочие БД, аккаунты, фотографии и копии не входят в сборку. На хостинге они
  хранятся отдельно от кода. Исходные ресурсы копируются по ссылкам и seed.json.
- SEO, robots, sitemap и llms-файлы формируются PHP из актуальных данных;
  домен и noindex берутся из `config/deploy.php`.
- `dist/` руками не правится и не хранится в git. Перед выгрузкой — тесты dist,
  проверка Apache/HTTPS и DoD из deploy.md. Публикация на GitHub Pages исключена.

## Definition of Done

Задача не завершена, пока не выполнены все пункты
[docs/product/dod-global.md](docs/product/dod-global.md) и DoD соответствующих спек
(разделы «Definition of Done» в html.md, css.md, js.md, deploy.md). В ответе всегда перечисляй,
что нужно проверить вручную.

## Реестры проекта

Обязательны по DoD: при добавлении или изменении бизнес-логики, модуля или интеграции —
создай/обнови документ и добавь строку в таблицу ниже.

### Бизнес-логика (`docs/product/logic/`)

| Документ | Что описывает |
|----------|---------------|
| [docs/product/logic/admin-publishing.md](docs/product/logic/admin-publishing.md) | Сохранение данных и согласованное отображение на лендинге |
| [docs/product/logic/product-order.md](docs/product/logic/product-order.md) | Порядок товаров, навигации и FAQ; конфликты сохранения |
| [docs/product/logic/reviews.md](docs/product/logic/reviews.md) | Видимость отзывов/FAQ, рейтинг и публичные блоки |
| [docs/product/logic/order-preview.md](docs/product/logic/order-preview.md) | Выбор позиций, количество и расчёт самовывоза/доставки без отправки и оплаты |

### Модули (`docs/modules/`)

| Документ | Что описывает |
|----------|---------------|
| [docs/modules/web-app.md](docs/modules/web-app.md) | Веб-приложение: манифест и иконки для «На экран Домой» |
| [docs/modules/build-dist.md](docs/modules/build-dist.md) | PHP-дистрибутив, версии ресурсов и приватные данные |
| [docs/modules/order-preview.md](docs/modules/order-preview.md) | Локальный состав заказа, пересчёт сумм, доступность и состояние без JS |
| [docs/modules/order-drawer.md](docs/modules/order-drawer.md) | Боковая модальная корзина, открытие, закрытие, фокус и прокрутка |
| [docs/modules/site-navigation.md](docs/modules/site-navigation.md) | Мобильная навигация, клавиатура, смена размера и состояние без JS |
| [docs/modules/fixed-header.md](docs/modules/fixed-header.md) | Фиксированная шапка, компенсация высоты и отступы якорей |
| [docs/modules/announcement-bar.md](docs/modules/announcement-bar.md) | Бегущая строка, пауза, доступность и уменьшение движения |
| [docs/design/og-image/README.md](docs/design/og-image/README.md) | Картинка превью ссылки `og:image` 1200×630: шаблон и сборка `bin/build-og-image.php` |
| [docs/modules/php-app.md](docs/modules/php-app.md) | PHP-приложение: запуск, роутер и приватные данные |
| [docs/modules/admin-auth.md](docs/modules/admin-auth.md) | Авторизация, сессии и CSRF админки |
| [docs/modules/admin-reorder.md](docs/modules/admin-reorder.md) | Перетаскивание товаров, навигации и FAQ; сохранение порядка |
| [docs/modules/admin-content.md](docs/modules/admin-content.md) | Редакторы и валидация данных админки |
| [docs/modules/images.md](docs/modules/images.md) | Проверка загрузок и WebP 1x/2x |
| [docs/modules/reviews.md](docs/modules/reviews.md) | Слайдер, фото и демо-отзывы, управление отзывами и FAQ |
| [docs/modules/admin-character-count.md](docs/modules/admin-character-count.md) | Подсказки и счётчики символов SEO-форм |
| [docs/modules/seo.md](docs/modules/seo.md) | SEO, Open Graph и актуальная JSON-LD-разметка |
| [docs/modules/backups.md](docs/modules/backups.md) | Экспорт, проверка и восстановление БД и фотографий |

### Интеграции (`docs/integrations/`)

Внешние сервисы: карты, формы заявок через сторонний сервис, аналитика, виджеты, мессенджеры.

| Документ | Что описывает |
|----------|---------------|
| [docs/integrations/github-pages.md](docs/integrations/github-pages.md) | Ручная сборка PHP-артефакта GitHub Actions без публикации |

## Если документация противоречит сама себе

Не выбирай вариант молча — уточни у пользователя и после ответа исправь спеку,
чтобы противоречие не повторилось.

## PHP-фаза

Спецификация: [docs/app/admin-spec.md](docs/app/admin-spec.md).
План и прогресс: [ADMIN-PLAN.md](ADMIN-PLAN.md).
