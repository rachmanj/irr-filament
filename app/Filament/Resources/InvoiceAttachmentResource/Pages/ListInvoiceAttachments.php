<?php

namespace App\Filament\Resources\InvoiceAttachmentResource\Pages;

use App\Filament\Resources\InvoiceAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceAttachments extends ListRecords
{
    protected static string $resource = InvoiceAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
