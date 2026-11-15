<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Add-ons chosen on a booking (lights, fan / AC, rentals): the list as JSON and the amount they add. */
class AddAddonsToBookings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('bookings', [
            'addons'       => ['type' => 'TEXT', 'null' => true],
            'addons_total' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', ['addons', 'addons_total']);
    }
}
