<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Opening hours per facility, a reminder stamp on bookings, and indexes for the overlap queries. */
class BookingHoursRemindersIndexes extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('open_hour', 'facilities')) {
            $this->forge->addColumn('facilities', [
                'open_hour'  => ['type' => 'TINYINT', 'constraint' => 2, 'unsigned' => true, 'null' => true],
                'close_hour' => ['type' => 'TINYINT', 'constraint' => 2, 'unsigned' => true, 'null' => true],
            ]);
        }
        if (! $this->db->fieldExists('reminded_at', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'reminded_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
        }
        foreach ([
            'idx_bookings_facility_time' => 'facility_id, start_at, end_at',
            'idx_bookings_status_start'  => 'status, start_at',
            'idx_bookings_group'         => 'group_id',
        ] as $name => $cols) {
            try {
                $this->db->query("CREATE INDEX {$name} ON bookings ({$cols})");
            } catch (\Throwable $e) {
                // index already there
            }
        }
    }

    public function down()
    {
        foreach (['idx_bookings_facility_time', 'idx_bookings_status_start', 'idx_bookings_group'] as $name) {
            try {
                $this->db->query($this->db->DBDriver === 'MySQLi' ? "DROP INDEX {$name} ON bookings" : "DROP INDEX {$name}");
            } catch (\Throwable $e) {
            }
        }
        $this->forge->dropColumn('bookings', 'reminded_at');
        $this->forge->dropColumn('facilities', ['open_hour', 'close_hour']);
    }
}
