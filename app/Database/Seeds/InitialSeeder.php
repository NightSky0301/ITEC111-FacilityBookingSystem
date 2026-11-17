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
                'name'       => 'Front Desk Staff',
                'email'      => 'staff@facility.test',
                'phone'      => null,
                'password'   => password_hash('staff123', PASSWORD_DEFAULT),
                'role'       => 'staff',
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
        // type must be one of the keys in FacilityModel::TYPES.
        // Court photos are generated automatically from the type and the A/B name.
        $courts = [
            ['Basketball Court A', 'basketball', 'Indoor full court with wooden floor, scoreboard and lights.', 15, 800],
            ['Basketball Court B', 'basketball', 'Outdoor court with night lights. Ball not included.', 15, 500],
            ['Badminton Court A',  'badminton',  'Indoor court with wooden floor. Shuttlecocks sold on site.', 4, 250],
            ['Badminton Court B',  'badminton',  'Outdoor court next to Court A with night lights. Good for practice and casual games.', 4, 250],
            ['Pickleball Court A', 'pickleball', 'Standard court with net and lines. Paddles can be rented at the counter.', 4, 350],
            ['Pickleball Court B', 'pickleball', 'Outdoor court with net and lines and night lights. Paddles can be rented at the counter.', 4, 350],
            ['Volleyball Court A', 'volleyball', 'Indoor court with adjustable net and wooden floor.', 18, 450],
            ['Volleyball Court B', 'volleyball', 'Outdoor sand court with night lights. Ball not included.', 18, 400],
            ['Futsal Court A', 'futsal', 'Indoor futsal court with goals, line markings and bright lighting.', 14, 700],
            ['Football Court', 'football', 'Full-size outdoor pitch with goals, line markings, team benches and floodlights. Open air.', 22, 1200],
            ['Tennis Court A', 'tennis', 'Indoor hard court with a net, full markings and stand lights.', 4, 450],
            ['Tennis Court B', 'tennis', 'Outdoor hard court with a net and full markings. Open air, so no air-conditioning.', 4, 350],
            ['Gym', 'gym', 'Open workout floor with cardio machines, free weights and a stretching area. Booked per person, up to 15 people at a time.', 15, 100],
        ];

        $rows = [];
        foreach ($courts as [$name, $type, $description, $capacity, $price]) {
            // The gym migration may already have added the Gym; never add a second one.
            if (($type === 'gym' || in_array($name, ['Futsal Court A', 'Football Court', 'Tennis Court A', 'Tennis Court B'], true))
                && $this->db->table('facilities')->where($type === 'gym' ? 'type' : 'name', $type === 'gym' ? 'gym' : $name)->countAllResults() > 0) {
                continue;
            }
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
