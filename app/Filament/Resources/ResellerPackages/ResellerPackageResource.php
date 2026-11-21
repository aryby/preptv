<?php

namespace App\Filament\Resources\ResellerPackages;

use App\Domain\Resellers\Enums\ResellerPackageStatus;
use App\Filament\Resources\ResellerPackages\Pages\ManageResellerPackages;
use App\Models\ResellerPackage;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
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

class ResellerPackageResource extends Resource
{
    protected static ?string $model = ResellerPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Revendeurs';

    protected static ?string $navigationLabel = 'Offres revendeur';

    protected static ?string $modelLabel = 'offre revendeur';

    protected static ?string $pluralModelLabel = 'offres revendeur';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reseller_profile_id')
                    ->relationship('resellerProfile', 'business_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('plan_price_id')
                    ->relationship(
                        'planPrice',
                        'id',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->with('plan'),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->plan->name} / {$record->currency_code} {$record->amount_minor}")
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('override_amount_minor')
                    ->label('Tarif dérogatoire en unités mineures')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('commission_basis_points')
                    ->label('Commission (points de base)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000),
                Select::make('status')
                    ->options(ResellerPackageStatus::class)
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
                TextColumn::make('resellerProfile.business_name')
                    ->label('Revendeur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('planPrice.plan.name')
                    ->label('Plan')
                    ->searchable(),
                TextColumn::make('planPrice.currency_code')
                    ->label('Devise'),
                TextColumn::make('override_amount_minor')
                    ->label('Tarif dérogatoire en unités mineures')
                    ->numeric()
                    ->placeholder('Tarif catalogue'),
                TextColumn::make('commission_basis_points')
                    ->label('Commission (points de base)')
                    ->numeric()
                    ->placeholder('Défaut'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('effective_from')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('effective_until')
                    ->dateTime()
                    ->placeholder('Sans échéance'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ResellerPackageStatus::class),
            ])
            ->defaultSort('effective_from', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageResellerPackages::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return parent::getEloquentQuery()
            ->visibleTo($user)
            ->with(['resellerProfile', 'planPrice.plan']);
    }
}
