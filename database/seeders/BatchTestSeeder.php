<?php
/**
 * LTPC Enrollment Management System (LTPC_EMS)
 *
 * @copyright  2025-2026 Clarence Buenaflor & Jester Pastor
 * @author     Clarence Buenaflor <cbuenaflor2@ssct.edu.ph>
 * @author     Jester Pastor <pastorjester98@mail.com>
 * @license    Proprietary - All Rights Reserved
 *
 * Unauthorized copying, modification, or distribution of this
 * software is strictly prohibited without express written permission.
 */
namespace Database\Seeders;

use App\Models\Trainee;
use App\Models\Program;
use App\Models\TraineeEnrollment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BatchTestSeeder extends Seeder
{
    const TEST_PROGRAM_ID = "PROG-BATCHTEST-001";
    const TEST_PROGRAM_NAME = "Batch Test Program";
    const ULI_PREFIX = "ULI-BTEST-";
    const BATCH_LIMIT = 25;
    const TOTAL_TRAINEES = 50;

    /**
     * Seed 50 trainees into a dedicated test program and verify batch assignment.
     *
     * Each trainee is created with status=active + payment_status=paid +
     * program_qualification set, which fires handleAutoEnrollment() via the
     * Trainee::created boot event. That method calls enrollInProgram() →
     * getNextAvailableBatch(), which is the exact production code path we fixed.
     *
     * Expected result:
     *   Batch 1 → 25 trainees
     *   Batch 2 → 25 trainees
     */
    public function run(): void
    {
        $this->command->info("╔══════════════════════════════════════════╗");
        $this->command->info("║        BATCH ASSIGNMENT TEST SEEDER  ║");
        $this->command->info("╚══════════════════════════════════════════╝");
        $this->command->newLine();

        $this->cleanUp();
        $program = $this->createTestProgram();

        $this->command->info("Program        : {$program->name}");
        $this->command->info("Program ID     : {$program->program_id}");
        $this->command->info(
            "current_batch  : {$program->current_batch}  (starts at 1)",
        );
        $this->command->info(
            "Batch limit    : " . self::BATCH_LIMIT . " trainees per batch",
        );
        $this->command->info(
            "Total to seed  : " . self::TOTAL_TRAINEES . " trainees",
        );
        $this->command->newLine();

        $this->seedTrainees($program);
        $this->report($program);
    }

    // -------------------------------------------------------------------------
    // Steps
    // -------------------------------------------------------------------------

    private function cleanUp(): void
    {
        $this->command->line("→ Removing any previous batch-test data...");

        // Collect test trainee IDs first so we can delete their enrollments
        $testTraineeIds = Trainee::where(
            "uli_number",
            "LIKE",
            self::ULI_PREFIX . "%",
        )->pluck("id");

        if ($testTraineeIds->isNotEmpty()) {
            TraineeEnrollment::whereIn("trainee_id", $testTraineeIds)->delete();
            Trainee::whereIn("id", $testTraineeIds)->delete();
            $this->command->line(
                "  Removed {$testTraineeIds->count()} previous test trainee(s).",
            );
        }

        // Remove the test program (FK cascade will also clean enrollments)
        $deleted = Program::where(
            "program_id",
            self::TEST_PROGRAM_ID,
        )->delete();
        if ($deleted) {
            $this->command->line("  Removed previous test program.");
        }

        $this->command->line("  Clean-up done.");
        $this->command->newLine();
    }

    private function createTestProgram(): Program
    {
        $this->command->line("→ Creating fresh test program...");

        $program = Program::create([
            "program_id" => self::TEST_PROGRAM_ID,
            "name" => self::TEST_PROGRAM_NAME,
            "description" =>
                "Dedicated program used only for batch-assignment testing. Safe to delete.",
            "duration" => "160 hours",
            "prerequisites" => null,
            "enrollment_fee" => 0,
            "status" => "active",
            "max_enrollments" => self::BATCH_LIMIT,
            "current_batch" => 1,
        ]);

        $this->command->line("  Done.");
        $this->command->newLine();

        return $program;
    }

    private function seedTrainees(Program $program): void
    {
        $this->command->line(
            "→ Creating " .
                self::TOTAL_TRAINEES .
                " trainees (auto-enrollment fires on each create)...",
        );
        $this->command->newLine();

        $firstNames = [
            "Juan",
            "Maria",
            "Jose",
            "Ana",
            "Carlos",
            "Rosa",
            "Antonio",
            "Carmen",
            "Manuel",
            "Dolores",
            "Pedro",
            "Teresa",
            "Francisco",
            "Miguel",
            "Concepcion",
            "Ramon",
            "Josefa",
            "Ricardo",
            "Fernando",
            "Catalina",
            "Roberto",
            "Margarita",
            "Eduardo",
            "Isabel",
            "Alfredo",
            "Patricia",
        ];

        $lastNames = [
            "Santos",
            "Reyes",
            "Cruz",
            "Bautista",
            "Ocampo",
            "Garcia",
            "Lopez",
            "Rodriguez",
            "Martinez",
            "Torres",
            "Fernandez",
            "Gonzalez",
            "Sanchez",
            "Perez",
            "Gomez",
            "Diaz",
            "Ramos",
            "Morales",
            "Rivera",
            "Mendoza",
            "Villanueva",
            "Castro",
            "Aquino",
            "De Guzman",
        ];

        $bar = $this->command
            ->getOutput()
            ->createProgressBar(self::TOTAL_TRAINEES);
        $bar->setFormat(
            " %current%/%max% [%bar%] %percent:3s%%  Trainee #%current%",
        );
        $bar->start();

        $errors = [];

        for ($i = 1; $i <= self::TOTAL_TRAINEES; $i++) {
            $birthYear = rand(1990, 2003);

            try {
                /*
                 * Setting status='active' + payment_status='paid' +
                 * program_qualification triggers handleAutoEnrollment() via the
                 * Trainee::created boot event, which calls:
                 *   enrollInProgram($programId, null, null)
                 *     → getNextAvailableBatch()          ← the method we fixed
                 *       → getEnrollmentCountForBatch($batch) for each batch
                 */
                Trainee::create([
                    "uli_number" =>
                        self::ULI_PREFIX . str_pad($i, 4, "0", STR_PAD_LEFT),
                    "entry_date" => Carbon::now(),
                    "last_name" => $lastNames[array_rand($lastNames)],
                    "first_name" => $firstNames[array_rand($firstNames)],
                    "middle_name" => null,
                    "street_number" => $i . " Batch Test St.",
                    "barangay" => "Barangay 1",
                    "district" => "District 1",
                    "city_municipality" => "Test City",
                    "province" => "Metro Manila",
                    "region" => "NCR",
                    "email_facebook" => "btest" . $i . "@testmail.com",
                    "contact_number" =>
                        "09" . str_pad($i, 9, "0", STR_PAD_LEFT),
                    "nationality" => "Filipino",
                    "sex" => $i % 2 === 0 ? "female" : "male",
                    "civil_status" => "single",
                    "employment_status" => "unemployed",
                    "employment_type" => "none",
                    "birth_month" => "01",
                    "birth_day" => rand(1, 28),
                    "birth_year" => $birthYear,
                    "age" => Carbon::now()->year - $birthYear,
                    "birthplace_city" => "Test City",
                    "birthplace_province" => "Metro Manila",
                    "birthplace_region" => "NCR",
                    "education" => json_encode([
                        "highest_level" => "High School",
                    ]),
                    "parent_guardian_name" => "Test Guardian " . $i,
                    "parent_guardian_address" =>
                        $i . " Guardian St., Test City",
                    "classification" => json_encode(["youth"]),
                    // These three fields are what trigger auto-enrollment:
                    "program_qualification" => $program->name,
                    "status" => "active",
                    "payment_status" => "paid",
                    // Payment details (trainees.batch is the legacy field; leave at DB default=1)
                    "scholarship_package" => null,
                    "requirements" => json_encode([
                        "birth_certificate" => true,
                    ]),
                    "payment_method" => "cash",
                    "payment_reference" =>
                        "BTEST-" . str_pad($i, 4, "0", STR_PAD_LEFT),
                    "payment_date" => Carbon::now(),
                ]);
            } catch (\Exception $e) {
                $errors[] = "Trainee #{$i}: " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        if (!empty($errors)) {
            $this->command->warn(
                "  The following errors occurred during seeding:",
            );
            foreach ($errors as $err) {
                $this->command->warn("  - {$err}");
            }
            $this->command->newLine();
        }
    }

    private function report(Program $program): void
    {
        $program->refresh();

        $batchRows = TraineeEnrollment::where(
            "program_id",
            $program->program_id,
        )
            ->where("status", "active")
            ->selectRaw("batch, COUNT(*) as count")
            ->groupBy("batch")
            ->orderBy("batch")
            ->get();

        $totalEnrolled = $batchRows->sum("count");
        $allUnderLimit = $batchRows->every(
            fn($r) => $r->count <= self::BATCH_LIMIT,
        );
        $passed = $allUnderLimit && $totalEnrolled === self::TOTAL_TRAINEES;

        $this->command->info("╔══════════════════════════════════════════╗");
        $this->command->info("║              RESULTS SUMMARY             ║");
        $this->command->info("╚══════════════════════════════════════════╝");
        $this->command->newLine();

        if ($batchRows->isEmpty()) {
            $this->command->error("  No enrollment records found.");
            $this->command->error(
                "  Auto-enrollment likely failed — check storage/logs/laravel.log.",
            );
            return;
        }

        // Per-batch breakdown with a simple bar
        foreach ($batchRows as $row) {
            $filled = min((int) $row->count, self::BATCH_LIMIT);
            $bar =
                str_repeat("█", $filled) .
                str_repeat("░", self::BATCH_LIMIT - $filled);
            $overTag =
                $row->count > self::BATCH_LIMIT
                    ? "  ← ✗ OVER LIMIT (+" .
                        ($row->count - self::BATCH_LIMIT) .
                        ")"
                    : "";

            $this->command->line(
                sprintf(
                    "  Batch %2d  |%s|  %2d / %d%s",
                    $row->batch,
                    $bar,
                    $row->count,
                    self::BATCH_LIMIT,
                    $overTag,
                ),
            );
        }

        $this->command->newLine();
        $this->command->line(
            sprintf(
                "  Total enrolled   : %d / %d",
                $totalEnrolled,
                self::TOTAL_TRAINEES,
            ),
        );
        $this->command->line(
            sprintf("  Batches used     : %d", $batchRows->count()),
        );
        $this->command->line(
            sprintf("  current_batch    : %d", $program->current_batch),
        );
        $this->command->newLine();

        if ($totalEnrolled < self::TOTAL_TRAINEES) {
            $missing = self::TOTAL_TRAINEES - $totalEnrolled;
            $this->command->warn(
                "  ⚠  {$missing} trainee(s) were NOT enrolled — check laravel.log for details.",
            );
            $this->command->newLine();
        }

        if ($passed) {
            $this->command->info(
                "  ✓  PASS — Batch assignment is working correctly.",
            );
            $this->command->info(
                "           No batch exceeds the " .
                    self::BATCH_LIMIT .
                    "-trainee limit.",
            );
        } else {
            $this->command->error(
                "  ✗  FAIL — Batch assignment did not behave as expected.",
            );
        }

        $this->command->newLine();
        $this->command->line(
            '  Tip: Run "php artisan db:seed --class=BatchTestSeeder" again at any',
        );
        $this->command->line(
            "       time — it always cleans up previous test data before re-seeding.",
        );
        $this->command->newLine();
    }
}
