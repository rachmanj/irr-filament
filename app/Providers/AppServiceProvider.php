<?php

namespace App\Providers;

use App\Models\Import;
use App\Models\ImportRow;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Filament import models
        if (class_exists('Filament\Actions\Imports\ImportAction')) {
            \Filament\Actions\Imports\ImportAction::defaultImportModel(Import::class);
            \Filament\Actions\Imports\ImportAction::defaultImportRowModel(ImportRow::class);
        }

        // Ensure ITO document type exists
        try {
            if (class_exists('App\Models\AdditionalDocumentType') && Schema::hasTable('additional_document_types')) {
                \App\Models\AdditionalDocumentType::firstOrCreate(['type_name' => 'ITO']);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error creating ITO document type: " . $e->getMessage());
        }
    }
}
