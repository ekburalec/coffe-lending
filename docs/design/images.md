# Изображения лендинга

Статус: 11 исходных изображений сгенерированы встроенным image_gen 24 ноября 2026 и экспортированы в 22 WebP-файла. Для корзины подготовлены ещё 18 WebP-миниатюр из фотографий меню. Экспорт через PHP GD, качество 82.
Визуальный стиль: [direction.md](direction.md). Продукты и рецептуры: [product.md](../product/product.md).

## Папки и имена файлов

Решение пользователя от 24 ноября 2026: фотографии группируются по секциям.
В каждой секции нумерация начинается с 1; имя файла `photo_1.webp`, `photo_2.webp`
и далее. Retina-версия сохраняет тот же номер и суффикс `@2x`.

| Секция | Папка | Фотографии |
|--------|-------|------------|
| Первый экран | assets/img/hero/ | photo_1.webp и photo_1@2x.webp |
| Меню | assets/img/menu/ | photo_1.webp … photo_9.webp, у каждой пара @2x |
| Пространство | assets/img/space/ | photo_1.webp и photo_1@2x.webp |
| Корзина | assets/img/order/ | photo_1.webp … photo_9.webp, у каждой пара @2x |

Номера в меню следуют порядку товаров на странице. Названия товаров и брифы
сохраняются в реестре ниже. Для секций без растровых изображений пустые папки не создаются.
Превью ссылки не относится к секциям: его служебный путь assets/img/og-image.jpg
сохранён по требованиям HTML и сборки.

## Производство и хранение

- Источник всех изображений: встроенный image_gen. Промпты ниже сохранены полностью.
- Никаких фотографий чужого бренда или фрагментов refs/ref.jpg на страницу не переносим.
- Результат генерации проверяется по рецептуре и брифу; запись о результате добавляется после создания.
- Файлы назначения: WebP, 1x и @2x; у каждого указан размер в пикселях. Пути соответствуют готовым файлам.
- Оригинал должен быть не меньше 2x по каждой стороне. При необходимости сначала получить больший
  оригинал или изменить согласованный размер, не выдавать увеличенный маленький файл за retina.
- Из оригинала кадрировать 2x; 1x получать уменьшением вдвое через PHP GD, качество около 82.
  Визуально проверить мелкую фактуру и отсутствие лишних артефактов сжатия.
- Исходные PNG генерации в проект не класть. Финальные изображения без прозрачности.
- Для HTML: width, height, alt, src на 1x и srcset с 1x/2x. Hero загружается приоритетно;
  изображения ниже первого экрана лениво. CSS не растягивает фото сверх размера 1x.
- Меню и цены всегда текстом в HTML, не частью фотографии. Нет текста поверх предметных фото.
- Размеры учитывают максимальную ширину в композиции из direction.md. Если композиция меняется,
  пересмотреть размер до экспорта, а не компенсировать размытость стилями.

## Общий стиль

Тёплый боковой дневной свет слева, матовая керамика, дерево орехового цвета,
натуральные оттенки еды. Предметы выглядят аппетитно, но без лишних ингредиентов,
сверхглянца, золота, дыма и театральных эффектов. Композиции из одного набора посуды.
Интерьер является иллюстрацией концепции, а не фотографией существующего заведения.

## Реестр

| Изображение | Путь 1x | Размер 1x | Размер 2x |
|-------------|---------|-----------|-----------|
| hero | assets/img/hero/photo_1.webp | 560×700 | 1120×1400 |
| americano | assets/img/menu/photo_1.webp | 480×360 | 960×720 |
| cappuccino | assets/img/menu/photo_2.webp | 480×360 | 960×720 |
| vanilla-raf | assets/img/menu/photo_3.webp | 480×360 | 960×720 |
| black-tea | assets/img/menu/photo_4.webp | 480×360 | 960×720 |
| sea-buckthorn-tea | assets/img/menu/photo_5.webp | 480×360 | 960×720 |
| berry-mint-tea | assets/img/menu/photo_6.webp | 480×360 | 960×720 |
| cheesecake | assets/img/menu/photo_7.webp | 480×360 | 960×720 |
| brownie | assets/img/menu/photo_8.webp | 480×360 | 960×720 |
| honey-cake | assets/img/menu/photo_9.webp | 480×360 | 960×720 |
| cafe-interior | assets/img/space/photo_1.webp | 720×450 | 1440×900 |

## hero

