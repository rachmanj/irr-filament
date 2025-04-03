<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Invoice';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                return $rule->where('supplier_id', $get('supplier_id'));
                            })
                            ->validationAttribute('Invoice Number')
                            ->validationMessages([
                                'unique' => 'This invoice number already exists for the selected supplier.',
                            ]),
                        Forms\Components\DatePicker::make('invoice_date')
                            ->required(),
                        Forms\Components\DatePicker::make('receive_date')
                            ->required()
                            ->label('Receive Date (from supplier)'),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->extraAttributes(['class' => 'fi-select-input']),
                        Forms\Components\TextInput::make('po_no')
                            ->maxLength(30)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, $livewire) {
                                if (empty($state)) {
                                    session()->forget('similar_po_number');
                                    return;
                                }
                                
                                // Search for additional documents with similar PO number
                                $additionalDocuments = \App\Models\AdditionalDocument::whereNull('invoice_id')
                                    ->where('po_no', 'like', "%{$state}%")
                                    ->count();
                                
                                if ($additionalDocuments == 0) {
                                    session()->forget('similar_po_number');
                                    return;
                                }
                                
                                // Store PO number for filtering the relation manager
                                session(['similar_po_number' => $state]);
                                
                                // Show notification about found documents
                                \Filament\Notifications\Notification::make()
                                    ->title('Additional Documents Found')
                                    ->body("Found {$additionalDocuments} unassociated additional document(s) with similar PO number. Check the Additional Documents tab to associate them.")
                                    ->icon('heroicon-o-document-text')
                                    ->iconColor('success')
                                    ->duration(10000) // 10 seconds
                                    ->persistent()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('view')
                                            ->label('Go to Additional Documents Tab')
                                            ->close()
                                            ->action(function () use ($livewire) {
                                                $script = <<<JS
                                                    document.querySelector('button[role="tab"][aria-controls*="additional-documents"]').click();
                                                JS;
                                                
                                                $livewire->dispatch('eval', ['js' => $script]);
                                            }),
                                    ])
                                    ->info()
                                    ->send();
                            }),
                        Forms\Components\Select::make('receive_project')
                            ->label('Receive Project (where invoice received)')
                            ->options(Project::orderBy('code')->pluck('code', 'code'))
                            ->searchable()
                            ->extraAttributes(['class' => 'fi-select-input']),
                        Forms\Components\Select::make('invoice_project')
                            ->label('Invoice Project (cost charged to)')
                            ->options(Project::orderBy('code')->pluck('code', 'code'))
                            ->searchable()
                            ->extraAttributes(['class' => 'fi-select-input']),
                        Forms\Components\Select::make('payment_project')
                            ->label('Payment Project (responsible for payment)')
                            ->options(Project::orderBy('code')->pluck('code', 'code'))
                            ->searchable()
                            ->extraAttributes(['class' => 'fi-select-input']),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'IDR' => 'IDR - Indonesian Rupiah',
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                                'SGD' => 'SGD - Singapore Dollar',
                            ])
                            ->default('IDR')
                            ->required()
                            ->searchable()
                            ->extraAttributes(['class' => 'fi-select-input']),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix(fn (callable $get) => $get('currency')),
                        Forms\Components\Select::make('type_id')
                            ->relationship('invoiceType', 'type_name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->extraAttributes(['class' => 'fi-select-input']),
                        Forms\Components\DatePicker::make('payment_date'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('remarks'),
                        Forms\Components\TextInput::make('cur_loc')
                            ->label('Current Location')
                            ->maxLength(30),
                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'verify' => 'Verified',
                                'return' => 'Returned',
                                'sap' => 'In SAP',
                                'close' => 'Closed',
                                'cancel' => 'Cancelled',
                            ])
                            ->default('open')
                            ->required()
                            ->visible(fn ($record) => $record !== null)
                            ->extraAttributes(['class' => 'fi-select-input']),
                        Forms\Components\Select::make('created_by_display')
                            ->label('Created By')
                            ->relationship('createdBy', 'name')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null)
                            ->extraAttributes(['class' => 'fi-select-input']),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => auth()->id())
                            ->required(),
                        Forms\Components\TextInput::make('sap_doc')
                            ->maxLength(20)
                            ->label('SAP Document Number'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('receive_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('po_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (Invoice $record) => $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoiceType.type_name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'gray',
                        'verify' => 'success',
                        'return' => 'danger',
                        'sap' => 'warning',
                        'close' => 'info',
                        'cancel' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttachmentsRelationManager::make(),
            RelationManagers\AdditionalDocumentsRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
