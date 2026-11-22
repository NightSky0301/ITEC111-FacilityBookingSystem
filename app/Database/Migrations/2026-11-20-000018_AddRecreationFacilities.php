<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds Table Tennis A-D, Bowling Alley A-C, Billiards Table A-D and Boxing Ring A-B.
 * A facility is only added if no facility with the exact same name exists, so running this twice
 * (or on a database that already has them) changes nothing. Nothing is renamed or deleted.
 */
class AddRecreationFacilities extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        // [name, type, description, capacity, price per hour, image]
        $rows = [];
        foreach (['A', 'B', 'C', 'D'] as $l) {
            $rows[] = ['Table Tennis ' . $l, 'tabletennis', 'Full-size table with net, paddles and balls. Indoor and air-conditioned.', 4, 150, 'assets/img/courts/tabletennis-' . strtolower($l) . '.jpg'];
        }
        foreach (['A', 'B', 'C'] as $l) {
            $rows[] = ['Bowling Alley ' . $l, 'bowling', 'Private lane with automatic scoring, rental shoes and balls. Price is per lane.', 6, 400, 'assets/img/courts/bowling-' . strtolower($l) . '.jpg'];
        }
        foreach (['A', 'B', 'C', 'D'] as $l) {
            $rows[] = ['Billiards Table ' . $l, 'billiards', 'Pool table with cues, rack and chalk. Indoor and air-conditioned.', 4, 200, 'assets/img/courts/billiards-' . strtolower($l) . '.jpg'];
        }
        foreach (['A', 'B'] as $l) {
            $rows[] = ['Boxing Ring ' . $l, 'boxing', 'Full ring with ropes and canvas floor, for sparring and training. Gloves not included.', 10, 500, 'assets/img/courts/boxing-' . strtolower($l) . '.jpg'];
        }

        foreach ($rows as [$name, $type, $desc, $cap, $price, $image]) {
            if ($this->db->table('facilities')->where('name', $name)->countAllResults() > 0) {
                continue;
            }
            $this->db->table('facilities')->insert([
                'name'           => $name,
                'type'           => $type,
                'description'    => $desc,
                'image'          => $image,
                'capacity'       => $cap,
                'price_per_hour' => $price,
                'status'         => 'active',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    public function down()
    {
        // Left in place on purpose.
    }
}
