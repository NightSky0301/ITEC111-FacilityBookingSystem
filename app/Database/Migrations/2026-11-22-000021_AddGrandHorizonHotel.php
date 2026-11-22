<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a third hotel, Grand Horizon Hotel & Suites, with seven rooms.
 * Needs migration 000019 (hotels table) first. Nothing is renamed or deleted; running it twice changes nothing.
 * Room names start with "Grand Horizon" so they cannot be mixed up with the other hotels' rooms in the booking lists.
 */
class AddGrandHorizonHotel extends Migration
{
    public function up()
    {
        $now   = date('Y-m-d H:i:s');
        $hotel = $this->db->table('hotels')->where('name', 'Grand Horizon Hotel & Suites')->get()->getRowArray();
        if (! $hotel) {
            $this->db->table('hotels')->insert([
                'name'           => 'Grand Horizon Hotel & Suites',
                'tagline'        => 'Palm-lined arrivals, city-view rooms.',
                'description'    => 'A modern four-storey hotel with a lit palm-tree entrance, warm wood-panelled rooms and floor-to-ceiling city views. Choose from seven rooms, from the Deluxe Room to the Penthouse Suite.',
                'address'        => null,
                'image'          => 'assets/img/hotels/grand-exterior.jpg',
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
            [101, 'Grand Horizon Superior Room 101',   3, 3800,  'Two beds with blue runners, a work desk and an armchair by a large city-view window.'],
            [100, 'Grand Horizon Deluxe Room 100',     2, 4300,  'A king bed with a blue runner, a plant-lined corner, a desk and a bright window over the city.'],
            [102, 'Grand Horizon Premier Room 102',    2, 5200,  'A king bed with a gold runner, designer lighting and a quiet seating corner.'],
            [105, 'Grand Horizon Junior Suite 105',    3, 6000,  'A king bed plus a separate lounge with armchairs and a coffee table, facing a wide city-view window.'],
            [104, 'Grand Horizon Family Room 104',     4, 6400,  'Two beds with terracotta runners and extra floor space, made for families and small groups.'],
            [103, 'Grand Horizon Executive Suite 103', 3, 7600,  'A king bed with bench, a corner sofa lounge, a desk and floor-to-ceiling windows.'],
            [106, 'Grand Horizon Penthouse Suite 106', 4, 12500, 'The top suite: a chandelier-lit bedroom, sofa lounge, dining table and a sweeping skyline view.'],
        ];
        foreach ($rooms as [$no, $name, $cap, $price, $desc]) {
            if ($this->db->table('facilities')->where('hotel_id', $hotelId)->where('name', $name)->countAllResults() > 0) {
                continue;
            }
            $this->db->table('facilities')->insert([
                'name'           => $name,
                'type'           => 'room',
                'hotel_id'       => $hotelId,
                'description'    => $desc,
                'image'          => 'assets/img/hotels/grand-room-' . $no . '.jpg',
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
        $hotel = $this->db->table('hotels')->where('name', 'Grand Horizon Hotel & Suites')->get()->getRowArray();
        if ($hotel) {
            $this->db->table('facilities')->where('hotel_id', $hotel['id'])->delete();
            $this->db->table('hotels')->where('id', $hotel['id'])->delete();
        }
    }
}
