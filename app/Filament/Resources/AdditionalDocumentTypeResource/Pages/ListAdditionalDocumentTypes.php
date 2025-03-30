<?php

namespace App\Filament\Resources\AdditionalDocumentTypeResource\Pages;

use App\Filament\Resources\AdditionalDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdditionalDocumentTypes extends ListRecords
{
    protected static string $resource = AdditionalDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
