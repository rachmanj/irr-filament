<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CreateItoTemplateCommand extends Command
{
    protected $signature = 'ito:create-template';

    protected $description = 'Create ITO import template file';

    public function handle()
    {
        $this->info('Creating ITO import template file...');
        
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set column headers
        $headers = [
            'ito_no', 
            'ito_date', 
            'po_no', 
            'ito_remarks', 
            'ito_created_by', 
            'grpo_no', 
            'origin_whs', 
            'destination_whs'
        ];
        
        $headerLabels = [
            'ITO Number (Required)', 
            'ITO Date (Required, dd/mm/yyyy)', 
            'PO Number', 
            'Remarks', 
            'ITO Creator', 
            'GRPO Number', 
            'Origin Warehouse', 
            'Destination Warehouse'
        ];
        
        // Set headers in row 1
        foreach ($headers as $key => $header) {
            $column = chr(65 + $key); // A, B, C, etc.
            $sheet->setCellValue($column . '1', $header);
        }
        
        // Set descriptive headers in row 2
        foreach ($headerLabels as $key => $label) {
            $column = chr(65 + $key); 
            $sheet->setCellValue($column . '2', $label);
        }
        
        // Add example data in row 3
        $sheet->setCellValue('A3', 'ITO-123456');
        $sheet->setCellValue('B3', '01/01/2023');
        $sheet->setCellValue('C3', 'PO-789012');
        $sheet->setCellValue('D3', 'Example remarks');
        $sheet->setCellValue('E3', 'John Doe');
        $sheet->setCellValue('F3', 'GRPO-456789');
        $sheet->setCellValue('G3', 'WH-Origin');
        $sheet->setCellValue('H3', 'WH-Destination');
        
        // Add formatting
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        
        $descStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        
        $exampleStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ];
        
        // Apply styles to each range
        $lastColumn = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray($headerStyle);
        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray($descStyle);
        $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray($exampleStyle);
        
        // Auto size columns
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Instructions sheet
        $spreadsheet->createSheet();
        $instructionSheet = $spreadsheet->getSheet(1);
        $instructionSheet->setTitle('Instructions');
        
        $instructionSheet->setCellValue('A1', 'ITO Import Instructions');
        $instructionSheet->setCellValue('A3', '1. Do not modify or remove the header row (row 1)');
        $instructionSheet->setCellValue('A4', '2. Enter your data starting from row 4');
        $instructionSheet->setCellValue('A5', '3. Required fields: ITO Number and ITO Date');
        $instructionSheet->setCellValue('A6', '4. Date format should be DD/MM/YYYY (e.g., 01/01/2023)');
        $instructionSheet->setCellValue('A7', '5. Save the file as Excel (.xlsx) format before uploading');
        
        $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $instructionSheet->getStyle('A3:A7')->getFont()->setSize(12);
        
        $instructionSheet->getColumnDimension('A')->setWidth(100);
        
        // Set first sheet as active
        $spreadsheet->setActiveSheetIndex(0);
        
        // Create directory if it doesn't exist
        if (!file_exists(storage_path('app/public/templates'))) {
            mkdir(storage_path('app/public/templates'), 0755, true);
        }
        
        // Save file
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/templates/ito_import_template.xlsx'));
        
        $this->info('Template file created successfully!');
        $this->info('Path: ' . storage_path('app/public/templates/ito_import_template.xlsx'));
        
        return Command::SUCCESS;
    }
} 