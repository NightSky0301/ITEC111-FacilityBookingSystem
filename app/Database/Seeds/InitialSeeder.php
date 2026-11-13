<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Default accounts (change these passwords after first login!)
        $this->db->table('users')->insertBatch([
            [
                'name'       => 'System Admin',
                'email'      => 'admin@facility.test',
                'phone'      => null,
                'password'   => password_hash('admin123', PASSWORD_DEFAULT),
                'role'       => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Sample Customer',
                'email'      => 'customer@facility.test',
                'phone'      => '09123456789',
                'password'   => password_hash('customer123', PASSWORD_DEFAULT),
                'role'       => 'customer',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Sample courts (edit or replace these from Admin > Facilities).
        // type must be one of the keys in FacilityModel::TYPES
        $courts = [
            ['Basketball Court A', 'basketball', 'Indoor full court with wooden floor, scoreboard and lights.', 10, 800],
            ['Basketball Court B', 'basketball', 'Outdoor court with night lights. Ball not included.', 10, 500],
            ['Volleyball Court',   'volleyball', 'Indoor court with adjustable net. Good for 6-a-side games.', 12, 600],
            ['Pickleball Court 1', 'pickleball', 'Standard court with net and lines. Paddles can be rented at the counter.', 4, 350],
            ['Pickleball Court 2', 'pickleball', 'Standard court with net and lines. Paddles can be rented at the counter.', 4, 350],
            ['Badminton Court',    'badminton',  'Indoor court with wooden floor. Shuttlecocks sold on site.', 4, 250],
            ['Covered Multipurpose Court', 'multipurpose', 'Covered court for tournaments, practice and events. Fits basketball and volleyball markings.', 60, 1500],
        ];

        $rows = [];
        foreach ($courts as [$name, $type, $description, $capacity, $price]) {
            $rows[] = [
                'name'           => $name,
                'type'           => $type,
                'description'    => $description,
                'capacity'       => $capacity,
                'price_per_hour' => $price,
                'status'         => 'active',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }
        $this->db->table('facilities')->insertBatch($rows);
    }
}
