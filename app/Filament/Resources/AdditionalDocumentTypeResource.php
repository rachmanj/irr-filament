<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdditionalDocumentTypeResource\Pages;
use App\Filament\Resources\AdditionalDocumentTypeResource\RelationManagers;
use App\Models\AdditionalDocumentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdditionalDocumentTypeResource extends Resource
{
    protected static ?string $model = AdditionalDocumentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static ?string $navigationGroup = 'Master';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Additional Document Types';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('type_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListAdditionalDocumentTypes::route('/'),
            'create' => Pages\CreateAdditionalDocumentType::route('/create'),
            'edit' => Pages\EditAdditionalDocumentType::route('/{record}/edit'),
        ];
    }
}
