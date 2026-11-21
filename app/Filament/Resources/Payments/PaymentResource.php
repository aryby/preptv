<?php

namespace App\Filament\Resources\Payments;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
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

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Paiements et facturation';

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $modelLabel = 'paiement';

    protected static ?string $pluralModelLabel = 'paiements';

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
                TextColumn::make('order.public_id')
                    ->label('Commande')
                    ->searchable(),
                TextColumn::make('provider')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('amount_minor')
                    ->label('Montant en unités mineures')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->label('Devise')
                    ->sortable(),
                TextColumn::make('confirmed_at')
                    ->dateTime()
                    ->placeholder('Non confirmé')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PaymentStatus::class),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return parent::getEloquentQuery()
            ->visibleTo($user)
            ->with('order');
    }
}
