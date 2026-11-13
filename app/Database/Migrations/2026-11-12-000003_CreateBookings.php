<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'facility_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'start_at'      => ['type' => 'DATETIME'],
            'end_at'        => ['type' => 'DATETIME'],
            'guests'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'purpose'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'], // pending | approved | rejected | cancelled
            'admin_remarks' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['facility_id', 'start_at', 'end_at']);
        $this->forge->addKey('user_id');
        $this->forge->createTable('bookings', true);
    }

    public function down()
    {
        $this->forge->dropTable('bookings', true);
    }
}
