<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\FacilityModel;

class Calendar extends BaseController
{
    public function index()
    {
        return view('calendar/index', [
            'facilities' => (new FacilityModel())->active(),
        ]);
    }

    /** JSON feed used by FullCalendar. */
    public function events()
    {
        $start      = $this->request->getGet('start');
        $end        = $this->request->getGet('end');
        $facilityId = (int) $this->request->getGet('facility_id');

        $startAt = $start ? date('Y-m-d H:i:s', strtotime($start)) : date('Y-m-01 00:00:00');
        $endAt   = $end ? date('Y-m-d H:i:s', strtotime($end)) : date('Y-m-t 23:59:59');

        $builder = (new BookingModel())
            ->select('bookings.*, facilities.name AS facility_name, users.name AS customer_name')
            ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
            ->join('users', 'users.id = bookings.user_id', 'left')
            ->whereIn('bookings.status', ['pending', 'approved'])
            ->where('bookings.start_at <', $endAt)
            ->where('bookings.end_at >', $startAt);

        if ($facilityId) {
            $builder->where('bookings.facility_id', $facilityId);
        }

        $me      = (int) session()->get('user_id');
        $isAdmin = is_manager();
        $events  = [];

        foreach ($builder->findAll() as $b) {
            $mine = (int) $b['user_id'] === $me;

            // Customers only see other people's APPROVED bookings (as "Booked"), plus all of their own.
            if (! $isAdmin && ! $mine && $b['status'] !== 'approved') {
                continue;
            }

            if ($isAdmin) {
                $title = $b['facility_name'] . ' - ' . $b['customer_name'] . ($b['status'] === 'pending' ? ' (pending)' : '');
            } elseif ($mine) {
                $title = 'My booking: ' . $b['facility_name'] . ($b['status'] === 'pending' ? ' (pending)' : '');
            } else {
                $title = 'Booked: ' . $b['facility_name'];
            }

            if ($b['status'] === 'pending') {
                $color = '#c28a1c';
            } elseif ($mine) {
                $color = '#0e3b3f';
            } else {
                $color = '#a93d36';
            }

            $events[] = [
                'id'              => $b['id'],
                'title'           => $title,
                'start'           => date('c', strtotime($b['start_at'])),
                'end'             => date('c', strtotime($b['end_at'])),
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'extendedProps'   => [
                    'status'   => $b['status'],
                    'facility' => $b['facility_name'],
                    'guests'   => $b['guests'],
                    'purpose'  => ($isAdmin || $mine) ? $b['purpose'] : null,
                ],
            ];
        }

        return $this->response->setJSON($events);
    }
}
