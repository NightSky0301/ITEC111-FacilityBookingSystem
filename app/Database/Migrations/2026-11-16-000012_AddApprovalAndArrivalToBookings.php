<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Who approved a booking, and when the customer's arrival was confirmed at the desk.
 * Only adds columns; old bookings keep working (their approver and arrival are simply empty).
 */
class AddApprovalAndArrivalToBookings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('bookings', [
            'approved_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'arrived_at'  => ['type' => 'DATETIME', 'null' => true],
            'arrived_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', ['approved_by', 'arrived_at', 'arrived_by']);
    }
}
