<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds two swimming pools and two dance studios to the Fitness tab.
 * A facility is only added if no facility with the exact same name exists, so running this twice
 * (or on a database that already has them) changes nothing. Nothing is renamed or deleted.
 * Prices and capacity can be changed later on the Facilities page.
 */
class AddFitnessFacilities extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        // [name, type, description, capacity, price per hour, image]
        $rows = [
            ['Swimming Pool A', 'swimming', 'Indoor lap pool with marked lanes, stainless-steel entry ladders and lounge chairs by the water. Booked per hour for your whole group.', 20, 800, 'assets/img/courts/swimming-a.jpg'],
            ['Swimming Pool B', 'swimming', 'Indoor lap pool with a lifeguard chair, ring buoy and floor-to-ceiling windows over the city. Booked per hour for your whole group.', 20, 800, 'assets/img/courts/swimming-b.jpg'],
            ['Dance Studio A', 'dance', 'Mirrored studio with a polished wood floor, ballet barres, a sound system and yoga mats. Booked per hour for your whole group.', 25, 600, 'assets/img/courts/dance-a.jpg'],
            ['Dance Studio B', 'dance', 'Wide mirrored studio with a wood floor, ballet barres, speakers and a free-weight corner. Booked per hour for your whole group.', 25, 600, 'assets/img/courts/dance-b.jpg'],
        ];

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
