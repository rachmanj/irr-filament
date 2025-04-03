<?php

namespace App\Filament\Resources\AdditionalDocumentResource\Pages;

use App\Filament\Resources\AdditionalDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdditionalDocument extends EditRecord
{
    protected static string $resource = AdditionalDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
