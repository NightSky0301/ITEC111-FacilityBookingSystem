<?php
/**
 * Messages after an action (saved, error, wrong password...) show as a pop-up in the middle of the screen.
 * The page behind is blurred and the pop-up closes by itself after 3 seconds. Included once, at the end of <body>.
 */
$popup = null;
if ($m = session()->getFlashdata('success')) {
    $popup = ['ok', 'bi-check-circle', 'Done', [(string) $m]];
} elseif ($m = session()->getFlashdata('error')) {
    $popup = ['bad', 'bi-exclamation-triangle', 'Something went wrong', [(string) $m]];
} elseif ($m = session()->getFlashdata('errors')) {
    $popup = ['bad', 'bi-exclamation-triangle', 'Please fix the following', array_map('strval', (array) $m)];
}
?>
<?php if ($popup): ?>
    <div class="wl-overlay pp-overlay" id="ppPop" role="<?= $popup[0] === 'ok' ? 'status' : 'alert' ?>" aria-live="assertive">
        <div class="wl-card pp-card pp-<?= $popup[0] ?>" style="--ms:3000ms">
            <span class="wl-ico"><i class="bi <?= $popup[1] ?>" aria-hidden="true"></i></span>
            <div class="wl-text">
                <small><?= esc($popup[2]) ?></small>
                <?php if (count($popup[3]) === 1): ?>
                    <strong><?= esc($popup[3][0]) ?></strong>
                <?php else: ?>
                    <ul><?php foreach ($popup[3] as $line): ?><li><?= esc($line) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>
            </div>
            <i class="wl-bar" aria-hidden="true"></i>
        </div>
    </div>
<?php endif; ?>
<script>
    /* window.athletixPopup('ok' | 'bad', 'Message') shows the same pop-up from JavaScript. The timer (3 seconds) is separate from the login greeting's. */
    window.athletixPopup = function (type, text, ms) {
        var old = document.getElementById('ppPop'); if (old) old.remove();
        var o = document.createElement('div'); o.className = 'wl-overlay pp-overlay'; o.id = 'ppPop'; o.setAttribute('role', type === 'ok' ? 'status' : 'alert');
        o.innerHTML = '<div class="wl-card pp-card pp-' + (type === 'ok' ? 'ok' : 'bad') + '" style="--ms:' + (ms || 3000) + 'ms"><span class="wl-ico"><i class="bi ' + (type === 'ok' ? 'bi-check-circle' : 'bi-exclamation-triangle') + '"></i></span><div class="wl-text"><small></small><strong></strong></div><i class="wl-bar"></i></div>';
        o.querySelector('small').textContent = type === 'ok' ? 'Done' : 'Something went wrong';
        o.querySelector('strong').textContent = text;
        document.body.appendChild(o); run(o, ms);
    };
    function run(pop, ms) {
        document.documentElement.classList.add('wl-blur');
        setTimeout(function () {
            pop.classList.add('is-out'); document.documentElement.classList.remove('wl-blur');
            setTimeout(function () { pop.remove(); }, 250);
        }, ms || 3000);
    }
    (function () { var p = document.getElementById('ppPop'); if (p) run(p, 3000); })();
</script>
