<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PointsService;

class SyncUserPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'points:sync {--user-id= : Sync specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync user points from external points system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pointsService = new PointsService();
        
        if ($userId = $this->option('user-id')) {
            $this->info("Syncing points for user ID: {$userId}");
            
            if ($pointsService->updateUserPointsLocally($userId)) {
                $this->info("✅ Points synced successfully for user {$userId}");
            } else {
                $this->error("❌ Failed to sync points for user {$userId}");
            }
        } else {
            $this->info("Syncing points for all users...");
            
            $updatedCount = $pointsService->syncAllUsersPoints();
            
            if ($updatedCount > 0) {
                $this->info("✅ Successfully synced points for {$updatedCount} users");
            } else {
                $this->warn("⚠️ No users were updated");
            }
        }
    }
}