<?php

namespace App\Filament\Resources\Currencies;

use App\Domain\Catalog\Actions\SetDefaultCurrency;
use App\Domain\Catalog\Services\CatalogSettings;
use App\Filament\Resources\Currencies\Pages\ManageCurrencies;
use App\Models\Currency;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Devises';

    protected static ?string $modelLabel = 'devise';

    protected static ?string $pluralModelLabel = 'devises';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->length(3)
                    ->regex('/^[A-Z]{3}$/')
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Currency $record): bool => $record !== null),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('minor_unit')
                    ->label('Décimales mineures')
                    ->numeric()
                    ->rules(['integer'])
                    ->minValue(0)
                    ->maxValue(4)
                    ->required()
                    ->disabled(fn (?Currency $record): bool => $record !== null),
                TextInput::make('symbol')
                    ->maxLength(8),
                Toggle::make('is_active')
                    ->required()
                    ->disabled(
                        fn (?Currency $record): bool => $record !== null
                            && $record->is_active
                            && app(CatalogSettings::class)->isDefault($record)
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        $catalogSettings = app(CatalogSettings::class);
        $defaultCurrencyCode = $catalogSettings->defaultCurrencyCode();

        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('minor_unit')
                    ->label('Décimales mineures'),
                TextColumn::make('symbol'),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('code')
                    ->label('Par défaut')
                    ->state(fn (Currency $record): bool => $record->code === $defaultCurrencyCode)
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('setDefaultCurrency')
                    ->label('Définir par défaut')
                    ->action(function (Currency $record): void {
                        $actor = Filament::auth()->user();
                        abort_unless($actor instanceof User, 403);

                        app(SetDefaultCurrency::class)->execute($actor, $record->code);

                        Notification::make()
                            ->title('Devise par défaut mise à jour')
                            ->success()
                            ->send();
                    })
                    ->visible(function (Currency $record) use ($defaultCurrencyCode): bool {
                        $actor = Filament::auth()->user();

                        return $record->is_active
                            && $record->code !== $defaultCurrencyCode
                            && $actor instanceof User
                            && $actor->can('update', $record);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCurrencies::route('/'),
        ];
    }
}
