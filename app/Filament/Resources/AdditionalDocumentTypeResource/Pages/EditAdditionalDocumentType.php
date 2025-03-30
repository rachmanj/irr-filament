<?php

namespace App\Filament\Resources\AdditionalDocumentTypeResource\Pages;

use App\Filament\Resources\AdditionalDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdditionalDocumentType extends EditRecord
{
    protected static string $resource = AdditionalDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
