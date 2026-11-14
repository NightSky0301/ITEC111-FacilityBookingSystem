<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\FacilityModel;
use DateTime;

/**
 * Facility menu:  Activity (basketball, badminton...)  ->  Court (A, B)  ->  Date (calendar)  ->  booking form (time in + hours)
 */
class Facilities extends BaseController
{
    /** Step 1: choose an activity. */
    public function index()
    {
        $cards = [];
        foreach ((new FacilityModel())->activeByType() as $type => $courts) {
            $cards[] = [
                'type'      => $type,
                'name'      => FacilityModel::activityName($type),
                'tagline'   => FacilityModel::ACTIVITIES[$type][1] ?? '',
                'count'     => count($courts),
                'min_price' => min(array_column($courts, 'price_per_hour')),
                'first'     => $courts[0],
            ];
        }

        return view('facilities/index', ['cards' => $cards]);
    }

    /** Step 2: choose a court of that activity. */
    public function category(string $type)
    {
        $courts = (new FacilityModel())->activeByType()[$type] ?? null;
        if (! $courts) {
            return redirect()->to('/facilities')->with('error', 'We could not find that activity.');
        }

        $bookings = new BookingModel();
        foreach ($courts as &$court) {
            $court['live'] = $bookings->liveStatus((int) $court['id']);
        }
        unset($court);

        return view('facilities/category', [
            'type'     => $type,
            'activity' => FacilityModel::activityName($type),
            'courts'   => $courts,
        ]);
    }

    /**
     * Step 3: pick a date on a month calendar. Days that already have sessions are marked, and the
     * sessions of the selected day are listed next to it. The time itself is chosen in the booking form.
     */
    public function court(int $id)
    {
        $facility = (new FacilityModel())->find($id);
        if (! $facility || ($facility['status'] !== 'active' && ! is_manager())) {
            return redirect()->to('/facilities')->with('error', 'That court is not available.');
        }

        // Selected day (defaults to today). Anything that is not a real Y-m-d date falls back to today.
        $dateStr = (string) $this->request->getGet('date');
        $date    = DateTime::createFromFormat('!Y-m-d', $dateStr);
        if (! $date || $date->format('Y-m-d') !== $dateStr) {
            $date = new DateTime('today');
        }
        $dateStr  = $date->format('Y-m-d');
        $dayStart = $dateStr . ' 00:00:00';
        $dayEnd   = (clone $date)->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

        // Month shown on the calendar (?month=Y-m). Defaults to the month of the selected day.
        $monthStr = (string) $this->request->getGet('month');
        $month    = DateTime::createFromFormat('!Y-m', $monthStr);
        if (! $month || $month->format('Y-m') !== $monthStr || (int) $month->format('Y') < 2000 || (int) $month->format('Y') > 2100) {
            $month = new DateTime($date->format('Y-m-01'));
        }

        $isManager = is_manager();
        $me        = (int) session()->get('user_id');

        // Approved sessions block the court. Pending ones are shown only to staff/admin and to their owner.
        $visible = static function (array $b) use ($isManager, $me): bool {
            return $b['status'] === 'approved' || $isManager || (int) $b['user_id'] === $me;
        };

        // Sessions of the selected day, for the list.
        $sessions = [];
        foreach ((new BookingModel())->sessionsBetween($id, $dayStart, $dayEnd, ['approved', 'pending']) as $b) {
            if (! $visible($b)) {
                continue;
            }
            $mine = (int) $b['user_id'] === $me;
            $sessions[] = [
                'id'       => (int) $b['id'],
                'start'    => $b['start_at'],
                'end'      => $b['end_at'],
                'status'   => $b['status'],
                'mine'     => $mine,
                'who'      => $isManager ? $b['customer_name'] : ($mine ? 'You' : 'Reserved'),
                'purpose'  => ($isManager || $mine) ? $b['purpose'] : null,
                'players'  => (int) $b['guests'],
            ];
        }

        // Month grid (Sunday first): only the weeks the month needs, 4 to 6 rows. The rows stretch to fill the panel.
        $weeks     = (int) ceil(((int) $month->format('w') + (int) $month->format('t')) / 7);
        $gridStart = (clone $month)->modify('-' . (int) $month->format('w') . ' days');
        $gridEnd   = (clone $gridStart)->modify('+' . ($weeks * 7) . ' days');

        // How many sessions touch each day of the grid (a session past midnight marks both days).
        $dayCounts = [];
        foreach ((new BookingModel())->sessionsBetween($id, $gridStart->format('Y-m-d') . ' 00:00:00', $gridEnd->format('Y-m-d') . ' 00:00:00', ['approved', 'pending']) as $b) {
            if (! $visible($b)) {
                continue;
            }
            $cursor = new DateTime(date('Y-m-d', strtotime($b['start_at'])));
            $last   = date('Y-m-d', strtotime($b['end_at']) - 1);
            while ($cursor->format('Y-m-d') <= $last) {
                $key             = $cursor->format('Y-m-d');
                $dayCounts[$key] = ($dayCounts[$key] ?? 0) + 1;
                $cursor->modify('+1 day');
            }
        }

        $today = date('Y-m-d');
        $cells = [];
        for ($i = 0; $i < $weeks * 7; $i++) {
            $d       = (clone $gridStart)->modify('+' . $i . ' days');
            $ds      = $d->format('Y-m-d');
            $cells[] = [
                'date'     => $ds,
                'day'      => (int) $d->format('j'),
                'inMonth'  => $d->format('Y-m') === $month->format('Y-m'),
                'past'     => $ds < $today,
                'today'    => $ds === $today,
                'selected' => $ds === $dateStr,
                'count'    => $dayCounts[$ds] ?? 0,
                'label'    => $d->format('l, F j, Y'),
            ];
        }

        $prevMonth = (clone $month)->modify('-1 month')->format('Y-m');

        return view('facilities/court', [
            'facility'   => $facility,
            'activity'   => FacilityModel::activityName($facility['type']),
            'date'       => $date,
            'isPast'     => $dateStr < $today,
            'month'      => $month,
            'cells'      => $cells,
            'weeks'      => $weeks,
            'prevMonth'  => $prevMonth,
            'nextMonth'  => (clone $month)->modify('+1 month')->format('Y-m'),
            // Customers cannot book the past, so they cannot browse back before the current month.
            'canPrev'    => $isManager || $prevMonth >= date('Y-m'),
            'showToday'  => $dateStr !== $today || $month->format('Y-m') !== date('Y-m'),
            'opens'      => date('g:i A', strtotime(sprintf('%02d:00', FacilityModel::OPEN_HOUR))),
            'closes'     => date('g:i A', strtotime(sprintf('%02d:00', FacilityModel::CLOSE_HOUR))),
            'sessions'   => $sessions,
            'live'       => (new BookingModel())->liveStatus($id),
            'isManager'  => $isManager,
        ]);
    }
}
