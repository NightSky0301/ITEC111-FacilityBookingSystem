<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * "Court A" becomes "(Indoor)" and "Court B" becomes "(Outdoor)" for basketball, badminton,
 * pickleball, volleyball and tennis. Futsal, gym, multipurpose and football keep their names
 * (futsal, gym and multipurpose are always indoor, football is always outdoor).
 */
class RenameCourtsIndoorOutdoor extends Migration
{
    private array $types = ['basketball', 'badminton', 'pickleball', 'volleyball', 'tennis'];

    public function up()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $rows = $db->table('facilities')->whereIn('type', $this->types)->get()->getResultArray();
        $names = array_column($rows, 'name');

        foreach ($rows as $r) {
            if (! preg_match('/^(.+) Court ([AB])$/', trim($r['name']), $m)) {
                continue;
            }
            $new = $m[1] . ' Court (' . ($m[2] === 'A' ? 'Indoor' : 'Outdoor') . ')';
            if (in_array($new, $names, true)) {
                continue;
            }
            $desc = str_replace(['next to Court A', 'next to Court B'], ['next to the indoor court', 'next to the outdoor court'], (string) $r['description']);
            $db->table('facilities')->where('id', $r['id'])->update(['name' => $new, 'description' => $desc, 'updated_at' => $now]);
        }
    }

    public function down()
    {
        $now = date('Y-m-d H:i:s');
        $rows = $this->db->table('facilities')->whereIn('type', $this->types)->get()->getResultArray();
        foreach ($rows as $r) {
            if (preg_match('/^(.+) Court \((Indoor|Outdoor)\)$/', $r['name'], $m)) {
                $this->db->table('facilities')->where('id', $r['id'])->update(['name' => $m[1] . ' Court ' . ($m[2] === 'Indoor' ? 'A' : 'B'), 'updated_at' => $now]);
            }
        }
    }
}
