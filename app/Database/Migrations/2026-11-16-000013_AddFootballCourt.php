<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Adds the outdoor Football Court if it is not there yet (matched by exact name). Nothing is renamed or deleted. */
class AddFootballCourt extends Migration
{
    public function up()
    {
        if ($this->db->table('facilities')->where('name', 'Football Court')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('facilities')->insert([
            'name'           => 'Football Court',
            'type'           => 'football',
            'description'    => 'Full-size outdoor pitch with goals, line markings, team benches and floodlights. Open air.',
            'capacity'       => 22,
            'price_per_hour' => 1200,
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
