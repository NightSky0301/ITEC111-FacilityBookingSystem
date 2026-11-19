<?php

namespace App\Models;

use CodeIgniter\Model;

class FacilityModel extends Model
{
    protected $table         = 'facilities';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'type', 'description', 'image', 'capacity', 'price_per_hour', 'status'];
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;   // deleted rows go to the Archive and can be restored
    protected $deletedField   = 'deleted_at';

    /** Hours shown on the availability page (24h clock). Last slot is CLOSE_HOUR - 1. */
    public const OPEN_HOUR  = 6;
    public const CLOSE_HOUR = 22;

    /**
     * Booking length. Courts are rented per hour: a booking is MIN_HOURS (1 hour) or more,
     * added one hour at a time with the +/- stepper, up to the hours the facility is open in one day.
     * For longer stays the customer picks days, weeks or months instead (see stay()).
     */
    public const MIN_HOURS = 1;
    public const MAX_HOURS = self::CLOSE_HOUR - self::OPEN_HOUR;

    /**
     * Longer stays: unit => [fewest, most, days in one unit]. A month is 30 days.
     * A stay starts at the chosen arrival time on the first day and ends at closing time on the last day.
     */
    public const STAY_UNITS = [
        'day'   => [1, 6, 1],
        'week'  => [1, 3, 7],
        'month' => [1, 6, 30],
    ];

    /**
     * Works out a days / weeks / months stay that begins at $startTs.
     *
     * @return array{days:int,endTs:int,hours:float}|null  null when the unit or the count is not allowed
     */
    public static function stay(string $unit, int $count, int $startTs): ?array
    {
        if (! isset(self::STAY_UNITS[$unit])) {
            return null;
        }
        [$min, $max, $per] = self::STAY_UNITS[$unit];
        if ($count < $min || $count > $max) {
            return null;
        }

        $days      = $count * $per;
        $first     = date('Y-m-d', $startTs);
        $lastDay   = date('Y-m-d', strtotime($first . ' +' . ($days - 1) . ' days'));
        $endTs     = strtotime($lastDay . sprintf(' %02d:00', self::CLOSE_HOUR));
        $startMin  = (int) date('G', $startTs) * 60 + (int) date('i', $startTs);
        $hours     = (self::CLOSE_HOUR * 60 - $startMin) / 60 + ($days - 1) * (self::CLOSE_HOUR - self::OPEN_HOUR);

        return ['days' => $days, 'endTs' => $endTs, 'hours' => round($hours, 2)];
    }

    /** Booking length in minutes for a number of whole hours. */
    public static function durationMinutes(int $hours): int
    {
        return $hours * 60;
    }

    /** Facility types that are booked per person (the price is per head, per hour). */
    public const PER_HEAD = ['gym'];

    public static function isPerHead(array|string $facilityOrType): bool
    {
        $type = is_array($facilityOrType) ? (string) ($facilityOrType['type'] ?? '') : $facilityOrType;

        return in_array($type, self::PER_HEAD, true);
    }

    /** Total price: the hourly rate for every hour booked, times the number of people for per-head facilities. */
    public static function totalPrice(float $hourlyRate, int|float $hours, int $people = 1): float
    {
        return round($hourlyRate * $hours * max(1, $people), 2);
    }

    /** Facility types shown in the dropdowns (also the "activities" customers choose first). */
    public const TYPES = [
        'basketball'    => 'Basketball Court',
        'badminton'     => 'Badminton Court',
        'pickleball'    => 'Pickleball Court',
        'volleyball'    => 'Volleyball Court',
        'tennis'        => 'Tennis Court',
        'futsal'        => 'Futsal Court',
        'football'      => 'Football Court',
        'multipurpose'  => 'Multipurpose Court',
        'gym'           => 'Gym',
        'other'         => 'Other',
    ];

    /** Short name + one-line description for each activity card. */
    public const ACTIVITIES = [
        'basketball'   => ['Basketball',   'Full-court games and practice'],
        'badminton'    => ['Badminton',    'Doubles and singles on marked courts'],
        'pickleball'   => ['Pickleball',   'Standard courts with nets and lines'],
        'volleyball'   => ['Volleyball',   'Indoor court with adjustable net'],
        'tennis'       => ['Tennis',       'Hard courts for singles and doubles'],
        'futsal'       => ['Futsal', 'Small-sided indoor games'],
        'football'     => ['Football', 'Full-size outdoor pitch'],
        'multipurpose' => ['Multipurpose', 'Events, tournaments and practice'],
        'gym'          => ['Gym',          'Workout floor, booked per person'],
        'other'        => ['Other',        'Other facilities'],
    ];

    /**
     * Suggested number of people for each activity (information only, shown to customers).
     * A court's own capacity can raise the top of the range but never lowers it.
     */
    public const PEOPLE = [
        'basketball'   => [10, 15],
        'badminton'    => [2, 4],
        'pickleball'   => [2, 4],
        'volleyball'   => [12, 18],
        'tennis'       => [2, 4],
        'futsal'       => [10, 14],
        'football'     => [14, 22],
        'multipurpose' => [10, 30],
        'gym'          => [1, 15],
    ];

    /** @return array{0:int,1:int} [min, max] people the court comfortably holds. */
    public static function peopleRange(array $facility): array
    {
        $capacity = max(1, (int) ($facility['capacity'] ?? 1));
        $range    = self::PEOPLE[$facility['type'] ?? ''] ?? [1, $capacity];

        return [min($range[0], max($range[1], $capacity)), max($range[1], $capacity)];
    }

    /** Most people a per-head facility takes at the same time. */
    public static function maxPeople(array $facility): int
    {
        return max(1, (int) ($facility['capacity'] ?? 1));
    }

    /** "10–15 people" or "Up to 4 people". */
    public static function peopleLabel(array $facility): string
    {
        if (self::isPerHead($facility)) {
            return 'Up to ' . self::maxPeople($facility) . ' people at a time';
        }
        [$min, $max] = self::peopleRange($facility);

        return $min >= $max ? 'Up to ' . $max . ' people' : $min . '–' . $max . ' people';
    }

    public function active(): array
    {
        return $this->where('status', 'active')->orderBy('type')->orderBy('name')->findAll();
    }

    /** Active facilities grouped by type, in the order of TYPES. */
    public function activeByType(): array
    {
        $groups = [];
        foreach ($this->where('status', 'active')->orderBy('name')->findAll() as $f) {
            $groups[$f['type']][] = $f;
        }

        $ordered = [];
        foreach (array_keys(self::TYPES) as $type) {
            if (! empty($groups[$type])) {
                $ordered[$type] = $groups[$type];
            }
        }
        // Unknown types (e.g. old data) go last instead of disappearing.
        foreach ($groups as $type => $list) {
            if (! isset($ordered[$type])) {
                $ordered[$type] = $list;
            }
        }
        return $ordered;
    }

    public static function activityName(string $type): string
    {
        return self::ACTIVITIES[$type][0] ?? (self::TYPES[$type] ?? ucfirst($type));
    }
}
