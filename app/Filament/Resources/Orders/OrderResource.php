<?php

namespace App\Filament\Resources\Orders;

use App\Domain\Orders\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\Order;
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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

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
                TextColumn::make('public_id')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
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
                TextColumn::make('total_minor')
                    ->label('Total en unités mineures')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->label('Devise')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrders::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return parent::getEloquentQuery()
            ->visibleTo($user)
            ->with(['user', 'resellerProfile']);
    }
}
