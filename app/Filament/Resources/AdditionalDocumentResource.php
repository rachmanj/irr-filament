<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdditionalDocumentResource\Pages;
use App\Filament\Resources\AdditionalDocumentResource\RelationManagers;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdditionalDocumentResource extends Resource
{
    protected static ?string $model = AdditionalDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Additional Docs';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Additional Documents';

    protected static bool $shouldRegisterNavigation = true;

    protected static bool $shouldShowNavigation = true;

    protected static bool $shouldShowNavigationCloseButton = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\Select::make('type_id')
                            ->label('Document Type')
                            ->options(
                                AdditionalDocumentType::query()
                                    ->orderBy('type_name')
                                    ->get()
                                    ->mapWithKeys(function ($type) {
                                        return [$type->id => $type->type_name];
                                    })
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('type_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->validationAttribute('Document Type Name'),
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('document_number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('document_date')
                            ->required(),
                        Forms\Components\TextInput::make('po_no')
                            ->maxLength(50),
                        Forms\Components\Select::make('invoice_id')
                            ->relationship('invoice', 'invoice_number')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('receive_date'),
                        Forms\Components\Select::make('cur_loc')
                            ->label('Current Location')
                            ->options(
                                Department::query()
                                    ->whereNotNull('location_code')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($department) {
                                        $label = $department->name;
                                        if ($department->project) {
                                            $label .= " ({$department->project})";
                                        }
                                        $label .= " - {$department->location_code}";
                                        return [$department->location_code => $label];
                                    })
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->validationAttribute('Department Name'),
                                Forms\Components\TextInput::make('project')
                                    ->maxLength(10)
                                    ->validationAttribute('Project Code'),
                                Forms\Components\TextInput::make('location_code')
                                    ->required()
                                    ->maxLength(30)
                                    ->unique(ignoreRecord: true)
                                    ->validationAttribute('Location Code'),
                                Forms\Components\TextInput::make('transit_code')
                                    ->maxLength(30)
                                    ->validationAttribute('Transit Code'),
                                Forms\Components\TextInput::make('akronim')
                                    ->maxLength(10)
                                    ->validationAttribute('Akronim'),
                                Forms\Components\TextInput::make('sap_code')
                                    ->maxLength(10)
                                    ->validationAttribute('SAP Code'),
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('remarks')
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('attachment')
                            ->disk('public')
                            ->directory('documents')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['application/pdf', 'image/*']),
                        Forms\Components\Select::make('created_by')
                            ->label('Created By')
                            ->relationship('createdBy', 'name')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => auth()->id())
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('type.type_name')
                    ->label('Document Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Document Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_date')
                    ->label('Document Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('receive_date')
                    ->label('Receive Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('po_no')
                    ->label('PO No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Inv No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cur_loc')
                    ->label('Location Code')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_id')
                    ->label('Document Type')
                    ->relationship('type', 'type_name')
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('department')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'verified' => 'Verified',
                        'returned' => 'Returned',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->tooltip('Edit document'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Delete document')
                    ->visible(fn () => auth()->user()->can('delete_additional::document')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->tooltip('Delete selected documents')
                        ->visible(fn () => auth()->user()->can('delete_any_additional::document')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdditionalDocuments::route('/'),
            'create' => Pages\CreateAdditionalDocument::route('/create'),
            'edit' => Pages\EditAdditionalDocument::route('/{record}/edit'),
        ];
    }
}
