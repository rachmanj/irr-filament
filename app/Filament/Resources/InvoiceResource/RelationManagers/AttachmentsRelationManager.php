<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('file_path')
                    ->label('File')
                    ->required()
                    ->disk('public')
                    ->directory('invoice-attachments')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->storeFileNamesIn('original_name')
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label('File Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mime_type')
                    ->label('File Type'),
                Tables\Columns\TextColumn::make('size')
                    ->label('Size (KB)')
                    ->formatStateUsing(fn (int $state) => number_format($state / 1024, 2) . ' KB'),
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Upload Date')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['uploaded_by'] = auth()->id();
                        $data['mime_type'] = mime_content_type(storage_path('app/public/' . $data['file_path']));
                        $data['size'] = filesize(storage_path('app/public/' . $data['file_path']));
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 