<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Athletix') ?></title>
    <link rel="preload" href="<?= base_url('assets/vendor/fonts/fraunces-600.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link href="<?= base_url('assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/fontawesome/css/fontawesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/fontawesome/css/solid.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <?= $this->renderSection('head') ?>
</head>
<body<?php if (session()->get('user_id')): ?> data-live-url="<?= site_url('notifications/pulse') ?>" data-live-stamp="<?= esc(live_stamp(), 'attr') ?>"<?php endif; ?>>
<?php
    $segs    = service('uri')->getTotalSegments();
    $seg1    = $segs >= 1 ? service('uri')->getSegment(1) : 'dashboard';   // "/" is the dashboard
    $seg2    = $segs >= 2 ? service('uri')->getSegment(2) : null;
    $active  = static fn (string $a, ?string $b = null): string => ($seg1 === $a && ($b === null || $seg2 === $b)) ? 'active' : '';
    $guest   = ! session()->get('user_id');

    // Which of Activities / Hotels / Fitness the current page belongs to (customers and guests).
    $section = $navSection ?? null;
    if ($section === null) {
        if ($seg1 === 'fitness' || ($seg1 === 'facilities' && $seg2 === 'gym')) {
            $section = 'fitness';
        } elseif ($seg1 === 'hotels') {
            $section = 'hotels';
        } elseif ($seg1 === 'facilities') {
            $section = 'activities';
        } elseif ($seg1 === 'bookings' && $seg2 === 'new') {
            $fid     = (int) service('request')->getGet('facility_id');
            $f       = $fid > 0 ? (new \App\Models\FacilityModel())->find($fid) : null;
            $section = $f ? (\App\Models\FacilityModel::isRoom($f) ? 'hotels' : ($f['type'] === 'gym' ? 'fitness' : 'activities')) : 'activities';
        }
    }
    $onSec = static fn (string $s): string => $section === $s ? 'active' : '';
    $unread  = unread_notifications();
    $name    = (string) session()->get('name');
    $initial = strtoupper(mb_substr($name, 0, 1)) ?: '?';

    // Customers keep only Dashboard in the bar; their other pages live in the account menu.
    $menu = [];
    if (! $guest && ! is_manager()) {
        $menu = [
            ['Calendar',    'calendar',    'bi-calendar3',        $active('calendar')],
            ['My bookings', 'my-bookings', 'bi-calendar2-check',  $active('my-bookings')],
        ];
    }
?>
<header class="site-header">
    <nav class="navbar navbar-expand-lg">
        <div class="container-xl">
            <a class="brand" href="<?= site_url('dashboard') ?>">
                <span class="brand-logo"><img src="<?= base_url('assets/img/logo.png') ?>" alt="" width="56" height="56"></span>
                <span class="brand-word">Athleti<b>x</b></span>
            </a>

            <button class="nav-toggle d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav site-nav<?= is_manager() ? '' : ' site-nav-pills' ?>">
                    <?php if (! $guest && is_manager()): ?><li class="nav-item"><a class="nav-link <?= $active('dashboard') ?>" href="<?= site_url('dashboard') ?>">Dashboard</a></li><?php endif; ?>
                    <?php if (! $guest && is_manager()): ?><li class="nav-item"><a class="nav-link <?= $active('calendar') ?>" href="<?= site_url('calendar') ?>">Calendar</a></li><?php endif; ?>
                    <?php if ($guest || ! is_manager()): ?>
                        <li class="nav-item"><a class="nav-link <?= $onSec('activities') ?>" href="<?= site_url('facilities') ?>">Activities</a></li>
                        <li class="nav-item"><a class="nav-link <?= $onSec('hotels') ?>" href="<?= site_url('hotels') ?>">Hotels</a></li>
                        <li class="nav-item"><a class="nav-link <?= $onSec('fitness') ?>" href="<?= site_url('fitness') ?>">Fitness</a></li>
                    <?php elseif (is_manager()): ?>
                        <li class="nav-item"><a class="nav-link <?= $active('manage', 'bookings') ?>" href="<?= site_url('manage/bookings') ?>">Bookings</a></li>
                        <?php if (is_admin()): ?>
                            <li class="nav-item"><a class="nav-link <?= $active('admin', 'facilities') ?>" href="<?= site_url('admin/facilities') ?>">Facilities</a></li>
                            <li class="nav-item"><a class="nav-link <?= $active('admin', 'users') ?>" href="<?= site_url('admin/users') ?>">Users</a></li>
                            <li class="nav-item"><a class="nav-link <?= $active('admin', 'archive') ?>" href="<?= site_url('admin/archive') ?>">Archive</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link <?= $active('facilities') ?: ($seg1 === 'bookings' && $seg2 === 'new' ? 'active' : '') ?>" href="<?= site_url('facilities') ?>">Facilities</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <div class="header-tools mt-2 mt-lg-0">
<?php if ($guest): ?>
                    <a class="login-pill" href="<?= site_url('login') ?>"><i class="bi bi-person-circle" aria-hidden="true"></i> Log in</a>
<?php else: ?>

                    <a class="icon-btn" href="<?= site_url('notifications') ?>" data-notif-open aria-label="Notifications<?= $unread ? ', ' . $unread . ' unread' : '' ?>">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread > 0): ?><span class="dot-count"><?= $unread > 99 ? '99+' : $unread ?></span><?php endif; ?>
                    </a>

                    <span class="tools-divider d-none d-lg-block" aria-hidden="true"></span>

                    <div class="dropdown">
                        <button class="user-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Account menu">
                            <span class="avatar"><?= esc($initial) ?></span>
                            <span class="user-meta">
                                <span class="user-name"><?= esc($name) ?></span>
                                <span class="user-role"><?= esc(session()->get('role')) ?></span>
                            </span>
                            <i class="bi bi-chevron-down user-chev" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end um-menu">
                            <div class="um-head">
                                <span class="um-avatar" aria-hidden="true"><?= esc($initial) ?></span>
                                <span class="um-who">
                                    <strong><?= esc($name) ?></strong>
                                    <small><?= esc(session()->get('email')) ?></small>
                                    <em><?= esc(session()->get('role')) ?></em>
                                </span>
                            </div>
                            <nav class="um-list" aria-label="Account">
                                <?php foreach ($menu as [$label, $path, $icon, $on]): ?>
                                    <a class="um-item <?= $on ?>" href="<?= site_url($path) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
                                        <span class="um-ico"><i class="bi <?= $icon ?>" aria-hidden="true"></i></span>
                                        <span class="um-label"><?= esc($label) ?></span>
                                        <i class="bi bi-chevron-right um-go" aria-hidden="true"></i>
                                    </a>
                                <?php endforeach; ?>
                                <?php if ($menu): ?><hr class="um-rule"><?php endif; ?>
                                <a class="um-item um-out" href="<?= site_url('logout') ?>">
                                    <span class="um-ico"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></span>
                                    <span class="um-label">Log out</span>
                                </a>
                            </nav>
                        </div>
                    </div>
<?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<main class="page">
    <?= $this->include('layouts/alerts') ?>
    <?= $this->renderSection('content') ?>
</main>

<?php if (! $guest): ?>
<!-- Notifications pop-up -->
<div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifModalTitle" aria-hidden="true" data-feed="<?= site_url('notifications/feed') ?>">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable notif-dialog">
        <div class="modal-content notif-modal">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="notif-head-icon"><i class="bi bi-bell"></i></span>
                    <div>
                        <h2 class="modal-title" id="notifModalTitle">Notifications</h2>
                        <div class="notif-sub">Updates about your bookings</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="notifBody"></div>
        </div>
    </div>
</div>

<?php endif; ?>

<?= $this->include('layouts/_confirm') ?>

<script src="<?= base_url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