Файлы: `assets/img/hero/photo_1.webp` (560×700) и `assets/img/hero/photo_1@2x.webp` (1120×1400).
Где: Первый экран, правая часть; на mobile после текста и действий.
Alt: «Капучино и чизкейк на деревянном столе».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Vertical 4:5 composition. A matte ivory ceramic cup of cappuccino with simple leaf latte art, standing on a saucer, beside one modest slice of plain cheesecake on a small ceramic plate. No toppings. The cappuccino is the dominant subject. Warm blurred cafe background, generous breathing room above, no text space required because the headline lives outside this photograph.
```

## americano

Файлы: `assets/img/menu/photo_1.webp` (480×360) и `assets/img/menu/photo_1@2x.webp` (960×720).
Где: Меню, кофе.
Alt: «Американо в керамической чашке».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Horizontal 4:3 composition. One matte ivory ceramic cup containing black americano coffee on a matching saucer. Dark translucent coffee, a very thin natural coffee sheen, absolutely no milk, foam, latte art or garnish. Three-quarter angle, cup entirely visible.
```

## cappuccino

Файлы: `assets/img/menu/photo_2.webp` (480×360) и `assets/img/menu/photo_2@2x.webp` (960×720).
Где: Меню, кофе.
Alt: «Капучино с молочной пеной».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Horizontal 4:3 composition. One matte ivory ceramic cup of cappuccino with smooth white milk microfoam and a simple leaf latte art pattern on a matching saucer. Natural coffee brown edge, no cocoa dust, cinnamon, whipped cream, syrup or decorative garnish. Three-quarter angle.
```

## vanilla-raf

Файлы: `assets/img/menu/photo_3.webp` (480×360) и `assets/img/menu/photo_3@2x.webp` (960×720).
Где: Меню, кофе.
Alt: «Ванильный раф в стеклянном стакане».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Horizontal 4:3 composition. One clear heat-resistant glass filled with pale creamy vanilla raf coffee, uniformly mixed espresso and cream with a thin fine foam. Smooth light beige liquid, no dramatic layers. No vanilla pods, ice cream, whipped cream, sprinkles or visible garnish. Three-quarter angle.
```

## black-tea

Файлы: `assets/img/menu/photo_4.webp` (480×360) и `assets/img/menu/photo_4@2x.webp` (960×720).
Где: Меню, чай.
Alt: «Чёрный чай в прозрачной чашке».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Horizontal 4:3 composition. One clear heat-resistant glass cup on a plain saucer filled with clear amber black tea. No lemon, mint, berries, sugar cubes, milk or garnish. Clean tea surface, warm light passing through the glass, three-quarter angle.
```

## sea-buckthorn-tea

Файлы: `assets/img/menu/photo_5.webp` (480×360) и `assets/img/menu/photo_5@2x.webp` (960×720).
Где: Меню, чай.
Alt: «Облепиховый чай с апельсином».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Horizontal 4:3 composition. One clear heat-resistant glass mug of orange-amber black tea with natural sea buckthorn berries and one modest orange slice immersed in the drink. Plausible rustic brewed tea rather than a smoothie. No mint, cinnamon, star anise, ice or other ingredients. Three-quarter angle.
```

## berry-mint-tea

Файлы: `assets/img/menu/photo_6.webp` (480×360) и `assets/img/menu/photo_6@2x.webp` (960×720).
Где: Меню, чай.
Alt: «Ягодный чай с малиной, смородиной и мятой».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Horizontal 4:3 composition. One clear heat-resistant glass mug of deep ruby black tea with a few raspberries, blackcurrants and small mint leaves in the drink. Realistic clear brewed tea, not a thick juice or cocktail. No orange, lemon, ice, cinnamon or other ingredients. Three-quarter angle.
```

## cheesecake

Файлы: `assets/img/menu/photo_7.webp` (480×360) и `assets/img/menu/photo_7@2x.webp` (960×720).
Где: Меню, десерты.
Alt: «Кусочек чизкейка на светлой тарелке».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Horizontal 4:3 composition. One modest triangular slice of plain creamy cheesecake with a thin crumbly baked base on a matte ivory plate. Smooth pale cheese filling, realistic cut surface, no berries, chocolate, caramel, mint or other toppings. No utensils. Three-quarter angle.
```

## brownie

