<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\AdditionalDocument;
use Filament\Notifications\Notification;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
    
    protected function afterCreate(): void
    {
        // We don't auto-associate documents anymore, but we should clean up session data
        session()->forget('similar_po_number');
        session()->forget('pending_document_associations');
    }
}
