<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Optional uploaded photo per facility. When empty, a generated court illustration is shown. */
class AddImageToFacilities extends Migration
{
    public function up()
    {
        $this->forge->addColumn('facilities', [
            'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'description'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('facilities', 'image');
    }
}
