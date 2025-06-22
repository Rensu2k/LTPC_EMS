<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
            ]
        );
        User::firstOrCreate(
            ['username' => 'LTPC1'],
            [
                'name' => 'LTPC Officer',
                'email' => 'ltpc1@example.com',
                'password' => bcrypt('LTPC1'),
                'role' => 'officer',
            ]
        );
        User::firstOrCreate(
            ['username' => 'cashier1'],
            [
                'name' => 'Cashier',
                'email' => 'cashier1@example.com',
                'password' => bcrypt('cashier1'),
                'role' => 'cashier',
            ]
        );
    }
}
