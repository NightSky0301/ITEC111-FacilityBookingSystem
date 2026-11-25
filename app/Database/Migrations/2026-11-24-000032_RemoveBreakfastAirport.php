<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Hotels no longer offer Breakfast or Airport pickup: remove them from every room and from the add-on list. */
class RemoveBreakfastAirport extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('addons')) {
            return;
        }
        $ids = array_column($this->db->table('addons')->select('id')->whereIn('addon_key', ['breakfast', 'airport'])->get()->getResultArray(), 'id');
        if (! $ids) {
            return;
        }
        if ($this->db->tableExists('facility_addons')) {
            $this->db->table('facility_addons')->whereIn('addon_id', $ids)->delete();
        }
        $this->db->table('addons')->whereIn('id', $ids)->delete();
    }

    public function down()
    {
    }
}
