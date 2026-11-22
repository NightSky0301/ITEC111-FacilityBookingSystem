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
            ->select('bookings.*, facilities.name AS facility_name, facilities.price_per_hour AS facility_price, facilities.type AS facility_type, users.name AS customer_name')
            ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
            ->join('users', 'users.id = bookings.user_id', 'left')
            ->where('facilities.deleted_at', null)
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
                $title = $b['facility_name'] . ($b['status'] === 'pending' ? ' (pending)' : '');
            } else {
                $title = $b['facility_name'];
            }

            if ($b['status'] === 'pending') {
                $color = '#c28a1c';
                $kind  = 'pending';
            } elseif ($mine) {
                $color = '#0e3b3f';
                $kind  = 'mine';
            } else {
                $color = '#a93d36';
                $kind  = 'booked';
            }

            $hours   = max(0, (strtotime($b['end_at']) - strtotime($b['start_at'])) / 3600);
            $private = $isAdmin || $mine;
            $addons  = [];
            if ($private && ! empty($b['addons'])) {
                foreach ((array) json_decode((string) $b['addons'], true) as $a) {
                    $addons[] = (string) ($a['label'] ?? '');
                }
            }

            $events[] = [
                'id'              => $b['id'],
                'title'           => $title,
                'start'           => date('c', strtotime($b['start_at'])),
                'end'             => date('c', strtotime($b['end_at'])),
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'classNames'      => ['ev-' . $kind],
                'extendedProps'   => [
                    'status'      => $b['status'],
                    'kind'        => $kind,
                    'facility'    => $b['facility_name'],
                    'facility_id' => (int) $b['facility_id'],
                    'customer'    => $isAdmin ? $b['customer_name'] : null,
                    'hours'       => $hours,
                    'nights'      => \App\Models\FacilityModel::isRoom((string) $b['facility_type']) ? max(1, (int) round((strtotime(date('Y-m-d', strtotime($b['end_at']))) - strtotime(date('Y-m-d', strtotime($b['start_at'])))) / 86400)) : null,
                    'people'      => \App\Models\FacilityModel::isPerHead((string) $b['facility_type']) ? max(1, (int) $b['guests']) : null,
                    'total'       => $private && \App\Models\FacilityModel::isRoom((string) $b['facility_type']) && isset($b['total_price']) ? (float) $b['total_price'] : ($private ? $hours * (float) $b['facility_price'] * (\App\Models\FacilityModel::isPerHead((string) $b['facility_type']) ? max(1, (int) $b['guests']) : 1) + (float) ($b['addons_total'] ?? 0) : null),
                    'addons'      => $addons,
                    'purpose'     => $private ? $b['purpose'] : null,
                ],
            ];
        }

        return $this->response->setJSON($events);
    }
}
