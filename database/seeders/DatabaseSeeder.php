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

        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
        ]);
        User::create([
            'name' => 'LTPC Officer',
            'username' => 'LTPC1',
            'email' => 'ltpc1@example.com',
            'password' => bcrypt('LTPC1'),
            'role' => 'officer',
        ]);
        User::create([
            'name' => 'Cashier',
            'username' => 'cashier1',
            'email' => 'cashier1@example.com',
            'password' => bcrypt('cashier1'),
            'role' => 'cashier',
        ]);
    }
}
