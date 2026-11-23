<?php

namespace App\Models;

use CodeIgniter\Model;

class FacilityModel extends Model
{
    protected $table         = 'facilities';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'type', 'description', 'image', 'capacity', 'price_per_hour', 'status', 'hotel_id'];
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
     * Longer stays: the same arrival time and hours repeat every day. Months, weeks and days add up to the
     * number of days (a month is 30 days). Each day is its own booking, linked by bookings.group_id.
     */
    public const STAY_MAX = ['months' => 3, 'weeks' => 3, 'days' => 6];

    /** Number of days for a months / weeks / days choice, or 0 when it is outside the allowed limits. */
    public static function stayDays(int $months, int $weeks, int $days): int
    {
        if ($months < 0 || $weeks < 0 || $days < 0
            || $months > self::STAY_MAX['months'] || $weeks > self::STAY_MAX['weeks'] || $days > self::STAY_MAX['days']) {
            return 0;
        }

        return $months * 30 + $weeks * 7 + $days;
    }

    /** The dates (Y-m-d) of a stay of $n days that begins on $startDate. */
    public static function stayDates(string $startDate, int $n): array
    {
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = date('Y-m-d', strtotime($startDate . ' +' . $i . ' days'));
        }

        return $out;
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
        'tabletennis'   => 'Table Tennis',
        'bowling'       => 'Bowling Alley',
        'billiards'     => 'Billiards Table',
        'boxing'        => 'Boxing Ring',
        'multipurpose'  => 'Multipurpose Court',
        'gym'           => 'Gym',
        'swimming'      => 'Swimming Pool',
        'dance'         => 'Dance Studio',
        'room'          => 'Hotel Room',
        'other'         => 'Other',
    ];

    /** Facility types that live under the Fitness tab (everything else is an Activity). */
    public const FITNESS = ['gym', 'swimming', 'dance'];

    public static function isFitness(array|string $facilityOrType): bool
    {
        $type = is_array($facilityOrType) ? (string) ($facilityOrType['type'] ?? '') : $facilityOrType;

        return in_array($type, self::FITNESS, true);
    }

    /** Hotel rooms are booked by the night and live under Hotels, never in the court lists. */
    public const ROOM = 'room';

    public static function isRoom(array|string $facilityOrType): bool
    {
        $type = is_array($facilityOrType) ? (string) ($facilityOrType['type'] ?? '') : $facilityOrType;

        return $type === self::ROOM;
    }

    /** Short name + one-line description for each activity card. */
    /** What one facility of a type is called: [singular, plural]. Used by the live availability line. */
    public const UNITS = [
        'gym' => ['gym', 'gyms'], 'swimming' => ['pool', 'pools'], 'dance' => ['studio', 'studios'], 'bowling' => ['lane', 'lanes'], 'billiards' => ['table', 'tables'],
        'tabletennis' => ['table', 'tables'], 'boxing' => ['ring', 'rings'],
    ];

    public static function unitName(string $type, int $n = 2): string
    {
        $u = self::UNITS[$type] ?? ['court', 'courts'];

        return $n === 1 ? $u[0] : $u[1];
    }

    public const ACTIVITIES = [
        'basketball'   => ['Basketball',   'Full-court games and practice'],
        'badminton'    => ['Badminton',    'Doubles and singles on marked courts'],
        'pickleball'   => ['Pickleball',   'Standard courts with nets and lines'],
        'volleyball'   => ['Volleyball',   'Indoor court with adjustable net'],
        'tennis'       => ['Tennis',       'Hard courts for singles and doubles'],
        'futsal'       => ['Futsal', 'Small-sided indoor games'],
        'football'     => ['Football', 'Full-size outdoor pitch'],
        'tabletennis'  => ['Table Tennis', 'Singles and doubles on full-size tables'],
        'bowling'      => ['Bowling', 'Lanes with automatic scoring'],
        'billiards'    => ['Billiards', 'Pool tables with cues and racks'],
        'boxing'       => ['Boxing', 'Rings for sparring and training'],
        'multipurpose' => ['Multipurpose', 'Events, tournaments and practice'],
        'gym'          => ['Gym',          'Workout floor, booked per person'],
        'swimming'     => ['Swimming',     'Lap pools with marked lanes'],
        'dance'        => ['Dance Studio', 'Mirrored studios for classes and practice'],
        'room'         => ['Hotel Room',   'Rooms booked by the night'],
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
        'tabletennis'  => [2, 4],
        'bowling'      => [2, 6],
        'billiards'    => [2, 4],
        'boxing'       => [2, 10],
        'multipurpose' => [10, 30],
        'gym'          => [1, 15],
        'swimming'     => [4, 20],
        'dance'        => [8, 25],
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
        return $this->where('status', 'active')->where('type !=', self::ROOM)->orderBy('type')->orderBy('name')->findAll();
    }

    /** Active facilities grouped by type, in the order of TYPES. */
    public function activeByType(): array
    {
        $groups = [];
        foreach ($this->where('status', 'active')->where('type !=', self::ROOM)->orderBy('name')->findAll() as $f) {
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
