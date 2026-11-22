<?php
/** Home page: banner that cycles through the courts, with the search card that starts a booking. */
$slides = $slides ?? [];
if (empty($slides)) {
    $slides = [['img' => base_url('assets/img/courts/basketball-a.jpg')]];
}
?>
<section class="hx" aria-label="Find a court" aria-roledescription="carousel">
    <?php foreach ($slides as $i => $s): ?>
        <div class="hx-photo <?= $i === 0 ? 'is-on' : '' ?>" data-src="<?= esc($s['img'], 'attr') ?>" style="background-image:url('<?= esc($s['img'], 'attr') ?>')" role="group" aria-roledescription="slide" aria-label="Photo <?= $i + 1 ?> of <?= count($slides) ?>"></div>
    <?php endforeach; ?>
    <div class="hx-copy">
        <h1 class="hx-title">Where are you playing today?</h1>
        <p class="hx-sub"><?= (int) $courtTotal ?> courts and rooms across <?= count($activities) ?> activities. See what is free and book in a minute.</p>
    </div>
    <?php if (count($slides) > 1): ?>
        <div class="hx-dots" role="group" aria-label="Choose a court photo">
            <?php foreach ($slides as $i => $s): ?>
                <button type="button" class="hx-dot <?= $i === 0 ? 'is-on' : '' ?>" aria-label="Show photo <?= $i + 1 ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<form class="hx-search" action="<?= site_url('facilities') ?>" method="get" role="search" aria-label="Search courts">
    <div class="hx-field">
        <label for="hxType"><i class="bi bi-trophy"></i> Activity</label>
        <select id="hxType" name="type" data-dd>
            <option value="">Any activity</option>
            <?php foreach ($activities as $k => $name): ?>
                <option value="<?= esc($k, 'attr') ?>"><?= esc($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="hx-field">
        <label for="hxDate"><i class="bi bi-calendar-event"></i> Date</label>
        <input id="hxDate" type="date" name="date" data-datepicker value="<?= esc(date('Y-m-d'), 'attr') ?>" min="<?= esc(date('Y-m-d'), 'attr') ?>">
    </div>
    <button class="btn hx-go" type="submit"><i class="bi bi-search"></i> Search courts</button>
</form>
