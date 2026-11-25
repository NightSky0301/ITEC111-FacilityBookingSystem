<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds two massage rooms and two foot spa lounges to Fitness and Wellbeing.
 * A facility is only added if no facility with the exact same name exists, so running this twice changes nothing.
 * Prices and capacity can be changed later on the Facilities page.
 */
class AddMassageAndFootSpa extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        // [name, type, description, capacity, price per hour, image]
        $rows = [
            ['Massage Room A', 'massage', 'Calm treatment room with warm lighting, massage beds with fresh linen and a quiet sauna corner next door. Booked per hour for your whole group.', 4, 900, 'assets/img/courts/massage-a.jpg'],
            ['Massage Room B', 'massage', 'Quiet twin-bed treatment room with candles, aromatic oils and soft music. Booked per hour for your whole group.', 4, 900, 'assets/img/courts/massage-b.jpg'],
            ['Foot Spa Lounge A', 'footspa', 'Row of cream recliners with marble soaking basins, warm towels and an orchid-lit counter. Booked per hour for your whole group.', 6, 600, 'assets/img/courts/footspa-a.jpg'],
            ['Foot Spa Lounge B', 'footspa', 'Recliners with white soaking basins on a glowing marble platform, with shelves of towels and fresh plants. Booked per hour for your whole group.', 6, 600, 'assets/img/courts/footspa-b.jpg'],
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
