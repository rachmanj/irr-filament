<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdditionalDocumentResource\Pages;
use App\Filament\Resources\AdditionalDocumentResource\RelationManagers;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Invoice;
use App\Models\User;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('type_id')
                            ->relationship('type', 'type_name')
                            ->required()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('type_name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
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
                        Forms\Components\TextInput::make('project')
                            ->maxLength(50),
                        Forms\Components\DatePicker::make('receive_date'),
                        Forms\Components\Select::make('created_by')
                            ->relationship('createdBy', 'name')
                            ->required()
                            ->default(auth()->id())
                            ->preload()
                            ->searchable(),
                        Forms\Components\FileUpload::make('attachment')
                            ->disk('public')
                            ->directory('documents')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['application/pdf', 'image/*']),
                    ])->columns(2),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('remarks')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('flag')
                            ->maxLength(30),
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
                        Forms\Components\TextInput::make('cur_loc')
                            ->label('Current Location')
                            ->maxLength(30),
                        Forms\Components\TextInput::make('ito_creator')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('grpo_no')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('origin_wh')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('destination_wh')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('batch_no')
                            ->numeric(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type.type_name')
                    ->label('Document Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Document Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('po_no')
                    ->label('PO No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Inv No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cur_loc')
                    ->label('Current Location')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_id')
                    ->label('Document Type')
                    ->relationship('type', 'type_name')
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
