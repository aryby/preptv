<?php

namespace App\Filament\Resources\Plans;

use App\Domain\Catalog\Enums\PlanStatus;
use App\Filament\Resources\Plans\Pages\ManagePlans;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Plans';

    protected static ?string $modelLabel = 'plan';

    protected static ?string $pluralModelLabel = 'plans';

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
                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(PlanStatus::class)
                    ->required(),
                Toggle::make('is_public')
                    ->label('Visible publiquement')
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Ordre d’affichage')
                    ->numeric()
                    ->rules(['integer'])
                    ->minValue(0)
                    ->required(),
                TextInput::make('device_limit')
                    ->numeric()
                    ->rules(['integer'])
                    ->minValue(1)
                    ->maxValue(65535)
                    ->required(),
                TextInput::make('concurrent_session_limit')
                    ->numeric()
                    ->rules(['integer'])
                    ->minValue(1)
                    ->maxValue(65535)
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
                IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
                TextColumn::make('device_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('concurrent_session_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('plan_prices_count')
                    ->counts('planPrices')
                    ->label('Prices'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PlanStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlans::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('planPrices');
    }
}
