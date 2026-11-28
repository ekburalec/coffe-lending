<div class="faq__list">
<?php foreach ($faq as $item): ?><details class="faq__item" <?= visualManagedAttributes('faq', $visualPreview, $item['id']) ?>><summary class="faq__question"><?= e($item['question']) ?></summary><p class="faq__answer"><?= e($item['answer']) ?></p></details><?php endforeach; ?>
</div>
