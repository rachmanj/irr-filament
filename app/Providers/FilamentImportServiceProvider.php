<?php

namespace App\Providers;

use App\Models\Import;
use App\Models\ImportRow;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css\Css;
use Filament\Support\Assets\Js\Js;
use Illuminate\Support\ServiceProvider;

class FilamentImportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register models in AppServiceProvider instead
        // This prevents "Class not found" errors during initial setup
        
        if (class_exists('Filament\Actions\Imports\ImportAction')) {
            \Filament\Actions\Imports\ImportAction::defaultImportModel(Import::class);
            \Filament\Actions\Imports\ImportAction::defaultImportRowModel(ImportRow::class);
        }
    }
} 