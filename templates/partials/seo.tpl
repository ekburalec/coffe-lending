<title><?= e($seo['title']) ?></title>
<meta name="description" content="<?= e($seo['description']) ?>">
<?php if ($seo['noindex']): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<?php if ($seo['canonical']): ?><link rel="canonical" href="<?= e($seo['canonical']) ?>"><meta property="og:url" content="<?= e($seo['canonical']) ?>"><?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($contacts['name']) ?>">
<meta property="og:locale" content="ru_RU">
<meta property="og:title" content="<?= e($seo['og_title']) ?>">
<meta property="og:description" content="<?= e($seo['og_description']) ?>">
<meta property="og:image" content="<?= e($seo['image_url']) ?>">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= e($seo['og_title']) ?>">
<meta name="twitter:card" content="summary_large_image">
<script type="application/ld+json"><?= $seo['json_ld'] ?></script>

<link rel="describedby" href="/llms.txt" type="text/plain">
