<?php
/** Bottom of the home page: one card for each part of ATHLETIX. Every card links to its page. Numbers come from the real data. */
$n   = static fn (int $v, string $one, string $many): string => $v . ' ' . ($v === 1 ? $one : $many);
$sc  = $sectionCounts ?? ['activities' => 0, 'fitness' => 0, 'entertainment' => 0];
?>
<section class="why" aria-label="What you can book with ATHLETIX">
    <a class="why-card why-green" href="<?= site_url('facilities') ?>">
        <div class="why-art" aria-hidden="true">
            <svg viewBox="0 0 200 150" role="presentation">
                <ellipse cx="100" cy="136" rx="58" ry="8" fill="#bcd5c8"/>
                <circle cx="100" cy="76" r="54" fill="#c8954a"/>
                <path d="M100 22v108M46 76h108" stroke="#0e3b3f" stroke-width="4"/>
                <path d="M62 38c20 14 20 60 0 76M138 38c-20 14-20 60 0 76" fill="none" stroke="#0e3b3f" stroke-width="4"/>
                <circle cx="100" cy="76" r="54" fill="none" stroke="#0e3b3f" stroke-width="5"/>
                <circle cx="100" cy="76" r="26" fill="#fffdf7"/>
                <circle cx="91" cy="71" r="3.6" fill="#14292b"/><circle cx="109" cy="71" r="3.6" fill="#14292b"/>
                <path d="M92 83q8 8 16 0" fill="none" stroke="#14292b" stroke-width="3.2" stroke-linecap="round"/>
                <circle cx="84" cy="80" r="4" fill="#f0b9a0" opacity=".75"/><circle cx="116" cy="80" r="4" fill="#f0b9a0" opacity=".75"/>
                <path d="M160 24l4 9 9 4-9 4-4 9-4-9-9-4 9-4z" fill="#a8782b"/>
                <path d="M34 36l3 6 6 3-6 3-3 6-3-6-6-3 6-3z" fill="#0e3b3f"/>
            </svg>
        </div>
        <span class="why-kicker">Sports Activities</span>
        <h3>Find your game</h3>
        <p>Basketball, badminton, baseball and more. <?= esc($n((int) $sc['activities'], 'court', 'courts')) ?> with live free-now status.</p>
    </a>

    <a class="why-card why-brass" href="<?= site_url('hotels') ?>">
        <div class="why-art" aria-hidden="true">
            <svg viewBox="0 0 200 150" role="presentation">
                <ellipse cx="100" cy="138" rx="70" ry="8" fill="#e6d4ae"/>
                <rect x="28" y="84" width="144" height="46" rx="10" fill="#0e3b3f"/>
                <rect x="28" y="62" width="14" height="68" rx="6" fill="#a8782b"/>
                <rect x="158" y="96" width="14" height="34" rx="6" fill="#a8782b"/>
                <rect x="40" y="74" width="120" height="26" rx="10" fill="#fffdf7"/>
                <rect x="46" y="64" width="46" height="26" rx="12" fill="#fffdf7" stroke="#e6d4ae" stroke-width="3"/>
                <circle cx="69" cy="77" r="17" fill="#fffdf7" stroke="#0e3b3f" stroke-width="3.5"/>
                <path d="M61 75q4 4 8 0M75 75q4 4 8 0" transform="translate(-2 0)" fill="none" stroke="#14292b" stroke-width="3" stroke-linecap="round"/>
                <path d="M64 84q5 4 10 0" fill="none" stroke="#14292b" stroke-width="2.6" stroke-linecap="round"/>
                <rect x="94" y="86" width="66" height="30" rx="8" fill="#15494e"/>
                <path d="M104 86v30M122 86v30M140 86v30" stroke="#0e3b3f" stroke-width="2.5" opacity=".6"/>
                <path d="M138 30a22 22 0 1 0 22 26 17 17 0 1 1-22-26z" fill="#a8782b"/>
                <path d="M104 24h12l-12 14h12M122 40h9l-9 10h9" fill="none" stroke="#0e3b3f" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <span class="why-kicker">Hotels &amp; Suites</span>
        <h3>Worn out? Stay the night</h3>
        <p>When training wears you out, a good bed is close. <?= esc($n((int) $roomCount, 'room', 'rooms')) ?> across <?= esc($n((int) $hotelCount, 'hotel', 'hotels')) ?>.</p>
    </a>

    <a class="why-card why-white" href="<?= site_url('fitness') ?>">
        <div class="why-art" aria-hidden="true">
            <svg viewBox="0 0 200 150" role="presentation">
                <ellipse cx="100" cy="136" rx="62" ry="8" fill="#e4ebe6"/>
                <rect x="20" y="58" width="16" height="36" rx="5" fill="#a8782b"/><rect x="164" y="58" width="16" height="36" rx="5" fill="#a8782b"/>
                <rect x="36" y="42" width="22" height="68" rx="8" fill="#0e3b3f"/><rect x="142" y="42" width="22" height="68" rx="8" fill="#0e3b3f"/>
                <rect x="58" y="70" width="84" height="12" fill="#c8c4b8"/>
                <rect x="66" y="46" width="68" height="60" rx="18" fill="#0e3b3f"/>
                <rect x="72" y="52" width="56" height="48" rx="14" fill="#fffdf7"/>
                <circle cx="90" cy="72" r="3.8" fill="#14292b"/><circle cx="110" cy="72" r="3.8" fill="#14292b"/>
                <path d="M91 84q9 9 18 0" fill="none" stroke="#14292b" stroke-width="3.4" stroke-linecap="round"/>
                <circle cx="80" cy="82" r="4.5" fill="#f0b9a0" opacity=".75"/><circle cx="120" cy="82" r="4.5" fill="#f0b9a0" opacity=".75"/>
                <path d="M100 38c-6-9-20-3-14 7 3 5 14 11 14 11s11-6 14-11c6-10-8-16-14-7z" fill="#2e7d5b" transform="translate(0 -12) scale(1)"/>
            </svg>
        </div>
        <span class="why-kicker">Fitness &amp; Wellbeing</span>
        <h3>Be stronger tomorrow</h3>
        <p>Build strength and bounce back. Gym, yoga, gymnastics, massage and foot spa.</p>
    </a>

    <a class="why-card why-dark" href="<?= site_url('entertainment') ?>">
        <div class="why-art" aria-hidden="true">
            <svg viewBox="0 0 200 150" role="presentation">
                <ellipse cx="100" cy="138" rx="54" ry="8" fill="#0a2d30"/>
                <path d="M100 112v18M78 132h44" stroke="#c8954a" stroke-width="6" stroke-linecap="round"/>
                <rect x="88" y="86" width="24" height="32" rx="10" fill="#a8782b"/>
                <circle cx="100" cy="60" r="38" fill="#fffdf7"/>
                <path d="M62 62a38 38 0 0 1 76 0" fill="none" stroke="#c8954a" stroke-width="6" stroke-linecap="round"/>
                <circle cx="88" cy="58" r="4" fill="#14292b"/><circle cx="112" cy="58" r="4" fill="#14292b"/>
                <path d="M86 72q14 14 28 0" fill="#14292b"/>
                <circle cx="78" cy="68" r="5" fill="#f0b9a0" opacity=".8"/><circle cx="122" cy="68" r="5" fill="#f0b9a0" opacity=".8"/>
                <path d="M152 44v-22l18-5v22" fill="none" stroke="#f3c065" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="148" cy="46" r="6" fill="#f3c065"/><circle cx="166" cy="41" r="6" fill="#f3c065"/>
                <path d="M30 34l3 6 6 3-6 3-3 6-3-6-6-3 6-3z" fill="#f3c065"/>
                <path d="M168 96l3 6 6 3-6 3-3 6-3-6-6-3 6-3z" fill="#f3c065"/>
            </svg>
        </div>
        <span class="why-kicker">Entertainment</span>
        <h3>Your free time, your way</h3>
        <p>Unwind when the game is done. Bowling, billiards, swimming, dance, music and karaoke.</p>
    </a>
</section>
