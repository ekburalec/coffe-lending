# Стиль кода — JS

## Назначение

Этот файл задаёт правила JavaScript в проекте.
Цель — простой, модульный, читаемый и предсказуемый JS-код как при ручной разработке, так и при разработке с ИИ.

## Стек

- Нативный JavaScript (ES2020+)
- ES modules (`import` / `export`)
- Без jQuery
- Без React / Vue / Angular и любых фронтенд-фреймворков
- Без TypeScript
- Без сборщиков (никаких Webpack / Vite / Babel) — если их не добавят явно позже
- Без инлайн-JS в HTML (`onclick`, `onchange` и т. п.)
- Исключение для внешней библиотеки: CodeMirror 6 (редактор кода в блоке «Код») — один готовый файл `assets/js/vendor/codemirror/codemirror.js`, загружается через `import()` только на страницах, где он нужен; если он не загрузился, остаётся обычная textarea

---

## Структура файлов

Весь JavaScript лежит в:

```
assets/js/
├── main.js              ← точка входа, импортирует и инициализирует все модули
├── modules/
│   ├── modal.js         ← логика модального окна
│   ├── tabs.js          ← логика вкладок
│   ├── accordion.js     ← логика аккордеона / раскрывающихся блоков
│   ├── form.js          ← общее поведение форм (отклик валидации, состояния)
│   ├── upload.js        ← интерфейс загрузки файлов
│   ├── rich-text.js     ← текстовое поле с панелью оформления
│   ├── notify.js        ← уведомления / всплывающие сообщения
│   └── ...              ← по одному файлу на функциональность
```

Правила:

- Один файл — одна ответственность
- Один модуль — одна фича или компонент
- Не смешивать несвязанную логику в одном файле
- `main.js` только импортирует и вызывает init-функции — своей логики в нём нет
- Новая функциональность — новый файл модуля

---

## main.js

`main.js` — единственная точка входа, подключённая в HTML.
Он импортирует все модули и инициализирует их.

```js
import { initModal } from './modules/modal.js';
import { initTabs } from './modules/tabs.js';
import { initAccordion } from './modules/accordion.js';
import { initForms } from './modules/form.js';
import { initUpload } from './modules/upload.js';
import { initRichText } from './modules/rich-text.js';

document.addEventListener('DOMContentLoaded', () => {
	initModal();
	initTabs();
	initAccordion();
	initForms();
	initUpload();
	initRichText();
});
```

Правила:

- Все init-вызовы — внутри `DOMContentLoaded`
- В `main.js` нет встроенной логики — только импорты и init-вызовы
- Каждый модуль сам проверяет, есть ли его элементы на странице, прежде чем работать

## Сборка (`assets/build/main.js`)

Исключение для текущей HTML-фазы по прямому решению пользователя: main.js подключается
как нативный ES-модуль через статический HTTP-сервер, сборка PHP отложена.
См. [static-landing.md](../modules/static-landing.md).

Страницы не грузят `main.js` и его модули по одному: `bin/build-assets.php`
собирает граф импортов от `main.js` в `assets/build/main.js`, каждый модуль —
в своей области видимости, а `asset('assets/js/main.js')` отдаёт его с хэшем
содержимого `?v=`. Билд хранится в git и коммитится вместе с изменением
(`docs/modules/assets-build.md`, D-12).

- Модуль попадает в билд, только если его импортирует `main.js` или
  другой импортированный модуль
- Импорты и экспорты — только в формах, которые понимает сборщик, с начала
  строки:
    - `import { a, b as c } from './x.js';` (фигурные скобки на несколько строк — можно), `import './x.js';`
    - `export function`, `export async function`, `export const`, `export class`
    - ленивая загрузка — `import('./path.js')` со строковым литералом, не с переменной;
      файл остаётся отдельным и получает свой `?v=`
- Не поддерживается, сборка падает с указанием файла и строки: `export default`,
  `export { … }`, `export … from`, `import x from`, `import * as`,
  `export let` / `export var`, `import()` с переменной, `import.meta`,
  циклические импорты
- `assets/build/` руками не правится никогда. В dev билд пересобирается при
  открытии страницы; перед коммитом запусти `php bin/build-assets.php`

---

## Структура модуля

Каждый модуль строится по этому образцу:

