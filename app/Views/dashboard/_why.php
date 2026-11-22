<?php
/** Bottom of the home page: three short reasons to use ATHLETIX. Numbers come from the real data. */
$n = static fn (int $v, string $one, string $many): string => $v . ' ' . ($v === 1 ? $one : $many);
?>
<section class="why" aria-label="Why book with ATHLETIX">
    <article class="why-card why-green">
        <div class="why-art" aria-hidden="true">
            <svg viewBox="0 0 200 150" role="presentation">
                <circle cx="100" cy="128" r="40" fill="#cfe1d8"/>
                <rect x="88" y="14" width="24" height="12" rx="4" fill="#a8782b"/>
                <rect x="94" y="24" width="12" height="10" fill="#a8782b"/>
                <circle cx="100" cy="78" r="50" fill="#0e3b3f"/>
                <circle cx="100" cy="78" r="41" fill="#fffdf7"/>
                <path d="M100 44v6M100 106v6M66 78h6M128 78h6" stroke="#a8782b" stroke-width="3" stroke-linecap="round"/>
                <path d="M100 78V58M100 78l14 8" stroke="#0e3b3f" stroke-width="4" stroke-linecap="round"/>
                <circle cx="88" cy="70" r="4" fill="#14292b"/><circle cx="112" cy="70" r="4" fill="#14292b"/>
                <path d="M90 90q10 9 20 0" fill="none" stroke="#14292b" stroke-width="3.5" stroke-linecap="round"/>
                <circle cx="78" cy="84" r="5" fill="#f0b9a0" opacity=".7"/><circle cx="122" cy="84" r="5" fill="#f0b9a0" opacity=".7"/>
                <path d="M150 38l4 8 8 4-8 4-4 8-4-8-8-4 8-4z" fill="#c8954a"/>
            </svg>
        </div>
        <h3>See what is free this minute</h3>
        <p><?= $freeCount > 0 ? esc($n((int) $freeCount, 'court is', 'courts are')) . ' open right now.' : 'The live badge shows what is open right now.' ?> No calling ahead to ask.</p>
    </article>

    <article class="why-card why-brass">
        <div class="why-art" aria-hidden="true">
            <svg viewBox="0 0 200 150" role="presentation">
                <ellipse cx="100" cy="138" rx="62" ry="8" fill="#e6d4ae"/>
                <rect x="52" y="50" width="96" height="86" rx="12" fill="#0e3b3f"/>
                <rect x="40" y="38" width="120" height="20" rx="8" fill="#a8782b"/>
                <rect x="64" y="64" width="16" height="14" rx="3" fill="#fffdf7"/><rect x="92" y="64" width="16" height="14" rx="3" fill="#fffdf7"/><rect x="120" y="64" width="16" height="14" rx="3" fill="#fffdf7"/>
                <rect x="84" y="100" width="32" height="36" rx="8" fill="#fffdf7"/>
                <circle cx="94" cy="112" r="3.5" fill="#14292b"/><circle cx="106" cy="112" r="3.5" fill="#14292b"/>
                <path d="M94 122q6 6 12 0" fill="none" stroke="#14292b" stroke-width="3" stroke-linecap="round"/>
                <circle cx="150" cy="30" r="14" fill="#fffdf7" stroke="#a8782b" stroke-width="3"/>
                <path d="M143 30l5 5 9-10" fill="none" stroke="#2e7d5b" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3>Courts, gym and hotel rooms in one place</h3>
        <p><?= esc($n((int) $courtTotal, 'court or gym', 'courts and gyms')) ?>, plus <?= esc($n((int) $roomCount, 'room', 'rooms')) ?> across <?= esc($n((int) $hotelCount, 'hotel', 'hotels')) ?>. One account books them all.</p>
    </article>

    <article class="why-card why-white">
        <div class="why-art" aria-hidden="true">
            <svg viewBox="0 0 200 150" role="presentation">
                <rect x="38" y="30" width="124" height="104" rx="16" fill="#0e3b3f"/>
                <rect x="38" y="30" width="124" height="28" rx="16" fill="#15494e"/>
                <rect x="62" y="16" width="10" height="26" rx="5" fill="#a8782b"/><rect x="128" y="16" width="10" height="26" rx="5" fill="#a8782b"/>
                <rect x="50" y="66" width="100" height="56" rx="10" fill="#fffdf7"/>
                <circle cx="84" cy="88" r="4" fill="#14292b"/><circle cx="116" cy="88" r="4" fill="#14292b"/>
                <path d="M88 101q12 11 24 0" fill="none" stroke="#14292b" stroke-width="3.5" stroke-linecap="round"/>
                <circle cx="70" cy="98" r="5" fill="#f0b9a0" opacity=".7"/><circle cx="130" cy="98" r="5" fill="#f0b9a0" opacity=".7"/>
                <path d="M158 96l16-16m0 0v12m0-12h-12" fill="none" stroke="#c8954a" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3>Request it, then watch the status change</h3>
        <p>Pick a time and send the request. Staff approve it, and your booking updates on its own, no refresh needed.</p>
    </article>
</section>
