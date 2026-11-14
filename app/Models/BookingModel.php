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
        'purpose', 'status', 'admin_remarks',
    ];
    protected $useTimestamps = true;

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
        $builder = $this->select('bookings.*, facilities.name AS facility_name, facilities.type AS facility_type, facilities.image AS facility_image, '
                . 'users.name AS customer_name, users.email AS customer_email')
            ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
            ->join('users', 'users.id = bookings.user_id', 'left');

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
        return $this->select('bookings.*, facilities.name AS facility_name, users.name AS customer_name, users.email AS customer_email')
            ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
            ->join('users', 'users.id = bookings.user_id', 'left')
            ->where('bookings.id', $id)
            ->first();
    }
}
