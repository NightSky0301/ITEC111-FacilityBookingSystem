<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\FacilityModel;
use DateTime;

/**
 * Facility menu:  Activity (basketball, badminton...)  ->  Court (A, B)  ->  Availability
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

    /** Step 3: availability of one court for one day, with each session's time in and out. */
    public function court(int $id)
    {
        $facility = (new FacilityModel())->find($id);
        if (! $facility || ($facility['status'] !== 'active' && ! is_manager())) {
            return redirect()->to('/facilities')->with('error', 'That court is not available.');
        }

        // Day to show (defaults to today). Anything that is not a real Y-m-d date falls back to today.
        $dateStr = (string) $this->request->getGet('date');
        $date    = DateTime::createFromFormat('!Y-m-d', $dateStr);
        if (! $date || $date->format('Y-m-d') !== $dateStr) {
            $date = new DateTime('today');
        }
        $dayStart = $date->format('Y-m-d') . ' 00:00:00';
        $dayEnd   = (clone $date)->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

        $isManager = is_manager();
        $me        = (int) session()->get('user_id');

        // Approved sessions block the court. Pending ones are shown only to staff/admin and to their owner.
        $sessions = [];
        foreach ((new BookingModel())->sessionsBetween($id, $dayStart, $dayEnd, ['approved', 'pending']) as $b) {
            $mine = (int) $b['user_id'] === $me;
            if ($b['status'] === 'pending' && ! $isManager && ! $mine) {
                continue;
            }
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

        // Hour-by-hour slots.
        $now   = time();
        $slots = [];
        for ($h = FacilityModel::OPEN_HOUR; $h < FacilityModel::CLOSE_HOUR; $h++) {
            $s = strtotime($date->format('Y-m-d') . sprintf(' %02d:00:00', $h));
            $e = $s + 3600;

            $covered = 0;
            $inSlot  = [];
            foreach ($sessions as $idx => $sess) {
                $bs = strtotime($sess['start']);
                $be = strtotime($sess['end']);
                if ($bs < $e && $be > $s) {
                    $inSlot[] = ['idx' => $idx, 'continues' => $bs < $s];
                    if ($sess['status'] === 'approved') {
                        $covered += min($be, $e) - max($bs, $s);
                    }
                }
            }

            $slots[] = [
                'start'    => $s,
                'end'      => $e,
                'state'    => $covered >= 3600 ? 'booked' : ($covered > 0 ? 'partial' : 'free'),
                'past'     => $e <= $now,
                'current'  => $s <= $now && $now < $e,
                'sessions' => $inSlot,
            ];
        }

        return view('facilities/court', [
            'facility'  => $facility,
            'activity'  => FacilityModel::activityName($facility['type']),
            'date'      => $date,
            'isToday'   => $date->format('Y-m-d') === date('Y-m-d'),
            'prev'      => (clone $date)->modify('-1 day')->format('Y-m-d'),
            'next'      => (clone $date)->modify('+1 day')->format('Y-m-d'),
            'sessions'  => $sessions,
            'slots'     => $slots,
            'live'      => (new BookingModel())->liveStatus($id),
            'isManager' => $isManager,
        ]);
    }
}
