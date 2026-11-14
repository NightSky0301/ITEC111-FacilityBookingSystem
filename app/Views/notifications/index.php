<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Notifications</h1>
        <p>Updates about your bookings.</p>
    </div>
    <?php if (! empty($notifications)): ?>
    <div class="page-actions">
        <form method="post" action="<?= site_url('notifications/read-all') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline-secondary">Mark all as read</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<div class="panel">
    <?= $this->include('notifications/_list') ?>
</div>
<?= $this->endSection() ?>
