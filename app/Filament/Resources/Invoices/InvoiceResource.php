<?php

namespace App\Filament\Resources\Invoices;

use App\Domain\Billing\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Models\Invoice;
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

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Paiements et facturation';

    protected static ?string $navigationLabel = 'Factures';

    protected static ?string $modelLabel = 'facture';

    protected static ?string $pluralModelLabel = 'factures';

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
                TextColumn::make('invoice_number')
                    ->label('Numéro')
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
                TextColumn::make('total_minor')
                    ->label('Total en unités mineures')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->label('Devise')
                    ->sortable(),
                TextColumn::make('issued_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('due_at')
                    ->dateTime()
                    ->placeholder('Sans échéance')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInvoices::route('/'),
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
