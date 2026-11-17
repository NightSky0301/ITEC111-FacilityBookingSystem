<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the Gym (one gym, booked per person, 15 people at a time) if it is not there yet.
 * Nothing is renamed or deleted. Price is per person, per hour.
 */
class AddGymFacility extends Migration
{
    public function up()
    {
        $exists = $this->db->table('facilities')->where('type', 'gym')->countAllResults() > 0;
        if ($exists) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('facilities')->insert([
            'name'           => 'Gym',
            'type'           => 'gym',
            'description'    => 'Open workout floor with cardio machines, free weights and a stretching area. Booked per person, up to 15 people at a time.',
            'capacity'       => 15,
            'price_per_hour' => 100,
            'status'         => 'active',
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
    }

    public function down()
    {
        // Left in place on purpose.
    }
}
