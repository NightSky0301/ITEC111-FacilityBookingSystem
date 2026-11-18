<?php

namespace App\Libraries;

/**
 * Optional extras a customer can add to a booking.
 *   per    'hour'    -> price x hours booked
 *          'session' -> one flat price
 *          'person'  -> price x people (per-head facilities, such as the gym)
 *          'person_hour' -> price x people x hours
 *   group  'cooling' -> pick none, fan OR air-con (one choice)
 *          'extra'   -> any combination
 *   evening  true    -> only when the arrival time is 5:00 PM or later (lights)
 *   types    null    -> every activity, otherwise only the listed activity types
 *   indoor   true    -> not offered on outdoor courts
 * Change prices here.
 */
class Addons
{
    /** Arrival time (minutes after midnight) from which the "Evening" extras are offered. */
    public const EVENING_FROM = 17 * 60;

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
    ];

    public static function amount(string $key, int $hours, int $people = 1): float
    {
        $a = self::CATALOG[$key];

        return (float) match ($a['per']) {
            'hour'        => $a['price'] * $hours,
            'person'      => $a['price'] * max(1, $people),
            'person_hour' => $a['price'] * max(1, $people) * $hours,
            default       => $a['price'],
        };
    }

    /**
     * Checks the keys a customer sent against the rules and prices them.
     *
     * @param  string[] $keys
     * @return array{items:array<int,array>,total:float}|string  items to store, or an error message
     */
    public static function resolve(array $keys, string $type, int $startMinute, int $hours, bool $outdoor = false, int $people = 1)
    {
        $items   = [];
        $total   = 0.0;
        $cooling = 0;

        foreach (array_values(array_unique(array_map('strval', $keys))) as $key) {
            if ($key === '') {
                continue;
            }
            $a = self::CATALOG[$key] ?? null;
            if (! $a || ($a['types'] !== null && ! in_array($type, $a['types'], true))) {
                return 'One of the add-ons is not available for this court.';
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
            $amount  = self::amount($key, $hours, $people);
            $total  += $amount;
            $items[] = ['key' => $key, 'label' => $a['label'], 'per' => $a['per'], 'price' => $a['price'], 'amount' => $amount];
        }

        return ['items' => $items, 'total' => $total];
    }
}
