<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\FacilityModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $bookings = new BookingModel();
        $userId   = (int) session()->get('user_id');

        if (! $userId) {
            // Guests see the same home page as customers: search, free right now, top bookings.
            return view('dashboard/index', ['guest' => true] + $this->browseData($bookings));
        }

        if (is_manager()) {
            $today = $this->sessionsToday($bookings);
            $data  = [
                'pending'    => $bookings->where('status', 'pending')->countAllResults(),
                'approved'   => $bookings->where('status', 'approved')->countAllResults(),
                'facilities' => (new FacilityModel())->where('status', 'active')->countAllResults(),
                'latest'     => $today,
            ] + $this->managerData($bookings, $today);
        } else {
            $mine = $bookings->withDetails(['user_id' => $userId]);
            $data = [
                'pending'  => count(array_filter($mine, fn ($b) => $b['status'] === 'pending')),
                // Same rule as the "Approved" tab on My bookings: approved and not finished yet.
                'approved' => count(array_filter($mine, fn ($b) => $b['status'] === 'approved' && strtotime($b['end_at']) > time())),
                'latest'   => $mine,
                'next'     => $this->nextSession($mine),
            ] + $this->browseData($bookings);
        }

        return view('dashboard/index', $data);
    }


    /** Extra numbers for the staff / admin dashboard, built from today's approved sessions. */
    private function managerData(BookingModel $bookings, array $today): array
    {
        // Same rule as the "Did not arrive" label on the Session(s) today cards.
        $missed = count(array_filter($today, static fn ($b) => arrival_state($b)[0] === 'missed'));

        // Oldest requests first, one line per stay (a stay's other days ride along).
        $pending = $bookings->withDetails(['status' => 'pending']);
        usort($pending, static fn ($a, $b) => strtotime($a['created_at']) <=> strtotime($b['created_at']));
        $perGroup = [];
        foreach ($pending as $p) {
            $g = (int) ($p['group_id'] ?: $p['id']);
            $perGroup[$g] = ($perGroup[$g] ?? 0) + 1;
        }
        $queue = [];
        foreach ($pending as $p) {
            $g = (int) ($p['group_id'] ?: $p['id']);
            if (isset($queue[$g])) {
                continue;
            }
            $p['days']  = $perGroup[$g];
            $queue[$g]  = $p;
        }

        // Four lists: activities (courts), hotels (rooms), fitness (gym, pools, studios) and entertainment (bowling, billiards).
        $groups = ['activities' => [], 'hotels' => [], 'fitness' => [], 'entertainment' => []];
        foreach ($queue as $q) {
            $type = (string) ($q['facility_type'] ?? '');
            $groups[FacilityModel::sectionOf($type)][] = $q;
        }

        return [
            'bookedToday'     => count($today),
            'missed'          => $missed,
            'bookedMonth'     => $bookings->countThisMonth('approved'),
            'cancelledMonth'  => $bookings->countThisMonth('cancelled'),
            'queues'     => $groups,
        ];
    }

    /** For the customer home: activity list for the search box and the courts that are free right now. */
    private function browseData(BookingModel $bookings): array
    {
        helper('booking');
        $byType   = (new FacilityModel())->activeByType();
        $acts     = [];
        $freeNow  = [];
        $total    = 0;
        $sec      = ['activities' => 0, 'fitness' => 0, 'entertainment' => 0];   // facilities per section (hotels are counted as rooms below)
        $slides   = $this->bannerSlides();
        foreach ($byType as $type => $courts) {
            if (FacilityModel::sectionOf($type) === 'activities') {
                $acts[$type] = FacilityModel::activityName($type);   // gym, pools, studios, bowling and billiards have their own tabs
            }
            foreach ($courts as $c) {
                $total++;
                $sec[FacilityModel::sectionOf($type)]++;
                if ($bookings->isFreeNow($c)) {
                    $freeNow[] = [
                        'id' => (int) $c['id'], 'name' => $c['name'], 'activity' => FacilityModel::activityName($type),
                        'price' => (float) $c['price_per_hour'], 'per_head' => FacilityModel::isPerHead($c), 'img' => facility_image($c),
                    ];
                }
            }
        }

        // Most booked this month, only for courts that are still open for booking.
        $top    = [];
        $active = [];
        foreach ($byType as $type => $courts) {
            foreach ($courts as $c) {
                $active[(int) $c['id']] = $c;
            }
        }
        foreach ($bookings->topThisMonth() as $fid => $n) {
            if (! isset($active[$fid])) {
                continue;
            }
            $c     = $active[$fid];
            $top[] = [
                'id' => $fid, 'name' => $c['name'], 'activity' => FacilityModel::activityName((string) $c['type']),
                'price' => (float) $c['price_per_hour'], 'per_head' => FacilityModel::isPerHead($c), 'img' => facility_image($c), 'count' => $n,
            ];
            if (count($top) >= 8) {
                break;
            }
        }

        $hotels = (new \App\Models\HotelModel())->withRooms();
        $rooms  = 0;
        foreach ($hotels as $h) {
            $rooms += count($h['rooms'] ?? []);
        }

        return ['activities' => $acts, 'freeNow' => array_slice($freeNow, 0, 10), 'courtTotal' => $total, 'top' => $top, 'slides' => $slides,
            'hotelCount' => count($hotels), 'roomCount' => $rooms, 'freeCount' => count($freeNow), 'sectionCounts' => $sec];
    }

    /**
     * Photos for the home page banner: up to 4. Anything you drop into public/assets/img/hero/ is used first
     * (hero-1.jpg ... hero-4.jpg, see the README there); until then the four sharpest court photos are used.
     */
    private function bannerSlides(): array
    {
        $files = glob(FCPATH . 'assets/img/hero/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
        natsort($files);
        $urls = [];
        foreach (array_slice(array_values($files), 0, 4) as $f) {
            $urls[] = base_url('assets/img/hero/' . basename($f)) . '?v=' . filemtime($f);
        }
        if (! $urls) {
            foreach (['football-a', 'multipurpose-a', 'gym-a', 'tennis-a'] as $n) {
                $urls[] = base_url('assets/img/courts/' . $n . '.jpg');
            }
        }

        return array_map(static fn ($u) => ['img' => $u], $urls);
    }

    /** The two shelves on the home page as ready-made HTML, so the page can refresh them without reloading. */
    public function live()
    {
        $data = $this->browseData(new BookingModel());

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'free' => view('dashboard/_freenow_cards', $data),
            'top'  => view('dashboard/_top_cards', $data),
        ]);
    }

    /** Approved bookings that run at any point today, earliest first. */
    private function sessionsToday(BookingModel $bookings): array
    {
        $from = strtotime(date('Y-m-d') . ' 00:00:00');
        $to   = $from + 86400;
        $rows = array_filter($bookings->withDetails(['status' => 'approved']), static fn ($b) => strtotime($b['start_at']) < $to && strtotime($b['end_at']) > $from);
        usort($rows, static fn ($a, $b) => strtotime($a['start_at']) <=> strtotime($b['start_at']));

        return array_values($rows);
    }

    /** The soonest approved booking that has not finished yet. */
    private function nextSession(array $mine): ?array
    {
        $upcoming = array_filter($mine, static fn ($b) => $b['status'] === 'approved' && strtotime($b['end_at']) > time());
        usort($upcoming, static fn ($a, $b) => strtotime($a['start_at']) <=> strtotime($b['start_at']));

        return $upcoming[0] ?? null;
    }
}
