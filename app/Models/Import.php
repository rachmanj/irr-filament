<?php

namespace App\Models;

use Filament\Actions\Imports\Models\Import as BaseImport;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends BaseImport
{
    protected $table = 'imports';
    
    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'importer',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'failed_rows_data',
        'status',
    ];

    protected $casts = [
        'failed_rows_data' => 'array',
    ];
    
    protected $attributes = [
        'status' => 'pending',
        'processed_rows' => 0,
        'successful_rows' => 0,
        'failed_rows' => 0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFailedRowsCount(): int
    {
        return $this->failed_rows;
    }

    public function incrementFailedRows(): void
    {
        $this->failed_rows++;
        $this->save();
    }

    public static function createFromFile($file, $userId = null)
    {
        $fileName = $file->getClientOriginalName();
        $filePath = $file->store('imports');
        $fullPath = \Storage::path($filePath);
        
        $import = new self();
        $import->user_id = $userId ?? (auth()->id() ?? 1);
        $import->file_name = $fileName;
        $import->file_path = $fullPath;
        $import->importer = \App\Imports\ItoImport::class;
        $import->total_rows = 0;
        $import->status = 'pending';
        $import->save();
        
        return $import;
    }
} 