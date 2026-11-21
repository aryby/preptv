<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOrdersWidget extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Commandes récentes')
            ->query(
                Order::query()
                    ->visibleTo(Filament::auth()->user())
                    ->with(['user', 'resellerProfile'])
            )
            ->columns([
                TextColumn::make('public_id')
                    ->label('Référence')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Client')
                    ->placeholder('Invité'),
                TextColumn::make('resellerProfile.business_name')
                    ->label('Revendeur')
                    ->placeholder('Vente directe'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total_minor')
                    ->label('Total en unités mineures')
                    ->numeric(),
                TextColumn::make('currency_code')
                    ->label('Devise'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10]);
    }
}
