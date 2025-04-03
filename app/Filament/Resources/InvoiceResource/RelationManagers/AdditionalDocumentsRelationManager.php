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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->modifyQueryUsing(function (Builder $query) {
                // If we have a similar PO number in the session, also include unassociated documents
                $similarPo = session('similar_po_number');
                
                if ($similarPo) {
                    $invoiceId = $this->getOwnerRecord()->id;
                    
                    // Get both already associated documents and unassociated documents with similar PO
                    $query->where(function($q) use ($invoiceId, $similarPo) {
                        $q->where('invoice_id', $invoiceId) // Already associated docs
                          ->orWhere(function($subQuery) use ($similarPo) {
                              $subQuery->whereNull('invoice_id') // Unassociated docs with matching PO
                                      ->where('po_no', 'like', "%{$similarPo}%");
                          });
                    });
                }
            })
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
                    ->getStateUsing(fn ($record) => !is_null($record->invoice_id))
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
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('associate')
                    ->label('Associate')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->visible(fn ($record) => $record->invoice_id === null)
                    ->action(function ($record) {
                        // Get the current invoice id
                        $invoiceId = $this->getOwnerRecord()->id;
                        
                        // Associate the document with this invoice
                        $record->invoice_id = $invoiceId;
                        $record->save();
                        
                        // Show success notification
                        \Filament\Notifications\Notification::make()
                            ->title('Document Associated')
                            ->body('The document has been associated with this invoice.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('dissociate')
                    ->label('Dissociate')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->invoice_id !== null)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Remove the association
                        $record->invoice_id = null;
                        $record->save();
                        
                        // Show success notification
                        \Filament\Notifications\Notification::make()
                            ->title('Document Dissociated')
                            ->body('The document has been removed from this invoice.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('associateSelected')
                        ->label('Associate Selected')
                        ->icon('heroicon-o-link')
                        ->color('success')
                        ->action(function (array $records) {
                            $invoiceId = $this->getOwnerRecord()->id;
                            $count = 0;
                            
                            foreach ($records as $record) {
                                if ($record->invoice_id === null) {
                                    $record->invoice_id = $invoiceId;
                                    $record->save();
                                    $count++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Documents Associated')
                                ->body("$count document(s) have been associated with this invoice.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('dissociateSelected')
                        ->label('Dissociate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (array $records) {
                            $count = 0;
                            
                            foreach ($records as $record) {
                                if ($record->invoice_id !== null) {
                                    $record->invoice_id = null;
                                    $record->save();
                                    $count++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Documents Dissociated')
                                ->body("$count document(s) have been removed from this invoice.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 