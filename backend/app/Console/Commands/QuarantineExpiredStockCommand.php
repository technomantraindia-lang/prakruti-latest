<?php

namespace App\Console\Commands;

use App\Models\ProductBatch;
use Illuminate\Console\Command;

class QuarantineExpiredStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'prakruti:quarantine-expired-stock';

    /**
     * The console command description.
     */
    protected $description = 'Automatically quarantine batches within 60 days of expiry and mark expired batches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting batch expiry and quarantine audit...');

        // 1. Mark past-expiry batches as expired
        $expiredCount = ProductBatch::where('status', '!=', 'expired')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString())
            ->update(['status' => 'expired']);

        $this->info("Expired batches marked: {$expiredCount}");

        // 2. Mark batches expiring within 60 days as near_expiry / quarantined
        $nearExpiryCount = ProductBatch::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereDate('expiry_date', '<=', now()->addDays(60)->toDateString())
            ->update(['status' => 'near_expiry']);

        $this->info("Near-expiry batches (<= 60 days) quarantined from eCommerce: {$nearExpiryCount}");

        $this->info('Batch quarantine audit completed successfully.');

        return Command::SUCCESS;
    }
}
