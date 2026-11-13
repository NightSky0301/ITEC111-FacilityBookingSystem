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
    <?= $this->renderSection('head') ?>
</head>
<body>
<?php
    $seg1    = service('uri')->getSegment(1);
    $seg2    = service('uri')->getSegment(2);
    $active  = static fn (string $a, ?string $b = null): string => ($seg1 === $a && ($b === null || $seg2 === $b)) ? 'active' : '';
    $unread  = unread_notifications();
    $name    = (string) session()->get('name');
    $initial = strtoupper(mb_substr($name, 0, 1)) ?: '?';
?>
<header class="site-header">
    <nav class="navbar navbar-expand-lg">
        <div class="container-xl d-flex flex-wrap align-items-center">
            <a class="brand" href="<?= site_url('dashboard') ?>">
                <span class="brand-mark"><i class="bi bi-trophy"></i></span>
                <span>Facility Booking</span>
            </a>

            <button class="nav-toggle ms-auto d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav site-nav me-auto">
                    <li class="nav-item"><a class="nav-link <?= $active('dashboard') ?>" href="<?= site_url('dashboard') ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active('calendar') ?>" href="<?= site_url('calendar') ?>">Calendar</a></li>
                    <?php if (is_admin()): ?>
                        <li class="nav-item"><a class="nav-link <?= $active('admin', 'bookings') ?>" href="<?= site_url('admin/bookings') ?>">Bookings</a></li>
                        <li class="nav-item"><a class="nav-link <?= $active('admin', 'facilities') ?>" href="<?= site_url('admin/facilities') ?>">Facilities</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link <?= $active('facilities') ?>" href="<?= site_url('facilities') ?>">Facilities</a></li>
                        <li class="nav-item"><a class="nav-link <?= $active('my-bookings') ?>" href="<?= site_url('my-bookings') ?>">My bookings</a></li>
                    <?php endif; ?>
                </ul>

                <div class="header-tools mt-2 mt-lg-0">
                    <?php if (! is_admin()): ?>
                        <a class="btn btn-sm btn-light-on-dark" href="<?= site_url('bookings/new') ?>"><i class="bi bi-plus-lg"></i> New booking</a>
                    <?php endif; ?>

                    <a class="icon-btn" href="<?= site_url('notifications') ?>" aria-label="Notifications<?= $unread ? ', ' . $unread . ' unread' : '' ?>">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread > 0): ?><span class="dot-count"><?= $unread > 99 ? '99+' : $unread ?></span><?php endif; ?>
                    </a>

                    <div class="dropdown">
                        <button class="user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                            <span class="avatar"><?= esc($initial) ?></span>
                            <span class="d-none d-xl-block">
                                <span class="user-name"><?= esc($name) ?></span>
                                <span class="user-role"><?= esc(session()->get('role')) ?></span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text"><strong><?= esc($name) ?></strong><br><small class="text-muted"><?= esc(session()->get('email')) ?></small></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= site_url('notifications') ?>"><i class="bi bi-bell me-2"></i>Notifications</a></li>
                            <li><a class="dropdown-item text-danger" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<main class="page">
    <?= $this->include('layouts/alerts') ?>
    <?= $this->renderSection('content') ?>
</main>

<script src="<?= base_url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
