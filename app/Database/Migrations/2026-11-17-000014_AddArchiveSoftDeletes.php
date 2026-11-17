<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Adds a deleted_at column to users, facilities and bookings. A row with deleted_at set is "archived" and can be restored. */
class AddArchiveSoftDeletes extends Migration
{
    private array $tables = ['users', 'facilities', 'bookings'];

    public function up()
    {
        foreach ($this->tables as $table) {
            if (! $this->db->fieldExists('deleted_at', $table)) {
                $this->forge->addColumn($table, ['deleted_at' => ['type' => 'DATETIME', 'null' => true]]);
            }
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            if ($this->db->fieldExists('deleted_at', $table)) {
                $this->forge->dropColumn($table, 'deleted_at');
            }
        }
    }
}
