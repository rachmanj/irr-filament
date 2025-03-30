<?php

namespace App\Console\Commands;

use App\Filament\Imports\ItoImporter;
use App\Models\AdditionalDocument;
use App\Models\Import;
use Filament\Actions\Imports\ImportColumn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnoseImportCommand extends Command
{
    protected $signature = 'import:diagnose';
    protected $description = 'Diagnose issues with the import functionality';

    public function handle()
    {
        $this->info('Starting import diagnosis...');
        
        // Get all imports
        $imports = Import::orderBy('created_at', 'desc')->get();
        $this->info('Total imports: ' . $imports->count());
        
        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->info("Database connection successfully established: " . DB::connection()->getDatabaseName());
        } catch (\Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
        }
        
        // Check AdditionalDocument table
        $adCount = AdditionalDocument::count();
        $this->info("AdditionalDocument records: $adCount");
        
        // Show import details
        $this->info("\nRecent imports:");
        $this->table(
            ['ID', 'File Name', 'File Path', 'Importer', 'Total', 'Processed', 'Success', 'Failed', 'Status', 'Created'],
            $imports->take(5)->map(function($import) {
                return [
                    'id' => $import->id,
                    'file_name' => $import->file_name,
                    'file_path' => $import->file_path,
                    'importer' => $import->importer,
                    'total' => $import->total_rows,
                    'processed' => $import->processed_rows,
                    'success' => $import->successful_rows,
                    'failed' => $import->failed_rows,
                    'status' => $import->status,
                    'created' => $import->created_at->format('Y-m-d H:i:s'),
                ];
            })
        );
        
        // Check ItoImporter existence
        if (class_exists(ItoImporter::class)) {
            $this->info("ItoImporter class found");
            
            // Get columns
            $columns = ItoImporter::getColumns();
            $this->info("ItoImporter has " . count($columns) . " columns defined");
            
            if (count($columns) > 0) {
                $columnInfo = [];
                foreach ($columns as $column) {
                    if ($column instanceof ImportColumn) {
                        $columnInfo[] = [
                            'name' => $column->getName(),
                            'label' => $column->getLabel(),
                            'required' => method_exists($column, 'isRequired') ? ($column->isRequired() ? 'Yes' : 'No') : 'Unknown',
                        ];
                    }
                }
                
                $this->table(
                    ['Name', 'Label', 'Required'],
                    $columnInfo
                );
            }
        } else {
            $this->error("ItoImporter class not found");
        }
        
        // Log message for verification
        $this->info("\nAdding diagnostic message to Laravel log...");
        Log::info('Import diagnostics run at ' . now());
        
        return Command::SUCCESS;
    }
} 