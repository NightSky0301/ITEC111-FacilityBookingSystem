<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\NotificationService;
use App\Models\BookingModel;
use App\Models\FacilityModel;

class Bookings extends BaseController
{
    public function index()
    {
        $status     = $this->request->getGet('status');
        $facilityId = (int) $this->request->getGet('facility_id');

        return view('admin/bookings/index', [
            'bookings'   => (new BookingModel())->withDetails([
                'status'      => $status ?: null,
                'facility_id' => $facilityId ?: null,
            ]),
            'facilities' => (new FacilityModel())->orderBy('name')->findAll(),
            'status'     => $status,
            'facilityId' => $facilityId,
        ]);
    }

    public function approve(int $id)
    {
        $bookings = new BookingModel();
        $booking  = $bookings->findWithDetails($id);

        if (! $booking || $booking['status'] !== 'pending') {
            return redirect()->back()->with('error', 'Only pending bookings can be approved.');
        }
        if ($bookings->hasConflict((int) $booking['facility_id'], $booking['start_at'], $booking['end_at'], $id)) {
            return redirect()->back()->with('error', 'Cannot approve: it overlaps with an already approved booking.');
        }

        $remarks = trim((string) $this->request->getPost('admin_remarks'));
        $bookings->update($id, ['status' => 'approved', 'admin_remarks' => $remarks]);

        (new NotificationService())->notifyUser(
            (int) $booking['user_id'],
            'Booking approved',
            'Your booking for ' . $booking['facility_name'] . ' (' . fmt_dt($booking['start_at']) . ' - ' . fmt_dt($booking['end_at']) . ') was approved.' . ($remarks ? ' Note: ' . $remarks : ''),
            $id
        );

        return redirect()->back()->with('success', 'Booking approved.');
    }

    public function reject(int $id)
    {
        $bookings = new BookingModel();
        $booking  = $bookings->findWithDetails($id);

        if (! $booking || $booking['status'] !== 'pending') {
            return redirect()->back()->with('error', 'Only pending bookings can be rejected.');
        }

        $remarks = trim((string) $this->request->getPost('admin_remarks'));
        $bookings->update($id, ['status' => 'rejected', 'admin_remarks' => $remarks]);

        (new NotificationService())->notifyUser(
            (int) $booking['user_id'],
            'Booking rejected',
            'Your booking for ' . $booking['facility_name'] . ' (' . fmt_dt($booking['start_at']) . ') was rejected.' . ($remarks ? ' Reason: ' . $remarks : ''),
            $id
        );

        return redirect()->back()->with('success', 'Booking rejected.');
    }

    public function cancel(int $id)
    {
        $bookings = new BookingModel();
        $booking  = $bookings->findWithDetails($id);

        if (! $booking || $booking['status'] !== 'approved') {
            return redirect()->back()->with('error', 'Only approved bookings can be cancelled by admin.');
        }

        $remarks = trim((string) $this->request->getPost('admin_remarks'));
        $bookings->update($id, ['status' => 'cancelled', 'admin_remarks' => $remarks]);

        (new NotificationService())->notifyUser(
            (int) $booking['user_id'],
            'Booking cancelled',
            'Your approved booking for ' . $booking['facility_name'] . ' (' . fmt_dt($booking['start_at']) . ') was cancelled by the admin.' . ($remarks ? ' Reason: ' . $remarks : ''),
            $id
        );

        return redirect()->back()->with('success', 'Booking cancelled.');
    }
}
