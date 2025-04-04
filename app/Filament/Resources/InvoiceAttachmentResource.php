<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceAttachmentResource\Pages;
use App\Filament\Resources\InvoiceAttachmentResource\RelationManagers;
use App\Models\InvoiceAttachment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceAttachmentResource extends Resource
{
    protected static ?string $model = InvoiceAttachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationGroup = 'Invoice';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Invoice Attachments';
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\FileUpload::make('file_path')
                    ->required()
                    ->disk('public')
                    ->directory('invoice-attachments')
                    ->visibility('public')
                    ->storeFileNamesIn('original_name')
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
                Forms\Components\Textarea::make('remarks')
                    ->label('Remarks')
                    ->placeholder('Add description of what this attachment contains')
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\Select::make('uploaded_by')
                    ->relationship('uploader', 'name')
                    ->required()
                    ->default(auth()->id())
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('original_name')
                    ->label('File Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remarks')
                    ->label('Remarks')
                    ->formatStateUsing(fn ($state, $record) => $state ?? $record->original_name)
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListInvoiceAttachments::route('/'),
            'create' => Pages\CreateInvoiceAttachment::route('/create'),
            'edit' => Pages\EditInvoiceAttachment::route('/{record}/edit'),
        ];
    }
}
