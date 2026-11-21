<?php

namespace App\Filament\Resources\Devices;

use App\Filament\Resources\Devices\Pages\ManageDevices;
use App\Models\Device;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Appareils et sessions';

    protected static ?string $navigationLabel = 'Appareils';

    protected static ?string $modelLabel = 'appareil';

    protected static ?string $pluralModelLabel = 'appareils';

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
                TextColumn::make('label')
                    ->searchable()
                    ->placeholder('Sans nom'),
                TextColumn::make('device_type')
                    ->searchable()
                    ->placeholder('Inconnu'),
                TextColumn::make('owner.email')
                    ->label('Propriétaire')
                    ->searchable()
                    ->placeholder('Non attribué'),
                TextColumn::make('last_seen_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Jamais'),
                TextColumn::make('archived_at')
                    ->dateTime()
                    ->placeholder('Actif')
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('archived_at')
                    ->label('Archivé')
                    ->nullable(),
            ])
            ->defaultSort('last_seen_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDevices::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return parent::getEloquentQuery()
            ->visibleTo($user)
            ->with('owner');
    }
}
