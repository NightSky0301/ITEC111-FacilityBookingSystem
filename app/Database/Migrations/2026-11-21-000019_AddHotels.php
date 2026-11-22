<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hotels.
 *  - hotels            : one row per hotel (name, photo, check-in/check-out times).
 *  - facilities.hotel_id: a hotel room is a facility of type "room" that belongs to a hotel.
 *  - bookings.total_price: a hotel stay is priced per night, so the total is saved with the booking.
 * Adds Velora Grand Hotel with six rooms. Nothing is renamed or deleted; running it twice changes nothing.
 */
class AddHotels extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('hotels')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'           => ['type' => 'VARCHAR', 'constraint' => 120],
                'tagline'        => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
                'description'    => ['type' => 'TEXT', 'null' => true],
                'address'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
                'image'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'check_in_time'  => ['type' => 'VARCHAR', 'constraint' => 5, 'default' => '14:00'],
                'check_out_time' => ['type' => 'VARCHAR', 'constraint' => 5, 'default' => '12:00'],
                'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('hotels');
        }

        if (! $this->db->fieldExists('hotel_id', 'facilities')) {
            $this->forge->addColumn('facilities', ['hotel_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true]]);
        }
        if (! $this->db->fieldExists('total_price', 'bookings')) {
            $this->forge->addColumn('bookings', ['total_price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true]]);
        }

        $now   = date('Y-m-d H:i:s');
        $hotel = $this->db->table('hotels')->where('name', 'Velora Grand Hotel')->get()->getRowArray();
        if (! $hotel) {
            $this->db->table('hotels')->insert([
                'name'           => 'Velora Grand Hotel',
                'tagline'        => 'Stay in comfort. Feel at home.',
                'description'    => 'A five-storey city hotel with air-conditioned rooms, floor-to-ceiling windows and quiet, well-lit interiors. Choose from six rooms, from a standard room for two to the Presidential Suite.',
                'address'        => null,
                'image'          => 'assets/img/hotels/velora-exterior.jpg',
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
            [100, 'Standard Room 100',     2, 3200,  'A king bed, work desk, armchair and a city-view window. A comfortable base for one or two guests.'],
            [101, 'Deluxe Room 101',       2, 4200,  'A larger room with a king bed, desk, armchair and floor-to-ceiling windows over the city.'],
            [102, 'Junior Suite 102',      3, 5800,  'A bedroom with a separate sofa and coffee-table area, plus a wide city view.'],
            [103, 'Family Room 103',       4, 6200,  'Two double beds and extra floor space, made for families and small groups.'],
            [104, 'Premium Room 104',      2, 5000,  'A king bed with bench, designer finishes and extra space to relax.'],
            [105, 'Presidential Suite 105', 4, 12500, 'The top room in the hotel: a chandelier-lit suite with a king bed, lounge seating and a sweeping skyline view.'],
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
                'image'          => 'assets/img/hotels/velora-room-' . $no . '.jpg',
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
        $this->db->table('facilities')->where('type', 'room')->where('hotel_id IS NOT NULL', null, false)->delete();
        $this->forge->dropTable('hotels', true);
        if ($this->db->fieldExists('hotel_id', 'facilities')) {
            $this->forge->dropColumn('facilities', 'hotel_id');
        }
        if ($this->db->fieldExists('total_price', 'bookings')) {
            $this->forge->dropColumn('bookings', 'total_price');
        }
    }
}
