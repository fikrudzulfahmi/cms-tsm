<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0D47A1">
    <title><?= $seo['title'] ?? 'Jurusan Teknik Sepeda Motor — SMKS Islam 1 Blitar' ?></title>
    <meta name="description" content="<?= e($seo['description'] ?? '') ?>">
    <link rel="canonical" content="<?= $seo['canonical'] ?? url()->current() ?>" />

    <!-- Open Graph -->
    <meta property="og:type" content="<?= $seo['og_type'] ?? 'website' ?>">
    <meta property="og:title" content="<?= e($seo['title'] ?? '') ?>">
    <meta property="og:description" content="<?= e($seo['description'] ?? '') ?>">
    <meta property="og:url" content="<?= $seo['canonical'] ?? url()->current() ?>">
    <?php if (! empty($seo['og_image'])): ?>
    <meta property="og:image" content="<?= e($seo['og_image']) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <?php endif; ?>

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($seo['title'] ?? '') ?>">
    <meta name="twitter:description" content="<?= e($seo['description'] ?? '') ?>">
    <?php if (! empty($seo['og_image'])): ?>
    <meta name="twitter:image" content="<?= e($seo['og_image']) ?>">
    <?php endif; ?>

    <!-- JSON-LD -->
    <?php if (! empty($seo['json_ld'])): ?>
    <script type="application/ld+json"><?= $seo['json_ld'] ?></script>
    <?php endif; ?>

    @vite(['resources/js/public/main.ts'])
</head>
<body>
    <div id="app">
        {{-- konten semantik ringkas untuk crawler & pengguna tanpa JS (spec 5.4 poin 3) --}}
        <main>
            <h1><?= e($seo['h1'] ?? ($seo['title'] ?? 'Jurusan Teknik Sepeda Motor SMKS Islam 1 Blitar')) ?></h1>
            <?php if (! empty($seo['fallback_text'])): ?>
            <p><?= e($seo['fallback_text']) ?></p>
            <?php endif; ?>
            <?php if (! empty($seo['fallback_body'])): ?>
            <article><?= $seo['fallback_body'] ?></article>
            <?php endif; ?>
        </main>
    </div>
    @routes
    <script>
        window.__INITIAL__ = <?= $initial ?? '{}' ?>;
    </script>
</body>
</html>
