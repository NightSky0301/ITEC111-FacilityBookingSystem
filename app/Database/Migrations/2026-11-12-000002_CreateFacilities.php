<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFacilities extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 120],
            'type'           => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'room'], // room | pool | function_hall | other ...
            'description'    => ['type' => 'TEXT', 'null' => true],
            'capacity'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'price_per_hour' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'], // active | inactive
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('facilities', true);
    }

    public function down()
    {
        $this->forge->dropTable('facilities', true);
    }
}
