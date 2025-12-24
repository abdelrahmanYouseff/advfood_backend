<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteAllFines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fines:delete-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all data from the fines table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Check if table exists
            if (!DB::getSchemaBuilder()->hasTable('fines')) {
                $this->error('The fines table does not exist in the database.');
                return 1;
            }

            // Get count before deletion
            $count = DB::table('fines')->count();
            
            if ($count === 0) {
                $this->info('The fines table is already empty.');
                return 0;
            }

            // Confirm deletion
            if (!$this->confirm("Are you sure you want to delete all {$count} records from the fines table?")) {
                $this->info('Operation cancelled.');
                return 0;
            }

            // Delete all records
            $deleted = DB::table('fines')->delete();

            $this->info("Successfully deleted {$deleted} record(s) from the fines table.");
            return 0;

        } catch (\Exception $e) {
            $this->error('Error deleting fines: ' . $e->getMessage());
            return 1;
        }
    }
}


