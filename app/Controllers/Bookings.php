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
            'stayMax'    => FacilityModel::STAY_MAX,
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
        $facility = $facilityId > 0 ? (new FacilityModel())->find($facilityId) : null;
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

    /** JSON: is the court free on every day of a stay (same arrival time and hours each day)? */
    public function span()
    {
        $facilityId = (int) $this->request->getGet('facility_id');
        $date       = (string) $this->request->getGet('date');
        $time       = (string) $this->request->getGet('time');
        $hours      = (int) $this->request->getGet('hours');
        $facility   = $facilityId > 0 ? (new FacilityModel())->find($facilityId) : null;
        $dt         = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)
            ? \DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time) : false;
        $n = FacilityModel::stayDays((int) $this->request->getGet('months'), (int) $this->request->getGet('weeks'), (int) $this->request->getGet('days'));
        if (! $facility || ! $dt || $n < 1 || $hours < FacilityModel::MIN_HOURS || $hours > FacilityModel::MAX_HOURS) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Bad request']);
        }

        $bookings = new BookingModel();
        $head     = FacilityModel::isPerHead($facility);
        $people   = $head ? max(1, min(FacilityModel::maxPeople($facility), (int) $this->request->getGet('people'))) : 1;
        $wantsRef = (string) $this->request->getGet('ref') === '1';
        $clashes  = [];
        $refShort = [];
        foreach (FacilityModel::stayDates($date, $n) as $d) {
            $startTs = strtotime($d . ' ' . $time);
            $startAt = date('Y-m-d H:i:s', $startTs);
            $endAt   = date('Y-m-d H:i:s', $startTs + $hours * 3600);
            $ok      = $head ? $bookings->hasRoom($facility, $startAt, $endAt, $people) : $bookings->firstClash($facilityId, $startAt, $endAt) === null;
            if (! $ok) {
                $clashes[] = date('D, M j', $startTs);
            }
            if ($wantsRef && ! $bookings->refereeAvailable($startAt, $endAt)) {
                $refShort[] = date('D, M j', $startTs);
            }
        }

        return $this->response->setJSON([
            'free'     => $clashes === [],
            'clashes'  => $clashes,
            'refShort' => $refShort,
            'days'     => $n,
            'hours'    => $hours,
            'lastDay'  => date('D, M j, Y', strtotime($date . ' +' . ($n - 1) . ' days')),
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

        // Hourly booking (one day, 1 to 16 hours) OR a stay: the same arrival time and hours repeat every day
        // for the chosen months / weeks / days. Each day is saved as its own booking.
        $sm = (int) $this->request->getPost('stay_months');
        $sw = (int) $this->request->getPost('stay_weeks');
        $sd = (int) $this->request->getPost('stay_days');
        $stayN = 1;
        if ($sm + $sw + $sd > 0) {
            $stayN = FacilityModel::stayDays($sm, $sw, $sd);
            if ($stayN < 1) {
                return redirect()->back()->withInput()->with('error', 'That stay length is not allowed (up to ' . FacilityModel::STAY_MAX['months'] . ' months, ' . FacilityModel::STAY_MAX['weeks'] . ' weeks and ' . FacilityModel::STAY_MAX['days'] . ' days).');
            }
        }
        $stay     = $stayN > 1 ? true : false;
        $rawHours = trim((string) $this->request->getPost('hours'));
        $hours    = $rawHours === '' ? FacilityModel::MIN_HOURS : (ctype_digit($rawHours) ? (int) $rawHours : 0);
        if ($hours < FacilityModel::MIN_HOURS || $hours > FacilityModel::MAX_HOURS) {
            return redirect()->back()->withInput()->with('error', 'Choose ' . FacilityModel::MIN_HOURS . ' to ' . FacilityModel::MAX_HOURS . ' hours, added one hour at a time.');
        }
        $endTs = $startTs + FacilityModel::durationMinutes($hours) * 60;

        // Every day has to fall inside opening hours (the hours repeat daily, so one check covers them all).
        $opens  = strtotime($date . ' ' . sprintf('%02d:00', FacilityModel::OPEN_HOUR));
        $closes = strtotime($date . ' ' . sprintf('%02d:00', FacilityModel::CLOSE_HOUR));
        if ($startTs < $opens || $endTs > $closes) {
            return redirect()->back()->withInput()->with('error', 'Bookings must fall within opening hours (' . date('g:i A', $opens) . ' – ' . date('g:i A', $closes) . '). Choose an earlier time in or fewer hours.');
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
        $addons = Addons::resolve($keys, (string) $facility['type'], (int) date('G', $startTs) * 60 + (int) date('i', $startTs), $hours, facility_is_outdoor($facility), $perHead ? $guests : 1);
        if (is_string($addons)) {
            return redirect()->back()->withInput()->with('error', $addons);
        }

        $wantsReferee = in_array(Addons::REFEREE_KEY, array_column($addons['items'], 'key'), true);
        $perDay       = FacilityModel::totalPrice((float) $facility['price_per_hour'], $hours, $perHead ? $guests : 1) + $addons['total'];
        $total        = $perDay * $stayN;
        $bookings     = new BookingModel();

        // Build every day of the stay and check each one before anything is saved.
        $days = [];
        foreach (FacilityModel::stayDates($date, $stayN) as $d) {
            $sTs = strtotime($d . ' ' . date('H:i:s', $startTs));
            $days[] = [date('Y-m-d H:i:s', $sTs), date('Y-m-d H:i:s', $sTs + FacilityModel::durationMinutes($hours) * 60)];
        }
        $clashes = [];
        $refShort = [];
        foreach ($days as [$sAt, $eAt]) {
            $ok = $perHead ? $bookings->hasRoom($facility, $sAt, $eAt, $guests) : ! $bookings->hasConflict((int) $facility['id'], $sAt, $eAt);
            if (! $ok) {
                $clashes[] = date('D, M j', strtotime($sAt));
            }
            if ($wantsReferee && ! $bookings->refereeAvailable($sAt, $eAt)) {
                $refShort[] = date('D, M j', strtotime($sAt));
            }
        }
        if ($clashes) {
            $msg = $perHead
                ? 'Sorry, the gym does not have room for ' . $guests . ' ' . ($guests === 1 ? 'person' : 'people') . ' on ' . implode(', ', array_slice($clashes, 0, 4)) . (count($clashes) > 4 ? ' and ' . (count($clashes) - 4) . ' more' : '') . '. Pick other days or fewer people.'
                : (! $stay ? 'Sorry, that time is already booked. Please check the schedule and pick another time.'
                    : 'Sorry, that time is already booked on ' . implode(', ', array_slice($clashes, 0, 4)) . (count($clashes) > 4 ? ' and ' . (count($clashes) - 4) . ' more day(s)' : '') . '. Choose other days or another time.');

            return redirect()->back()->withInput()->with('error', $msg);
        }
        if ($refShort) {
            return redirect()->back()->withInput()->with('error', 'Sorry, all ' . Addons::REFEREE_POOL . ' referees are already booked on ' . implode(', ', array_slice($refShort, 0, 4)) . (count($refShort) > 4 ? ' and ' . (count($refShort) - 4) . ' more day(s)' : '') . '. Pick other days or remove the referee.');
        }

        $auto = is_manager();
        $uid  = (int) session()->get('user_id');
        $purpose = trim((string) $this->request->getPost('purpose'));
        $row  = static fn (string $sAt, string $eAt, ?int $group): array => [
            'group_id'     => $group,
            'facility_id'  => $facility['id'],
            'user_id'      => $uid,
            'start_at'     => $sAt,
            'end_at'       => $eAt,
            'guests'       => $guests,
            'addons'       => $addons['items'] ? json_encode($addons['items']) : null,
            'addons_total' => $addons['total'],
            'purpose'      => $purpose,
            'status'       => $auto ? 'approved' : 'pending',
            'approved_by'  => $auto ? $uid : null,
        ];

        $db = \Config\Database::connect();
        $db->transStart();
        $ids = [];
        foreach ($days as $i => [$sAt, $eAt]) {
            $ids[] = (int) $bookings->insert($row($sAt, $eAt, $i === 0 ? null : $ids[0]), true);
        }
        if ($stayN > 1) {
            $bookings->update($ids[0], ['group_id' => $ids[0]]);
        }
        $db->transComplete();
        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Sorry, the booking could not be saved. Please try again.');
        }
        $id = $ids[0];

        // Two customers can press "Send" at the same moment: check again now that this booking is saved,
        // and undo it if it took a referee that was not there.
        if ($wantsReferee) {
            foreach ($days as [$sAt, $eAt]) {
                if ($bookings->refereePeak($sAt, $eAt) > Addons::REFEREE_POOL) {
                    $bookings->whereIn('id', $ids)->delete(null, true);

                    return redirect()->back()->withInput()->with('error', 'Sorry, the last referee was just taken by another booking. Pick another time or remove the referee.');
                }
            }
        }

        $lastEnd = $days[$stayN - 1][1];
        $what    = $stay
            ? $stayN . ' days, ' . date('M j', $startTs) . ' – ' . date('M j', strtotime($lastEnd)) . ', ' . date('g:i A', $startTs) . ' – ' . date('g:i A', $endTs) . ' each day'
            : fmt_range($days[0][0], $days[0][1]);
        $who     = $perHead ? ' (' . $guests . ' ' . ($guests === 1 ? 'person' : 'people') . ')' : '';

        $notify = new NotificationService();
        if ($auto) {
            // Admin and staff bookings need no approval: the time is theirs straight away.
            $notify->notifyUser($uid, 'Booking confirmed', 'Your booking for ' . $facility['name'] . $who . ' (' . $what . ') is confirmed.', $id);

            return redirect()->to('/manage/bookings?status=approved')->with('success', 'Booked ' . $what . ' (₱' . number_format($total, 2) . '). It is confirmed, no approval needed.');
        }
        $notify->notifyManagers('New booking request', session()->get('name') . ' requested ' . $facility['name'] . ' for ' . $what . '.', $id);
        $notify->notifyUser($uid, 'Booking request sent', 'Your request for ' . $facility['name'] . $who . ' (' . $what . ') is waiting for admin approval.', $id);

        return redirect()->to('/my-bookings')->with('success', 'Booking request sent for ' . $what . ' (₱' . number_format($total, 2) . '). You will be notified once it is reviewed.');
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

        // A stay is cancelled as a whole: every day that has not ended yet. ?day=1 cancels only this one day.
        $rows = $this->request->getPost('single') === '1'
            ? [$booking]
            : $bookings->groupRows($booking, ['pending', 'approved'], true);
        foreach ($rows as $r) {
            $bookings->update((int) $r['id'], ['status' => 'cancelled']);
        }

        $n = count($rows);
        (new NotificationService())->notifyManagers(
            'Booking cancelled by customer',
            $booking['customer_name'] . ' cancelled ' . ($n > 1 ? $n . ' days of the booking' : 'the booking') . ' for ' . $booking['facility_name'] . ' (' . fmt_dt($booking['start_at']) . ').',
            $id
        );

        return redirect()->to('/my-bookings')->with('success', $n > 1 ? 'Booking cancelled (' . $n . ' days).' : 'Booking cancelled.');
    }
}
