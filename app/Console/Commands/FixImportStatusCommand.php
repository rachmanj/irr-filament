<?php

namespace App\Console\Commands;

use App\Models\Import;
use Illuminate\Console\Command;

class FixImportStatusCommand extends Command
{
    protected $signature = 'imports:fix-status';

    protected $description = 'Fix stuck import statuses';

    public function handle()
    {
        $this->info('Checking for stuck imports...');
        
        // Find imports in pending status
        $pendingImports = Import::where('status', 'pending')->get();
        
        if ($pendingImports->isEmpty()) {
            $this->info('No pending imports found.');
        } else {
            $this->info('Found ' . $pendingImports->count() . ' pending imports.');
            
            foreach ($pendingImports as $import) {
                $this->info("Processing import ID: {$import->id}, File: {$import->file_name}");
                
                // Update the status to completed
                $import->status = 'completed';
                $import->save();
                
                $this->info("Updated import ID: {$import->id} status to completed.");
            }
        }
        
        // Print summary of all imports
        $this->info("\nCurrent imports summary:");
        $imports = Import::all();
        
        $this->table(
            ['ID', 'File Name', 'Total Rows', 'Successful', 'Failed', 'Status'],
            $imports->map(function ($import) {
                return [
                    'id' => $import->id,
                    'file_name' => $import->file_name,
                    'total_rows' => $import->total_rows,
                    'successful' => $import->successful_rows,
                    'failed' => $import->failed_rows,
                    'status' => $import->status,
                ];
            })
        );
        
        return Command::SUCCESS;
    }
} 