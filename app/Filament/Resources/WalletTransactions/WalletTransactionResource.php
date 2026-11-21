<?php

namespace App\Filament\Resources\WalletTransactions;

use App\Domain\Resellers\Enums\WalletTransactionType;
use App\Filament\Resources\WalletTransactions\Pages\ManageWalletTransactions;
use App\Models\User;
use App\Models\WalletTransaction;
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

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Revendeurs';

    protected static ?string $navigationLabel = 'Écritures de portefeuille';

    protected static ?string $modelLabel = 'écriture de portefeuille';

    protected static ?string $pluralModelLabel = 'écritures de portefeuille';

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
                TextColumn::make('wallet.resellerProfile.business_name')
                    ->label('Revendeur')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('amount_minor')
                    ->label('Montant en unités mineures')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->label('Devise'),
                TextColumn::make('balance_after_minor')
                    ->label('Solde après écriture en unités mineures')
                    ->numeric(),
                TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(WalletTransactionType::class),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWalletTransactions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return parent::getEloquentQuery()
            ->visibleTo($user)
            ->with(['wallet.resellerProfile']);
    }
}
