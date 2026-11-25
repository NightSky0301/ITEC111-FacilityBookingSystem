<?php

namespace App\Database\Migrations;

use App\Libraries\Addons;
use CodeIgniter\Database\Migration;

/**
 * Add-ons become data the admin manages: a list of add-ons (`addons`) and which facility offers which (`facility_addons`).
 * Everything from the old built-in list is copied over and linked to the facilities it used to apply to,
 * so nothing changes for existing facilities.
 */
class CreateAddons extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        if (! $this->db->tableExists('addons')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'addon_key'  => ['type' => 'VARCHAR', 'constraint' => 40],
                'label'      => ['type' => 'VARCHAR', 'constraint' => 80],
                'note'       => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
                'price'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'per'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'session'], // hour | session | person | person_hour
                'grp'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'extra'],   // extra | cooling
                'evening'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'indoor'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('addon_key');
            $this->forge->createTable('addons', true);

            foreach (Addons::CATALOG as $key => $a) {
                $this->db->table('addons')->insert([
                    'addon_key' => $key, 'label' => $a['label'], 'note' => $a['note'], 'price' => $a['price'], 'per' => $a['per'],
                    'grp' => $a['group'], 'evening' => $a['evening'] ? 1 : 0, 'indoor' => ! empty($a['indoor']) ? 1 : 0,
                    'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        if (! $this->db->tableExists('facility_addons')) {
            $this->forge->addField([
                'facility_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'addon_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $this->forge->addKey(['facility_id', 'addon_id'], true);
            $this->forge->createTable('facility_addons', true);

            // Link every existing facility (archived ones too) to the add-ons its type used to get.
            $ids = [];
            foreach ($this->db->table('addons')->get()->getResultArray() as $row) {
                $ids[$row['addon_key']] = (int) $row['id'];
            }
            foreach ($this->db->table('facilities')->select('id, type')->get()->getResultArray() as $f) {
                foreach (Addons::defaultsFor((string) $f['type']) as $key) {
                    if (isset($ids[$key])) {
                        $this->db->table('facility_addons')->insert(['facility_id' => (int) $f['id'], 'addon_id' => $ids[$key]]);
                    }
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('facility_addons', true);
        $this->forge->dropTable('addons', true);
    }
}
