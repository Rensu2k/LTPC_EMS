<?php

namespace Database\Seeders;

use App\Models\Trainee;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TraineeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available programs
        $programs = Program::all();
        if ($programs->isEmpty()) {
            $this->command->warn('No programs found. Please run ProgramSeeder first.');
            return;
        }

        $scholarshipPackages = ['TWSP', 'PESFA', 'STEP', 'TUPAD', '4Ps'];
        $sexes = ['male', 'female'];
        $civilStatuses = ['single', 'married', 'separated', 'widowed'];
        $employmentStatuses = ['wage_employed', 'underemployed', 'self_employed', 'unemployed'];
        $employmentTypes = ['none', 'casual', 'probationary', 'contractual', 'regular', 'job_order'];
        $regions = ['NCR', 'Region III', 'Region IV-A', 'Region V', 'Region VI', 'Region VII'];
        $provinces = ['Metro Manila', 'Bulacan', 'Laguna', 'Cavite', 'Batangas', 'Quezon'];
        $cities = ['Manila', 'Quezon City', 'Makati', 'Pasig', 'Caloocan', 'Las Piñas', 'Malabon', 'Navotas', 'Valenzuela', 'Marikina'];
        $barangays = ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5', 'Barangay 6', 'Barangay 7', 'Barangay 8'];
        
        // Filipino first names
        $firstNames = [
            'Juan', 'Maria', 'Jose', 'Ana', 'Carlos', 'Rosa', 'Antonio', 'Carmen', 'Manuel', 'Dolores',
            'Pedro', 'Teresa', 'Francisco', 'Rosa', 'Miguel', 'Concepcion', 'Ramon', 'Josefa', 'Ricardo', 'Filomena',
            'Fernando', 'Catalina', 'Roberto', 'Margarita', 'Eduardo', 'Isabel', 'Alfredo', 'Patricia', 'Rodrigo', 'Mercedes',
            'Angel', 'Cristina', 'Victor', 'Gloria', 'Rafael', 'Lourdes', 'Enrique', 'Amparo', 'Alberto', 'Esperanza',
            'Jose', 'Rosario', 'Luis', 'Soledad', 'Felipe', 'Consuelo', 'Jorge', 'Beatriz', 'Arturo', 'Dolores'
        ];
        
        // Filipino last names
        $lastNames = [
            'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Lopez', 'Rodriguez', 'Martinez', 'Torres',
            'Fernandez', 'Gonzalez', 'Sanchez', 'Perez', 'Gomez', 'Diaz', 'Ramos', 'Morales', 'Rivera', 'Mendoza',
            'Villanueva', 'Delos Santos', 'Ramos', 'Castro', 'Aquino', 'De Guzman', 'Villanueva', 'Del Rosario', 'Mendoza', 'Ramos',
            'Alvarez', 'Romero', 'Jimenez', 'Herrera', 'Medina', 'Vargas', 'Ortega', 'Silva', 'Moreno', 'Delgado'
        ];
        
        $middleNames = ['Dela', 'De', 'Mac', 'Van', 'San', 'Tan', 'Lim', 'Chua', 'Sy', 'Ng'];

        // Create 20 Regular Trainees
        $this->command->info('Creating 20 regular trainees...');
        for ($i = 1; $i <= 20; $i++) {
            $birthYear = rand(1995, 2005);
            $birthMonth = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
            $birthDay = rand(1, 28);
            $age = Carbon::now()->year - $birthYear;
            
            $program = $programs->random();
            
            Trainee::create([
                'uli_number' => 'ULI-REG-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'entry_date' => Carbon::now()->subDays(rand(1, 90)),
                'last_name' => $lastNames[array_rand($lastNames)],
                'first_name' => $firstNames[array_rand($firstNames)],
                'middle_name' => rand(0, 1) ? $middleNames[array_rand($middleNames)] : null,
                'extension' => rand(0, 10) === 0 ? 'Jr.' : null,
                'street_number' => rand(1, 999) . ' ' . ['St.', 'Ave.', 'Rd.', 'Blvd.'][array_rand(['St.', 'Ave.', 'Rd.', 'Blvd.'])],
                'barangay' => $barangays[array_rand($barangays)],
                'district' => 'District ' . rand(1, 6),
                'city_municipality' => $cities[array_rand($cities)],
                'province' => $provinces[array_rand($provinces)],
                'region' => $regions[array_rand($regions)],
                'email_facebook' => 'trainee.reg' . $i . '@email.com',
                'contact_number' => '09' . rand(100000000, 999999999),
                'nationality' => 'Filipino',
                'sex' => $sexes[array_rand($sexes)],
                'civil_status' => $civilStatuses[array_rand($civilStatuses)],
                'employment_status' => $employmentStatuses[array_rand($employmentStatuses)],
                'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                'birth_month' => $birthMonth,
                'birth_day' => $birthDay,
                'birth_year' => $birthYear,
                'age' => $age,
                'birthplace_city' => $cities[array_rand($cities)],
                'birthplace_province' => $provinces[array_rand($provinces)],
                'birthplace_region' => $regions[array_rand($regions)],
                'education' => json_encode([
                    'highest_level' => ['High School', 'Senior High School', 'College Undergraduate'][array_rand(['High School', 'Senior High School', 'College Undergraduate'])],
                    'school_name' => 'Sample High School',
                    'year_graduated' => rand(2015, 2023)
                ]),
                'parent_guardian_name' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
                'parent_guardian_address' => rand(1, 999) . ' Sample Street, ' . $barangays[array_rand($barangays)],
                'classification' => json_encode(['youth', 'out_of_school']),
                'program_qualification' => $program->name,
                'batch' => rand(1, 3),
                'scholarship_package' => null, // Regular trainee - no scholarship
                'requirements' => json_encode([
                    'birth_certificate' => true,
                    'id_picture' => true,
                    'form_137' => rand(0, 1) === 1,
                    'medical_certificate' => rand(0, 1) === 1
                ]),
                'status' => rand(0, 1) === 1 ? 'active' : 'pending', // Some paid, some pending
                'payment_status' => rand(0, 1) === 1 ? 'paid' : 'unpaid',
                'payment_method' => rand(0, 1) === 1 ? ['cash', 'bank_transfer', 'online'][array_rand(['cash', 'bank_transfer', 'online'])] : null,
                'payment_reference' => rand(0, 1) === 1 ? 'PAY-' . strtoupper(uniqid()) : null,
                'payment_date' => rand(0, 1) === 1 ? Carbon::now()->subDays(rand(1, 30)) : null,
            ]);
        }

        // Create 20 Scholar Trainees
        $this->command->info('Creating 20 scholar trainees...');
        for ($i = 1; $i <= 20; $i++) {
            $birthYear = rand(1995, 2005);
            $birthMonth = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
            $birthDay = rand(1, 28);
            $age = Carbon::now()->year - $birthYear;
            
            $program = $programs->random();
            $scholarshipPackage = $scholarshipPackages[array_rand($scholarshipPackages)];
            
            Trainee::create([
                'uli_number' => 'ULI-SCH-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'entry_date' => Carbon::now()->subDays(rand(1, 90)),
                'last_name' => $lastNames[array_rand($lastNames)],
                'first_name' => $firstNames[array_rand($firstNames)],
                'middle_name' => rand(0, 1) ? $middleNames[array_rand($middleNames)] : null,
                'extension' => rand(0, 10) === 0 ? 'Jr.' : null,
                'street_number' => rand(1, 999) . ' ' . ['St.', 'Ave.', 'Rd.', 'Blvd.'][array_rand(['St.', 'Ave.', 'Rd.', 'Blvd.'])],
                'barangay' => $barangays[array_rand($barangays)],
                'district' => 'District ' . rand(1, 6),
                'city_municipality' => $cities[array_rand($cities)],
                'province' => $provinces[array_rand($provinces)],
                'region' => $regions[array_rand($regions)],
                'email_facebook' => 'trainee.sch' . $i . '@email.com',
                'contact_number' => '09' . rand(100000000, 999999999),
                'nationality' => 'Filipino',
                'sex' => $sexes[array_rand($sexes)],
                'civil_status' => $civilStatuses[array_rand($civilStatuses)],
                'employment_status' => $employmentStatuses[array_rand($employmentStatuses)],
                'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                'birth_month' => $birthMonth,
                'birth_day' => $birthDay,
                'birth_year' => $birthYear,
                'age' => $age,
                'birthplace_city' => $cities[array_rand($cities)],
                'birthplace_province' => $provinces[array_rand($provinces)],
                'birthplace_region' => $regions[array_rand($regions)],
                'education' => json_encode([
                    'highest_level' => ['High School', 'Senior High School', 'College Undergraduate'][array_rand(['High School', 'Senior High School', 'College Undergraduate'])],
                    'school_name' => 'Sample High School',
                    'year_graduated' => rand(2015, 2023)
                ]),
                'parent_guardian_name' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
                'parent_guardian_address' => rand(1, 999) . ' Sample Street, ' . $barangays[array_rand($barangays)],
                'classification' => json_encode(['youth', 'out_of_school', 'indigent']),
                'program_qualification' => $program->name,
                'batch' => rand(1, 3),
                'scholarship_package' => $scholarshipPackage, // Scholar trainee
                'requirements' => json_encode([
                    'birth_certificate' => true,
                    'id_picture' => true,
                    'form_137' => rand(0, 1) === 1,
                    'medical_certificate' => rand(0, 1) === 1,
                    'scholarship_documents' => true
                ]),
                'status' => 'active', // Scholars are automatically active
                'payment_status' => 'paid', // Scholars are automatically paid
                'payment_method' => 'scholarship_exemption',
                'payment_reference' => 'SCHOLAR-' . strtoupper($scholarshipPackage) . '-' . time() . '-' . $i,
                'payment_date' => Carbon::now()->subDays(rand(1, 30)),
                'payment_notes' => "Payment exempted due to {$scholarshipPackage} scholarship package",
            ]);
        }

        $this->command->info('Successfully created 20 regular trainees and 20 scholar trainees!');
    }
}

