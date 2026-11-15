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
        $facilities = new FacilityModel();

        // Links pass ?start=2026-11-14T15:00 (date and time) or just ?start=2026-11-14 (the court calendar).
        // The form has a separate date and time, so split it here. A bare date leaves the time to the customer.
        $startDate = '';
        $startTime = '';
        $rawStart  = (string) $this->request->getGet('start');
        if ($rawStart !== '' && ($ts = strtotime($rawStart))) {
            $startDate = date('Y-m-d', $ts);
            $startTime = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawStart) ? '' : date('H:i', $ts);
        }

        return view('bookings/create', [
            'facilities' => $facilities->active(),
            'activities' => array_combine(
                array_keys($facilities->activeByType()),
                array_map(static fn ($type) => FacilityModel::activityName($type), array_keys($facilities->activeByType()))
            ),
            'selected'   => (int) $this->request->getGet('facility_id'),
            'startDate'  => $startDate,
            'startTime'  => $startTime,
            'courts'     => array_map(static fn ($f) => [
                'id'    => (int) $f['id'],
                'type'  => $f['type'],
                'name'  => $f['name'],
                'price' => (float) $f['price_per_hour'],
                'img'   => facility_image($f),
                'people' => FacilityModel::peopleLabel($f),
            ], $facilities->active()),
            'open'       => FacilityModel::OPEN_HOUR,
            'close'      => FacilityModel::CLOSE_HOUR,
            'minHours'   => FacilityModel::MIN_HOURS,
            'maxHours'   => FacilityModel::MAX_HOURS,
        ]);
    }

    /** JSON for the time picker: approved sessions on one court and day, plus the server's clock. */
    public function busy()
    {
        $facilityId = (int) $this->request->getGet('facility_id');
        $date       = (string) $this->request->getGet('date');
        if ($facilityId < 1 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! strtotime($date)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Bad request']);
        }

        $dayStart = strtotime($date . ' 00:00:00');
        $dayEnd   = $dayStart + 86400;
        $busy     = [];
        foreach ((new BookingModel())->sessionsBetween($facilityId, date('Y-m-d H:i:s', $dayStart), date('Y-m-d H:i:s', $dayEnd)) as $b) {
            $busy[] = [
                (int) max(0, round((strtotime($b['start_at']) - $dayStart) / 60)),
                (int) min(1440, round((strtotime($b['end_at']) - $dayStart) / 60)),
            ];
        }

        return $this->response->setJSON([
            'date'  => $date,
            'today' => date('Y-m-d'),
            'now'   => (int) date('G') * 60 + (int) date('i'),
            'open'  => FacilityModel::OPEN_HOUR * 60,
            'close' => FacilityModel::CLOSE_HOUR * 60,
            'busy'  => $busy,
        ]);
    }

    public function store()
    {
        $rules = [
            'facility_id' => 'required|is_natural_no_zero',
            'purpose'     => 'permit_empty|max_length[255]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $facility = (new FacilityModel())->find((int) $this->request->getPost('facility_id'));
        if (! $facility || $facility['status'] !== 'active') {
            return redirect()->back()->withInput()->with('error', 'That facility is not available for booking.');
        }

        // Time in comes from two inputs: a date (Y-m-d) and a time (H:i). Check both strictly.
        $date    = trim((string) $this->request->getPost('start_date'));
        $time    = trim((string) $this->request->getPost('start_time'));
        $startTs = null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time)) {
            $dt     = \DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . substr($time, 0, 5));
            $errors = \DateTime::getLastErrors();
            if ($dt && (! $errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                $startTs = $dt->getTimestamp();
            }
        }
        if ($startTs === null) {
            return redirect()->back()->withInput()->with('error', 'Please choose a valid date and time in.');
        }
        // Anything earlier than the current minute has already passed (5:00 PM is still fine at 5:00:30 PM).
        if ($startTs < strtotime(date('Y-m-d H:i:00'))) {
            return redirect()->back()->withInput()->with('error', 'That time has already passed. Please choose a time from now onward.');
        }

        // Courts are rented per hour: 1 hour minimum, added one hour at a time with the + / - stepper.
        // The time out is always calculated here (time in + hours), never taken from the form.
        $rawHours = trim((string) $this->request->getPost('hours'));
        $hours    = $rawHours === '' ? FacilityModel::MIN_HOURS : (ctype_digit($rawHours) ? (int) $rawHours : 0);
        if ($hours < FacilityModel::MIN_HOURS || $hours > FacilityModel::MAX_HOURS) {
            return redirect()->back()->withInput()->with('error', 'A booking is ' . FacilityModel::MIN_HOURS . ' to ' . FacilityModel::MAX_HOURS . ' hours, added one hour at a time.');
        }
        $endTs = $startTs + FacilityModel::durationMinutes($hours) * 60;

        // The whole session has to fall inside opening hours, on the same day.
        $opens  = strtotime($date . ' ' . sprintf('%02d:00', FacilityModel::OPEN_HOUR));
        $closes = strtotime($date . ' ' . sprintf('%02d:00', FacilityModel::CLOSE_HOUR));
        if ($startTs < $opens || $endTs > $closes) {
            return redirect()->back()->withInput()->with('error', 'Bookings must fall within opening hours (' . date('g:i A', $opens) . ' – ' . date('g:i A', $closes) . '). Choose an earlier time in or fewer hours.');
        }

        // The form no longer asks for a head count; the suggested number of people is information only.
        $guests = FacilityModel::peopleRange($facility)[1];

        $startAt  = date('Y-m-d H:i:s', $startTs);
        $endAt    = date('Y-m-d H:i:s', $endTs);
        $total    = FacilityModel::totalPrice((float) $facility['price_per_hour'], $hours);
        $bookings = new BookingModel();

        if ($bookings->hasConflict((int) $facility['id'], $startAt, $endAt)) {
            return redirect()->back()->withInput()->with('error', 'Sorry, that time is already booked. Please check the schedule and pick another time.');
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
        $notify->notifyManagers(
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

        return redirect()->to('/my-bookings')->with(
            'success',
            'Booking request sent for ' . fmt_range($startAt, $endAt) . ' (₱' . number_format($total, 2) . '). You will be notified once it is reviewed.'
        );
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

        (new NotificationService())->notifyManagers(
            'Booking cancelled by customer',
            $booking['customer_name'] . ' cancelled the booking for ' . $booking['facility_name'] . ' (' . fmt_dt($booking['start_at']) . ').',
            $id
        );

        return redirect()->to('/my-bookings')->with('success', 'Booking cancelled.');
    }
}
