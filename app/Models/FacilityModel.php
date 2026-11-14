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

    /** Hours shown on the availability page (24h clock). Last slot is CLOSE_HOUR - 1. */
    public const OPEN_HOUR  = 6;
    public const CLOSE_HOUR = 22;

    /**
     * Booking length. Courts are rented per hour: a booking is MIN_HOURS (1 hour) or more,
     * added one hour at a time with the +/- stepper, up to MAX_HOURS.
     */
    public const MIN_HOURS = 1;
    public const MAX_HOURS = 5;

    /** Booking length in minutes for a number of whole hours. */
    public static function durationMinutes(int $hours): int
    {
        return $hours * 60;
    }

    /** Total price: the hourly rate for every hour booked. */
    public static function totalPrice(float $hourlyRate, int $hours): float
    {
        return round($hourlyRate * $hours, 2);
    }

    /** Facility types shown in the dropdowns (also the "activities" customers choose first). */
    public const TYPES = [
        'basketball'    => 'Basketball Court',
        'badminton'     => 'Badminton Court',
        'pickleball'    => 'Pickleball Court',
        'volleyball'    => 'Volleyball Court',
        'tennis'        => 'Tennis Court',
        'futsal'        => 'Futsal / Football',
        'multipurpose'  => 'Multipurpose Court',
        'other'         => 'Other',
    ];

    /** Short name + one-line description for each activity card. */
    public const ACTIVITIES = [
        'basketball'   => ['Basketball',   'Full-court games and practice'],
        'badminton'    => ['Badminton',    'Doubles and singles on marked courts'],
        'pickleball'   => ['Pickleball',   'Standard courts with nets and lines'],
        'volleyball'   => ['Volleyball',   'Indoor court with adjustable net'],
        'tennis'       => ['Tennis',       'Hard courts for singles and doubles'],
        'futsal'       => ['Futsal / Football', 'Small-sided games'],
        'multipurpose' => ['Multipurpose', 'Events, tournaments and practice'],
        'other'        => ['Other',        'Other facilities'],
    ];

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
