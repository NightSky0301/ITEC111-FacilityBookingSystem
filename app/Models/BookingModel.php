<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table         = 'bookings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'facility_id', 'user_id', 'start_at', 'end_at', 'guests',
        'purpose', 'status', 'admin_remarks', 'addons', 'addons_total',
        'approved_by', 'arrived_at', 'arrived_by',
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
    public function peakPeople(int $facilityId, string $startAt, string $endAt, ?int $ignoreId = null): int
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
