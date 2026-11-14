<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Lets the admin deactivate accounts (for example when a staff member leaves). */
class AddStatusToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active', 'after' => 'role'], // active | inactive
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'status');
    }
}
