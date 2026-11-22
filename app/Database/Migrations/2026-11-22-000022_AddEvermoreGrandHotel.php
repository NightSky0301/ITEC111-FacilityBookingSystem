<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a fourth hotel, Evermore Grand Hotel & Suites, with seven rooms.
 * Needs migration 000019 (hotels table) first. Nothing is renamed or deleted; running it twice changes nothing.
 * Room names start with "Evermore Grand" so they cannot be mixed up with the other hotels' rooms in the booking lists.
 */
class AddEvermoreGrandHotel extends Migration
{
    public function up()
    {
        $now   = date('Y-m-d H:i:s');
        $hotel = $this->db->table('hotels')->where('name', 'Evermore Grand Hotel & Suites')->get()->getRowArray();
        if (! $hotel) {
            $this->db->table('hotels')->insert([
                'name'           => 'Evermore Grand Hotel & Suites',
                'tagline'        => 'Lit palm gardens, skyline views.',
                'description'    => 'A five-storey hotel with a lit palm garden and fountain entrance, warm wood-panelled rooms and floor-to-ceiling skyline views. Choose from seven rooms, from the Deluxe Room to the Penthouse Suite.',
                'address'        => null,
                'image'          => 'assets/img/hotels/evermore-exterior.jpg',
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
            [101, 'Evermore Grand Superior Room 101',   3, 3700,  'Two beds with blue runners, a work desk and armchairs by a large skyline-view window.'],
            [100, 'Evermore Grand Deluxe Room 100',     2, 4200,  'A king bed with a blue runner, a plant-lined corner, a desk and a bright window over the city.'],
            [102, 'Evermore Grand Premier Room 102',    2, 5100,  'A king bed with a gold runner, a gold wall accent, designer lighting and a quiet seating corner.'],
            [105, 'Evermore Grand Junior Suite 105',    3, 5900,  'A king bed with purple accents plus a separate lounge with armchairs and a coffee table.'],
            [104, 'Evermore Grand Family Room 104',     4, 6300,  'Two beds with terracotta runners and extra floor space, made for families and small groups.'],
            [103, 'Evermore Grand Executive Suite 103', 3, 7500,  'A king bed with bench, a corner sofa lounge, a desk and floor-to-ceiling windows.'],
            [106, 'Evermore Grand Penthouse Suite 106', 4, 12000, 'The top suite: a chandelier-lit bedroom, sofa lounge, dining table and a sweeping skyline view.'],
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
                'image'          => 'assets/img/hotels/evermore-room-' . $no . '.jpg',
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
        $hotel = $this->db->table('hotels')->where('name', 'Evermore Grand Hotel & Suites')->get()->getRowArray();
        if ($hotel) {
            $this->db->table('facilities')->where('hotel_id', $hotel['id'])->delete();
            $this->db->table('hotels')->where('id', $hotel['id'])->delete();
        }
    }
}
