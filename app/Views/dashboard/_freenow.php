<?php /** Home page: courts that are free at this moment, then the most booked courts this month. Both refresh by themselves. */ ?>
<section class="fn" aria-labelledby="fnTitle">
    <div class="fn-head">
        <h2 id="fnTitle"><span class="fn-dot" aria-hidden="true"></span> Free right now</h2>
        <a class="fn-all" href="<?= site_url('facilities') ?>">Browse all courts <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="fn-row" tabindex="0" aria-label="Courts free now" data-shelf="free"><?= view('dashboard/_freenow_cards', ['freeNow' => $freeNow]) ?></div>
</section>

<section class="fn" aria-labelledby="tpTitle">
    <div class="fn-head">
        <h2 id="tpTitle"><i class="bi bi-trophy tp-ico" aria-hidden="true"></i> Top bookings <small class="fn-sub">Most booked this month</small></h2>
    </div>
    <div class="fn-row" tabindex="0" aria-label="Most booked courts this month" data-shelf="top"><?= view('dashboard/_top_cards', ['top' => $top]) ?></div>
</section>
