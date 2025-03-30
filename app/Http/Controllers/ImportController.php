<?php

namespace App\Http\Controllers;

use App\Imports\ItoImport;
use App\Models\AdditionalDocument;
use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function importForm()
    {
        return view('import.form');
    }
    
    public function import(Request $request)
    {
        // Validate request
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);
        
        // Get file
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        
        // Store file temporarily
        $filePath = $file->store('imports');
        $fullPath = Storage::path($filePath);
        
        try {
            DB::beginTransaction();
            
            // Create import record
            $import = new Import();
            $import->user_id = auth()->id() ?? 1;
            $import->file_name = $fileName;
            $import->file_path = $fullPath;
            $import->importer = ItoImport::class;
            $import->total_rows = 0; // Will update after import
            $import->status = 'processing';
            $import->save();
            
            // Create ITO importer with custom batch
            $importer = new ItoImport(true);
            
            // Import the data
            Log::info("Starting direct import of file: " . $fileName);
            $collection = Excel::toCollection($importer, $fullPath);
            
            $totalRows = count($collection[0] ?? []);
            $import->total_rows = $totalRows;
            
            $successCount = 0;
            $failedCount = 0;
            $failedRows = [];
            
            // Process each row
            foreach ($collection[0] as $row) {
                try {
                    $data = $row->toArray();
                    
                    // Check if row has data
                    if (empty($data)) {
                        continue;
                    }
                    
                    // Create document
                    $document = $importer->model($data);
                    
                    if ($document) {
                        $document->save();
                        $successCount++;
                    } else {
                        $failedCount++;
                        $failedRows[] = $data;
                    }
                } catch (\Exception $e) {
                    Log::error("Error importing row: " . json_encode($data ?? []) . " - " . $e->getMessage());
                    $failedCount++;
                    $failedRows[] = [
                        'data' => $data ?? [],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Update import record
            $import->processed_rows = $successCount + $failedCount;
            $import->successful_rows = $successCount;
            $import->failed_rows = $failedCount;
            $import->failed_rows_data = $failedRows;
            $import->status = 'completed';
            $import->save();
            
            DB::commit();
            
            // Delete the imported file after successful processing
            if (file_exists($fullPath)) {
                unlink($fullPath);
                Log::info("Deleted imported file: " . $fullPath);
            }
            
            // Return response
            return response()->json([
                'success' => true,
                'message' => "Import completed. $successCount records imported successfully, $failedCount records failed. File deleted from server.",
                'import_id' => $import->id,
                'redirect' => route('filament.admin.resources.imports.index')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error importing file: " . $e->getMessage());
            
            // Update import record to failed
            if (isset($import)) {
                $import->status = 'failed';
                $import->save();
            }
            
            // Still try to delete the file even if import failed
            if (file_exists($fullPath)) {
                unlink($fullPath);
                Log::info("Deleted imported file after failure: " . $fullPath);
            }
            
            return response()->json([
                'success' => false,
                'message' => "Import failed: " . $e->getMessage()
            ], 500);
        }
    }
    
    public function downloadTemplate()
    {
        $templatePath = storage_path('app/public/templates/ito_import_template.xlsx');
        
        if (!file_exists($templatePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Template file not found.'
            ], 404);
        }
        
        return response()->download($templatePath);
    }
} 