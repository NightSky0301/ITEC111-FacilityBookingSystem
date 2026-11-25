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
        if (is_manager()) {
            return redirect()->to('/dashboard')->with('error', 'Staff and admin accounts cannot make bookings. Customers book from their own accounts.');
        }
        $facilities = new FacilityModel();

        // Links pass ?start=2026-11-14T15:00 (date and time) or just ?start=2026-11-14 (the court calendar).
        // The form has a separate date and time, so split it here. A bare date leaves the time to the customer.
        $startDate = '';
        $startTime = '';
        $endDate   = '';
        $rawStart  = (string) $this->request->getGet('start');
        if ($rawStart !== '' && ($ts = strtotime($rawStart))) {
            $startDate = date('Y-m-d', $ts);
            $startTime = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawStart) ? '' : date('H:i', $ts);
            $endDate   = valid_end_date($startDate, (string) $this->request->getGet('end'));   // a Flexible Days search from the home page
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
            'endDate'    => $endDate,
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
                'open'  => FacilityModel::hoursFor($f)[0],
                'close' => FacilityModel::hoursFor($f)[1],
                'hours' => min(FacilityModel::MAX_HOURS, FacilityModel::maxHoursFor($f)),
                'addons' => array_keys(Addons::forFacility($f)),
            ], $facilities->active()),
            'addons'     => Addons::all(),
            'eveningFrom' => Addons::EVENING_FROM,
            'open'       => FacilityModel::OPEN_HOUR,
            'close'      => FacilityModel::CLOSE_HOUR,
            'minHours'   => FacilityModel::MIN_HOURS,
            'maxHours'   => FacilityModel::MAX_HOURS,
            'safetyDays' => FacilityModel::STAY_SAFETY_DAYS,
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
        [$openH, $closeH] = $facility ? FacilityModel::hoursFor($facility) : [FacilityModel::OPEN_HOUR, FacilityModel::CLOSE_HOUR];

        if ($head) {
            // Per-head facility: a half hour is "full" when the people already approved plus the people
            // in this request would go over the limit. Everyone else can still book the other half hours.
            $people = max(1, min(FacilityModel::maxPeople($facility), (int) $this->request->getGet('people')));
            for ($m = $openH * 60; $m < $closeH * 60; $m += 30) {
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
            'open'  => $openH * 60,
            'close' => $closeH * 60,
            'busy'  => $busy,
            'head'  => $head,
            'cap'   => $head ? FacilityModel::maxPeople($facility) : null,
            'sessions' => $sessions,
            'refPool'  => Addons::REFEREE_POOL,
            'ref'      => $bookings->refereeUsage($dayStart, $openH * 60, $closeH * 60),
        ]);
    }

    /** Reads the common query parameters of span() and days(). Returns null when something is missing or wrong. */
    private function stayParams(): ?array
    {
        $facilityId = (int) $this->request->getGet('facility_id');
        $time       = (string) $this->request->getGet('time');
        $hours      = (int) $this->request->getGet('hours');
        $facility   = $facilityId > 0 ? (new FacilityModel())->find($facilityId) : null;
        if (! $facility || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) || $hours < FacilityModel::MIN_HOURS || $hours > FacilityModel::maxHoursFor($facility)) {
            return null;
        }
        $head = FacilityModel::isPerHead($facility);

        return [
            'facility' => $facility,
            'time'     => $time,
            'hours'    => $hours,
            'head'     => $head,
            'people'   => $head ? max(1, min(FacilityModel::maxPeople($facility), (int) $this->request->getGet('people'))) : 1,
            'ref'      => (string) $this->request->getGet('ref') === '1',
        ];
    }

    /** One day of a stay: is the facility free, is a referee left, and how many other requests wait for the same time? */
    private function dayState(BookingModel $bookings, array $p, string $day): array
    {
        $startTs = strtotime($day . ' ' . $p['time']);
        $startAt = date('Y-m-d H:i:s', $startTs);
        $endAt   = date('Y-m-d H:i:s', $startTs + $p['hours'] * 3600);
        $fid     = (int) $p['facility']['id'];
        $free    = $p['head'] ? $bookings->hasRoom($p['facility'], $startAt, $endAt, $p['people']) : $bookings->firstClash($fid, $startAt, $endAt) === null;

        return [
            'free'    => $free,
            'refOk'   => ! $p['ref'] || $bookings->refereeAvailable($startAt, $endAt),
            'waiting' => $bookings->pendingOverlap($fid, $startAt, $endAt),
            'label'   => date('D, M j', $startTs),
        ];
    }

    /** JSON: is the facility free on every day from date to end_date (same arrival time and hours each day)? */
    public function span()
    {
        $date = (string) $this->request->getGet('date');
        $end  = (string) ($this->request->getGet('end_date') ?: $date);
        $p    = $this->stayParams();
        $n    = FacilityModel::daysBetween($date, $end);
        if (! $p || $n < 1) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Bad request']);
        }

        $bookings = new BookingModel();
        $clashes  = [];
        $refShort = [];
        $waiting  = 0;
        foreach (FacilityModel::stayDates($date, $n) as $d) {
            $s = $this->dayState($bookings, $p, $d);
            if (! $s['free']) { $clashes[] = $s['label']; }
            if (! $s['refOk']) { $refShort[] = $s['label']; }
            $waiting = max($waiting, $s['waiting']);
        }

        return $this->response->setJSON([
            'free'     => $clashes === [],
            'clashes'  => $clashes,
            'refShort' => $refShort,
            'waiting'  => $waiting,
            'days'     => $n,
            'hours'    => $p['hours'],
            'lastDay'  => date('D, M j, Y', strtotime($date . ' +' . ($n - 1) . ' days')),
        ]);
    }

    /** JSON for the range calendar: which days between from and to (at most 62) are already taken at this time. */
    public function days()
    {
        $from = (string) $this->request->getGet('from');
        $to   = (string) $this->request->getGet('to');
        $p    = $this->stayParams();
        $n    = FacilityModel::daysBetween($from, $to);
        if (! $p || $n < 1 || $n > 62) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Bad request']);
        }

        $bookings = new BookingModel();
        $taken    = [];
        $refShort = [];
        foreach (FacilityModel::stayDates($from, $n) as $d) {
            $s = $this->dayState($bookings, $p, $d);
            if (! $s['free']) { $taken[] = $d; }
            if (! $s['refOk']) { $refShort[] = $d; }
        }

        return $this->response->setJSON(['today' => date('Y-m-d'), 'taken' => $taken, 'refShort' => $refShort]);
    }

    public function store()
    {
        if (is_manager()) {
            return redirect()->to('/dashboard')->with('error', 'Staff and admin accounts cannot make bookings. Customers book from their own accounts.');
        }
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

        // By the hour (one day) OR multiple days: the same arrival time and hours repeat every day from the
        // first date to the last. Each day is saved as its own booking. There is no limit on how long.
        $endDate = trim((string) $this->request->getPost('end_date'));
        $stayN   = 1;
        if ($this->request->getPost('mode') === 'days' && $endDate !== '' && $endDate !== $date) {
            $stayN = FacilityModel::daysBetween($date, $endDate);
            if ($stayN < 1) {
                return redirect()->back()->withInput()->with('error', 'Please choose a last day that is the same as or after the first day.');
            }
        }
        $stay     = $stayN > 1;
        [$openH, $closeH] = FacilityModel::hoursFor($facility);
        $maxHours = min(FacilityModel::MAX_HOURS, $closeH - $openH);
        $rawHours = trim((string) $this->request->getPost('hours'));
        $hours    = $rawHours === '' ? FacilityModel::MIN_HOURS : (ctype_digit($rawHours) ? (int) $rawHours : 0);
        if ($hours < FacilityModel::MIN_HOURS || $hours > $maxHours) {
            return redirect()->back()->withInput()->with('error', 'Choose ' . FacilityModel::MIN_HOURS . ' to ' . $maxHours . ' hours, added one hour at a time.');
        }
        $endTs = $startTs + FacilityModel::durationMinutes($hours) * 60;

        // Every day has to fall inside this facility's opening hours (the hours repeat daily, so one check covers them all).
        $opens  = strtotime($date . ' ' . sprintf('%02d:00', $openH));
        $closes = strtotime($date . ' ' . sprintf('%02d:00', $closeH));
        if ($startTs < $opens || $endTs > $closes) {
            return redirect()->back()->withInput()->with('error', $facility['name'] . ' is open ' . date('g:i A', $opens) . ' – ' . ($closeH >= 24 ? '12:00 AM' : date('g:i A', $closes)) . '. Choose an earlier time in or fewer hours.');
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
        $addons = Addons::resolve($keys, (string) $facility['type'], (int) date('G', $startTs) * 60 + (int) date('i', $startTs), $hours, facility_is_outdoor($facility), $perHead ? $guests : 1, false, Addons::forFacility($facility));
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
        // Take turns with anyone saving the same facility at this moment, then look once more: the checks above
        // may be a few milliseconds old.
        $bookings->lockFacility((int) $facility['id']);
        foreach ($days as [$sAt, $eAt]) {
            $ok = $perHead ? $bookings->hasRoom($facility, $sAt, $eAt, $guests) : ! $bookings->hasConflict((int) $facility['id'], $sAt, $eAt);
            if (! $ok || ($wantsReferee && ! $bookings->refereeAvailable($sAt, $eAt))) {
                $db->transComplete();

                return redirect()->back()->withInput()->with('error', 'Sorry, that time was just taken by another booking. Please pick another time.');
            }
        }
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
        $waiting = 0;
        foreach ($days as [$sAt, $eAt]) {
            $waiting = max($waiting, $bookings->pendingOverlap((int) $facility['id'], $sAt, $eAt, $ids));
        }
        $note = $waiting > 0 ? ' Heads up: ' . $waiting . ' other request' . ($waiting === 1 ? ' is' : 's are') . ' also waiting for this time, and the first one approved gets it.' : '';
        $notify->notifyManagers('New booking request', session()->get('name') . ' requested ' . $facility['name'] . ' for ' . $what . '.', $id);
        $notify->notifyUser($uid, 'Booking request sent', 'Your request for ' . $facility['name'] . $who . ' (' . $what . ') is waiting for admin approval.', $id);

        return redirect()->to('/my-bookings')->with('success', 'Booking request sent for ' . $what . ' (₱' . number_format($total, 2) . '). You will be notified once it is reviewed.' . $note);
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

        // Pending requests can be cancelled any time; approved ones until 24 hours before they start.
        // After that the customer contacts the staff, who can always cancel.
        $targets = $this->request->getPost('single') === '1' ? [$booking] : $bookings->groupRows($booking, ['pending', 'approved'], true);
        $rows    = array_values(array_filter($targets, static fn ($r) => BookingModel::customerCanCancel($r)));
        $kept    = count($targets) - count($rows);
        if (! $rows) {
            return redirect()->to('/my-bookings')->with('error', 'Free cancellation for approved bookings closes ' . BookingModel::CANCEL_HOURS_BEFORE . ' hours before the start. Please contact the staff to cancel this one.');
        }
        foreach ($rows as $r) {
            $bookings->update((int) $r['id'], ['status' => 'cancelled']);
        }

        $n = count($rows);
        (new NotificationService())->notifyManagers(
            'Booking cancelled by customer',
            $booking['customer_name'] . ' cancelled ' . ($n > 1 ? $n . ' days of the booking' : 'the booking') . ' for ' . $booking['facility_name'] . ' (' . fmt_dt($booking['start_at']) . ').',
            $id
        );

        $msg = $n > 1 ? 'Booking cancelled (' . $n . ' days).' : 'Booking cancelled.';
        if ($kept > 0) {
            $msg .= ' ' . $kept . ' day' . ($kept === 1 ? ' starts' : 's start') . ' within ' . BookingModel::CANCEL_HOURS_BEFORE . ' hours and ' . ($kept === 1 ? 'was' : 'were') . ' kept; contact the staff for ' . ($kept === 1 ? 'it' : 'those') . '.';
        }

        return redirect()->to('/my-bookings')->with('success', $msg);
    }
}
