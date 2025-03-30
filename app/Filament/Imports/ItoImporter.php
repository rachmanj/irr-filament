<?php

namespace App\Filament\Imports;

use App\Imports\ItoImport;
use App\Models\AdditionalDocument;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItoImporter extends Importer
{
    protected static ?string $model = AdditionalDocument::class;

    // Directly save the model when generated
    public static bool $shouldSaveModelAutomatically = true;
    
    // Add job queuing - we'll process immediately instead
    public static bool $shouldQueueImports = false;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('ito_no')
                ->label('ITO Number')
                ->requiredMapping()
                ->rules(['required', 'string']),
            
            ImportColumn::make('ito_date')
                ->label('ITO Date')
                ->requiredMapping()
                ->rules(['required', 'date_format:d/m/Y']),
            
            ImportColumn::make('po_no')
                ->label('PO Number')
                ->rules(['nullable', 'string']),
            
            ImportColumn::make('ito_remarks')
                ->label('Remarks')
                ->rules(['nullable', 'string']),
            
            ImportColumn::make('ito_created_by')
                ->label('ITO Creator')
                ->rules(['nullable', 'string']),
            
            ImportColumn::make('grpo_no')
                ->label('GRPO Number')
                ->rules(['nullable', 'string']),
            
            ImportColumn::make('origin_whs')
                ->label('Origin Warehouse')
                ->rules(['nullable', 'string']),
            
            ImportColumn::make('destination_whs')
                ->label('Destination Warehouse')
                ->rules(['nullable', 'string']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your ITO import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';
        
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }
        
        return $body;
    }

    public function resolveRecord(): ?AdditionalDocument
    {
        // This will be a new record each time
        return null;
    }

    public static function getLabel(): string
    {
        return 'ITO Import';
    }

    public static function getModelLabel(): string
    {
        return 'ITO Document';
    }

    public function importRow(array $row, Import $import): AdditionalDocument
    {
        try {
            // Force update status to processing
            $import->status = 'processing';
            $import->save();
    
            // Use the ItoImport logic
            $itoImport = new ItoImport(true); // Pass true to check for duplicates
            $document = $itoImport->model($row);
            
            // If document is null, it means it was a duplicate or empty row
            if ($document === null) {
                // Try to get the ITO number from either expected column name
                $itoNo = $row['ito_no'] ?? $row['document_number'] ?? 'Unknown';
                $errorMessage = 'Row skipped: ' . (!empty($itoNo) && $itoNo !== 'Unknown' ? 'Duplicate ITO document: ' . $itoNo : 'Empty ITO number');
                
                // Add to failed rows data
                $failedData = $import->failed_rows_data ?? [];
                $failedData[] = [
                    'row' => $row,
                    'error' => $errorMessage
                ];
                
                $import->failed_rows_data = $failedData;
                $import->failed_rows++;
                $import->processed_rows++;
                $import->save();
                
                throw new \Exception($errorMessage);
            }
            
            // Force save the document - this is critical!
            DB::beginTransaction();
            try {
                $document->save();
                DB::commit();
                Log::info("ITO Import: Successfully imported ITO: " . $document->document_number);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error saving document: " . $e->getMessage());
                throw $e;
            }
            
            // Update import record
            $import->successful_rows++;
            $import->processed_rows++;
            
            // Mark as completed if this is the last row
            if ($import->processed_rows >= $import->total_rows) {
                $import->status = 'completed';
            }
            
            $import->save();
            
            return $document;
        } catch (\Exception $e) {
            // Log error
            Log::error("ITO Import Error: " . $e->getMessage());
            
            // Update failed count
            $import->failed_rows++;
            $import->processed_rows++;
            $import->save();
            
            throw $e;
        }
    }
    
    public function afterImport(): void
    {
        // Get the current import
        $import = $this->getImport();
        
        // Force update status to completed
        $import->status = 'completed';
        $import->save();
        
        Log::info("Import ID {$import->id} completed with {$import->successful_rows} successful and {$import->failed_rows} failed rows.");
        
        // Delete the original file
        if (!empty($import->file_path) && file_exists($import->file_path)) {
            try {
                unlink($import->file_path);
                Log::info("Deleted import file: {$import->file_path}");
            } catch (\Exception $e) {
                Log::error("Failed to delete import file: {$import->file_path} - {$e->getMessage()}");
            }
        }
    }

    // Process all rows immediately
    public static function getChunkSize(): int
    {
        return 50; // Process in chunks of 50 rows at a time
    }
    
    // Don't queue import chunks
    public static function shouldQueue(): bool
    {
        return false;
    }
    
    // Don't use background jobs for importing
    public static function usingQueue(): bool 
    {
        return false;
    }
} 