```js
// modules/modal.js

/**
 * Модальное окно — открытие, закрытие, ловушка фокуса, работа с клавиатуры.
 */

export function initModal() {
	const triggers = document.querySelectorAll('[data-modal]');
	if (!triggers.length) return;

	triggers.forEach((trigger) => {
		trigger.addEventListener('click', () =>
			openModal(trigger.dataset.modal),
		);
	});

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') closeAllModals();
	});
}

function openModal(id) {
	const modal = document.getElementById(id);
	if (!modal) return;
	modal.removeAttribute('hidden');
	modal.setAttribute('aria-modal', 'true');
	trapFocus(modal);
}

function closeAllModals() {
	document.querySelectorAll('[role="dialog"]').forEach((modal) => {
		modal.setAttribute('hidden', '');
		modal.removeAttribute('aria-modal');
	});
}

function trapFocus(element) {
	// реализация ловушки фокуса
}
```

Правила:

- Каждый модуль экспортирует одну главную функцию `init*`
- Внутренние вспомогательные функции не экспортируются, если не используются где-то ещё
- В начале модуль защищается от отсутствующих DOM-элементов: `if (!element) return;`
- Каждый модуль самодостаточен — не зависит от глобальных переменных других модулей

---

## Именование

### Файлы

- `kebab-case` для всех имён файлов: `modal.js`, `file-upload.js`, `editor-init.js`

### Переменные и функции

- `camelCase` для переменных и функций
- Имя ясно говорит, что хранит переменная или что делает функция
- Никаких случайных сокращений

Хорошо:

```js
const modalTrigger = document.querySelector('[data-modal]');
function openModal(id) { ... }
function closeModal(modal) { ... }
function trapFocus(container) { ... }
```

Плохо:

```js
const el = document.querySelector('[data-modal]');
function handle(x) { ... }
function run() { ... }
function process() { ... }
```

### Константы

- `UPPER_SNAKE_CASE` для настоящих констант (значений, которые никогда не меняются):

```js
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_TYPES = ['image/jpeg', 'image/png'];
```

### Data-атрибуты как JS-хуки

- Элементы из JS находим по атрибутам `data-*` — классы оформления как JS-хуки не используем:

```html
<button class="btn btn--primary" data-modal="login">Войти</button>
```

```js
document.querySelectorAll('[data-modal]'); // правильно
document.querySelectorAll('.btn--primary'); // неправильно — класс оформления как JS-хук
```

---

## Функции

Правила:

- Одна функция — одна ответственность
- Функции короткие и сфокусированные
- Ранний возврат вместо глубокой вложенности
- Передавать только нужные аргументы
- Возвращать предсказуемый результат
- По возможности избегать побочных эффектов внутри функций

Хорошо:

```js
function getFormData(form) {
	return new FormData(form);
}

function showError(field, message) {
	const error = document.getElementById(field.dataset.errorId);
	if (!error) return;
	error.textContent = message;
	field.setAttribute('aria-invalid', 'true');
}

function clearError(field) {
	const error = document.getElementById(field.dataset.errorId);
	if (!error) return;
	error.textContent = '';
	field.removeAttribute('aria-invalid');
}
```

Плохо:

```js
function handleEverything(e) {
	// 80 строк вперемешку: валидация, DOM, fetch и состояние
}
```

---

## Работа с DOM

Правила:

- Элемент ищем один раз и сохраняем в переменную — не запрашиваем один и тот же элемент повторно
- Перед работой с элементом всегда проверяем, что он существует
- Не меняем DOM внутри циклов, если изменения можно сделать пачкой
- Показываем и скрываем элементы атрибутом `hidden` или `aria-hidden` — не инлайн-стилем `display:none`

```js
// Правильно — нашли один раз, проверили, используем
const form = document.querySelector('#login-form');
if (!form) return;

// Показать / скрыть через атрибут
element.removeAttribute('hidden');
element.setAttribute('hidden', '');

// Добавить / убрать состояние классом (когда оно чисто визуальное)
element.classList.add('is-active');
element.classList.remove('is-active');
element.classList.toggle('is-open');
```

Префикс классов состояния:

- Классы состояния, которые переключает JS, — с префиксом `is-*`: `is-active`, `is-open`, `is-loading`, `is-error`
- Классы состояния — только для визуального отображения; логическое состояние хранится в ARIA- или data-атрибутах

---

## Обработка событий

Правила:

- События вешаем через `addEventListener` — никогда через инлайн-атрибуты HTML
- Для динамических и повторяющихся элементов предпочитаем делегирование событий
- Обработчики, которые больше не нужны, всегда снимаем (модальные окна, попапы)
- Если обработчик нужно снять — именованная функция, а не анонимная

