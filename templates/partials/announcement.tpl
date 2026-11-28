<aside <?= visualManagedAttributes('announcements', $visualPreview) ?> class="announcement-bar" data-announcement-bar aria-label="Информация кофейни" tabindex="0">
    <p class="sr-only"><?= e(implode('. ', $announcements)) ?></p>
    <div class="announcement-bar__viewport"><div class="announcement-bar__track">
    <?php foreach ([false, true] as $duplicate): ?><ul class="announcement-bar__group <?= $duplicate ? 'announcement-bar__group--duplicate' : '' ?>" aria-hidden="true">
    <?php foreach ($announcements as $text): ?><li><?= e($text) ?></li><?php endforeach; ?>
    </ul><?php endforeach; ?>
    </div></div>
</aside>
