<?php

namespace App\Libraries;

/**
 * Optional extras a customer can add to a booking.
 *   per    'hour'    -> price x hours booked
 *          'session' -> one flat price
 *   group  'cooling' -> pick none, fan OR air-con (one choice)
 *          'extra'   -> any combination
 *   evening  true    -> only when the arrival time is 5:00 PM or later (lights)
 *   types    null    -> every activity, otherwise only the listed activity types
 * Change prices here.
 */
class Addons
{
    /** Arrival time (minutes after midnight) from which the "Evening" extras are offered. */
    public const EVENING_FROM = 17 * 60;

    public const CATALOG = [
        'lights'  => ['label' => 'Court lights',        'note' => 'Evening arrivals only',          'price' => 150, 'per' => 'hour',    'group' => 'extra',   'evening' => true,  'types' => null],
        'fan'     => ['label' => 'Electric fan',        'note' => 'Stand fans around the court',    'price' => 50,  'per' => 'hour',    'group' => 'cooling', 'evening' => false, 'types' => null],
        'ac'      => ['label' => 'Air-conditioning',    'note' => 'Cools the whole court',          'price' => 200, 'per' => 'hour',    'group' => 'cooling', 'evening' => false, 'types' => null],
        'ball'    => ['label' => 'Ball rental',         'note' => 'Match ball for your session',    'price' => 50,  'per' => 'session', 'group' => 'extra',   'evening' => false, 'types' => ['basketball', 'volleyball', 'futsal', 'multipurpose']],
        'racket'  => ['label' => 'Racket / paddle set', 'note' => 'Two rackets or paddles and balls', 'price' => 100, 'per' => 'session', 'group' => 'extra',   'evening' => false, 'types' => ['badminton', 'pickleball', 'tennis']],
        'referee' => ['label' => 'Referee',             'note' => 'Officiates your game',           'price' => 300, 'per' => 'session', 'group' => 'extra',   'evening' => false, 'types' => ['basketball', 'volleyball', 'futsal']],
    ];

    public static function amount(string $key, int $hours): float
    {
        $a = self::CATALOG[$key];

        return (float) ($a['per'] === 'hour' ? $a['price'] * $hours : $a['price']);
    }

    /**
     * Checks the keys a customer sent against the rules and prices them.
     *
     * @param  string[] $keys
     * @return array{items:array<int,array>,total:float}|string  items to store, or an error message
     */
    public static function resolve(array $keys, string $type, int $startMinute, int $hours)
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
            if ($a['evening'] && $startMinute < self::EVENING_FROM) {
                return $a['label'] . ' is only available for evening arrivals (5:00 PM or later).';
            }
            if ($a['group'] === 'cooling' && ++$cooling > 1) {
                return 'Choose either a fan or air-conditioning, not both.';
            }
            $amount  = self::amount($key, $hours);
            $total  += $amount;
            $items[] = ['key' => $key, 'label' => $a['label'], 'per' => $a['per'], 'price' => $a['price'], 'amount' => $amount];
        }

        return ['items' => $items, 'total' => $total];
    }
}
