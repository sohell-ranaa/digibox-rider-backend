<?php

namespace Database\Seeders;

use App\Models\InstallationLocation;
use Illuminate\Database\Seeder;

class InstallationLocationSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing installations (disable foreign key checks)
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        InstallationLocation::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $installations = [
            [
                'name' => 'Tesco Extra Cheras Selatan - POS System',
                'address' => 'Jalan Taman Cheras, Balakong, 43200 Cheras, Selangor',
                'latitude' => 3.0412,
                'longitude' => 101.7685,
                'geofence_radius_meters' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'MINES Shopping Mall - Digital Signage',
                'address' => 'Jalan Dulang, Mines Resort City, 43300 Seri Kembangan, Selangor',
                'latitude' => 3.0330,
                'longitude' => 101.7170,
                'geofence_radius_meters' => 150,
                'is_active' => true,
            ],
            [
                'name' => 'South City Plaza - Network Infrastructure',
                'address' => 'Persiaran Serdang Perdana, Taman Serdang Perdana, 43300 Seri Kembangan, Selangor',
                'latitude' => 3.0150,
                'longitude' => 101.7290,
                'geofence_radius_meters' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Sunway College - Network Maintenance',
                'address' => 'No. 2, Jalan Universiti, Bandar Sunway, 47500 Petaling Jaya, Selangor',
                'latitude' => 3.0680,
                'longitude' => 101.7450,
                'geofence_radius_meters' => 120,
                'is_active' => true,
            ],
            [
                'name' => 'KFC Balakong - POS Troubleshooting',
                'address' => 'Jalan Balakong, Taman Balakong Jaya, 43300 Seri Kembangan, Selangor',
                'latitude' => 3.0398,
                'longitude' => 101.7625,
                'geofence_radius_meters' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($installations as $installation) {
            InstallationLocation::create($installation);
        }

        $this->command->info('✓ Created 5 installation locations around Balakong/Mines/South City Plaza area');
    }
}
