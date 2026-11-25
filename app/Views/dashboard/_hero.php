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

<form class="hx-search" action="<?= site_url('search') ?>" method="get" role="search" aria-label="Search" autocomplete="off">
    <div class="hx-field hx-q">
        <label for="hxQ"><i class="bi bi-search"></i> What are you looking for?</label>
        <div class="hx-box">
            <i class="bi bi-search hx-box-ico" aria-hidden="true"></i>
            <input id="hxQ" name="q" type="search" placeholder="Basketball, a hotel, a room, the gym..." role="combobox" aria-expanded="false" aria-controls="hxList" aria-autocomplete="list" autocomplete="off" spellcheck="false" maxlength="60">
            <button type="button" class="hx-clear" aria-label="Clear search" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <ul class="hx-list" id="hxList" role="listbox" aria-label="Suggestions" hidden></ul>
    </div>
    <div class="hx-field">
        <div class="hx-when">
            <label for="hxDate" id="hxDateLabel"><i class="bi bi-calendar-event"></i> Date</label>
            <div class="bf-tabs hx-tabs" role="tablist" aria-label="Booking type">
                <button type="button" role="tab" class="bf-tab is-on" data-hx-mode="hour" aria-selected="true"><i class="bi bi-clock"></i> Hourly</button>
                <button type="button" role="tab" class="bf-tab" data-hx-mode="days" aria-selected="false"><i class="bi bi-calendar2-range"></i> Flexible Days</button>
            </div>
        </div>
        <input id="hxDate" type="date" name="date" data-datepicker data-range-end="#hxEnd" value="<?= esc(date('Y-m-d'), 'attr') ?>" min="<?= esc(date('Y-m-d'), 'attr') ?>">
        <input id="hxEnd" type="hidden" name="end_date" value="">
    </div>
    <button class="btn hx-go" type="submit"><i class="bi bi-search"></i> Search</button>
</form>
<script src="<?= base_url('assets/js/search.js') ?>" data-suggest="<?= site_url('search/suggest') ?>" defer></script>
