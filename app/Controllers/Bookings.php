<?php

namespace App\Controllers;

use App\Libraries\NotificationService;
use App\Models\BookingModel;
use App\Models\FacilityModel;

/** Customer side: request a booking, see my bookings, cancel. */
class Bookings extends BaseController
{
    public function mine()
    {
        return view('bookings/mine', [
            'bookings' => (new BookingModel())->withDetails(['user_id' => (int) session()->get('user_id')]),
        ]);
    }

    public function create()
    {
        return view('bookings/create', [
            'facilities' => (new FacilityModel())->active(),
            'selected'   => (int) $this->request->getGet('facility_id'),
            'start'      => $this->request->getGet('start'),
        ]);
    }

    public function store()
    {
        $rules = [
            'facility_id' => 'required|is_natural_no_zero',
            'start_at'    => 'required',
            'end_at'      => 'required',
            'guests'      => 'required|is_natural_no_zero',
            'purpose'     => 'permit_empty|max_length[255]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $facility = (new FacilityModel())->find((int) $this->request->getPost('facility_id'));
        if (! $facility || $facility['status'] !== 'active') {
            return redirect()->back()->withInput()->with('error', 'That facility is not available for booking.');
        }

        $startTs = strtotime((string) $this->request->getPost('start_at'));
        $endTs   = strtotime((string) $this->request->getPost('end_at'));
        $guests  = (int) $this->request->getPost('guests');

        if (! $startTs || ! $endTs) {
            return redirect()->back()->withInput()->with('error', 'Please enter valid start and end date/time.');
        }
        if ($startTs < time()) {
            return redirect()->back()->withInput()->with('error', 'Start time must be in the future.');
        }
        if ($endTs <= $startTs) {
            return redirect()->back()->withInput()->with('error', 'End time must be after the start time.');
        }
        if ($guests > (int) $facility['capacity']) {
            return redirect()->back()->withInput()->with('error', 'Maximum capacity for ' . $facility['name'] . ' is ' . $facility['capacity'] . ' players.');
        }

        $startAt  = date('Y-m-d H:i:s', $startTs);
        $endAt    = date('Y-m-d H:i:s', $endTs);
        $bookings = new BookingModel();

        if ($bookings->hasConflict((int) $facility['id'], $startAt, $endAt)) {
            return redirect()->back()->withInput()->with('error', 'Sorry, that time is already booked. Please check the calendar and pick another time.');
        }

        $id = $bookings->insert([
            'facility_id' => $facility['id'],
            'user_id'     => (int) session()->get('user_id'),
            'start_at'    => $startAt,
            'end_at'      => $endAt,
            'guests'      => $guests,
            'purpose'     => trim((string) $this->request->getPost('purpose')),
            'status'      => 'pending',
        ], true);

        $notify = new NotificationService();
        $notify->notifyAdmins(
            'New booking request',
            session()->get('name') . ' requested ' . $facility['name'] . ' from ' . fmt_dt($startAt) . ' to ' . fmt_dt($endAt) . '.',
            (int) $id
        );
        $notify->notifyUser(
            (int) session()->get('user_id'),
            'Booking request sent',
            'Your request for ' . $facility['name'] . ' (' . fmt_dt($startAt) . ' - ' . fmt_dt($endAt) . ') is waiting for admin approval.',
            (int) $id
        );

        return redirect()->to('/my-bookings')->with('success', 'Booking request sent! You will be notified once it is reviewed.');
    }

    public function cancel(int $id)
    {
        $bookings = new BookingModel();
        $booking  = $bookings->findWithDetails($id);

        if (! $booking || (int) $booking['user_id'] !== (int) session()->get('user_id')) {
            return redirect()->to('/my-bookings')->with('error', 'Booking not found.');
        }
        if (! in_array($booking['status'], ['pending', 'approved'], true)) {
            return redirect()->to('/my-bookings')->with('error', 'This booking can no longer be cancelled.');
        }

        $bookings->update($id, ['status' => 'cancelled']);

        (new NotificationService())->notifyAdmins(
            'Booking cancelled by customer',
            $booking['customer_name'] . ' cancelled the booking for ' . $booking['facility_name'] . ' (' . fmt_dt($booking['start_at']) . ').',
            $id
        );

        return redirect()->to('/my-bookings')->with('success', 'Booking cancelled.');
    }
}
