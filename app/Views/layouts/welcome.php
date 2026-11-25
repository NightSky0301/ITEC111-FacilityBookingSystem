<?php if ($w = session()->getFlashdata('welcome')): ?>
    <div class="wl-overlay" id="wlPop" role="status" aria-live="polite">
        <div class="wl-card pp-hi" style="--ms:2000ms">
            <span class="wl-ico"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
            <div class="wl-text"><small>Signed in</small><strong>Welcome back, <?= esc($w) ?>!</strong></div>
            <i class="wl-bar" aria-hidden="true"></i>
        </div>
    </div>
    <script>
        (function () {
            var pop = document.getElementById('wlPop'); if (!pop) return;
            document.documentElement.classList.add('wl-blur');
            setTimeout(function () {
                pop.classList.add('is-out'); document.documentElement.classList.remove('wl-blur');
                setTimeout(function () { pop.remove(); }, 250);
            }, 2000);
        })();
    </script>
<?php endif; ?>
