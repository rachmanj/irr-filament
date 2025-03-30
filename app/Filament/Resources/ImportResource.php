<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportResource\Pages;
use App\Models\Import;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Import History';

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('file_name')
                            ->label('File Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('importer')
                            ->label('Importer Class')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_rows')
                            ->label('Total Rows')
                            ->disabled(),
                        Forms\Components\TextInput::make('successful_rows')
                            ->label('Successful Rows')
                            ->disabled(),
                        Forms\Components\TextInput::make('failed_rows')
                            ->label('Failed Rows')
                            ->disabled(),
                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Import Date')
                            ->disabled(),
                    ])->columns(2),
                Forms\Components\Section::make('Failed Rows Data')
                    ->schema([
                        Forms\Components\Textarea::make('failed_rows_data')
                            ->disabled()
                            ->visible(fn (Import $record) => !empty($record->failed_rows_data))
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn (Import $record) => !empty($record->failed_rows_data)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('importer')
                    ->label('Import Type')
                    ->formatStateUsing(fn (string $state) => class_basename($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_rows')
                    ->label('Total'),
                Tables\Columns\TextColumn::make('successful_rows')
                    ->label('Successful'),
                Tables\Columns\TextColumn::make('failed_rows')
                    ->label('Failed'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'pending' => 'gray',
                        'failed' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Import Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListImports::route('/'),
            'view' => Pages\ViewImport::route('/{record}'),
        ];
    }
} 