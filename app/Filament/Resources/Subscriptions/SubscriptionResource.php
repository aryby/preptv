<?php

namespace App\Filament\Resources\Subscriptions;

use App\Domain\Subscriptions\Enums\SubscriptionStatus;
use App\Filament\Resources\Subscriptions\Pages\ManageSubscriptions;
use App\Models\Subscription;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Abonnements';

    protected static ?string $navigationLabel = 'Abonnements';

    protected static ?string $modelLabel = 'abonnement';

    protected static ?string $pluralModelLabel = 'abonnements';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextColumn::make('user.email')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('Invité'),
                TextColumn::make('resellerProfile.business_name')
                    ->label('Revendeur')
                    ->searchable()
                    ->placeholder('Vente directe'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('amount_minor')
                    ->label('Montant en unités mineures')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->label('Devise'),
                TextColumn::make('current_period_ends_at')
                    ->dateTime()
                    ->placeholder('Non défini')
                    ->sortable(),
                TextColumn::make('device_limit_snapshot')
                    ->label('Limite d’appareils'),
                TextColumn::make('concurrent_session_limit_snapshot')
                    ->label('Sessions simultanées'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SubscriptionStatus::class),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSubscriptions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return parent::getEloquentQuery()
            ->visibleTo($user)
            ->with(['plan', 'user', 'resellerProfile']);
    }
}
