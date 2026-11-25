<?php

namespace App\Libraries;

/**
 * Optional extras a customer can add to a booking.
 *   per    'hour'    -> price x hours booked
 *          'session' -> one flat price
 *          'person'  -> price x people (per-head facilities, such as the gym)
 *          'person_hour' -> price x people x hours
 *          'night' / 'person_night' -> hotel rooms: price x nights (x guests)
 *   group  'cooling' -> pick none, fan OR air-con (one choice)
 *          'extra'   -> any combination
 *   evening  true    -> only when the arrival time is 5:00 PM or later (lights)
 *   types    null    -> every activity, otherwise only the listed activity types
 *   indoor   true    -> not offered on outdoor courts
 * The list lives in the `addons` table and the admin manages it (Add-ons page and the Add facility form);
 * CATALOG below is only the starting set and the fallback before the add-ons migration has run.
 */
class Addons
{
    /** Arrival time (minutes after midnight) from which the "Evening" extras are offered. */
    public const EVENING_FROM = 17 * 60;

    /** Referees are shared by the whole system: this many can be out on games at the same time. */
    public const REFEREE_POOL = 5;
    public const REFEREE_KEY  = 'referee';

    public const CATALOG = [
        'lights'  => ['label' => 'Court lights',        'note' => 'Evening arrivals only',          'price' => 150, 'per' => 'hour',    'group' => 'extra',   'evening' => true,  'types' => ['basketball', 'badminton', 'pickleball', 'volleyball', 'tennis', 'futsal', 'football', 'multipurpose', 'other']],
        'fan'     => ['label' => 'Electric fan',        'note' => 'Stand fans around the court',    'price' => 50,  'per' => 'hour',    'group' => 'cooling', 'evening' => false, 'types' => ['basketball', 'badminton', 'pickleball', 'volleyball', 'tennis', 'futsal', 'football', 'multipurpose', 'other']],
        'ac'      => ['label' => 'Air-conditioning',    'note' => 'Indoor courts only',             'price' => 200, 'per' => 'hour',    'group' => 'cooling', 'evening' => false, 'types' => ['basketball', 'badminton', 'pickleball', 'volleyball', 'tennis', 'futsal', 'football', 'multipurpose', 'other'], 'indoor' => true],
        'ball'    => ['label' => 'Ball rental',         'note' => 'Match ball for your session',    'price' => 50,  'per' => 'session', 'group' => 'extra',   'evening' => false, 'types' => ['basketball', 'volleyball', 'futsal', 'football', 'multipurpose']],
        'racket'  => ['label' => 'Racket / paddle set', 'note' => 'Two rackets or paddles and balls', 'price' => 100, 'per' => 'session', 'group' => 'extra',   'evening' => false, 'types' => ['badminton', 'pickleball', 'tennis']],
        'towel'   => ['label' => 'Towel',               'note' => 'Fresh towel per person',          'price' => 30,  'per' => 'person',  'group' => 'extra',   'evening' => false, 'types' => ['gym']],
        'coach'   => ['label' => 'Training coach',      'note' => 'Personal coach for your group',   'price' => 400, 'per' => 'hour',    'group' => 'extra',   'evening' => false, 'types' => ['gym']],
        'locker'  => ['label' => 'Rental locker',       'note' => 'One locker per person',           'price' => 40,  'per' => 'person',  'group' => 'extra',   'evening' => false, 'types' => ['gym']],
        'yoga'    => ['label' => 'Yoga mat rental',     'note' => 'One mat per person',              'price' => 30,  'per' => 'person',  'group' => 'extra',   'evening' => false, 'types' => ['gym']],
        'referee' => ['label' => 'Referee',             'note' => 'Officiates your game',           'price' => 300, 'per' => 'session', 'group' => 'extra',   'evening' => false, 'types' => ['basketball', 'volleyball', 'futsal', 'football']],
        // Hotel rooms
        'extra-bed'     => ['label' => 'Extra bed',        'note' => 'One additional bed set up in the room',          'price' => 600, 'per' => 'night',        'group' => 'extra', 'evening' => false, 'types' => ['room']],
        'early-checkin' => ['label' => 'Early check-in',   'note' => 'Get into your room from 10:00 AM',              'price' => 500, 'per' => 'session',      'group' => 'extra', 'evening' => false, 'types' => ['room']],
        'late-checkout' => ['label' => 'Late check-out',   'note' => 'Stay until 4:00 PM on your last day',           'price' => 500, 'per' => 'session',      'group' => 'extra', 'evening' => false, 'types' => ['room']],
    ];

