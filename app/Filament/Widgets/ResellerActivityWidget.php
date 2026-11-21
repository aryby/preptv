<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ResellerActivityWidget extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Activité récente des revendeurs')
            ->query(
                Order::query()
                    ->whereNotNull('reseller_profile_id')
                    ->with('resellerProfile')
            )
            ->columns([
                TextColumn::make('resellerProfile.business_name')
                    ->label('Revendeur')
                    ->searchable(),
                TextColumn::make('public_id')
                    ->label('Commande')
                    ->searchable(),
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
