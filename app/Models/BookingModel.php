<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    /** The customer picks a 30-minute arrival window when booking (e.g. 12:00 – 12:30). Arrival can only be confirmed inside it. */
    public const ARRIVAL_WINDOW_MIN = 30;

    /** Customers can cancel an approved booking free of charge until this many hours before it starts (pending: any time). */
    public const CANCEL_HOURS_BEFORE = 24;

    /** Reminders go out this many hours before a booking starts. */
    public const REMIND_HOURS_BEFORE = 24;

    protected $table         = 'bookings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'group_id', 'facility_id', 'user_id', 'start_at', 'end_at', 'guests',
        'purpose', 'status', 'admin_remarks', 'addons', 'addons_total',
        'approved_by', 'arrived_at', 'arrived_by', 'total_price', 'reminded_at',
    ];
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;   // deleted rows go to the Archive and can be restored
    protected $deletedField   = 'deleted_at';

    /**
     * Is the facility already taken (APPROVED booking) in this time range?
     * Two ranges overlap when: existing.start < new.end AND existing.end > new.start
     */
    public function hasConflict(int $facilityId, string $startAt, string $endAt, ?int $ignoreId = null): bool
    {
        $builder = $this->where('facility_id', $facilityId)
            ->where('status', 'approved')
            ->where('start_at <', $endAt)
            ->where('end_at >', $startAt);

        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Most people already approved at the same moment anywhere in [$startAt, $endAt).
     * Used by per-head facilities (the gym), where bookings share the floor up to its capacity.
     */
    public function peakPeople(int $facilityId, string $startAt, string $endAt, $ignoreId = null): int
    {
        $rows = $this->where('facility_id', $facilityId)->where('status', 'approved')
            ->where('start_at <', $endAt)->where('end_at >', $startAt)->findAll();
        $rows = array_values(array_filter($rows, static fn ($r) => $ignoreId === null || (int) $r['id'] !== $ignoreId));
        if (! $rows) {
            return 0;
        }

        $from   = strtotime($startAt);
        $to     = strtotime($endAt);
        $points = [$from];
        foreach ($rows as $r) {
            foreach ([strtotime($r['start_at']), strtotime($r['end_at'])] as $t) {
                if ($t > $from && $t < $to) {
                    $points[] = $t;
                }
            }
        }

        $peak = 0;
        foreach (array_unique($points) as $t) {
            $sum = 0;
            foreach ($rows as $r) {
                if (strtotime($r['start_at']) <= $t && strtotime($r['end_at']) > $t) {
                    $sum += max(1, (int) $r['guests']);
                }
            }
            $peak = max($peak, $sum);
        }

        return $peak;
    }

    /** Can $people more fit in this time range without going over the facility's capacity? */
    public function hasRoom(array $facility, string $startAt, string $endAt, int $people, ?int $ignoreId = null): bool
    {
        return $this->peakPeople((int) $facility['id'], $startAt, $endAt, $ignoreId) + $people <= FacilityModel::maxPeople($facility);
    }

    /**
     * Lock the facility row for the rest of the transaction so two people saving at the same moment
     * take turns (the second one re-checks after the first has been saved). No-op on SQLite, which locks the whole file.
     */
    public function lockFacility(int $facilityId): void
    {
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('SELECT id FROM facilities WHERE id = ? FOR UPDATE', [$facilityId]);
        }
    }

    /** How many OTHER pending requests overlap this time (they are not blocking it, but the admin can only approve one). */
    public function pendingOverlap(int $facilityId, string $startAt, string $endAt, $ignoreId = null): int
    {
        $b = $this->where('facility_id', $facilityId)->where('status', 'pending')
            ->where('start_at <', $endAt)->where('end_at >', $startAt);
        if (is_array($ignoreId) && $ignoreId) {
            $b->whereNotIn('id', $ignoreId);
        } elseif ($ignoreId !== null && ! is_array($ignoreId)) {
            $b->where('id !=', $ignoreId);
        }

        return $b->countAllResults();
    }

    /** May a customer still cancel this booking by themselves? Pending: yes. Approved: until CANCEL_HOURS_BEFORE before it starts. */
    public static function customerCanCancel(array $booking): bool
    {
        if ($booking['status'] === 'pending') {
            return strtotime($booking['end_at']) > time();
        }

        return $booking['status'] === 'approved'
            && strtotime($booking['start_at']) - time() >= self::CANCEL_HOURS_BEFORE * 3600;
    }

    /**
     * Approved bookings starting within REMIND_HOURS_BEFORE that have not been reminded yet get an in-app note
     * and an email, once. A stay is reminded day by day. Returns how many reminders were sent.
     */
    public function remindUpcoming(): int
    {
        helper('booking');
        $now  = date('Y-m-d H:i:s');
        $till = date('Y-m-d H:i:s', time() + self::REMIND_HOURS_BEFORE * 3600);
        $rows = $this->withDetails(['status' => 'approved']);
        $rows = array_filter($rows, static fn ($b) => empty($b['reminded_at']) && $b['start_at'] > $now && $b['start_at'] <= $till);
        $notify = new \App\Libraries\NotificationService();
        $n = 0;
        foreach ($rows as $b) {
            $notify->notifyUser((int) $b['user_id'], 'Booking reminder', 'Reminder: ' . $b['facility_name'] . ' starts ' . fmt_dt($b['start_at']) . '. Please arrive within your 30-minute arrival window.', (int) $b['id']);
            $this->update((int) $b['id'], ['reminded_at' => $now]);
            $n++;
        }

        return $n;
    }

    // ------------------------------------------------------------------ Stays (one booking per day)

    /**
     * Every day of the stay this booking belongs to (the booking itself when it is a single booking),
     * soonest first. Pass $statuses to keep only those statuses, $futureOnly to drop days that already ended.
     */
    public function groupRows(array $booking, ?array $statuses = null, bool $futureOnly = false): array
    {
        if (empty($booking['group_id'])) {
            $rows = [$booking];
        } else {
            $rows = $this->where('group_id', (int) $booking['group_id'])->orderBy('start_at')->findAll();
        }

        return array_values(array_filter($rows, static function (array $r) use ($statuses, $futureOnly): bool {
            return ($statuses === null || in_array($r['status'], $statuses, true))
                && (! $futureOnly || strtotime($r['end_at']) > time());
        }));
    }

    // ------------------------------------------------------------------ Referee pool
    // Referees are shared by every facility. A referee is "out" only between the start and the end of the
    // booking that has one, so he is back in the pool the moment that booking ends (or is cancelled / rejected).

    /** Pending and approved bookings with a referee that overlap [$from, $to). */
    private function refereeBookings(string $from, string $to, ?int $ignoreId = null): array
    {
        $builder = $this->select('id, start_at, end_at')
            ->whereIn('status', ['pending', 'approved'])
            ->like('addons', '"key":"' . \App\Libraries\Addons::REFEREE_KEY . '"')
            ->where('start_at <', $to)
            ->where('end_at >', $from);
        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->findAll();
    }

    /** Most referees out at the same moment anywhere in [$startAt, $endAt). */
    public function refereePeak(string $startAt, string $endAt, $ignoreId = null): int
    {
        $rows = $this->refereeBookings($startAt, $endAt, $ignoreId);
        if (! $rows) {
            return 0;
        }
        $from   = strtotime($startAt);
        $to     = strtotime($endAt);
        $points = [$from];
        foreach ($rows as $r) {
            foreach ([strtotime($r['start_at']), strtotime($r['end_at'])] as $t) {
                if ($t > $from && $t < $to) {
                    $points[] = $t;
                }
            }
        }
        $peak = 0;
        foreach (array_unique($points) as $t) {
            $n = 0;
            foreach ($rows as $r) {
                if (strtotime($r['start_at']) <= $t && strtotime($r['end_at']) > $t) {
                    $n++;
                }
            }
            $peak = max($peak, $n);
        }

        return $peak;
    }

    /** Can one more referee be added anywhere in this time range? */
    public function refereeAvailable(string $startAt, string $endAt, ?int $ignoreId = null): bool
    {
        return $this->refereePeak($startAt, $endAt, $ignoreId) < \App\Libraries\Addons::REFEREE_POOL;
    }

    /**
     * Referees out in every half hour of one day, for the booking form: one number per half hour from
     * $openMin to $closeMin (minutes after midnight).
     *
     * @return int[]
     */
    public function refereeUsage(int $dayStartTs, int $openMin, int $closeMin): array
    {
        $rows  = $this->refereeBookings(date('Y-m-d H:i:s', $dayStartTs), date('Y-m-d H:i:s', $dayStartTs + 86400));
        $slots = [];
        for ($m = $openMin; $m < $closeMin; $m += 30) {
            $a = $dayStartTs + $m * 60;
            $b = $a + 1800;
            $n = 0;
            foreach ($rows as $r) {
                if (strtotime($r['start_at']) < $b && strtotime($r['end_at']) > $a) {
                    $n++;
                }
            }
            $slots[] = $n;
        }

        return $slots;
    }

    /** Start of the first approved booking that overlaps the range, or null (used to explain a blocked stay). */
    public function firstClash(int $facilityId, string $startAt, string $endAt): ?string
    {
        $row = $this->where('facility_id', $facilityId)->where('status', 'approved')
            ->where('start_at <', $endAt)->where('end_at >', $startAt)->orderBy('start_at')->first();

        return $row ? max($row['start_at'], $startAt) : null;
    }

    /** Bookings of one facility that overlap [$from, $to), with the customer's name. */
    public function sessionsBetween(int $facilityId, string $from, string $to, array $statuses = ['approved']): array
    {
        return $this->select('bookings.*, users.name AS customer_name')
            ->join('users', 'users.id = bookings.user_id', 'left')
            ->where('bookings.facility_id', $facilityId)
            ->whereIn('bookings.status', $statuses)
            ->where('bookings.start_at <', $to)
            ->where('bookings.end_at >', $from)
            ->orderBy('bookings.start_at')
            ->findAll();
    }

    /** Bookings (a multi-day stay counts once) whose first day falls in this month, with the given status. */
    public function countThisMonth(string $status): int
    {
        return $this->where('status', $status)
            ->where('start_at >=', date('Y-m-01 00:00:00'))
            ->where('start_at <', date('Y-m-01 00:00:00', strtotime('first day of next month')))
            ->groupStart()->where('group_id', null)->orWhere('group_id = id', null, false)->groupEnd()
            ->countAllResults();
    }

    /**
     * Pending requests nobody reviewed before their start time can no longer happen, so they are rejected
     * automatically (every past day of a stay on its own) and the customer is told. Returns how many were rejected.
     */
    public function expirePending(): int
    {
        helper('booking');
        $now  = date('Y-m-d H:i:s');
        $rows = array_values(array_filter(
            $this->withDetails(['status' => 'pending']),
            static fn ($b) => $b['start_at'] < $now
        ));
        if (! $rows) {
            return 0;
        }

        $this->update(array_map(static fn ($b) => (int) $b['id'], $rows), [
            'status'        => 'rejected',
            'admin_remarks' => 'Automatically rejected: the booking time passed before it was reviewed.',
        ]);

        // One notification per request (a stay is one request, however many of its days expired).
        $notify = new \App\Libraries\NotificationService();
        $sent   = [];
        foreach ($rows as $b) {
            $key = (int) ($b['group_id'] ?: $b['id']);
            if (isset($sent[$key])) {
                continue;
            }
            $sent[$key] = true;
            $notify->notifyUser(
                (int) $b['user_id'],
                'Booking rejected',
                'Your request for ' . $b['facility_name'] . ' (' . fmt_dt($b['start_at']) . ') was automatically rejected because the booking time passed before it was reviewed.',
                (int) $b['id']
            );
        }

        return count($rows);
    }

    /** Is the facility in use right now, and when is the next approved session? */
    public function liveStatus(int $facilityId): array
    {
        $now = date('Y-m-d H:i:s');

        $current = $this->where('facility_id', $facilityId)->where('status', 'approved')
            ->where('start_at <=', $now)->where('end_at >', $now)->orderBy('start_at')->first();
        if ($current) {
            return ['in_use' => true, 'until' => $current['end_at'], 'next' => null];
        }

        $next = $this->where('facility_id', $facilityId)->where('status', 'approved')
            ->where('start_at >', $now)->orderBy('start_at')->first();

        return ['in_use' => false, 'until' => null, 'next' => $next['start_at'] ?? null];
    }

    /**
     * Facilities with the most bookings made this month (a multi-day stay counts once).
     * Pending and approved count; rejected and cancelled do not. Returns [facility_id => count], highest first.
     */
    public function topThisMonth(int $limit = 8): array
    {
        $rows = $this->select('facility_id, COUNT(*) AS n')
            ->whereIn('status', ['pending', 'approved'])
            ->where('created_at >=', date('Y-m-01 00:00:00'))
            ->groupStart()->where('group_id', null)->orWhere('group_id = id', null, false)->groupEnd()
            ->groupBy('facility_id')->orderBy('n', 'DESC')->orderBy('facility_id', 'ASC')
            ->findAll();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['facility_id']] = (int) $r['n'];
        }

        return $out;
    }

    /** Is this facility free right now? A gym needs room for one more person; everything else needs no approved session running. */
    public function isFreeNow(array $facility): bool
    {
        if (FacilityModel::isPerHead($facility)) {
            return $this->hasRoom($facility, date('Y-m-d H:i:s'), date('Y-m-d H:i:s', time() + 60), 1);
        }

        return ! $this->liveStatus((int) $facility['id'])['in_use'];
    }

    /** Bookings joined with facility + customer info, newest first. */
    public function withDetails(array $filters = []): array
    {
        $builder = $this->select('bookings.*, facilities.name AS facility_name, facilities.type AS facility_type, facilities.image AS facility_image, facilities.price_per_hour AS facility_price, '
                . 'users.name AS customer_name, users.email AS customer_email, approver.name AS approver_name, approver.role AS approver_role')
            ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
            ->join('users', 'users.id = bookings.user_id', 'left')
            ->join('users AS approver', 'approver.id = bookings.approved_by', 'left');

        if (! empty($filters['user_id'])) {
            $builder->where('bookings.user_id', $filters['user_id']);
        }
        if (! empty($filters['status'])) {
            $builder->where('bookings.status', $filters['status']);
        }
        if (! empty($filters['facility_id'])) {
            $builder->where('bookings.facility_id', $filters['facility_id']);
        }

        return $builder->orderBy('bookings.created_at', 'DESC')->findAll();
    }

    public function findWithDetails(int $id): ?array
    {
        return $this->select('bookings.*, facilities.name AS facility_name, facilities.type AS facility_type, facilities.capacity AS facility_capacity, users.name AS customer_name, users.email AS customer_email')
            ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
            ->join('users', 'users.id = bookings.user_id', 'left')
            ->where('bookings.id', $id)
            ->first();
    }
}
