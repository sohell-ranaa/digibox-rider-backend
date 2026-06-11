<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rider;
use Illuminate\Support\Facades\Hash;

class RiderSeeder extends Seeder
{
    public function run(): void
    {
        Rider::create([
            'username' => 'rider01',
            'password' => Hash::make('password'),
            'name' => 'Test Rider',
            'phone' => '01712345678',
            'email' => 'rider01@digibox.com',
            'is_active' => true,
        ]);

        Rider::create([
            'username' => 'rider02',
            'password' => Hash::make('password'),
            'name' => 'Second Rider',
            'phone' => '01712345679',
            'email' => 'rider02@digibox.com',
            'is_active' => true,
        ]);
    }
}
