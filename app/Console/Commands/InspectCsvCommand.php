<?php

namespace App\Console\Commands;

use App\Models\Import;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InspectCsvCommand extends Command
{
    protected $signature = 'csv:inspect {import_id : The ID of the import to inspect}';

    protected $description = 'Inspect the contents of a CSV file';

    public function handle()
    {
        $importId = $this->argument('import_id');
        
        // Find the import
        $import = Import::find($importId);
        if (!$import) {
            $this->error("Import ID $importId not found!");
            return Command::FAILURE;
        }
        
        $this->info("Inspecting file: {$import->file_name} (Path: {$import->file_path})");
        
        // Check if file exists
        if (!file_exists($import->file_path)) {
            $this->error("File not found: {$import->file_path}");
            return Command::FAILURE;
        }
        
        // Read file contents
        $this->info("Reading file contents...");
        
        // Open the file
        $handle = fopen($import->file_path, 'r');
        if (!$handle) {
            $this->error("Failed to open file: {$import->file_path}");
            return Command::FAILURE;
        }
        
        // Read and output the first few lines
        $lineNumber = 0;
        $headers = [];
        $maxLinesToRead = 5; // Adjust as needed
        
        $this->info("\nFile content (first $maxLinesToRead lines):");
        
        while (($data = fgetcsv($handle)) !== false && $lineNumber < $maxLinesToRead) {
            $lineNumber++;
            
            // Format the data for display
            $formattedData = [];
            foreach ($data as $index => $value) {
                $formattedData[] = "Col $index: " . (empty($value) ? "[EMPTY]" : $value);
            }
            
            $this->info("Line $lineNumber: " . implode(" | ", $formattedData));
            
            // Save headers
            if ($lineNumber === 1) {
                $headers = $data;
                $this->info("\nHeaders detected: " . implode(", ", $headers));
                
                // Check for expected headers
                $expectedHeaders = ['ito_no', 'ito_date', 'po_no', 'ito_remarks', 'ito_created_by', 'grpo_no', 'origin_whs', 'destination_whs'];
                $missingHeaders = array_diff($expectedHeaders, $headers);
                
                if (!empty($missingHeaders)) {
                    $this->warn("Missing expected headers: " . implode(", ", $missingHeaders));
                }
            }
        }
        
        // Count total lines
        fseek($handle, 0);
        $totalLines = 0;
        while (fgetcsv($handle) !== false) {
            $totalLines++;
        }
        
        $this->info("\nTotal lines in file: $totalLines");
        
        fclose($handle);
        
        return Command::SUCCESS;
    }
} 