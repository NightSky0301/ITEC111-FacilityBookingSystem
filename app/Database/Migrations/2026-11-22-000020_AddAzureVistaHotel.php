<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a second hotel, Azure Vista Hotel & Suites, with seven rooms.
 * Needs migration 000019 (hotels table) first. Nothing is renamed or deleted; running it twice changes nothing.
 */
class AddAzureVistaHotel extends Migration
{
    public function up()
    {
        $now   = date('Y-m-d H:i:s');
        $hotel = $this->db->table('hotels')->where('name', 'Azure Vista Hotel & Suites')->get()->getRowArray();
        if (! $hotel) {
            $this->db->table('hotels')->insert([
                'name'           => 'Azure Vista Hotel & Suites',
                'tagline'        => 'Calm rooms with wide views.',
                'description'    => 'A modern four-storey hotel set among palm trees, with warm, softly lit rooms and large windows. Choose from seven rooms, from a Deluxe Room to the Penthouse Suite.',
                'address'        => null,
                'image'          => 'assets/img/hotels/azure-exterior.jpg',
                'check_in_time'  => '14:00',
                'check_out_time' => '12:00',
                'status'         => 'active',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            $hotelId = (int) $this->db->insertID();
        } else {
            $hotelId = (int) $hotel['id'];
        }

        // [number, name, capacity, price per night, description]
        $rooms = [
            [101, 'Superior Room 101',   3, 3600,  'Two beds, a work desk and an armchair by a city-view window. Good for friends or a small family.'],
            [100, 'Deluxe Room 100',     2, 4000,  'A king bed with a blue accent runner, a plant-lined corner, desk and a bright window over the city.'],
            [102, 'Premier Room 102',    2, 4800,  'A king bed in warm neutrals with designer lighting and a quiet seating corner.'],
            [105, 'Junior Suite 105',    3, 5600,  'A king bed plus a separate lounge area with a sofa and coffee table, and a wide balcony-view window.'],
            [104, 'Family Room 104',     4, 6000,  'Two beds and extra floor space with a lounge corner, made for families and small groups.'],
            [103, 'Executive Suite 103', 3, 7200,  'A king bed with bench, a large corner sofa lounge, desk and floor-to-ceiling windows.'],
            [106, 'Penthouse Suite 106', 4, 11500, 'The top suite: a chandelier-lit bedroom, sofa lounge, dining table for four and a sweeping skyline view.'],
        ];
        foreach ($rooms as [$no, $name, $cap, $price, $desc]) {
            if ($this->db->table('facilities')->where('name', $name)->countAllResults() > 0) {
                continue;
            }
            $this->db->table('facilities')->insert([
                'name'           => $name,
                'type'           => 'room',
                'hotel_id'       => $hotelId,
                'description'    => $desc,
                'image'          => 'assets/img/hotels/azure-room-' . $no . '.jpg',
                'capacity'       => $cap,
                'price_per_hour' => $price,   // for hotel rooms this column holds the price per NIGHT
                'status'         => 'active',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    public function down()
    {
        $hotel = $this->db->table('hotels')->where('name', 'Azure Vista Hotel & Suites')->get()->getRowArray();
        if ($hotel) {
            $this->db->table('facilities')->where('hotel_id', $hotel['id'])->delete();
            $this->db->table('hotels')->where('id', $hotel['id'])->delete();
        }
    }
}
