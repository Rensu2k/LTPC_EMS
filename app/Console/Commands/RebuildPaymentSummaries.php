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
namespace App\Console\Commands;

use App\Models\PaymentSummary;
use Illuminate\Console\Command;

/**
 * Artisan command to rebuild the payment_summaries cache table from scratch.
 *
 * Usage:
 *   php artisan app:rebuild-payment-summaries
 *
 * Run this after:
 *   - Initial migration (to populate from existing data)
 *   - Bulk data imports/deletions (stress test cleanup, etc.)
 *   - If cache drift is suspected
 */
class RebuildPaymentSummaries extends Command
{
    protected $signature = 'app:rebuild-payment-summaries';
    protected $description = 'Rebuild the payment_summaries cache table from source data';

    public function handle(): int
    {
        $this->info('Rebuilding payment summaries from source tables...');
        $this->newLine();

        $startTime = microtime(true);
        $results = PaymentSummary::rebuild();
        $elapsed = round((microtime(true) - $startTime) * 1000, 1);

        // Display results in a table
        $rows = [];
        foreach ($results as $key => $value) {
            $rows[] = [$key, number_format($value, 2)];
        }

        $this->table(['Metric', 'Value'], $rows);
        $this->newLine();
        $this->info("Rebuild completed in {$elapsed}ms.");

        return Command::SUCCESS;
    }
}