```js
// Делегирование событий — правильно для списков и повторяющихся элементов
document.addEventListener('click', (e) => {
	if (e.target.closest('[data-modal]')) {
		openModal(e.target.closest('[data-modal]').dataset.modal);
	}
});

// Именованная функция для снимаемого обработчика
function handleKeydown(e) {
	if (e.key === 'Escape') closeModal();
}

document.addEventListener('keydown', handleKeydown);
// позже:
document.removeEventListener('keydown', handleKeydown);
```

---

## Формы

Поведение форм на клиенте — только улучшение интерфейса.
Настоящая валидация — на сервере, в PHP.

За что отвечает JS в формах:

- Показывать и скрывать отклик валидации из ответа сервера
- Давать подсказки к полям на лету (не блокируя)
- Управлять состоянием загрузки во время отправки
- Отправлять форму через AJAX, если нужно

```js
// modules/form.js

export function initForms() {
	const forms = document.querySelectorAll('[data-form]');
	if (!forms.length) return;

	forms.forEach((form) => {
		form.addEventListener('submit', handleSubmit);
	});
}

async function handleSubmit(e) {
	e.preventDefault();
	const form = e.currentTarget;

	setFormLoading(form, true);

	try {
		const response = await fetch(form.action, {
			method: form.method,
			body: new FormData(form),
		});

		const data = await response.json();

		if (!response.ok) {
			showFormErrors(form, data.errors);
			return;
		}

		handleFormSuccess(form, data);
	} catch (err) {
		showGlobalError(form);
	} finally {
		setFormLoading(form, false);
	}
}

function setFormLoading(form, state) {
	const btn = form.querySelector('[type="submit"]');
	if (!btn) return;
	btn.disabled = state;
	btn.setAttribute('aria-busy', String(state));
}

function showFormErrors(form, errors = {}) {
	Object.entries(errors).forEach(([field, message]) => {
		const input = form.querySelector(`[name="${field}"]`);
		if (!input) return;
		showError(input, message);
	});
}
```

---

## Fetch / асинхронность

Правила:

- Все асинхронные запросы — через `fetch`, без XMLHttpRequest
- Всегда `async/await` — без голых цепочек `.then()`
- `fetch` всегда оборачиваем в `try/catch`
- Перед чтением данных всегда проверяем `response.ok`
- Всегда обрабатываем состояние загрузки (блокируем кнопку, показываем спиннер)
- Ошибки всегда обрабатываем аккуратно — с видимым пользователю откликом

```js
async function fetchData(url, options = {}) {
	try {
		const response = await fetch(url, options);

		if (!response.ok) {
			throw new Error(`Server error: ${response.status}`);
		}

		return await response.json();
	} catch (err) {
		console.error('Fetch error:', err);
		throw err;
	}
}
```

Сервер отвечает в формате JSON.
Ответ сервера — `{ success: true, data: {...} }` или `{ success: false, errors: {...} }`.

---

## Текст с оформлением

Поле с оформлением — свой модуль из прототипов, `modules/rich-text.js`
с панелью `format-bar`. WYSIWYG-библиотеки не используются.

Правила:

- Панель даёт только: абзац, H2, H3, цитату, жирный, курсив, подчёркнутый,
  зачёркнутый, код в строке, ссылку, маркированный и нумерованный списки, разделитель
- Вставленный контент вставляется как обычный текст
- HTML поля синхронизируется в скрытый `<input>` и отправляется вместе с формой
- Очистка в JS — удобство, а не защита: сервер чистит HTML
  по белому списку при каждом сохранении (`docs/specs/php.md`, «Редактор и контент»)
- Логику текста с оформлением не смешивать с формами и другими модулями

---

## Уведомления / всплывающие сообщения

Сообщения для пользователя (успех, ошибка, информация) идут через центральный модуль notify:

```js
// modules/notify.js

export function initNotify() {
	// при необходимости подготовить контейнер при инициализации
}

export function notify(message, type = 'info') {
	const container = getOrCreateContainer();
	const toast = createToast(message, type);
	container.appendChild(toast);

	setTimeout(() => toast.remove(), 4000);
}

function getOrCreateContainer() {
	let container = document.getElementById('notify-container');
	if (!container) {
		container = document.createElement('div');
		container.id = 'notify-container';
		container.setAttribute('aria-live', 'polite');
		container.setAttribute('aria-atomic', 'false');
		document.body.appendChild(container);
	}
	return container;
}

function createToast(message, type) {
	const toast = document.createElement('div');
	toast.className = `notify notify--${type}`;
	toast.textContent = message;
	toast.setAttribute('role', 'status');
	return toast;
}
```