    /** Add-on keys that suit a facility type: the starting suggestion on the Add facility form. */
    public static function defaultsFor(string $type): array
    {
        $out = [];
        foreach (self::CATALOG as $key => $a) {
            if ($a['types'] === null || in_array($type, $a['types'], true)) {
                $out[] = $key;
            }
        }

        return $out;
    }

    /** How each pricing rule reads on screen. */
    public const PER_LABELS = [
        'hour'        => 'per hour',
        'session'     => 'flat, once per booking',
        'person'      => 'per person',
        'person_hour' => 'per person, per hour',
        'night'        => 'per night (hotels)',
        'person_night' => 'per person, per night (hotels)',
    ];

    private static ?bool $tables = null;
    private static ?array $all   = null;

    private static function hasTables(): bool
    {
        return self::$tables ??= db_connect()->tableExists('addons') && db_connect()->tableExists('facility_addons');
    }

    /** One database row shaped like a CATALOG entry. */
    public static function shape(array $r): array
    {
        return [
            'id'      => (int) ($r['id'] ?? 0),
            'label'   => (string) $r['label'],
            'note'    => (string) ($r['note'] ?? ''),
            'price'   => (float) $r['price'],
            'per'     => (string) $r['per'],
            'group'   => (string) $r['grp'],
            'evening' => (bool) $r['evening'],
            'indoor'  => (bool) $r['indoor'],
            'status'  => (string) $r['status'],
            'types'   => null,
        ];
    }

    /**
     * Every add-on (key => details). Active ones only unless $withInactive. Falls back to CATALOG.
     *
     * @return array<string,array>
     */
    public static function all(bool $withInactive = false): array
    {
        if (! self::hasTables()) {
            return self::CATALOG;
        }
        if (self::$all === null) {
            self::$all = [];
            foreach (db_connect()->table('addons')->orderBy('grp', 'DESC')->orderBy('label')->get()->getResultArray() as $r) {
                self::$all[$r['addon_key']] = self::shape($r);
            }
        }

        return $withInactive ? self::$all : array_filter(self::$all, static fn ($a) => $a['status'] === 'active');
    }

    /** Forget what was read (after the admin changes add-ons in this same request). */
    public static function reset(): void
    {
        self::$all = null;
    }

    /**
     * The add-ons one facility offers (active ones only).
     *
     * @return array<string,array>
     */
    public static function forFacility(array|int $facility): array
    {
        $id   = is_array($facility) ? (int) $facility['id'] : $facility;
        $type = is_array($facility) ? (string) ($facility['type'] ?? '') : '';
        if (! self::hasTables()) {
            return array_filter(self::CATALOG, static fn ($a) => $a['types'] === null || in_array($type, $a['types'], true));
        }
        $keys = array_column(
            db_connect()->table('facility_addons fa')->select('a.addon_key')->join('addons a', 'a.id = fa.addon_id')->where('fa.facility_id', $id)->get()->getResultArray(),
            'addon_key'
        );

        return array_intersect_key(self::all(), array_flip($keys));
    }

    /** Add-on keys a facility offers (any status), for the edit form. */
    public static function keysFor(int $facilityId): array
    {
        if (! self::hasTables()) {
            return [];
        }

        return array_column(
            db_connect()->table('facility_addons fa')->select('a.addon_key')->join('addons a', 'a.id = fa.addon_id')->where('fa.facility_id', $facilityId)->get()->getResultArray(),
            'addon_key'
        );
    }

