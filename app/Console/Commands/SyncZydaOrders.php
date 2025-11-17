<?php

namespace App\Console\Commands;

use App\Services\ZydaScriptRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncZydaOrders extends Command
{
    protected $signature = 'sync:zyda-orders';

    protected $description = 'Sync orders from Zyda dashboard into the local database';

    public function __construct(protected ZydaScriptRunner $runner)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting Zyda orders sync...');

        try {
            $result = $this->runner->run();
            $summary = $result['summary'] ?? null;

            if ($summary) {
                $this->info(sprintf(
                    'Summary: created=%d updated=%d skipped=%d failed=%d',
                    $summary['created'],
                    $summary['updated'],
                    $summary['skipped'],
                    $summary['failed'],
                ));
            } else {
                $this->info('No summary detected in output.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Zyda scheduled sync failed', [
                'error' => $e->getMessage(),
            ]);

            $this->error('Failed to sync Zyda orders: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}

