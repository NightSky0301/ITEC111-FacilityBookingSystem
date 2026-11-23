<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Athletix') ?></title>
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/fraunces-600.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link href="<?= base_url('assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <script>(function(){try{var t=localStorage.getItem('athletix-theme');if(t!=='dark'&&t!=='light'){t=window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}var d=document.documentElement;d.setAttribute('data-theme',t);d.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/dark.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-split">
    <aside class="auth-art">
        <a class="brand brand-hero" href="<?= site_url('/') ?>" aria-label="Athletix"><span class="brand-word">Athleti<b>x</b></span></a>
        <div>
            <h2>Reserve your court in minutes.</h2>
            <p>Basketball, volleyball, pickleball and more. See which courts are free, send a request, and get notified when it is approved.</p>
        </div>
        <small style="color: rgba(255,255,255,.55)">Requests are reviewed by the facility manager.</small>
    </aside>

    <section class="auth-panel">
        <div class="auth-bar">
            <a class="auth-back" href="<?= site_url('/') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Back</span></a>
            <button type="button" class="auth-theme" data-theme-toggle role="switch" aria-checked="false" aria-label="Dark mode" title="Dark / light mode"><i class="bi bi-moon-stars" aria-hidden="true"></i><i class="bi bi-sun" aria-hidden="true"></i></button>
        </div>
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
<script src="<?= base_url('assets/js/theme.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
