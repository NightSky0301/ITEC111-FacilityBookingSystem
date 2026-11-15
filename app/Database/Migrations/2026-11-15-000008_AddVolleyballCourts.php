<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Volleyball gets two courts like the other activities:
 *   Volleyball Court   ->  Volleyball Court A (+ adds Volleyball Court B)
 * Only an exact name match is renamed, and nothing is deleted.
 */
class AddVolleyballCourts extends Migration
{
    public function up()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');
        $has = static fn (string $name): bool => $db->table('facilities')->where('name', $name)->countAllResults() > 0;

        if ($has('Volleyball Court') && ! $has('Volleyball Court A')) {
            $db->table('facilities')->where('name', 'Volleyball Court')->update(['name' => 'Volleyball Court A', 'updated_at' => $now]);
        }

        if ($has('Volleyball Court A') && ! $has('Volleyball Court B')) {
            $a = $db->table('facilities')->where('name', 'Volleyball Court A')->get()->getRowArray();
            $db->table('facilities')->insert([
                'name'           => 'Volleyball Court B',
                'type'           => 'volleyball',
                'description'    => 'Outdoor sand court with night lights. Ball not included.',
                'capacity'       => $a ? (int) $a['capacity'] : 12,
                'price_per_hour' => $a ? $a['price_per_hour'] : 400,
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
