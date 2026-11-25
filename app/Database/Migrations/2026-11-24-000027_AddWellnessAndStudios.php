<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a gymnastics hall and two yoga studios (Fitness and Wellbeing), plus a music studio and a karaoke room (Entertainment).
 * A facility is only added if no facility with the exact same name exists, so running this twice changes nothing.
 * Nothing is renamed or deleted. Prices and capacity can be changed later on the Facilities page.
 * (Swimming pools and dance studios moved to Entertainment by type, so no data is touched for that.)
 */
class AddWellnessAndStudios extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        // [name, type, description, capacity, price per hour, image]
        $rows = [
            ['Gymnastics Hall', 'gymnastics', 'Big indoor hall with a balance beam, parallel bars, rings, a vault and sprung blue mats. Booked per hour for your whole group.', 20, 700, 'assets/img/courts/gymnastics-a.jpg'],
            ['Yoga Studio A', 'yoga', 'Warm wood-floor studio with mats, blocks and bolsters, a wall of mirrors and floor-to-ceiling windows. Booked per hour for your whole group.', 15, 400, 'assets/img/courts/yoga-a.jpg'],
            ['Yoga Studio B', 'yoga', 'Bright, quiet studio with mats, blocks and balance balls, and garden views through tall windows. Booked per hour for your whole group.', 15, 400, 'assets/img/courts/yoga-b.jpg'],
            ['Music Studio', 'music', 'Sound-treated studio with a drum kit, guitars, a keyboard, a vocal mic and a recording desk. Booked per hour for your whole group.', 8, 500, 'assets/img/courts/music-a.jpg'],
            ['Karaoke Room', 'karaoke', 'Private lounge with a big screen, a song library, wireless mics and leather sofas. Booked per hour for your whole group.', 12, 600, 'assets/img/courts/karaoke-a.jpg'],
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