Файлы: `assets/img/menu/photo_8.webp` (480×360) и `assets/img/menu/photo_8@2x.webp` (960×720).
Где: Меню, десерты.
Alt: «Шоколадный брауни с грецким орехом».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Horizontal 4:3 composition. One square chocolate brownie on a matte ivory plate, dense moist dark chocolate interior, slightly crackled top and visible small walnut pieces baked inside. No ice cream, sauce, fruit, mint, powdered sugar or other toppings. No utensils. Three-quarter angle.
```

## honey-cake

Файлы: `assets/img/menu/photo_9.webp` (480×360) и `assets/img/menu/photo_9@2x.webp` (960×720).
Где: Меню, десерты.
Alt: «Кусочек медовика со сметанным кремом».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial food photograph for a warm city coffee shop. Soft warm window light from the upper left, natural restrained colors, deep espresso shadows, matte walnut table and a quiet warm beige background. Realistic food texture and plausible proportions, subtle depth of field, refined but approachable. No lettering, labels, logos, watermarks, price tags, people, hands, extra utensils or unrelated food. Keep the complete subject within the central 80 percent of the frame with room for responsive cropping. No added decorative ingredients. Horizontal 4:3 composition. One modest triangular slice of honey cake on a matte ivory plate, clearly visible thin golden honey cake layers alternating with pale sour cream filling, fine honey cake crumbs on top. No nuts, berries, flowers, caramel or other toppings. No utensils. Three-quarter angle.
```

## cafe-interior

Файлы: `assets/img/space/photo_1.webp` (720×450) и `assets/img/space/photo_1@2x.webp` (1440×900).
Где: Блок о пространстве, широкое изображение.
Alt: «Столики и общий стол у окна в кофейне».
Источник: встроенный image_gen; создано 24 ноября 2026, осмотрено после генерации.
Прозрачность: нет. Проверка: предмет целиком, естественная геометрия, содержимое по рецептуре.

Полный промпт:

```text
Create a photorealistic editorial interior photograph of an imagined approachable city coffee shop. Horizontal 8:5 composition, warm natural afternoon window light, walnut wood, matte cream plaster, deep brown upholstery, restrained copper details. Show small separate tables for two and a communal table near a window with visible ordinary power sockets along the wall. Plausible spatial layout, comfortable scale, uncluttered tables. No people, readable signs, logos, menus, brands, pastries, text or watermarks. No gold ornament, luxury hotel styling, neon or glass partitions. Architectural perspective with straight vertical lines and natural materials, no excessive wide-angle distortion.
```

## Превью ссылки

Отдельный итоговый файл: assets/img/og-image.jpg, 1200×630.
Шаблон: docs/design/og-image/og-image.html; сборка по [og-image/README.md](og-image/README.md).
На этапе реализации перенести палитру и типографику направления в шаблон.
Композиция: название «Вкусный повод», подпись «Кофе, чай и десерты · Екатеринбург»,
фотография из hero справа; текст создаётся в HTML-шаблоне, а не генерацией внутри растра.
На OG не помещаем мелкие составы, цены, вымышленные отзывы и кнопку реальной покупки.
Retina-пара для OG не требуется: это отдельный формат по правилам проекта.

## Проверка готовых изображений

- [x] Созданы 11 изображений и 22 WebP-файла с точными размерами 1x/2x.
- [x] На фото каждый товар соответствует названию, составу и описанию в меню.
- [x] Цвет света, посуда и фон согласованы; нет случайных подписей и водяных знаков.
- [x] Нет артефактов краёв, искажённых чашек, лишних ручек и неестественной еды.
- [x] Hero и интерьер выдерживают нужное кадрирование на 320px и desktop.
- [x] Alt перенесён в HTML, изображения не содержат единственную копию значимого текста.
- [x] Создан отдельный JPG для OG; он проверен после сборки.
- [x] Реестр обновлён фактическими источниками и результатами создания.

## Фактические исходники генерации

Результат встроенного инструмента сохраняется вне проекта. В секционных папках assets/img находятся WebP; служебное OG-превью хранится отдельно в корне assets/img.
Hero экспортирован 560×700 и 1120×1400, интерьер 720×450 и 1440×900: размеры уточнены
по нативному разрешению генерации, без искусственного увеличения. Пропорции и композиция
сохранены. В CSS размеры отображения ограничены этими значениями.
Для hero, americano и cappuccino к сохранённому промпту было добавлено требование одного
изображения высокого разрешения: соответственно не менее 1280×1600 и 960×720.
Для остальных промпты использованы без дополнений. Генератор вернул фактические размеры ниже.

