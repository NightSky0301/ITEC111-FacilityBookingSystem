<?php if (session()->getFlashdata('success')): ?>
    <div class="flash flash-ok" role="status">
        <i class="bi bi-check-circle"></i>
        <div class="flash-text"><?= esc(session()->getFlashdata('success')) ?></div>
        <button type="button" class="flash-close" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="flash flash-bad" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <div class="flash-text"><?= esc(session()->getFlashdata('error')) ?></div>
        <button type="button" class="flash-close" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('errors')): ?>
    <div class="flash flash-bad" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <div class="flash-text">
            <strong>Please fix the following:</strong>
            <ul>
                <?php foreach ((array) session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="flash-close" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
    </div>
<?php endif; ?>
