<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds Futsal Court A and Tennis Court A / B if they are not there yet (matched by exact name).
 * Nothing is renamed or deleted. Prices can be changed later on the Facilities page.
 */
class AddFutsalAndTennis extends Migration
{
    public function up()
    {
        $now  = date('Y-m-d H:i:s');
        $rows = [
            ['Futsal Court A', 'futsal', 'Indoor futsal court with goals, line markings and bright lighting.', 14, 700],
            ['Tennis Court A', 'tennis', 'Indoor hard court with a net, full markings and stand lights.', 4, 450],
            ['Tennis Court B', 'tennis', 'Outdoor hard court with a net and full markings. Open air, so no air-conditioning.', 4, 350],
        ];
        foreach ($rows as [$name, $type, $description, $capacity, $price]) {
            if ($this->db->table('facilities')->where('name', $name)->countAllResults() > 0) {
                continue;
            }
            $this->db->table('facilities')->insert([
                'name'           => $name,
                'type'           => $type,
                'description'    => $description,
                'capacity'       => $capacity,
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
