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
                Forms\Components\Textarea::make('remarks')
                    ->label('Remarks')
                    ->placeholder('Add description of what this attachment contains')
                    ->nullable()
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('remarks')
                    ->label('Remarks')
                    ->formatStateUsing(fn ($state, $record) => $state ?? $record->original_name)
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By'),
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
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->color('success')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 