    /** Replaces the add-ons a facility offers. */
    public static function syncFacility(int $facilityId, array $keys): void
    {
        if (! self::hasTables()) {
            return;
        }
        $db  = db_connect();
        $ids = [];
        foreach ($db->table('addons')->whereIn('addon_key', array_values(array_unique(array_map('strval', $keys))) ?: [''])->get()->getResultArray() as $r) {
            $ids[] = (int) $r['id'];
        }
        $db->table('facility_addons')->where('facility_id', $facilityId)->delete();
        foreach ($ids as $aid) {
            $db->table('facility_addons')->insert(['facility_id' => $facilityId, 'addon_id' => $aid]);
        }
    }

    /** Creates an add-on and returns its key. Returns null when the label is empty. */
    public static function create(string $label, float $price, string $per, string $group, string $note = '', bool $evening = false, bool $indoor = false): ?string
    {
        $label = trim($label);
        if ($label === '' || ! self::hasTables()) {
            return null;
        }
        $db   = db_connect();
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($label)), '-') ?: 'addon';
        $key  = $base;
        for ($i = 2; $db->table('addons')->where('addon_key', $key)->countAllResults() > 0; $i++) {
            $key = $base . '-' . $i;
        }
        $now = date('Y-m-d H:i:s');
        $db->table('addons')->insert([
            'addon_key' => $key, 'label' => mb_substr($label, 0, 80), 'note' => mb_substr(trim($note), 0, 160),
            'price' => max(0, $price), 'per' => isset(self::PER_LABELS[$per]) ? $per : 'session',
            'grp' => $group === 'cooling' ? 'cooling' : 'extra', 'evening' => $evening ? 1 : 0, 'indoor' => $indoor ? 1 : 0,
            'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        self::reset();

        return $key;
    }

    private static function amountOf(array $a, int|float $hours, int $people, int $nights = 1): float
    {
        return (float) match ($a['per']) {
            'night'        => $a['price'] * max(1, $nights),
            'person_night' => $a['price'] * max(1, $people) * max(1, $nights),
            'hour'        => $a['price'] * $hours,
            'person'      => $a['price'] * max(1, $people),
            'person_hour' => $a['price'] * max(1, $people) * $hours,
            default       => $a['price'],
        };
    }

    public static function amount(string $key, int|float $hours, int $people = 1): float
    {
        return self::amountOf(self::all(true)[$key], $hours, $people);
    }

    /**
     * Checks the keys a customer sent against the rules and prices them.
     * $offered is the list of add-ons this facility offers (see forFacility()); null = the built-in list for $type.
     *
     * @param  string[] $keys
     * @return array{items:array<int,array>,total:float}|string  items to store, or an error message
     */
    public static function resolve(array $keys, string $type, int $startMinute, int|float $hours, bool $outdoor = false, int $people = 1, bool $longStay = false, ?array $offered = null, int $nights = 1)
    {
        $items   = [];
        $total   = 0.0;
        $cooling = 0;

        foreach (array_values(array_unique(array_map('strval', $keys))) as $key) {
            if ($key === '') {
                continue;
            }
            $a = $offered !== null ? ($offered[$key] ?? null) : (self::CATALOG[$key] ?? null);
            if (! $a || ($offered === null && $a['types'] !== null && ! in_array($type, $a['types'], true))) {
                return 'One of the add-ons is not available for this court.';
            }
            if ($longStay && $key === self::REFEREE_KEY) {
                return 'A referee can only be added to hourly bookings, not to stays of days, weeks or months.';
            }
            if (! empty($a['indoor']) && $outdoor) {
                return $a['label'] . ' is only available on indoor courts.';
            }
            if ($a['evening'] && $startMinute < self::EVENING_FROM) {
                return $a['label'] . ' is only available for evening arrivals (5:00 PM or later).';
            }
            if ($a['group'] === 'cooling' && ++$cooling > 1) {
                return 'Choose either a fan or air-conditioning, not both.';
            }
            $amount  = self::amountOf($a, $hours, $people, $nights);
            $total  += round($amount, 2);
            $items[] = ['key' => $key, 'label' => $a['label'], 'per' => $a['per'], 'price' => $a['price'], 'amount' => round($amount, 2)];
        }

        return ['items' => $items, 'total' => $total];
    }
}
