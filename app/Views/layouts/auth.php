<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Athletix') ?></title>
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/fraunces-600.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link href="<?= base_url('assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-split">
    <aside class="auth-art">
        <a class="brand brand-hero" href="<?= site_url('/') ?>" aria-label="Athletix"><span class="brand-logo brand-logo-hero"><img src="<?= base_url('assets/img/logo-lg.png') ?>" alt="" width="72" height="72"></span><span class="brand-word">Athleti<b>x</b></span></a>
        <div>
            <h2>Reserve your court in minutes.</h2>
            <p>Basketball, volleyball, pickleball and more. See which courts are free, send a request, and get notified when it is approved.</p>
        </div>
        <small style="color: rgba(255,255,255,.55)">Requests are reviewed by the facility manager.</small>
    </aside>

    <section class="auth-panel">
        <div class="auth-box">
            <a class="auth-logo-link" href="<?= site_url('/') ?>" aria-label="<?= esc('Athletix') ?>">
                <img class="auth-logo" src="<?= base_url('assets/img/logo-lg.png') ?>" alt="" width="120" height="120">
            </a>
            <?= $this->include('layouts/alerts') ?>
            <?= $this->renderSection('content') ?>
        </div>
    </section>
</div>
<script src="<?= base_url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
