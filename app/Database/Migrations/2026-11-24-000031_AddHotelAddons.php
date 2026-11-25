<?php

namespace App\Database\Migrations;

use App\Libraries\Addons;
use CodeIgniter\Database\Migration;

/** Hotel rooms get add-ons too (extra bed, early check-in, late check-out). Safe to run twice. */
class AddHotelAddons extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('addons') || ! $this->db->tableExists('facility_addons')) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        foreach (Addons::CATALOG as $key => $a) {
            if ($this->db->table('addons')->where('addon_key', $key)->countAllResults() === 0) {
                $this->db->table('addons')->insert([
                    'addon_key' => $key, 'label' => $a['label'], 'note' => $a['note'], 'price' => $a['price'], 'per' => $a['per'],
                    'grp' => $a['group'], 'evening' => $a['evening'] ? 1 : 0, 'indoor' => ! empty($a['indoor']) ? 1 : 0,
                    'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $ids = [];
        foreach ($this->db->table('addons')->get()->getResultArray() as $r) {
            $ids[$r['addon_key']] = (int) $r['id'];
        }
        foreach ($this->db->table('facilities')->select('id')->where('type', 'room')->get()->getResultArray() as $f) {
            foreach (Addons::defaultsFor('room') as $key) {
                $row = ['facility_id' => (int) $f['id'], 'addon_id' => $ids[$key] ?? 0];
                if ($row['addon_id'] && $this->db->table('facility_addons')->where($row)->countAllResults() === 0) {
                    $this->db->table('facility_addons')->insert($row);
                }
            }
        }
    }

    public function down()
    {
        // Nothing to undo: the add-ons stay available.
    }
}
