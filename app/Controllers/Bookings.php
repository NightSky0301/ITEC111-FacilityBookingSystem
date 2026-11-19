<?php

namespace App\Controllers;

use App\Libraries\Addons;
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
                'outdoor' => facility_is_outdoor($f),
                'head'  => FacilityModel::isPerHead($f),
                'max'   => FacilityModel::maxPeople($f),
            ], $facilities->active()),
            'addons'     => Addons::CATALOG,
            'eveningFrom' => Addons::EVENING_FROM,
            'open'       => FacilityModel::OPEN_HOUR,
            'close'      => FacilityModel::CLOSE_HOUR,
            'minHours'   => FacilityModel::MIN_HOURS,
            'maxHours'   => FacilityModel::MAX_HOURS,
            'stayUnits'  => FacilityModel::STAY_UNITS,
            'refPool'    => Addons::REFEREE_POOL,
            'dayHours'   => FacilityModel::CLOSE_HOUR - FacilityModel::OPEN_HOUR,
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
        $sessions = [];
        $facility = (new FacilityModel())->find($facilityId);
        $bookings = new BookingModel();
        $head     = $facility && FacilityModel::isPerHead($facility);

        if ($head) {
            // Per-head facility: a half hour is "full" when the people already approved plus the people
            // in this request would go over the limit. Everyone else can still book the other half hours.
            $people = max(1, min(FacilityModel::maxPeople($facility), (int) $this->request->getGet('people')));
            for ($m = FacilityModel::OPEN_HOUR * 60; $m < FacilityModel::CLOSE_HOUR * 60; $m += 30) {
                $from = date('Y-m-d H:i:s', $dayStart + $m * 60);
                $to   = date('Y-m-d H:i:s', $dayStart + ($m + 30) * 60);
                if (! $bookings->hasRoom($facility, $from, $to, $people)) {
                    $last = count($busy) - 1;
                    if ($last >= 0 && $busy[$last][1] === $m) { $busy[$last][1] = $m + 30; } else { $busy[] = [$m, $m + 30]; }
                }
            }
        }
        foreach ($bookings->sessionsBetween($facilityId, date('Y-m-d H:i:s', $dayStart), date('Y-m-d H:i:s', $dayEnd)) as $b) {
            $s = (int) max(0, round((strtotime($b['start_at']) - $dayStart) / 60));
            $e = (int) min(1440, round((strtotime($b['end_at']) - $dayStart) / 60));
            if ($head) { $sessions[] = [$s, $e, max(1, (int) $b['guests'])]; } else { $busy[] = [$s, $e]; }
        }

        return $this->response->setJSON([
            'date'  => $date,
            'today' => date('Y-m-d'),
            'now'   => (int) date('G') * 60 + (int) date('i'),
            'open'  => FacilityModel::OPEN_HOUR * 60,
            'close' => FacilityModel::CLOSE_HOUR * 60,
            'busy'  => $busy,
            'head'  => $head,
            'cap'   => $head ? FacilityModel::maxPeople($facility) : null,
            'sessions' => $sessions,
            'refPool'  => Addons::REFEREE_POOL,
            'ref'      => $bookings->refereeUsage($dayStart, FacilityModel::OPEN_HOUR * 60, FacilityModel::CLOSE_HOUR * 60),
        ]);
    }

    /** JSON: is the court free for a whole stay (days / weeks / months) that starts at this date and time? */
    public function span()
    {
        $facilityId = (int) $this->request->getGet('facility_id');
        $date       = (string) $this->request->getGet('date');
        $time       = (string) $this->request->getGet('time');
        $unit       = (string) $this->request->getGet('unit');
        $count      = (int) $this->request->getGet('count');
        $facility   = (new FacilityModel())->find($facilityId);
        $dt         = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)
            ? \DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time) : false;
        $stay = $dt ? FacilityModel::stay($unit, $count, $dt->getTimestamp()) : null;
        if (! $facility || ! $stay) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Bad request']);
        }

        $startAt  = date('Y-m-d H:i:s', $dt->getTimestamp());
        $endAt    = date('Y-m-d H:i:s', $stay['endTs']);
        $bookings = new BookingModel();
        if (FacilityModel::isPerHead($facility)) {
            $people = max(1, min(FacilityModel::maxPeople($facility), (int) $this->request->getGet('people')));
            $free   = $bookings->hasRoom($facility, $startAt, $endAt, $people);
            $clash  = null;
        } else {
            $clash = $bookings->firstClash($facilityId, $startAt, $endAt);
            $free  = $clash === null;
        }

        return $this->response->setJSON([
            'free'   => $free,
            'clash'  => $clash ? date('D, M j', strtotime($clash)) : null,
            'days'   => $stay['days'],
            'hours'  => $stay['hours'],
            'endDay' => date('D, M j, Y', $stay['endTs']),
            'endAt'  => date('g:i A', $stay['endTs']),
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
            return redirect()->back()->withInput()->with('error', 'Please choose a valid arrival time.');
        }
        // Anything earlier than the current minute has already passed (5:00 PM is still fine at 5:00:30 PM).
        if ($startTs < strtotime(date('Y-m-d H:i:00'))) {
            return redirect()->back()->withInput()->with('error', 'That time has already passed. Please choose a time from now onward.');
        }

        // Hourly booking (1 hour minimum, one hour at a time) OR a longer stay of days / weeks / months.
        // The time out is always calculated here, never taken from the form.
        $stayUnit  = trim((string) $this->request->getPost('stay_unit'));
        $stayCount = (int) $this->request->getPost('stay_count');
        $stay      = null;
        if ($stayUnit !== '') {
            $stay = FacilityModel::stay($stayUnit, $stayCount, $startTs);
            if (! $stay) {
                return redirect()->back()->withInput()->with('error', 'Please choose how many days, weeks or months you need.');
            }
            $hours = $stay['hours'];
            $endTs = $stay['endTs'];
            $opens = strtotime($date . ' ' . sprintf('%02d:00', FacilityModel::OPEN_HOUR));
            if ($startTs < $opens || $startTs >= strtotime($date . ' ' . sprintf('%02d:00', FacilityModel::CLOSE_HOUR))) {
                return redirect()->back()->withInput()->with('error', 'Please choose an arrival time inside opening hours.');
            }
        } else {
            $rawHours = trim((string) $this->request->getPost('hours'));
            $hours    = $rawHours === '' ? FacilityModel::MIN_HOURS : (ctype_digit($rawHours) ? (int) $rawHours : 0);
            if ($hours < FacilityModel::MIN_HOURS || $hours > FacilityModel::MAX_HOURS) {
                return redirect()->back()->withInput()->with('error', 'An hourly booking is ' . FacilityModel::MIN_HOURS . ' to ' . FacilityModel::MAX_HOURS . ' hours, added one hour at a time. For longer, choose days, weeks or months.');
            }
            $endTs = $startTs + FacilityModel::durationMinutes($hours) * 60;

            // The whole session has to fall inside opening hours, on the same day.
            $opens  = strtotime($date . ' ' . sprintf('%02d:00', FacilityModel::OPEN_HOUR));
            $closes = strtotime($date . ' ' . sprintf('%02d:00', FacilityModel::CLOSE_HOUR));
            if ($startTs < $opens || $endTs > $closes) {
                return redirect()->back()->withInput()->with('error', 'Bookings must fall within opening hours (' . date('g:i A', $opens) . ' – ' . date('g:i A', $closes) . '). Choose an earlier time in or fewer hours.');
            }
        }

        // Courts are booked as a whole; the suggested number of people is information only.
        // The gym is booked per person: the customer chooses how many, up to its limit.
        $perHead = FacilityModel::isPerHead($facility);
        $guests  = FacilityModel::peopleRange($facility)[1];
        if ($perHead) {
            $rawPeople = trim((string) $this->request->getPost('people'));
            $guests    = ctype_digit($rawPeople) ? (int) $rawPeople : 0;
            if ($guests < 1 || $guests > FacilityModel::maxPeople($facility)) {
                return redirect()->back()->withInput()->with('error', 'Choose between 1 and ' . FacilityModel::maxPeople($facility) . ' people for the gym.');
            }
        }

        // Add-ons: lights (evening only), fan or AC (one of them), rentals. Priced here, never taken from the form.
        $keys   = (array) $this->request->getPost('addons');
        $keys[] = (string) $this->request->getPost('cooling');
        $addons = Addons::resolve($keys, (string) $facility['type'], (int) date('G', $startTs) * 60 + (int) date('i', $startTs), $hours, facility_is_outdoor($facility), $perHead ? $guests : 1, $stay !== null);
        if (is_string($addons)) {
            return redirect()->back()->withInput()->with('error', $addons);
        }

        $startAt  = date('Y-m-d H:i:s', $startTs);
        $endAt    = date('Y-m-d H:i:s', $endTs);
        $total    = FacilityModel::totalPrice((float) $facility['price_per_hour'], $hours, $perHead ? $guests : 1) + $addons['total'];
        $bookings = new BookingModel();

        if ($perHead) {
            if (! $bookings->hasRoom($facility, $startAt, $endAt, $guests)) {
                return redirect()->back()->withInput()->with('error', 'Sorry, the gym does not have room for ' . $guests . ' ' . ($guests === 1 ? 'person' : 'people') . ' at that time (limit ' . FacilityModel::maxPeople($facility) . '). Pick another time or fewer people.');
            }
        } elseif ($bookings->hasConflict((int) $facility['id'], $startAt, $endAt)) {
            return redirect()->back()->withInput()->with('error', 'Sorry, that time is already booked. Please check the schedule and pick another time.');
        }

        $wantsReferee = in_array(Addons::REFEREE_KEY, array_column($addons['items'], 'key'), true);
        if ($wantsReferee && ! $bookings->refereeAvailable($startAt, $endAt)) {
            return redirect()->back()->withInput()->with('error', 'Sorry, all ' . Addons::REFEREE_POOL . ' referees are already booked during that time. Pick another time or remove the referee.');
        }

        $auto = is_manager();
        $id = $bookings->insert([
            'facility_id' => $facility['id'],
            'user_id'     => (int) session()->get('user_id'),
            'start_at'    => $startAt,
            'end_at'      => $endAt,
            'guests'      => $guests,
            'addons'      => $addons['items'] ? json_encode($addons['items']) : null,
            'addons_total' => $addons['total'],
            'purpose'     => trim((string) $this->request->getPost('purpose')),
            'status'      => $auto ? 'approved' : 'pending',
            'approved_by' => $auto ? (int) session()->get('user_id') : null,
        ], true);

        // Two customers can press "Send" at the same moment: check again now that this booking is saved,
        // and undo it if it took a referee that was not there.
        if ($wantsReferee && $bookings->refereePeak($startAt, $endAt) > Addons::REFEREE_POOL) {
            $bookings->delete($id, true);

            return redirect()->back()->withInput()->with('error', 'Sorry, the last referee was just taken by another booking. Pick another time or remove the referee.');
        }

        $notify = new NotificationService();
        if ($auto) {
            // Admin and staff bookings need no approval: the time is theirs straight away.
            $notify->notifyUser((int) session()->get('user_id'), 'Booking confirmed', 'Your booking for ' . $facility['name'] . ($perHead ? ' (' . $guests . ' ' . ($guests === 1 ? 'person' : 'people') . ')' : '') . ' (' . fmt_dt($startAt) . ' - ' . fmt_dt($endAt) . ') is confirmed.', (int) $id);

            return redirect()->to('/manage/bookings?status=approved')->with('success', 'Booked ' . fmt_range($startAt, $endAt) . ' (₱' . number_format($total, 2) . '). It is confirmed, no approval needed.');
        }
        $notify->notifyManagers(
            'New booking request',
            session()->get('name') . ' requested ' . $facility['name'] . ' from ' . fmt_dt($startAt) . ' to ' . fmt_dt($endAt) . '.',
            (int) $id
        );
        $notify->notifyUser(
            (int) session()->get('user_id'),
            'Booking request sent',
            'Your request for ' . $facility['name'] . ($perHead ? ' (' . $guests . ' ' . ($guests === 1 ? 'person' : 'people') . ')' : '') . ' (' . fmt_dt($startAt) . ' - ' . fmt_dt($endAt) . ') is waiting for admin approval.',
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
