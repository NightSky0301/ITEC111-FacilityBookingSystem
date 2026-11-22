<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A stay of several days is stored as one booking per day. group_id ties those days together
 * (it holds the id of the first day). Single bookings keep group_id empty.
 */
class AddBookingGroup extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('group_id', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'group_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null, 'after' => 'id'],
            ]);
            $this->db->query('CREATE INDEX bookings_group_id ON bookings (group_id)');
        }
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', 'group_id');
    }
}
