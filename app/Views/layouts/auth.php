<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Facility Booking') ?></title>
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/fraunces-600.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link href="<?= base_url('assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-split">
    <aside class="auth-art">
        <a class="brand" href="<?= site_url('/') ?>">
            <span class="brand-mark"><i class="bi bi-trophy"></i></span>
            <span>Facility Booking</span>
        </a>
        <div>
            <h2>Reserve your court in minutes.</h2>
            <p>Basketball, volleyball, pickleball and more. See which courts are free, send a request, and get notified when it is approved.</p>
        </div>
        <small style="color: rgba(255,255,255,.55)">Requests are reviewed by the facility manager.</small>
    </aside>

    <section class="auth-panel">
        <div class="auth-box">
            <a class="brand auth-brand-mobile" href="<?= site_url('/') ?>">
                <span class="brand-mark" style="color: var(--brass); border-color: var(--brass)"><i class="bi bi-trophy"></i></span>
                <span>Facility Booking</span>
            </a>
            <?= $this->include('layouts/alerts') ?>
            <?= $this->renderSection('content') ?>
        </div>
    </section>
</div>
<script src="<?= base_url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
