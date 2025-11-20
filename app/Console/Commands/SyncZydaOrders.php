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
        $startTime = microtime(true);
        
        Log::info('🚀 Zyda sync command started', [
            'timestamp' => now()->toDateTimeString(),
            'pid' => getmypid(),
        ]);
        
        $this->info('Starting Zyda orders sync...');

        try {
            $result = $this->runner->run();
            $summary = $result['summary'] ?? null;

            $duration = round(microtime(true) - $startTime, 2);
            
            if ($summary) {
                $message = sprintf(
                    'Summary: created=%d updated=%d skipped=%d failed=%d',
                    $summary['created'],
                    $summary['updated'],
                    $summary['skipped'],
                    $summary['failed'],
                );
                
                Log::info('✅ Zyda sync command completed successfully', [
                    'summary' => $summary,
                    'duration_seconds' => $duration,
                ]);
                
                $this->info($message);
            } else {
                Log::warning('⚠️ Zyda sync completed but no summary detected', [
                    'duration_seconds' => $duration,
                    'output_preview' => substr($result['output'] ?? '', 0, 500),
                ]);
                
                $this->info('No summary detected in output.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            Log::error('❌ Zyda scheduled sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_seconds' => $duration,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->error('Failed to sync Zyda orders: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}

