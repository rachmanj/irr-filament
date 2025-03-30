<?php

namespace App\Console\Commands;

use App\Imports\ItoImport;
use App\Models\AdditionalDocument;
use App\Models\Import;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class FixItoImportCommand extends Command
{
    protected $signature = 'ito:fix-import {import_id? : The ID of the import to fix}';

    protected $description = 'Check and fix ITO import issues';

    public function handle()
    {
        $this->info('Checking for ITO import issues...');
        
        // Check if additional_documents table exists
        if (!Schema::hasTable('additional_documents')) {
            $this->error('The additional_documents table does not exist!');
            return Command::FAILURE;
        }
        
        // Get table columns
        $columns = Schema::getColumnListing('additional_documents');
        $this->info("additional_documents table columns: " . implode(', ', $columns));
        
        // Count records in the table
        $count = AdditionalDocument::count();
        $this->info("Total records in additional_documents table: $count");
        
        // Get import ID from argument or ask for it
        $importId = $this->argument('import_id');
        if (!$importId) {
            $imports = Import::orderBy('id', 'desc')->get();
            
            if ($imports->isEmpty()) {
                $this->error('No imports found in the database.');
                return Command::FAILURE;
            }
            
            $this->table(
                ['ID', 'File Name', 'Total Rows', 'Status'],
                $imports->map(function ($import) {
                    return [
                        'id' => $import->id,
                        'file_name' => $import->file_name,
                        'total_rows' => $import->total_rows,
                        'status' => $import->status,
                    ];
                })
            );
            
            $importId = $this->ask('Enter import ID to fix:');
        }
        
        // Get the import record
        $import = Import::find($importId);
        if (!$import) {
            $this->error("Import ID $importId not found!");
            return Command::FAILURE;
        }
        
        $this->info("Processing import ID: {$import->id}, File: {$import->file_name}");
        
        // Check if the file exists
        if (!file_exists($import->file_path)) {
            $this->error("File not found: {$import->file_path}");
            return Command::FAILURE;
        }
        
        // Confirm before proceeding
        if (!$this->confirm('Do you want to manually import the file data?', true)) {
            return Command::SUCCESS;
        }
        
        // Manually import the file
        $this->info("Starting manual import of {$import->file_path}...");
        
        try {
            DB::beginTransaction();
            
            // Create a new ItoImport instance
            $importer = new ItoImport(true);
            
            // Load the file and process it
            $collection = Excel::toCollection($importer, $import->file_path);
            
            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            
            // Process each row in the first sheet
            foreach ($collection[0] as $row) {
                try {
                    $data = $row->toArray();
                    
                    // Create document using the importer
                    $document = $importer->model($data);
                    
                    if ($document) {
                        $document->save();
                        $successCount++;
                        $this->info("Imported ITO: {$data['ito_no']}");
                    } else {
                        $failedCount++;
                        $errors[] = "Skipped ITO: " . ($data['ito_no'] ?? 'Unknown') . " (duplicate or empty)";
                        $this->warn("Skipped ITO: " . ($data['ito_no'] ?? 'Unknown') . " (duplicate or empty)");
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Error with row: " . json_encode($row->toArray()) . " - " . $e->getMessage();
                    $this->error("Error: " . $e->getMessage());
                }
            }
            
            // Update the import record
            $import->successful_rows = $successCount;
            $import->failed_rows = $failedCount;
            $import->processed_rows = $successCount + $failedCount;
            $import->status = 'completed';
            if (!empty($errors)) {
                $import->failed_rows_data = $errors;
            }
            $import->save();
            
            DB::commit();
            
            $this->info("Manual import completed. Success: $successCount, Failed: $failedCount");
            
            // Delete the file after successful import
            if (file_exists($import->file_path)) {
                unlink($import->file_path);
                $this->info("Deleted import file: {$import->file_path}");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error during import: " . $e->getMessage());
            
            // Try to delete the file even on error
            if (file_exists($import->file_path)) {
                unlink($import->file_path);
                $this->info("Deleted import file after error: {$import->file_path}");
            }
            
            return Command::FAILURE;
        }
        
        // Count records in the table after import
        $newCount = AdditionalDocument::count();
        $this->info("Total records in additional_documents table after import: $newCount");
        $this->info("Added " . ($newCount - $count) . " new records.");
        
        return Command::SUCCESS;
    }
} 