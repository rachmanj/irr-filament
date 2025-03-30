<?php

namespace App\Filament\Resources\AdditionalDocumentResource\Pages;

use App\Filament\Imports\ItoImporter;
use App\Filament\Resources\AdditionalDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListAdditionalDocuments extends ListRecords
{
    protected static string $resource = AdditionalDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(url('storage/templates/ito_import_template.xlsx'))
                ->openUrlInNewTab(),
            Actions\Action::make('directImport')
                ->label('Import ITOs (Direct)')
                ->url(route('ito.import.form'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success'),
            Actions\ImportAction::make()
                ->label('Import ITOs')
                ->icon('heroicon-o-arrow-up-tray')
                ->importer(ItoImporter::class)
                ->chunkSize(10)
                ->maxRows(1000)
                ->modalHeading('Import ITOs')
                ->modalDescription('Upload a CSV file to import ITO documents. The required columns are ITO Number and ITO Date.')
                ->modalSubmitActionLabel('Start Import'),
            Actions\CreateAction::make(),
        ];
    }
}
