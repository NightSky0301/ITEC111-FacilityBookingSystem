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
    /** Extra words that also find something ("workout" finds the gym, "stay" finds hotels). */
    private const EXTRA = [
        'gym'  => 'fitness workout exercise training',
        'swimming' => 'fitness pool swim swimming laps water',
        'dance'    => 'fitness studio dance class zumba ballet',
        'room' => 'hotel stay night sleep suite',
    ];

    /** Lower-case words of the query. */
    private function words(string $q): array
    {
        $q = mb_strtolower(trim($q));

        return $q === '' ? [] : array_values(array_filter(preg_split('/[\s,]+/', $q)));
    }

    private function matches(array $words, string $haystack): bool
    {
        $haystack = mb_strtolower($haystack);
        foreach ($words as $w) {
            if (mb_strpos($haystack, $w) === false) {
                return false;
            }
        }

        return true;
    }

    private function courtText(array $c): string
    {
        $type = (string) $c['type'];

        return implode(' ', [$c['name'], FacilityModel::activityName($type), $type, (string) ($c['description'] ?? ''), self::EXTRA[$type] ?? '']);
    }

    private function hotelText(array $h): string
    {
        return implode(' ', [$h['name'], (string) ($h['tagline'] ?? ''), (string) ($h['description'] ?? ''), (string) ($h['address'] ?? ''), self::EXTRA['room']]);
    }

    /** Results page. */
    public function index()
    {
        helper('booking');
        $q     = trim((string) $this->request->getGet('q'));
        $date  = (string) $this->request->getGet('date');
        $d     = DateTime::createFromFormat('!Y-m-d', $date);
        $date  = ($d && $d->format('Y-m-d') === $date && $date >= date('Y-m-d')) ? $date : '';
        $words = $this->words($q);

        $bookings = new BookingModel();
        $courts   = [];
        $gyms     = [];
        foreach ((new FacilityModel())->activeByType() as $type => $list) {
            foreach ($list as $c) {
                if ($words && ! $this->matches($words, $this->courtText($c))) {
                    continue;
                }
                $item = facility_card_item($c, $bookings);
                if (FacilityModel::isFitness($type)) {
                    $gyms[] = $item;
                } else {
                    $courts[] = $item;
                }
            }
        }

        $hotels = [];
        $model  = new HotelModel();
        foreach ($model->withTonight($model->withRooms()) as $h) {
            $roomsHit = $words ? array_values(array_filter($h['rooms'], fn ($r) => $this->matches($words, $r['name'] . ' ' . $h['name'] . ' ' . (string) ($r['description'] ?? '') . ' ' . self::EXTRA['room']))) : [];
            if ($words && ! $this->matches($words, $this->hotelText($h)) && ! $roomsHit) {
                continue;
            }
            $h['hit_rooms'] = $roomsHit;
            $hotels[]       = $h;
        }

        $acts = [];
        foreach (FacilityModel::ACTIVITIES as $k => [$name]) {
            if (! in_array($k, ['room', 'other'], true) && ! FacilityModel::isFitness($k)) {
                $acts[$k] = $name;
            }
        }

        return view('search/index', [
            'q' => $q, 'date' => $date, 'courts' => $courts, 'gyms' => $gyms, 'hotels' => $hotels, 'acts' => $acts,
            'total' => count($courts) + count($gyms) + count($hotels),
        ]);
    }

    /** Up to 8 suggestions as JSON. With nothing typed yet it offers the main places to start. */
    public function suggest()
    {
        helper('booking');
        $q     = trim((string) $this->request->getGet('q'));
        $date  = (string) $this->request->getGet('date');
        $words = $this->words($q);
        $qs    = $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? '?date=' . rawurlencode($date) : '';
        $out   = [];

        $byType = (new FacilityModel())->activeByType();
        $hotels = (new HotelModel())->withRooms();

        if (! $words) {
            $out[] = ['kind' => 'Activities', 'title' => 'Browse all activities', 'sub' => 'Courts and tables for every sport', 'url' => site_url('facilities') . $qs, 'icon' => 'bi-grid-fill'];
            $out[] = ['kind' => 'Hotels', 'title' => 'Browse hotels', 'sub' => count($hotels) . ' hotels with rooms by the night', 'url' => site_url('hotels'), 'icon' => 'bi-building'];
            if (array_intersect(FacilityModel::FITNESS, array_keys($byType))) {
                $out[] = ['kind' => 'Fitness', 'title' => 'Fitness', 'sub' => 'Gym, swimming pools and dance studios', 'url' => site_url('fitness'), 'icon' => 'bi-heart-pulse'];
            }
            foreach (array_slice($byType, 0, 5, true) as $type => $list) {
                if (! FacilityModel::isFitness($type)) {
                    $out[] = ['kind' => 'Activity', 'title' => FacilityModel::activityName($type), 'sub' => count($list) . ' ' . FacilityModel::unitName($type, count($list)), 'url' => site_url('facilities') . '?type=' . $type . ($qs ? '&' . substr($qs, 1) : ''), 'icon' => facility_icon($type)];
                }
            }

            return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['items' => $out]);
        }

        // Activities first ("bad" -> Badminton), then courts and the gym, then hotels and rooms.
        foreach ($byType as $type => $list) {
            if (! FacilityModel::isFitness($type) && $this->matches($words, FacilityModel::activityName($type) . ' ' . $type)) {
                $out[] = ['kind' => 'Activity', 'title' => FacilityModel::activityName($type), 'sub' => count($list) . ' ' . FacilityModel::unitName($type, count($list)), 'url' => site_url('facilities') . '?type=' . $type . ($qs ? '&' . substr($qs, 1) : ''), 'icon' => facility_icon($type)];
            }
        }
        foreach ($byType as $type => $list) {
            foreach ($list as $c) {
                if ($this->matches($words, $this->courtText($c))) {
                    $out[] = [
                        'kind' => FacilityModel::isFitness($type) ? 'Fitness' : 'Court', 'title' => $c['name'], 'sub' => FacilityModel::activityName($type) . ' · ₱' . number_format((float) $c['price_per_hour'], 0) . (FacilityModel::isPerHead($c) ? ' per person/hr' : '/hr'),
                        'url' => site_url('facilities/court/' . (int) $c['id']) . $qs, 'icon' => facility_icon($type),
                    ];
                }
            }
        }
        foreach ($hotels as $h) {
            if ($this->matches($words, $this->hotelText($h))) {
                $out[] = ['kind' => 'Hotel', 'title' => $h['name'], 'sub' => count($h['rooms']) . ' rooms · from ₱' . number_format($h['from'], 0) . ' per night', 'url' => site_url('hotels/' . (int) $h['id']), 'icon' => 'bi-building'];
            }
            foreach ($h['rooms'] as $r) {
                if ($this->matches($words, $r['name'] . ' ' . $h['name'])) {
                    $out[] = ['kind' => 'Room', 'title' => $r['name'], 'sub' => $h['name'] . ' · ₱' . number_format((float) $r['price_per_hour'], 0) . ' per night', 'url' => site_url('hotels/room/' . (int) $r['id']), 'icon' => 'bi-door-open'];
                }
            }
        }

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['items' => array_slice($out, 0, 8), 'more' => max(0, count($out) - 8)]);
    }
}
