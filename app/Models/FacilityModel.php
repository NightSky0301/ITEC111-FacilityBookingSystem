<?php

namespace App\Models;

use CodeIgniter\Model;

class FacilityModel extends Model
{
    protected $table         = 'facilities';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'type', 'description', 'image', 'capacity', 'price_per_hour', 'status', 'hotel_id', 'open_hour', 'close_hour'];
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;   // deleted rows go to the Archive and can be restored
    protected $deletedField   = 'deleted_at';

    /** Hours shown on the availability page (24h clock). Last slot is CLOSE_HOUR - 1. */
    public const OPEN_HOUR  = 6;
    public const CLOSE_HOUR = 22;

    /**
     * Booking length. Courts are rented per hour: a booking is MIN_HOURS (1 hour) or more,
     * added one hour at a time with the +/- stepper, up to the hours the facility is open in one day.
     * For longer stays the customer picks a first and last date instead (see daysBetween()).
     */
    public const MIN_HOURS = 1;
    public const MAX_HOURS = self::CLOSE_HOUR - self::OPEN_HOUR;

    /**
     * Multiple-day bookings: the same arrival time and hours repeat every day between a first and a last date.
     * Each day is its own booking, linked by bookings.group_id. There is no limit on how long a stay can be;
     * STAY_SAFETY_DAYS (one year) only stops a mistyped date from creating thousands of rows.
     */
    public const STAY_SAFETY_DAYS = 366;

    /** Number of days from $first to $last (both included), or 0 when the dates are not valid. */
    public static function daysBetween(string $first, string $last): int
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $first) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $last)) {
            return 0;
        }
        $a = strtotime($first . ' 12:00:00');
        $b = strtotime($last . ' 12:00:00');
        if (! $a || ! $b || $b < $a) {
            return 0;
        }
        $n = (int) round(($b - $a) / 86400) + 1;

        return $n > self::STAY_SAFETY_DAYS ? 0 : $n;
    }

    /** Opening hours (open, close) when a facility has none of its own: 24 h clock, close 24 = midnight. */
    public const DEFAULT_HOURS = [
        'yoga' => [7, 21], 'gymnastics' => [7, 21], 'massage' => [9, 21], 'footspa' => [10, 21],
        'swimming' => [6, 20], 'dance' => [8, 22], 'music' => [9, 23], 'karaoke' => [10, 24],
        'bowling' => [10, 24], 'billiards' => [10, 24],
    ];

    /** [open hour, close hour] for a facility: its own hours if set on the admin page, else the type's, else 6 AM to 10 PM. */
    public static function hoursFor(array $facility): array
    {
        $own  = isset($facility['open_hour'], $facility['close_hour']) && $facility['open_hour'] !== '' && $facility['close_hour'] !== ''
            && (int) $facility['close_hour'] > (int) $facility['open_hour'];
        if ($own) {
            return [(int) $facility['open_hour'], min(24, (int) $facility['close_hour'])];
        }

        return self::DEFAULT_HOURS[(string) ($facility['type'] ?? '')] ?? [self::OPEN_HOUR, self::CLOSE_HOUR];
    }

    /** Longest booking in one day for this facility (the hours it is open). */
    public static function maxHoursFor(array $facility): int
    {
        [$o, $c] = self::hoursFor($facility);

        return $c - $o;
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
        'baseball'      => 'Baseball Court',
        'tabletennis'   => 'Table Tennis',
        'bowling'       => 'Bowling Alley',
        'billiards'     => 'Billiards Table',
        'boxing'        => 'Boxing Ring',
        'multipurpose'  => 'Multipurpose Court',
        'gym'           => 'Gym',
        'swimming'      => 'Swimming Pool',
        'dance'         => 'Dance Studio',
        'gymnastics'    => 'Gymnastics Hall',
        'yoga'          => 'Yoga Studio',
        'music'         => 'Music Studio',
        'karaoke'       => 'Karaoke Room',
        'massage'       => 'Massage Room',
        'footspa'       => 'Foot Spa Lounge',
        'room'          => 'Hotel Room',
        'other'         => 'Other',
    ];

    /** Facility types that live under the Fitness tab. */
    public const FITNESS = ['gym', 'gymnastics', 'yoga', 'massage', 'footspa'];

    public static function isFitness(array|string $facilityOrType): bool
    {
        $type = is_array($facilityOrType) ? (string) ($facilityOrType['type'] ?? '') : $facilityOrType;

        return in_array($type, self::FITNESS, true);
    }

    /** Facility types that live under the Entertainment tab. */
    public const ENTERTAINMENT = ['bowling', 'billiards', 'swimming', 'dance', 'music', 'karaoke'];

    public static function isEntertainment(array|string $facilityOrType): bool
    {
        $type = is_array($facilityOrType) ? (string) ($facilityOrType['type'] ?? '') : $facilityOrType;

        return in_array($type, self::ENTERTAINMENT, true);
    }

    /** The four sections of the site a facility type belongs to: activities, hotels, fitness or entertainment. */
    public static function sectionOf(array|string $facilityOrType): string
    {
        $type = is_array($facilityOrType) ? (string) ($facilityOrType['type'] ?? '') : $facilityOrType;

        return $type === self::ROOM ? 'hotels' : (self::isFitness($type) ? 'fitness' : (self::isEntertainment($type) ? 'entertainment' : 'activities'));
    }

    /** Page that lists a section ("facilities" is the Activities page). */
    public static function sectionPath(string $section): string
    {
        return ['activities' => 'facilities', 'hotels' => 'hotels', 'fitness' => 'fitness', 'entertainment' => 'entertainment'][$section] ?? 'facilities';
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
        'gymnastics' => ['hall', 'halls'], 'yoga' => ['studio', 'studios'], 'music' => ['studio', 'studios'], 'karaoke' => ['room', 'rooms'], 'massage' => ['room', 'rooms'], 'footspa' => ['lounge', 'lounges'],
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
        'baseball'     => ['Baseball', 'Full diamond with a pitcher\'s mound'],
        'tabletennis'  => ['Table Tennis', 'Singles and doubles on full-size tables'],
        'bowling'      => ['Bowling', 'Lanes with automatic scoring'],
        'billiards'    => ['Billiards', 'Pool tables with cues and racks'],
        'boxing'       => ['Boxing', 'Rings for sparring and training'],
        'multipurpose' => ['Multipurpose', 'Events, tournaments and practice'],
        'gym'          => ['Gym',          'Workout floor, booked per person'],
        'swimming'     => ['Swimming',     'Lap pools with marked lanes'],
        'dance'        => ['Dance Studio', 'Mirrored studios for classes and practice'],
        'gymnastics'   => ['Gymnastics Hall', 'Beams, bars, rings and sprung floors'],
        'yoga'         => ['Yoga Studio', 'Calm studios with mats and blocks'],
        'music'        => ['Music Studio', 'Instruments, a mic and a recording desk'],
        'karaoke'      => ['Karaoke Room', 'Private room with a big screen and sound'],
        'massage'      => ['Massage Room', 'Quiet treatment rooms with massage beds'],
        'footspa'      => ['Foot Spa Lounge', 'Recliners with soaking basins'],
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
        'baseball'     => [10, 18],
        'tabletennis'  => [2, 4],
        'bowling'      => [2, 6],
        'billiards'    => [2, 4],
        'boxing'       => [2, 10],
        'multipurpose' => [10, 30],
        'gym'          => [1, 15],
        'swimming'     => [4, 20],
        'dance'        => [8, 25],
        'gymnastics'   => [4, 20],
        'yoga'         => [4, 15],
        'music'        => [2, 8],
        'karaoke'      => [4, 12],
        'massage'      => [1, 4],
        'footspa'      => [2, 6],
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
