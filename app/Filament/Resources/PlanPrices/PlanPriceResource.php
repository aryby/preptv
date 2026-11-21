<?php

namespace App\Filament\Resources\PlanPrices;

use App\Domain\Catalog\Enums\BillingPeriodUnit;
use App\Filament\Resources\PlanPrices\Pages\ManagePlanPrices;
use App\Models\PlanPrice;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
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

class PlanPriceResource extends Resource
{
    protected static ?string $model = PlanPrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Tarifs';

    protected static ?string $modelLabel = 'tarif';

    protected static ?string $pluralModelLabel = 'tarifs';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('currency_code')
                    ->relationship('currency', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('billing_period_unit')
                    ->options(BillingPeriodUnit::class)
                    ->required(),
                TextInput::make('billing_period_count')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('amount_minor')
                    ->label('Montant en unités mineures')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                DateTimePicker::make('effective_from')
                    ->required(),
                DateTimePicker::make('effective_until'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('billing_period_unit')
                    ->badge(),
                TextColumn::make('billing_period_count')
                    ->numeric(),
                TextColumn::make('amount_minor')
                    ->label('Montant en unités mineures')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('effective_until')
                    ->dateTime()
                    ->placeholder('Sans échéance'),
            ])
            ->filters([
                SelectFilter::make('plan_id')
                    ->relationship('plan', 'name'),
                SelectFilter::make('currency_code')
                    ->relationship('currency', 'name'),
            ])
            ->defaultSort('effective_from', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlanPrices::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['plan', 'currency']);
    }
}
