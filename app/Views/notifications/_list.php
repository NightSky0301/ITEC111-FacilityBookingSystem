<?php
/**
 * The list of notifications, shared by the pop-up and the full page.
 * Needs $notifications (array of rows).
 */
$iconFor = static function (string $title): array {
    $t = strtolower($title);
    if (str_contains($t, 'approved'))  return ['bi-check-circle', 'is-ok'];
    if (str_contains($t, 'rejected') || str_contains($t, 'cancelled')) return ['bi-x-circle', 'is-bad'];
    if (str_contains($t, 'new'))       return ['bi-inbox', 'is-new'];
    return ['bi-bell', ''];
};
?>
<?php if (empty($notifications)): ?>
    <div class="empty"><i class="bi bi-bell"></i><strong>You are all caught up</strong>Booking updates will appear here.</div>
<?php endif; ?>
<?php foreach ($notifications as $n): [$icon, $tone] = $iconFor($n['title']);
    // Split "… was approved. Note: text" so the note can sit in its own box.
    $msg = (string) $n['message']; $noteLabel = ''; $noteText = '';
    if (preg_match('/^(.*?)\s+(Note|Reason):\s*(.+)$/s', $msg, $m)) { $msg = $m[1]; $noteLabel = $m[2]; $noteText = $m[3]; }
?>
    <a href="<?= site_url('notifications/' . (int) $n['id'] . '/read') ?>" class="note <?= $n['is_read'] ? '' : 'is-unread' ?>">
        <span class="note-icon <?= $tone ?>"><i class="bi <?= $icon ?>"></i></span>
        <span class="flex-fill">
            <span class="d-flex justify-content-between gap-3">
                <span class="note-title"><?= esc($n['title']) ?></span>
                <span class="note-time"><?= esc(fmt_dt($n['created_at'])) ?></span>
            </span>
            <span class="d-block"><?= esc($msg) ?></span>
            <?php if ($noteText !== ''): ?>
                <span class="note-extra"><b><?= esc($noteLabel) ?></b><span><?= esc($noteText) ?></span></span>
            <?php endif; ?>
        </span>
    </a>
<?php endforeach; ?>
