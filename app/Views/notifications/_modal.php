<?php /** Content of the notifications pop-up. Needs $notifications and $unread. */ ?>
<div id="notifFeed" data-unread="<?= (int) $unread ?>">
    <div class="notif-toolbar">
        <span><?= $unread > 0 ? '<strong>' . (int) $unread . '</strong> unread' : 'No unread notifications' ?></span>
        <?php if ($unread > 0): ?>
            <form method="post" action="<?= site_url('notifications/read-all') ?>" data-notif-readall>
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2-all me-1"></i> Mark all as read</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="notif-list">
        <?= $this->include('notifications/_list') ?>
    </div>
</div>
