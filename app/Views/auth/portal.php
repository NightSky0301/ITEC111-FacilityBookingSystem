<?php
/** Login + Create account on one page. Switching between them slides the two halves past each other. */
$isReg = ($mode ?? 'login') === 'register';
$mode  = $isReg ? 'register' : 'login';
$brand = 'Athletix';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isReg ? 'Create an account' : 'Log in' ?> · <?= esc($brand) ?></title>
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/fraunces-600.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link href="<?= base_url('assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <script>(function(){try{var t=localStorage.getItem('athletix-theme');if(t!=='dark'&&t!=='light'){t=window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}var d=document.documentElement;d.setAttribute('data-theme',t);d.setAttribute('data-bs-theme',t);}catch(e){}})();</script>
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/dark.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-split auth-stage <?= $isReg ? 'is-register' : '' ?>" id="authStage" data-login-url="<?= site_url('login') ?>" data-register-url="<?= site_url('register') ?>">
    <aside class="auth-art">
        <a class="brand brand-hero" href="<?= site_url('/') ?>" aria-label="<?= esc($brand) ?>"><span class="brand-word">Athleti<b>x</b></span></a>
        <div class="art-copies">
            <div class="art-copy" data-for="login" <?= $isReg ? 'inert' : '' ?>>
                <h2>Reserve your court in minutes.</h2>
                <p>Basketball, volleyball, pickleball and more. See which courts are free, send a request, and get notified when it is approved.</p>
            </div>
            <div class="art-copy" data-for="register" <?= $isReg ? '' : 'inert' ?>>
                <h2>Join and start booking today.</h2>
                <p>Create a free account, pick a court and a time, and we will email you as soon as your request is approved.</p>
            </div>
        </div>
        <small style="color: rgba(255,255,255,.55)">Requests are reviewed by the facility manager.</small>
    </aside>

    <section class="auth-panel">
        <div class="auth-bar">
            <a class="auth-back" href="<?= site_url('/') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Back</span></a>
            <button type="button" class="auth-theme" data-theme-toggle role="switch" aria-checked="false" aria-label="Dark mode" title="Dark / light mode"><i class="bi bi-moon-stars" aria-hidden="true"></i><i class="bi bi-sun" aria-hidden="true"></i></button>
        </div>
        <div class="auth-box" data-for="login" <?= $isReg ? 'inert' : '' ?>>
            <a class="auth-logo-link" href="<?= site_url('/') ?>" aria-label="<?= esc($brand) ?>">
                <img class="auth-logo" src="<?= base_url('assets/img/logo-lg.png') ?>" alt="" width="120" height="120">
            </a>
            <?php if (! $isReg) { echo $this->include('layouts/alerts'); } ?>
            <?= $this->include('auth/_login') ?>
        </div>
        <div class="auth-box" data-for="register" <?= $isReg ? '' : 'inert' ?>>
            <a class="auth-logo-link" href="<?= site_url('/') ?>" aria-label="<?= esc($brand) ?>">
                <img class="auth-logo" src="<?= base_url('assets/img/logo-lg.png') ?>" alt="" width="120" height="120">
            </a>
            <?php if ($isReg) { echo $this->include('layouts/alerts'); } ?>
            <?= $this->include('auth/_register') ?>
        </div>
    </section>
</div>
<script src="<?= base_url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/theme.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<script src="<?= base_url('assets/js/register.js') ?>"></script>
<script src="<?= base_url('assets/js/auth-slide.js') ?>"></script>
</body>
</html>
