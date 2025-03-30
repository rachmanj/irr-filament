<?php

namespace App\Console\Commands;

use App\Models\AdditionalDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmptyTableCommand extends Command
{
    protected $signature = 'table:empty {table? : The name of the table to empty}';

    protected $description = 'Empty a database table';

    public function handle()
    {
        $table = $this->argument('table') ?? 'additional_documents';
        
        if (!$this->confirm("Are you sure you want to empty the '$table' table? This cannot be undone.", false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
        
        try {
            switch ($table) {
                case 'additional_documents':
                    // Get current count
                    $count = AdditionalDocument::count();
                    $this->info("Current records in additional_documents: $count");
                    
                    // Delete records
                    AdditionalDocument::truncate();
                    
                    // Verify deletion
                    $newCount = AdditionalDocument::count();
                    $this->info("Records after truncate: $newCount");
                    $this->info("Deleted $count records from additional_documents table.");
                    break;
                    
                default:
                    // Generic approach using DB facade
                    DB::table($table)->truncate();
                    $this->info("Table '$table' has been emptied.");
                    break;
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error emptying table: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 