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

        if (is_admin()) {
            $data = [
                'pending'    => $bookings->where('status', 'pending')->countAllResults(),
                'approved'   => $bookings->where('status', 'approved')->countAllResults(),
                'facilities' => (new FacilityModel())->where('status', 'active')->countAllResults(),
                'latest'     => array_slice($bookings->withDetails(['status' => 'pending']), 0, 5),
            ];
        } else {
            $mine = $bookings->withDetails(['user_id' => $userId]);
            $data = [
                'pending'  => count(array_filter($mine, fn ($b) => $b['status'] === 'pending')),
                'approved' => count(array_filter($mine, fn ($b) => $b['status'] === 'approved')),
                'latest'   => array_slice($mine, 0, 5),
            ];
        }

        return view('dashboard/index', $data);
    }
}
