<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Brings the earlier sample courts in line with the "Court A / Court B" layout:
 *   Pickleball Court 1 / 2  ->  Pickleball Court A / B
 *   Badminton Court         ->  Badminton Court A (+ adds Badminton Court B)
 * Only exact name matches are touched, and nothing is deleted.
 */
class AlignCourtNames extends Migration
{
    public function up()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');
        $has = static fn (string $name): bool => $db->table('facilities')->where('name', $name)->countAllResults() > 0;

        $rename = static function (string $from, string $to) use ($db, $has, $now): void {
            if ($has($from) && ! $has($to)) {
                $db->table('facilities')->where('name', $from)->update(['name' => $to, 'updated_at' => $now]);
            }
        };

        $rename('Pickleball Court 1', 'Pickleball Court A');
        $rename('Pickleball Court 2', 'Pickleball Court B');
        $rename('Badminton Court', 'Badminton Court A');

        if ($has('Badminton Court A') && ! $has('Badminton Court B')) {
            $db->table('facilities')->insert([
                'name'           => 'Badminton Court B',
                'type'           => 'badminton',
                'description'    => 'Indoor court next to Court A. Good for practice and casual games.',
                'capacity'       => 4,
                'price_per_hour' => 250,
                'status'         => 'active',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    public function down()
    {
        // Names are not reverted on purpose.
    }
}
