<?php

namespace App\Console\Commands;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Import;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class FixImportIssuesCommand extends Command
{
    protected $signature = 'import:fix-issues';

    protected $description = 'Fix any issues with the import process and data';

    public function handle()
    {
        $this->info('Starting to fix import issues...');
        
        // Step 1: Check tables
        $this->checkAndFixTables();
        
        // Step 2: Create ITO type if it doesn't exist
        $this->createItoType();
        
        // Step 3: Check for additional documents count
        $this->checkAdditionalDocuments();
        
        // Step 4: Fix import statuses
        $this->fixImportStatuses();
        
        // Step 5: Import any pending files
        $this->importPendingFiles();
        
        $this->info('Import issues fixed successfully!');
        
        return Command::SUCCESS;
    }
    
    private function checkAndFixTables()
    {
        $this->info('Checking database tables...');
        
        // Check additional_document_types table
        if (!Schema::hasTable('additional_document_types')) {
            $this->error('Table additional_document_types does not exist!');
            
            // Check if the typo version exists
            if (Schema::hasTable('additional_doument_types')) {
                $this->warn('Found table with typo: additional_doument_types');
                
                // Rename the table
                try {
                    DB::statement('RENAME TABLE additional_doument_types TO additional_document_types');
                    $this->info('Renamed table additional_doument_types to additional_document_types');
                } catch (\Exception $e) {
                    $this->error('Failed to rename table: ' . $e->getMessage());
                }
            } else {
                $this->warn('Creating additional_document_types table');
                
                // Create the table
                Schema::create('additional_document_types', function ($table) {
                    $table->id();
                    $table->string('type_name');
                    $table->timestamps();
                });
                
                $this->info('Created additional_document_types table');
            }
        } else {
            $this->info('additional_document_types table exists');
        }
        
        // Check additional_documents table
        if (!Schema::hasTable('additional_documents')) {
            $this->error('Table additional_documents does not exist!');
        } else {
            $this->info('additional_documents table exists');
        }
    }
    
    private function createItoType()
    {
        $this->info('Checking for ITO document type...');
        
        // Get or create ITO type
        $itoType = AdditionalDocumentType::firstOrCreate([
            'type_name' => 'ITO'
        ]);
        
        $this->info('ITO document type ID: ' . $itoType->id);
    }
    
    private function checkAdditionalDocuments()
    {
        $count = AdditionalDocument::count();
        $this->info("Found $count additional documents in the database");
        
        if ($count === 0) {
            $this->warn('No additional documents found. This may indicate an issue with import.');
        }
    }
    
    private function fixImportStatuses()
    {
        $this->info('Fixing import statuses...');
        
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
    }
    
    private function importPendingFiles()
    {
        $this->info('Checking for imports with no corresponding documents...');
        
        $imports = Import::where('successful_rows', 0)
            ->where('status', 'completed')
            ->get();
        
        if ($imports->isEmpty()) {
            $this->info('No imports with zero successful rows found.');
            return;
        }
        
        $this->info('Found ' . $imports->count() . ' imports with zero successful rows.');
        
        foreach ($imports as $import) {
            $this->info("Import ID: {$import->id}, File: {$import->file_name}");
            
            if (!$this->confirm("Do you want to attempt to manually import file for Import ID: {$import->id}?")) {
                continue;
            }
            
            // Check if file exists
            if (!file_exists($import->file_path)) {
                $this->error("File not found: {$import->file_path}");
                continue;
            }
            
            $this->info("File exists. To manually import this file, run:");
            $this->line("php artisan ito:fix-import {$import->id}");
        }
    }
} 