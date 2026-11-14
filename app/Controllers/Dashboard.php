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

        if (is_manager()) {
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
                // Same rule as the "Approved" tab on My bookings: approved and not finished yet.
                'approved' => count(array_filter($mine, fn ($b) => $b['status'] === 'approved' && strtotime($b['end_at']) > time())),
                'latest'   => array_slice($mine, 0, 5),
            ];
        }

        return view('dashboard/index', $data);
    }
}
