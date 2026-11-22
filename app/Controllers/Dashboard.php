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
            $data = [
                'pending'    => $bookings->where('status', 'pending')->countAllResults(),
                'approved'   => $bookings->where('status', 'approved')->countAllResults(),
                'facilities' => (new FacilityModel())->where('status', 'active')->countAllResults(),
                'latest'     => $this->sessionsToday($bookings),
            ];
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

    /** For the customer home: activity list for the search box and the courts that are free right now. */
    private function browseData(BookingModel $bookings): array
    {
        helper('booking');
        $byType   = (new FacilityModel())->activeByType();
        $acts     = [];
        $freeNow  = [];
        $total    = 0;
        $slides   = $this->bannerSlides();
        foreach ($byType as $type => $courts) {
            if ($type !== 'gym') {
                $acts[$type] = FacilityModel::activityName($type);   // the gym is under Fitness, not in the activity search
            }
            foreach ($courts as $c) {
                $total++;
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

        return ['activities' => $acts, 'freeNow' => array_slice($freeNow, 0, 10), 'courtTotal' => $total, 'top' => $top, 'slides' => $slides];
    }

    /**
     * Photos for the home page banner: up to 5. Anything you drop into public/assets/img/hero/ is used first
     * (hero-1.jpg ... hero-5.jpg, see the README there); until then the five sharpest court photos are used.
     */
    private function bannerSlides(): array
    {
        $files = glob(FCPATH . 'assets/img/hero/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
        natsort($files);
        $urls = [];
        foreach (array_slice(array_values($files), 0, 5) as $f) {
            $urls[] = base_url('assets/img/hero/' . basename($f)) . '?v=' . filemtime($f);
        }
        if (! $urls) {
            foreach (['football-a', 'multipurpose-a', 'gym-a', 'tennis-a', 'futsal-a'] as $n) {
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
