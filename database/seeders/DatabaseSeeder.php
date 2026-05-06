<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedOfficer();
        $this->seedCashier();

        $this->call([
            ProgramSeeder::class,
            TraineeSeeder::class,
        ]);
    }

    // -------------------------------------------------------------------------

    private function seedAdmin(): void
    {
        [$password, $generated] = $this->resolvePassword('ADMIN_DEFAULT_PASSWORD');

        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'               => 'System Administrator',
                'email'              => 'admin@ltpc.gov.ph',
                'password'           => Hash::make($password),
                'role'               => 'admin',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ]
        );

        // Fix role, status, and password on existing accounts
        $needsSave = false;
        if ($admin->role !== 'admin')   { $admin->role = 'admin';     $needsSave = true; }
        if ($admin->status !== 'active') { $admin->status = 'active'; $needsSave = true; }
        if (! $admin->wasRecentlyCreated && $generated === false) {
            $admin->password = Hash::make($password);
            $needsSave = true;
        }
        if ($needsSave) $admin->save();

        $this->printAccountStatus('Admin', 'admin@ltpc.gov.ph', $password, $generated, 'ADMIN_DEFAULT_PASSWORD');
    }

    private function seedOfficer(): void
    {
        [$password, $generated] = $this->resolvePassword('OFFICER_DEFAULT_PASSWORD');

        $officer = User::firstOrCreate(
            ['username' => 'LTPC1'],
            [
                'name'               => 'LTPC Officer',
                'email'              => 'ltpc1@ltpc.gov.ph',
                'password'           => Hash::make($password),
                'role'               => 'officer',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ]
        );

        $needsSave = false;
        if ($officer->role !== 'officer')  { $officer->role = 'officer';   $needsSave = true; }
        if ($officer->status !== 'active') { $officer->status = 'active';  $needsSave = true; }
        if (! $officer->wasRecentlyCreated && $generated === false) {
            $officer->password = Hash::make($password);
            $needsSave = true;
        }
        if ($needsSave) $officer->save();

        $this->printAccountStatus('Officer', 'ltpc1@ltpc.gov.ph', $password, $generated, 'OFFICER_DEFAULT_PASSWORD');
    }

    private function seedCashier(): void
    {
        [$password, $generated] = $this->resolvePassword('CASHIER_DEFAULT_PASSWORD');

        $cashier = User::firstOrCreate(
            ['username' => 'cashier1'],
            [
                'name'               => 'Cashier',
                'email'              => 'cashier1@ltpc.gov.ph',
                'password'           => Hash::make($password),
                'role'               => 'cashier',
                'status'             => 'active',
                'email_verified_at'  => now(),
            ]
        );

        $needsSave = false;
        if ($cashier->role !== 'cashier')  { $cashier->role = 'cashier';   $needsSave = true; }
        if ($cashier->status !== 'active') { $cashier->status = 'active';  $needsSave = true; }
        if (! $cashier->wasRecentlyCreated && $generated === false) {
            $cashier->password = Hash::make($password);
            $needsSave = true;
        }
        if ($needsSave) $cashier->save();

        $this->printAccountStatus('Cashier', 'cashier1@ltpc.gov.ph', $password, $generated, 'CASHIER_DEFAULT_PASSWORD');
    }

    // -------------------------------------------------------------------------

    /**
     * Returns [password, wasGenerated].
     * Uses the env var if set, otherwise auto-generates a complex password.
     */
    private function resolvePassword(string $envKey): array
    {
        $password = env($envKey);

        if (! $password) {
            $password = Str::random(16) . '!A1'; // meets uppercase, number, special-char requirements
            return [$password, true];
        }

        return [$password, false];
    }

    /**
     * Prints a consistent status block for each seeded account.
     */
    private function printAccountStatus(string $label, string $email, string $password, bool $generated, string $envKey): void
    {
        $this->command->info("✅ {$label} account ready: {$email}");

        if ($generated) {
            $this->command->warn("⚠️  [{$label}] Generated password: {$password} — CHANGE THIS after first login!");
        } else {
            $this->command->warn("⚠️  [{$label}] Password set from {$envKey} env var — CHANGE THIS after first login!");
        }
    }
}
