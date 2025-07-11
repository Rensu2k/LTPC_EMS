<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use Carbon\Carbon;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            // Computer and Information Technology Programs
            [
                'program_id' => 'PROG-CSS-NCII-001',
                'name' => 'Computer Systems Servicing NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to be able to install and configure computer systems, set up computer networks, and servers.',
                'duration' => '256 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3500.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(30),
                'end_date' => Carbon::now()->addDays(120),
            ],
            [
                'program_id' => 'PROG-CP-NCIV-001',
                'name' => 'Computer Programming NCIV',
                'description' => 'This qualification consists of competencies that a person must achieve to be able to develop applications and programs using various programming languages.',
                'duration' => '744 hours',
                'prerequisites' => 'Computer Systems Servicing NCII or equivalent',
                'enrollment_fee' => 5500.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(45),
                'end_date' => Carbon::now()->addDays(200),
            ],
            [
                'program_id' => 'PROG-WD-001',
                'name' => 'Web Development',
                'description' => 'Learn to create responsive websites and web applications using modern technologies including HTML, CSS, JavaScript, and frameworks.',
                'duration' => '320 hours',
                'prerequisites' => 'Basic computer literacy',
                'enrollment_fee' => 4200.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(15),
                'end_date' => Carbon::now()->addDays(95),
            ],

            // Automotive Programs
            [
                'program_id' => 'PROG-AS-NCII-001',
                'name' => 'Automotive Servicing NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to be able to service/maintain motor vehicles.',
                'duration' => '358 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 4000.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(20),
                'end_date' => Carbon::now()->addDays(110),
            ],
            [
                'program_id' => 'PROG-MSES-NCII-001',
                'name' => 'Motorcycle/Small Engine Servicing NCII',
                'description' => 'This qualification covers the knowledge, skills and attitudes required to service motorcycles and small engines.',
                'duration' => '256 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3200.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(25),
                'end_date' => Carbon::now()->addDays(90),
            ],

            // Construction Programs
            [
                'program_id' => 'PROG-CONPAINT-NCII-001',
                'name' => 'Construction Painting NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to be able to paint residential and commercial buildings.',
                'duration' => '148 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 2800.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addDays(65),
            ],
            [
                'program_id' => 'PROG-MAS-NCII-001',
                'name' => 'Masonry NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to carry out masonry work.',
                'duration' => '320 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3600.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(35),
                'end_date' => Carbon::now()->addDays(125),
            ],
            [
                'program_id' => 'PROG-CAR-NCII-001',
                'name' => 'Carpentry NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to carry out carpentry work in residential and commercial construction.',
                'duration' => '358 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3800.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(40),
                'end_date' => Carbon::now()->addDays(130),
            ],

            // Electronics Programs
            [
                'program_id' => 'PROG-CES-NCII-001',
                'name' => 'Consumer Electronics Servicing NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to be able to service consumer electronics products.',
                'duration' => '358 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3900.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(30),
                'end_date' => Carbon::now()->addDays(120),
            ],
            [
                'program_id' => 'PROG-EPAS-NCII-001',
                'name' => 'Electronics Products Assembly and Servicing NCII',
                'description' => 'This qualification covers the knowledge, skills and attitudes in the assembly and servicing of electronics products.',
                'duration' => '320 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3700.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(25),
                'end_date' => Carbon::now()->addDays(115),
            ],

            // Electrical Programs
            [
                'program_id' => 'PROG-EIM-NCII-001',
                'name' => 'Electrical Installation and Maintenance NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to enable him/her to install and maintain wiring circuits.',
                'duration' => '358 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 4100.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(20),
                'end_date' => Carbon::now()->addDays(110),
            ],

            // Food and Beverage Programs
            [
                'program_id' => 'PROG-BPP-NCII-001',
                'name' => 'Bread and Pastry Production NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to be able to clean equipment, prepare and produce bakery products.',
                'duration' => '320 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3300.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(15),
                'end_date' => Carbon::now()->addDays(100),
            ],
            [
                'program_id' => 'PROG-COOK-NCII-001',
                'name' => 'Cookery NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to be able to prepare, cook and serve food.',
                'duration' => '320 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3400.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(12),
                'end_date' => Carbon::now()->addDays(95),
            ],
            [
                'program_id' => 'PROG-FBS-NCII-001',
                'name' => 'Food and Beverage Services NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to provide food and beverage services to guests.',
                'duration' => '286 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3100.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(18),
                'end_date' => Carbon::now()->addDays(85),
            ],

            // Tourism and Hospitality Programs
            [
                'program_id' => 'PROG-HK-NCII-001',
                'name' => 'Housekeeping NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to clean and maintain guest rooms, public areas and other spaces.',
                'duration' => '148 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 2600.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(22),
                'end_date' => Carbon::now()->addDays(75),
            ],
            [
                'program_id' => 'PROG-FOS-NCII-001',
                'name' => 'Front Office Services NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to provide front office services in hotels and other accommodation facilities.',
                'duration' => '286 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 3000.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(28),
                'end_date' => Carbon::now()->addDays(90),
            ],

            // Beauty and Wellness Programs
            [
                'program_id' => 'PROG-BC-NCII-001',
                'name' => 'Beauty Care (Nail Care) NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to provide nail care services.',
                'duration' => '148 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 2500.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(8),
                'end_date' => Carbon::now()->addDays(60),
            ],
            [
                'program_id' => 'PROG-HD-NCII-001',
                'name' => 'Hairdressing NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to provide hairdressing services.',
                'duration' => '286 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 2900.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(14),
                'end_date' => Carbon::now()->addDays(85),
            ],

            // Agriculture Programs
            [
                'program_id' => 'PROG-OAP-NCII-001',
                'name' => 'Organic Agriculture Production NCII',
                'description' => 'This qualification covers the knowledge, skills and attitudes required to produce crops organically.',
                'duration' => '320 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 2800.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(30),
                'end_date' => Carbon::now()->addDays(120),
            ],

            // Welding and Fabrication Programs
            [
                'program_id' => 'PROG-SMAW-NCII-001',
                'name' => 'Shielded Metal Arc Welding (SMAW) NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to perform shielded metal arc welding.',
                'duration' => '358 hours',
                'prerequisites' => 'High School Graduate or ALS Graduate',
                'enrollment_fee' => 4300.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(35),
                'end_date' => Carbon::now()->addDays(125),
            ],
            [
                'program_id' => 'PROG-GMAW-NCII-001',
                'name' => 'Gas Metal Arc Welding (GMAW) NCII',
                'description' => 'This qualification consists of competencies that a person must achieve to perform gas metal arc welding.',
                'duration' => '358 hours',
                'prerequisites' => 'SMAW NCII or equivalent',
                'enrollment_fee' => 4500.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(40),
                'end_date' => Carbon::now()->addDays(130),
            ],
        ];

        foreach ($programs as $programData) {
            Program::firstOrCreate(
                ['name' => $programData['name']],
                $programData
            );
        }

        $this->command->info('Programs seeded successfully!');
    }
} 