<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** The default admin account is called "Administrator" instead of "System Admin". Other names are left alone. */
class RenameSystemAdmin extends Migration
{
    public function up()
    {
        $this->db->table('users')->where('name', 'System Admin')->where('role', 'admin')->update(['name' => 'Administrator']);
    }

    public function down()
    {
        $this->db->table('users')->where('name', 'Administrator')->where('role', 'admin')->where('email', 'admin@facility.test')->update(['name' => 'System Admin']);
    }
}