Правила:

- Все уведомления на странице — через `notify.js`
- У контейнера уведомлений обязателен `aria-live="polite"`
- Всплывающие сообщения исчезают сами по таймауту
- Не используем `alert()`, `confirm()` и `prompt()`

---

## Обработка ошибок

Правила:

- Ошибки никогда не глушим молча — всегда логируем или показываем
- `console.error()` — для неожиданных технических ошибок
- Ошибки для пользователя — через `notify()` или отклик в самой форме
- Не показываем пользователю стек вызовов и внутренние ошибки
- Аккуратно обрабатываем сбои сети (нет интернета, сервер недоступен)

```js
// Так не делать:
try {
	await fetchData('/api/posts');
} catch (err) {
	// молча проигнорировано ← неправильно
}

// Правильно:
try {
	await fetchData('/api/posts');
} catch (err) {
	console.error('Failed to load posts:', err);
	notify('Не удалось загрузить записи. Попробуйте ещё раз.', 'error');
}
```

---

## Доступность в JS

Правила:

- Все динамически добавленные интерактивные элементы доступны с клавиатуры
- При открытии модального окна — переносим фокус внутрь
- При закрытии модального окна — возвращаем фокус на элемент, который его открыл
- Изменения состояния отражаем через `aria-expanded`, `aria-hidden`, `aria-modal`, `aria-busy`
- Динамические обновления контента объявляются через области `aria-live`
- Не убираем обводку фокуса из JS

```js
function openModal(modal, trigger) {
	modal.removeAttribute('hidden');
	modal.setAttribute('aria-modal', 'true');
	focusTrap.activate(modal); // удерживаем фокус внутри
	modal._trigger = trigger; // запоминаем триггер, чтобы вернуть фокус
}

function closeModal(modal) {
	modal.setAttribute('hidden', '');
	modal.removeAttribute('aria-modal');
	focusTrap.deactivate();
	if (modal._trigger) modal._trigger.focus(); // возвращаем фокус
}
```

---

## Что мы не используем

- Без jQuery
- Без фронтенд-фреймворков (React, Vue, Alpine и т. п.)
- Без TypeScript
- Без сборщиков, если это не решено явно
- Без `eval()`
- Без `document.write()`
- Без инлайн-обработчиков событий в HTML
- Без `var` — только `const` и `let`
- Без глобальных переменных — всё внутри модулей
- Без `alert()`, `confirm()`, `prompt()`

---

## Правила для ИИ

ИИ обязан:

- выносить каждую новую функциональность в отдельный файл модуля в `assets/js/modules/`
- экспортировать функцию `init*` из каждого модуля
- импортировать и вызывать все init-функции в `main.js`
- в начале каждой init-функции защищаться от отсутствующих DOM-элементов
- использовать `const` и `let` — никогда `var`
- использовать `async/await` для всех асинхронных операций
- оборачивать вызовы fetch в `try/catch`
- использовать атрибуты `data-*` как JS-хуки — не классы оформления
- синхронизировать ARIA-атрибуты состояния с изменениями состояния в JS
- оборачивать вызовы в `main.js` в `DOMContentLoaded`

ИИ не должен:

- писать логику прямо в `main.js`
- смешивать несколько фич в одном файле модуля
- использовать jQuery или любой JS-фреймворк
- использовать инлайн-обработчики событий в HTML
- использовать `var`
- использовать глобальные переменные вне модулей
- использовать `alert()`, `confirm()`, `prompt()`
- молча глушить ошибки
- использовать классы оформления как JS-селекторы для поведения
- переписывать несвязанные модули при реализации новой фичи

---

## Definition of Done

JavaScript готов, когда:

- каждая фича живёт в своём файле модуля в `assets/js/modules/`
- каждый модуль экспортирует функцию `init*`
- все модули импортированы и инициализированы в `main.js` внутри `DOMContentLoaded`
- модули защищены от отсутствующих элементов на страницах, где они не нужны
- везде `const` / `let` — никакого `var`
- нет глобальных переменных и логики вне модулей
- асинхронные операции используют `async/await` с `try/catch`
- ARIA-атрибуты обновляются синхронно с состоянием в JS
- все интерактивные элементы остаются доступны с клавиатуры
- `alert()`, `confirm()`, `prompt()` не используются
- HTML текста с оформлением синхронизируется в скрытый input до отправки формы
- код читаемый, прямолинейный и поддерживается вручную
