<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Swimming Pool A becomes "Swimming Pool (Indoor)" and Swimming Pool B becomes "Swimming Pool (Outdoor)",
 * with a new description for the outdoor pool (its photo is public/assets/img/courts/swimming-b.jpg).
 * Only exact name matches are touched, and nothing is deleted.
 */
class MakePoolBOutdoor extends Migration
{
    public function up()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');
        $has = static fn (string $name): bool => $db->table('facilities')->where('name', $name)->countAllResults() > 0;

        if ($has('Swimming Pool A') && ! $has('Swimming Pool (Indoor)')) {
            $db->table('facilities')->where('name', 'Swimming Pool A')->update(['name' => 'Swimming Pool (Indoor)', 'updated_at' => $now]);
        }
        if ($has('Swimming Pool B') && ! $has('Swimming Pool (Outdoor)')) {
            $db->table('facilities')->where('name', 'Swimming Pool B')->update([
                'name'        => 'Swimming Pool (Outdoor)',
                'description' => 'Open-air lap pool with marked lanes, stainless-steel ladders, loungers, parasols and shaded poolside cabanas. Booked per hour for your whole group.',
                'image'       => 'assets/img/courts/swimming-b.jpg',
                'updated_at'  => $now,
            ]);
        }
    }

    public function down()
    {
        // Names are not reverted on purpose.
    }
}
