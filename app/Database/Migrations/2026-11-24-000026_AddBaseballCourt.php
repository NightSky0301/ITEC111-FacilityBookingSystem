<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Adds the outdoor Baseball Court if it is not there yet (matched by exact name). Nothing is renamed or deleted. */
class AddBaseballCourt extends Migration
{
    public function up()
    {
        if ($this->db->table('facilities')->where('name', 'Baseball Court')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('facilities')->insert([
            'name'           => 'Baseball Court',
            'type'           => 'baseball',
            'description'    => 'Full outdoor diamond with a pitcher\'s mound, base paths, home plate boxes, a scoreboard, grandstand seating and floodlights. Open air.',
            'image'          => 'assets/img/courts/baseball-a.jpg',
            'capacity'       => 18,
            'price_per_hour' => 1500,
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
