<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\AdditionalDocument;
use Filament\Tables\Enums\FiltersLayout;

class AdditionalDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'additionalDocuments';
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type_id')
                    ->relationship('type', 'type_name')
                    ->required()
                    ->preload(),
                Forms\Components\TextInput::make('document_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('document_date')
                    ->required(),
                Forms\Components\TextInput::make('po_no')
                    ->maxLength(50),
                Forms\Components\FileUpload::make('attachment')
                    ->disk('public')
                    ->directory('documents'),
                Forms\Components\Textarea::make('remarks')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'verified' => 'Verified',
                        'returned' => 'Returned',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('open'),
            ]);
    }

    protected function paginateTableQuery(Builder $query): \Illuminate\Contracts\Pagination\Paginator
    {
        // If a similar PO number is stored in the session and we're on the first page
        // add unassociated documents with similar PO to the results
        $similarPo = session('similar_po_number');
        
        if ($similarPo) {
            // First get the normal relationship results (documents already associated with this invoice)
            $relationshipResults = parent::paginateTableQuery($query);
            
            // Log the query that was executed
            \Illuminate\Support\Facades\Log::info("Original query: " . $query->toSql());
            \Illuminate\Support\Facades\Log::info("Query bindings: " . json_encode($query->getBindings()));
            
            // Now get unassociated documents with similar PO number
            $invoiceId = $this->getOwnerRecord()->id;
            $unassociatedDocs = \App\Models\AdditionalDocument::whereNull('invoice_id')
                ->where('po_no', 'like', "%{$similarPo}%")
                ->get();
                
            // Log what we found
            \Illuminate\Support\Facades\Log::info("Found " . $unassociatedDocs->count() . " unassociated docs with PO: {$similarPo}");
            
            // If there are any unassociated documents, merge them with the relationship results
            if ($unassociatedDocs->count() > 0) {
                // Get the original paginator data
                $relationshipData = $relationshipResults->items();
                
                // Merge with unassociated documents
                $allDocuments = collect($relationshipData)->merge($unassociatedDocs);
                
                // Create a custom paginator with all documents
                $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allDocuments,
                    $allDocuments->count(),
                    $allDocuments->count(),
                    1
                );
                
                // Add a path to the paginator to prevent URL errors
                $paginator->withPath(request()->url());
                
                // Add a "items" public property to match the EloquentCollection behavior
                $paginatorItemsProperty = new \ReflectionProperty(\Illuminate\Pagination\LengthAwarePaginator::class, 'items');
                $paginatorItemsProperty->setAccessible(true);
                $paginatorItemsProperty->setValue($paginator, $allDocuments);
                
                return $paginator;
            }
            
            // If no unassociated documents were found, return the original results
            return $relationshipResults;
        }
        
        // If no similar PO number in session, just return the normal relationship results
        return parent::paginateTableQuery($query);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                Tables\Columns\TextColumn::make('document_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_date')
                    ->date(),
                Tables\Columns\TextColumn::make('type.type_name')
                    ->label('Document Type'),
                Tables\Columns\TextColumn::make('po_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'gray',
                        'verified' => 'success',
                        'returned' => 'danger',
                        'closed' => 'info',
                        'cancelled' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_associated')
                    ->label('Associated')
                    ->boolean()
                    ->getStateUsing(function ($record) {
                        $currentInvoiceId = $this->getOwnerRecord()->id;
                        $isAssociated = $record->invoice_id === $currentInvoiceId;
                        \Illuminate\Support\Facades\Log::info("Document {$record->id} is associated with invoice {$record->invoice_id}, current invoice: {$currentInvoiceId}, result: " . ($isAssociated ? 'true' : 'false'));
                        return $isAssociated;
                    })
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('association_status')
                    ->label('Status')
                    ->options([
                        'associated' => 'Associated Documents',
                        'unassociated' => 'Unassociated Documents',
                        'all' => 'All Documents',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data) {
                        $invoiceId = $this->getOwnerRecord()->id;
                        
                        if ($data['value'] === 'associated') {
                            $query->where('invoice_id', $invoiceId);
                        } elseif ($data['value'] === 'unassociated') {
                            $query->whereNull('invoice_id');
                        }
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('associate')
                    ->label('Associate')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->visible(fn ($record) => $record->invoice_id === null)
                    ->url(fn ($record) => route('filament.admin.resources.invoices.associate-document', [
                        'invoice' => $this->getOwnerRecord()->id,
                        'document' => $record->id
                    ])),
                Tables\Actions\Action::make('dissociate')
                    ->label('Dissociate')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->invoice_id !== null)
                    ->url(fn ($record) => route('filament.admin.resources.invoices.dissociate-document', [
                        'invoice' => $this->getOwnerRecord()->id,
                        'document' => $record->id
                    ])),
            ])
            ->bulkActions([
                // Remove all bulk actions for now since they don't work
                // We'll re-implement them later when we figure out the issue
            ]);
    }
} 