<?php

namespace App\Imports;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ItoImport implements ToModel, WithHeadingRow
{
    public $itoTypeId;
    public $batchNo;
    protected $checkDuplicates;
    protected $successCount = 0;
    protected $skippedCount = 0;

    public function __construct($checkDuplicates = false)
    {
        $this->itoTypeId = $this->getItoTypeId();
        $this->batchNo = $this->getBatchNo();
        $this->checkDuplicates = $checkDuplicates;
    }

    public function model(array $row)
    {
        // Map column names from the CSV to expected field names
        $mappedRow = $this->mapColumns($row);
        
        if (empty($mappedRow['ito_no'])) {
            $this->skippedCount++;
            return null;
        }

        if ($this->checkDuplicates) {
            // Check for existing document with same document_number
            $exists = AdditionalDocument::whereHas('type', function ($query) {
                $query->where('type_name', 'ITO');
            })->where('document_number', $mappedRow['ito_no'])
                ->exists();

            if ($exists) {
                $this->skippedCount++;
                return null; // Skip this record
            }
        }

        $this->successCount++;
        
        // Create a new document
        $document = new AdditionalDocument([
            'type_id' => $this->itoTypeId,
            'document_number' => $mappedRow['ito_no'],
            'document_date' => $this->convert_date($mappedRow['ito_date'] ?? null),
            'po_no' => $mappedRow['po_no'] ?? null,
            'created_by' => auth()->id() ?? 1, // Default to ID 1 if not authenticated
            'remarks' => $mappedRow['ito_remarks'] ?? null,
            'ito_creator' => $mappedRow['ito_created_by'] ?? null,
            'grpo_no' => $mappedRow['grpo_no'] ?? null,
            'origin_wh' => $mappedRow['origin_whs'] ?? null,
            'destination_wh' => $mappedRow['destination_whs'] ?? null,
            'cur_loc' => '000H-LOG',
            'batch_no' => $this->batchNo,
            'status' => 'open',
        ]);
        
        // When called from ItoImporter::importRow, we shouldn't save here
        // as the importer will save the model
        // But when called directly, save immediately
        if (!$this->checkDuplicates) {
            $document->save();
        }
        
        return $document;
    }

    private function convert_date($date)
    {
        if (empty($date)) {
            return now()->format('Y-m-d');
        }
        
        try {
            if (is_string($date)) {
                // Handle formats with dots like "31.12.2023"
                if (strpos($date, '.') !== false) {
                    $parts = explode('.', $date);
                    if (count($parts) === 3) {
                        $day = $parts[0];
                        $month = $parts[1];
                        $year = $parts[2];
                        return $year . '-' . $month . '-' . $day;
                    }
                }
                
                // Handle formats with slashes like "31/12/2023"
                if (strpos($date, '/') !== false) {
                    $parts = explode('/', $date);
                    if (count($parts) === 3) {
                        $day = $parts[0];
                        $month = $parts[1];
                        $year = $parts[2];
                        return $year . '-' . $month . '-' . $day;
                    }
                }
                
                // Handle formats with hyphens like "31-12-2023"
                if (strpos($date, '-') !== false) {
                    $parts = explode('-', $date);
                    if (count($parts) === 3) {
                        // Check if it's already in Y-m-d format
                        if (strlen($parts[0]) === 4) {
                            // Likely already in Y-m-d format
                            return $date;
                        } else {
                            $day = $parts[0];
                            $month = $parts[1];
                            $year = $parts[2];
                            return $year . '-' . $month . '-' . $day;
                        }
                    }
                }
                
                // Try to parse with Carbon for other formats
                try {
                    return Carbon::parse($date)->format('Y-m-d');
                } catch (\Exception $e) {
                    // If Carbon can't parse it, log and return today's date
                    \Illuminate\Support\Facades\Log::warning("Could not parse date: $date. Error: " . $e->getMessage());
                    return now()->format('Y-m-d');
                }
            } elseif ($date instanceof \DateTime) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // If there's an error, log and return today's date
            \Illuminate\Support\Facades\Log::warning("Error converting date: $date. Error: " . $e->getMessage());
            return now()->format('Y-m-d');
        }
        
        return now()->format('Y-m-d');
    }

    private function getItoTypeId()
    {
        $ito_type = AdditionalDocumentType::firstOrCreate(
            ['type_name' => 'ITO']
        );

        if (!$ito_type) {
            throw new \Exception('Failed to get or create ITO document type');
        }

        return $ito_type->id;
    }

    private function getBatchNo()
    {
        $batch_no = AdditionalDocument::max('batch_no');
        return ($batch_no ?? 0) + 1;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    /**
     * Map the input columns to expected column names
     */
    private function mapColumns(array $row): array
    {
        $mapping = [
            // Original expected column => Possible alternative column names
            'ito_no' => ['ito_no', 'document_number', 'ito number', 'ito_number', 'ito-no', 'ito-number'],
            'ito_date' => ['ito_date', 'document_date', 'ito date', 'date', 'doc_date'],
            'po_no' => ['po_no', 'po number', 'po-no', 'po-number', 'po_number'],
            'ito_remarks' => ['ito_remarks', 'remarks', 'comment', 'ito remarks', 'notes', 'ito_remarks'],
            'ito_created_by' => ['ito_created_by', 'created_by', 'creator', 'ito creator', 'ito_created_by', 'ito_creator'],
            'grpo_no' => ['grpo_no', 'grpo number', 'grpo-no', 'grpo-number', 'grpo_number'],
            'origin_whs' => ['origin_whs', 'origin_wh', 'origin warehouse', 'origin', 'source', 'from_whs', 'from_warehouse'],
            'destination_whs' => ['destination_whs', 'destination_wh', 'destination warehouse', 'destination', 'target', 'to_whs', 'to_warehouse'],
        ];
        
        $mapped = [];
        
        // Normalize the row keys to lowercase and remove spaces
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(str_replace([' ', '-', '_'], ['', '', ''], $key));
            $normalizedRow[$normalizedKey] = $value;
            $normalizedRow[$key] = $value; // Keep original key too
        }
        
        foreach ($mapping as $targetColumn => $possibleColumns) {
            $mapped[$targetColumn] = null;
            
            // Try each possible column name
            foreach ($possibleColumns as $column) {
                // Try original column name
                if (isset($row[$column]) && !empty($row[$column])) {
                    $mapped[$targetColumn] = $row[$column];
                    break;
                }
                
                // Try normalized column name
                $normalizedColumn = strtolower(str_replace([' ', '-', '_'], ['', '', ''], $column));
                if (isset($normalizedRow[$normalizedColumn]) && !empty($normalizedRow[$normalizedColumn])) {
                    $mapped[$targetColumn] = $normalizedRow[$normalizedColumn];
                    break;
                }
            }
        }
        
        // Special case for empty document_number but documentnumber exists
        if (empty($mapped['ito_no']) && !empty($row['document_number'])) {
            $mapped['ito_no'] = $row['document_number'];
        }
        
        return $mapped;
    }
}
