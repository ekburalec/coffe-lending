<section class="admin__seo-panel" id="seo-texts" aria-labelledby="seo-texts-title">
    <h1 class="admin__title" id="seo-texts-title">Тексты для поисковиков и превью ссылки</h1>
    <p class="admin__lead">Заголовки и описания для поиска и публикации ссылки. Подсказки по длине — ориентир для краткого текста, а не обязательный лимит. Эти тексты редактируются вручную.</p>
    <form class="admin__form" method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="section_part" value="texts">
        <?php render('admin/fields', ['fields' => $seoTextFields]); ?>
        <button class="admin__button" type="submit">Сохранить тексты</button>
    </form>
</section>
<section class="admin__seo-panel" id="seo-preview" aria-labelledby="seo-preview-title">
    <h2 class="admin__title" id="seo-preview-title">Картинка превью ссылки</h2>
    <p class="admin__lead">Изображение для публикации ссылки на сайт. Обновление сохранённого превью во внешних сервисах может происходить с задержкой.</p>
    <img class="admin__seo-preview" src="<?= e($seoPreview['src']) ?>" srcset="<?= e($seoPreview['src']) ?> 1x, <?= e($seoPreview['srcset_2x']) ?> 2x" width="600" height="315" alt="<?= e($seoPreview['alt']) ?>">
    <form class="admin__form" method="post" enctype="multipart/form-data" autocomplete="off"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="section_part" value="preview"><input type="hidden" name="image" value="<?= e($seoPreview['image']) ?>">
        <label class="admin__field" for="seo-upload">Новая картинка (необязательно)<input class="admin__input" id="seo-upload" type="file" name="image_upload" accept="image/jpeg,image/png,image/webp" aria-describedby="seo-upload-hint seo-image-error" aria-invalid="<?= isset($errors['image']) ? 'true' : 'false' ?>"></label>
        <p class="admin__hint" id="seo-upload-hint">JPG, PNG или WebP, не меньше 1200×630 пикселей, до 10 МБ. Лишнее обрежется по краям. Для сайта сохраняется JPG 1200×630.</p>
        <span class="admin__message" id="seo-image-error"><?= e($errors['image'] ?? '') ?></span>
        <?php render('admin/fields', ['fields' => $seoImageFields]); ?>
        <button class="admin__button" type="submit">Сохранить превью</button>
    </form>
</section>
