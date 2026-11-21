<?php

namespace App\Filament\Resources\Themes;

use App\Domain\Themes\Enums\ThemeStatus;
use App\Filament\Resources\Themes\Pages\ManageThemes;
use App\Models\Theme;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Apparence et paramètres';

    protected static ?string $navigationLabel = 'Thèmes';

    protected static ?string $modelLabel = 'thème';

    protected static ?string $pluralModelLabel = 'thèmes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->options(ThemeStatus::class)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('settings_count')
                    ->counts('settings')
                    ->label('Paramètres'),
                TextColumn::make('archived_at')
                    ->dateTime()
                    ->placeholder('Actif')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ThemeStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageThemes::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('settings');
    }
}
