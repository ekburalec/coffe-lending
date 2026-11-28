<?php foreach ($fields as $field): ?>
<div class="admin__field <?= (!empty($field['wide']) || in_array($field['type'], ['textarea', 'image', 'url'], true)) ? 'admin__field--wide' : '' ?> <?= $field['type'] === 'checkbox' ? 'admin__field--checkbox' : '' ?>" <?= !empty($field['count']) ? 'data-character-field' : '' ?>>
<label for="<?= e($field['id']) ?>"><?= e($field['label']) ?></label>
<?php if ($field['type'] === 'textarea'): ?>
<textarea class="admin__input" id="<?= e($field['id']) ?>" name="<?= e($field['name']) ?>" rows="<?= e($field['rows'] ?? 5) ?>" maxlength="<?= e($field['max'] ?? 5000) ?>" <?= !empty($field['required']) ? 'required' : '' ?> aria-invalid="<?= $field['error'] ? 'true' : 'false' ?>" aria-describedby="<?= e($field['described_by']) ?>"><?= e($field['value']) ?></textarea>
<?php elseif ($field['type'] === 'select'): ?>
<select class="admin__input" id="<?= e($field['id']) ?>" name="<?= e($field['name']) ?>" aria-describedby="<?= e($field['described_by']) ?>" aria-invalid="<?= $field['error'] ? 'true' : 'false' ?>"><?php foreach ($field['options'] ?? [] as $value => $label): ?><option value="<?= e($value) ?>" <?= (string) $field['value'] === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
<?php elseif ($field['type'] === 'image'): ?>
<?php if (!empty($field['preview'])): ?><img class="admin__preview" src="<?= e($field['preview']['src']) ?>" srcset="<?= e($field['preview']['src']) ?> 1x, <?= e($field['preview']['retina']) ?> 2x" width="<?= e($field['preview']['width']) ?>" height="<?= e($field['preview']['height']) ?>" alt="<?= e($field['preview']['alt']) ?>"><?php endif; ?>
<input class="admin__input" id="<?= e($field['id']) ?>" name="<?= e($field['name']) ?>" value="<?= e($field['value']) ?>" readonly aria-describedby="<?= e($field['described_by']) ?>">
<label for="upload-<?= e($field['id']) ?>">Загрузить новую фотографию (JPG, PNG, WebP, до 10 МБ)</label><input class="admin__input admin__file-input" id="upload-<?= e($field['id']) ?>" type="file" name="<?= e($field['name']) ?>_upload" accept="image/jpeg,image/png,image/webp" aria-describedby="<?= e($field['described_by']) ?>">
<?php elseif ($field['type'] === 'checkbox'): ?>
<input id="<?= e($field['id']) ?>" type="checkbox" name="<?= e($field['name']) ?>" value="1" <?= $field['value'] ? 'checked' : '' ?> aria-describedby="<?= e($field['described_by']) ?>">
<?php else: ?>
<input class="admin__input" id="<?= e($field['id']) ?>" name="<?= e($field['name']) ?>" type="<?= e($field['type']) ?>" value="<?= e($field['value']) ?>" <?= !empty($field['required']) ? 'required' : '' ?> <?= $field['type'] === 'number' ? 'min="' . e($field['min'] ?? 0) . '" max="' . e($field['max'] ?? 1000000) . '" step="1"' : (in_array($field['type'], ['text', 'url', 'tel'], true) ? 'maxlength="' . e($field['max'] ?? 5000) . '"' : '') ?> aria-describedby="<?= e($field['described_by']) ?>" aria-invalid="<?= $field['error'] ? 'true' : 'false' ?>">
<?php endif; ?>
<?php if (!empty($field['hint'])): ?><span class="admin__hint" id="<?= e($field['hint_id']) ?>"><?= e($field['hint']) ?><?php if (!empty($field['count'])): ?> Сейчас: <span data-character-count><?= e($field['length']) ?></span>.<?php endif; ?></span><?php endif; ?>
<span class="admin__hint admin__message" id="<?= e($field['error_id']) ?>"><?= e($field['error']) ?></span>
</div>
<?php endforeach; ?>
