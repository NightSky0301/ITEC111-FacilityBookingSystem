<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\FacilityModel;
use App\Models\HotelModel;
use DateTime;

/**
 * One search for everything: activities, courts, the gym, hotels and hotel rooms.
 * /search shows the results page, /search/suggest feeds the suggestions under the search box on the home page.
 */
class Search extends BaseController
{
    /** No longer used: search is literal now (only words the person can see on the card), so nothing unrelated shows up ("workout" finds the gym, "stay" finds hotels). */
    private const EXTRA = [
        'gym'  => 'fitness workout exercise training',
        'bowling'   => 'entertainment fun game night alley lane',
        'billiards' => 'entertainment fun game night pool snooker cue',
        'baseball'  => 'bat ball diamond softball',
        'swimming' => 'entertainment pool swim swimming laps water',
        'dance'    => 'entertainment studio dance class zumba ballet',
        'gymnastics' => 'fitness gymnast tumble flip beam',
        'yoga'     => 'fitness stretch meditation mat pilates wellness',
        'music'    => 'entertainment band jam rehearsal recording instruments drums guitar',
        'massage'  => 'fitness wellness spa relax recovery therapy rubdown',
        'footspa'  => 'fitness wellness spa pedicure relax foot feet soak',
        'karaoke'  => 'entertainment sing singing videoke party mic',
        'room' => 'hotel stay night sleep suite',
    ];

    /** Lower-case words of the query. */
    private function words(string $q): array
    {
        $q = mb_strtolower(trim($q));

        return $q === '' ? [] : array_values(array_filter(preg_split('/[\s,]+/u', $q)));
    }

