<?php

namespace App\Filament\Widgets;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Models\Payment;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingPaymentsWidget extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Paiements en attente')
            ->query(
                Payment::query()
                    ->visibleTo(Filament::auth()->user())
                    ->with('order')
                    ->whereIn('status', [
                        PaymentStatus::Pending->value,
                        PaymentStatus::Processing->value,
                    ])
            )
            ->columns([
                TextColumn::make('order.public_id')
                    ->label('Commande')
                    ->searchable(),
                TextColumn::make('provider')
                    ->searchable(),
                TextColumn::make('amount_minor')
                    ->label('Montant en unités mineures')
                    ->numeric(),
                TextColumn::make('currency_code')
                    ->label('Devise'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        PaymentStatus::Pending->value => 'En attente',
                        PaymentStatus::Processing->value => 'En traitement',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10]);
    }
}
