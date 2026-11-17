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
        if (FacilityModel::isPerHead((string) $booking['facility_type'])) {
            $room = $bookings->hasRoom(['id' => $booking['facility_id'], 'capacity' => $booking['facility_capacity']], $booking['start_at'], $booking['end_at'], max(1, (int) $booking['guests']), $id);
            if (! $room) {
                return redirect()->back()->with('error', 'Cannot approve: the gym would go over its ' . (int) $booking['facility_capacity'] . '-person limit during this time.');
            }
        } elseif ($bookings->hasConflict((int) $booking['facility_id'], $booking['start_at'], $booking['end_at'], $id)) {
            return redirect()->back()->with('error', 'Cannot approve: it overlaps with an already approved booking.');
        }

        $remarks = trim((string) $this->request->getPost('admin_remarks'));
        $bookings->update($id, ['status' => 'approved', 'admin_remarks' => $remarks, 'approved_by' => (int) session()->get('user_id')]);

        (new NotificationService())->notifyUser(
            (int) $booking['user_id'],
            'Booking approved',
            'Your booking for ' . $booking['facility_name'] . ' (' . fmt_dt($booking['start_at']) . ' - ' . fmt_dt($booking['end_at']) . ') was approved.' . ($remarks ? ' Note: ' . $remarks : ''),
            $id
        );

        return redirect()->back()->with('success', 'Booking approved.');
    }

    /** Desk confirms the customer has arrived. Only possible once the arrival time has come (to the minute). */
    public function arrive(int $id)
    {
        $bookings = new BookingModel();
        $booking  = $bookings->findWithDetails($id);

        if (! $booking || $booking['status'] !== 'approved') {
            return redirect()->back()->with('error', 'Only approved bookings can be checked in.');
        }
        if (! empty($booking['arrived_at'])) {
            return redirect()->back()->with('error', 'This arrival is already confirmed.');
        }
        $now = strtotime(date('Y-m-d H:i:00'));
        if ($now < strtotime($booking['start_at'])) {
            return redirect()->back()->with('error', 'Too early: arrival can be confirmed from ' . date('g:i A', strtotime($booking['start_at'])) . '.');
        }
        if (time() >= strtotime($booking['end_at'])) {
            return redirect()->back()->with('error', 'This session has already finished.');
        }

        $bookings->update($id, ['arrived_at' => date('Y-m-d H:i:s'), 'arrived_by' => (int) session()->get('user_id')]);

        return redirect()->back()->with('success', 'Arrival confirmed for ' . $booking['customer_name'] . ' at ' . $booking['facility_name'] . '.');
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