    private function tokens(string $text): array
    {
        return array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text))));
    }

    /** One-letter slips are forgiven for longer words ("futsl" finds Futsal); only used when nothing matched exactly. */
    private function near(string $w, array $tokens): bool
    {
        $len = mb_strlen($w);
        if ($len < 4) {
            return false;
        }
        $max = $len >= 7 ? 2 : 1;
        foreach ($tokens as $t) {
            if (abs(mb_strlen($t) - $len) <= $max && levenshtein($w, $t) <= $max) {
                return true;
            }
            if (mb_strlen($t) > $len && levenshtein($w, mb_substr($t, 0, $len)) <= 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * How well a query fits one thing; 0 = it does not match. Every typed word has to match:
     *   - the start of a word in its NAME (best), the start of a word in its activity / category,
     *   - nothing hidden: no synonyms and no descriptions, only words you can see on the card.
     * Descriptions are never searched: that is what made "spa" find "sparring".
     */
    private function rank(array $words, string $name, string $main, string $alias, bool $fuzzy): int
    {
        $nameT = $this->tokens($name);
        $mainT = $this->tokens($main);
        $aliasT = $this->tokens($alias);
        $sum = 0;
        foreach ($words as $w) {
            $hit = 0;
            foreach ($nameT as $t) {
                if ($t === $w) { $hit = max($hit, 12); } elseif (str_starts_with($t, $w)) { $hit = max($hit, 10); }
            }
            foreach ($mainT as $t) {
                if ($t === $w) { $hit = max($hit, 7); } elseif (str_starts_with($t, $w)) { $hit = max($hit, 6); }
            }
            foreach ($aliasT as $t) {
                if ($t === $w || (mb_strlen($w) >= 4 && str_starts_with($t, $w))) { $hit = max($hit, 3); }
            }
            if (! $hit && $fuzzy && $this->near($w, array_merge($nameT, $mainT))) {
                $hit = 2;
            }
            if (! $hit) {
                return 0;
            }
            $sum += $hit;
        }

        return $sum;
    }

    private function courtRank(array $words, array $c, bool $fuzzy): int
    {
        $type    = (string) $c['type'];
        $section = FacilityModel::sectionOf($type);
        $label   = ['fitness' => 'fitness wellbeing', 'entertainment' => 'entertainment'][$section] ?? 'sports activities court';

        return $this->rank($words, (string) $c['name'], FacilityModel::activityName($type) . ' ' . $type . ' ' . $label, '', $fuzzy);
    }

    private function hotelRank(array $words, array $h, bool $fuzzy): int
    {
        return $this->rank($words, (string) $h['name'], 'hotel hotels suites ' . (string) ($h['address'] ?? ''), '', $fuzzy);
    }

    private function roomRank(array $words, array $r, array $h, bool $fuzzy): int
    {
        return $this->rank($words, (string) $r['name'], $h['name'] . ' room rooms', '', $fuzzy);
    }

    /** Results page. */
    public function index()
    {
        helper('booking');
        $q     = trim((string) $this->request->getGet('q'));
        $date  = (string) $this->request->getGet('date');
        $d     = DateTime::createFromFormat('!Y-m-d', $date);
        $date  = ($d && $d->format('Y-m-d') === $date && $date >= date('Y-m-d')) ? $date : '';
        $end   = $date !== '' ? valid_end_date($date, (string) $this->request->getGet('end_date')) : '';
        $words = $this->words($q);

        $bookings = new BookingModel();
        $model    = new HotelModel();
        $byType   = (new FacilityModel())->activeByType();
        $allHotels = $model->withTonight($model->withRooms());

        for ($fuzzy = false; ; $fuzzy = true) {
            $courts = $gyms = $fun = $hotels = [];
            foreach ($byType as $type => $list) {
                $scored = [];
                foreach ($list as $c) {
                    $sc = $words ? $this->courtRank($words, $c, $fuzzy) : 1;
                    if ($sc > 0) {
                        $scored[] = [$sc, $c];
                    }
                }
                if ($words) {
                    usort($scored, static fn ($a, $b) => $b[0] <=> $a[0]);
                }
                foreach ($scored as [, $c]) {
                    $item    = facility_card_item($c, $bookings);
                    $section = FacilityModel::sectionOf($type);
                    if ($section === 'fitness') { $gyms[] = $item; } elseif ($section === 'entertainment') { $fun[] = $item; } else { $courts[] = $item; }
                }
            }
            foreach ($allHotels as $h) {
                $roomsHit = $words ? array_values(array_filter($h['rooms'], fn ($r) => $this->roomRank($words, $r, $h, $fuzzy) > 0)) : [];
                if ($words && $this->hotelRank($words, $h, $fuzzy) === 0 && ! $roomsHit) {
                    continue;
                }
                $h['hit_rooms'] = $roomsHit;
                $hotels[]       = $h;
            }
            if (! $words || $fuzzy || count($courts) + count($gyms) + count($fun) + count($hotels) > 0) {
                break;
            }
        }

        $acts = [];
        foreach (FacilityModel::ACTIVITIES as $k => [$name]) {
            if (! in_array($k, ['room', 'other'], true) && FacilityModel::sectionOf($k) === 'activities') {
                $acts[$k] = $name;
            }
        }

        return view('search/index', [
            'q' => $q, 'date' => $date, 'end' => $end, 'courts' => $courts, 'gyms' => $gyms, 'fun' => $fun, 'hotels' => $hotels, 'acts' => $acts,
            'total' => count($courts) + count($gyms) + count($fun) + count($hotels),
        ]);
    }

    /** Up to 8 suggestions as JSON. With nothing typed yet it offers the main places to start. */
    public function suggest()
    {
        helper('booking');
        $q     = trim((string) $this->request->getGet('q'));
        $date  = (string) $this->request->getGet('date');
        $words = $this->words($q);
        $end   = valid_end_date($date, (string) $this->request->getGet('end_date'));
        $qs    = $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? '?date=' . rawurlencode($date) . ($end !== '' ? '&end_date=' . rawurlencode($end) : '') : '';
        $out   = [];

        $byType = (new FacilityModel())->activeByType();
        $hotels = (new HotelModel())->withRooms();

        if (! $words) {
            $out[] = ['kind' => 'Activities', 'title' => 'Browse all activities', 'sub' => 'Courts and tables for every sport', 'url' => site_url('facilities') . $qs, 'icon' => 'bi-grid-fill'];
            $out[] = ['kind' => 'Hotels', 'title' => 'Browse hotels', 'sub' => count($hotels) . ' hotels with rooms by the night', 'url' => site_url('hotels'), 'icon' => 'bi-building'];
            if (array_intersect(FacilityModel::FITNESS, array_keys($byType))) {
                $out[] = ['kind' => 'Fitness', 'title' => 'Fitness', 'sub' => 'Gym, gymnastics, yoga, massage and foot spa', 'url' => site_url('fitness'), 'icon' => 'bi-heart-pulse'];
            }
            if (array_intersect(FacilityModel::ENTERTAINMENT, array_keys($byType))) {
                $out[] = ['kind' => 'Entertainment', 'title' => 'Entertainment', 'sub' => 'Bowling, billiards, pools, dance, music and karaoke', 'url' => site_url('entertainment'), 'icon' => 'bi-controller'];
            }
            foreach (array_slice(array_filter($byType, static fn ($l, $t) => FacilityModel::sectionOf((string) $t) === 'activities', ARRAY_FILTER_USE_BOTH), 0, 5, true) as $type => $list) {
                $out[] = ['kind' => 'Activity', 'title' => FacilityModel::activityName($type), 'sub' => count($list) . ' ' . FacilityModel::unitName($type, count($list)), 'url' => site_url('facilities') . '?type=' . $type . ($qs ? '&' . substr($qs, 1) : ''), 'icon' => facility_icon($type)];
            }

            return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['items' => $out]);
        }

        // Activities first ("bad" -> Badminton), then courts and rooms ranked by how well they fit, then hotels.
        for ($fuzzy = false; ; $fuzzy = true) {
            $out = [];
            foreach ($byType as $type => $list) {
                if (FacilityModel::sectionOf($type) !== 'fitness' && $this->rank($words, FacilityModel::activityName($type), $type, '', $fuzzy) > 0) {
                    $out[] = ['kind' => FacilityModel::isEntertainment($type) ? 'Entertainment' : 'Activity', 'title' => FacilityModel::activityName($type), 'sub' => count($list) . ' ' . FacilityModel::unitName($type, count($list)), 'url' => site_url(FacilityModel::sectionPath(FacilityModel::sectionOf($type))) . '?type=' . $type . ($qs ? '&' . substr($qs, 1) : ''), 'icon' => facility_icon($type)];
                }
            }
            $hits = [];
            foreach ($byType as $type => $list) {
                foreach ($list as $c) {
                    if (($sc = $this->courtRank($words, $c, $fuzzy)) > 0) {
                        $hits[] = [$sc, [
                            'kind' => ['fitness' => 'Fitness', 'entertainment' => 'Entertainment'][FacilityModel::sectionOf($type)] ?? 'Court', 'title' => $c['name'], 'sub' => FacilityModel::activityName($type) . ' · ₱' . number_format((float) $c['price_per_hour'], 0) . (FacilityModel::isPerHead($c) ? ' per person/hr' : '/hr'),
                            'url' => site_url('facilities/court/' . (int) $c['id']) . $qs, 'icon' => facility_icon($type),
                        ]];
                    }
                }
            }
            foreach ($hotels as $h) {
                if ($this->hotelRank($words, $h, $fuzzy) > 0) {
                    $hits[] = [8, ['kind' => 'Hotel', 'title' => $h['name'], 'sub' => count($h['rooms']) . ' rooms · from ₱' . number_format($h['from'], 0) . ' per night', 'url' => site_url('hotels/' . (int) $h['id']), 'icon' => 'bi-building']];
                }
                foreach ($h['rooms'] as $r) {
                    if (($sc = $this->roomRank($words, $r, $h, $fuzzy)) > 0) {
                        $hits[] = [$sc, ['kind' => 'Room', 'title' => $r['name'], 'sub' => $h['name'] . ' · ₱' . number_format((float) $r['price_per_hour'], 0) . ' per night', 'url' => site_url('hotels/room/' . (int) $r['id']), 'icon' => 'bi-door-open']];
                    }
                }
            }
            usort($hits, static fn ($a, $b) => $b[0] <=> $a[0]);   // best match first, otherwise the usual order
            foreach ($hits as [, $item]) {
                $out[] = $item;
            }
            if ($out || $fuzzy) {
                break;
            }
        }

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['items' => array_slice($out, 0, 8), 'more' => max(0, count($out) - 8)]);
    }
}