| Изображение | PNG-исходник в CODEX_HOME/generated_images | Размер исходника |
|-------------|------------------------------------------|------------------|
| hero | exec-1b641f63-3432-4042-a4ac-384757b209cd.png | 1122×1402 |
| americano | exec-4dee5c72-a56f-4dfb-ab7a-9740a02b5cbd.png | 1448×1086 |
| cappuccino | exec-2b9e9642-b9f2-4138-b0ee-04c74d4c67e2.png | 1448×1086 |
| vanilla-raf | exec-b3ad3e3d-66ee-4c73-8c12-8c2a4785af2b.png | 1448×1086 |
| black-tea | exec-d5059d04-792b-4cb0-965a-49de58883b70.png | 1448×1086 |
| sea-buckthorn-tea | exec-347c2fa0-d426-40bb-abb2-95e78f322349.png | 1448×1086 |
| berry-mint-tea | exec-e9ee2912-79e3-43d4-b939-15463af16a53.png | 1448×1086 |
| cheesecake | exec-ce5d5e7b-c82d-4215-9e89-209e9ff27a99.png | 1448×1086 |
| brownie | exec-24eb133a-a957-4346-b0dc-9651684d09e3.png | 1448×1086 |
| honey-cake | exec-0411475e-2abe-4286-8fb9-4c85867e7e6c.png | 1448×1086 |
| cafe-interior | exec-d1be4d7d-b876-4628-aa86-9ebff05e1228.png | 1586×992 |


## Миниатюры корзины

Производные изображения, без новой генерации. Источник каждой миниатюры:
соответствующая фотография меню @2x. Центральное квадратное кадрирование,
экспорт 160×160, затем уменьшение вдвое до 80×80 через PHP GD, WebP качество 82.
Номера совпадают с порядком меню; alt равен названию товара. Изображения показываются
в строках боковой корзины, mobile размер отображения 64×64.

| Товар | Источник | Файл 1x, 80×80 | Файл 2x, 160×160 |
|-------|----------|----------------|------------------|
| Американо | assets/img/menu/photo_1@2x.webp | assets/img/order/photo_1.webp | assets/img/order/photo_1@2x.webp |
| Капучино | assets/img/menu/photo_2@2x.webp | assets/img/order/photo_2.webp | assets/img/order/photo_2@2x.webp |
| Ванильный раф | assets/img/menu/photo_3@2x.webp | assets/img/order/photo_3.webp | assets/img/order/photo_3@2x.webp |
| Чёрный чай | assets/img/menu/photo_4@2x.webp | assets/img/order/photo_4.webp | assets/img/order/photo_4@2x.webp |
| Облепиховый с апельсином | assets/img/menu/photo_5@2x.webp | assets/img/order/photo_5.webp | assets/img/order/photo_5@2x.webp |
| Ягодный с мятой | assets/img/menu/photo_6@2x.webp | assets/img/order/photo_6.webp | assets/img/order/photo_6@2x.webp |
| Чизкейк | assets/img/menu/photo_7@2x.webp | assets/img/order/photo_7.webp | assets/img/order/photo_7@2x.webp |
| Шоколадный брауни | assets/img/menu/photo_8@2x.webp | assets/img/order/photo_8.webp | assets/img/order/photo_8@2x.webp |
| Медовик | assets/img/menu/photo_9@2x.webp | assets/img/order/photo_9.webp | assets/img/order/photo_9@2x.webp |

## Миниатюры текущего og:image для админки

Производные существующего og-image.jpg без изменения содержания: assets/img/og-image.webp
600×315 и assets/img/og-image@2x.webp 1200×630. Используются в SEO-панели с srcset 1x/2x.
Обновляются вместе с JPG через bin/build-og-image.php. Новая иллюстрация не создавалась.


## Портреты для демо-отзывов

По поручению пользователя, встроенный image_gen, три отдельных генерации.
Это вымышленные персонажи, а не фотографии посетителей; на карточках есть подпись.
Финальные файлы: assets/img/reviews/demo-{anna,mikhail,elena}.webp (64×64)
и соответствующие @2x.webp (128×128). Уменьшение через PHP GD, WebP 88;
оригиналы PNG 1254×1254 остались в CODEX_HOME/generated_images.

Общий промпт: “Use case: photorealistic-natural. Asset type: circular avatar for an
explicitly fictional demo cafe review. ONE square natural head-and-shoulders photo.
Face centered with breathing room for circular cropping. Warm blurred cafe background,
soft daylight, natural skin texture. No text, logos, watermark or collage. Synthetic
character, not a real customer.” Варианты персонажей: Anna — woman about 30,
shoulder-length dark brown hair, oatmeal sweater; Mikhail — man about 34, short dark
hair, subtle beard, charcoal shirt; Elena — woman about 45, chin-length auburn hair,
thin round glasses, terracotta blouse. Полные промпты сохранены в docs/design/review-portraits-prompts.md.
