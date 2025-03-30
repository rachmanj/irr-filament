<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

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
            ])
            ->filters([
                //
            ])
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 