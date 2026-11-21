<?php

namespace App\Filament\Resources\StreamingSessions;

use App\Domain\Sessions\Enums\StreamingSessionStatus;
use App\Filament\Resources\StreamingSessions\Pages\ManageStreamingSessions;
use App\Models\StreamingSession;
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

class StreamingSessionResource extends Resource
{
    protected static ?string $model = StreamingSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Appareils et sessions';

    protected static ?string $navigationLabel = 'Sessions';

    protected static ?string $modelLabel = 'session';

    protected static ?string $pluralModelLabel = 'sessions';

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
                TextColumn::make('subscription.plan.name')
                    ->label('Plan')
                    ->searchable(),
                TextColumn::make('subscription.user.email')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('Invité'),
                TextColumn::make('deviceAssignment.device.label')
                    ->label('Appareil')
                    ->placeholder('Sans nom'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('heartbeat_at')
                    ->dateTime()
                    ->placeholder('Aucun signal')
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->dateTime()
                    ->placeholder('En cours')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(StreamingSessionStatus::class),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStreamingSessions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return parent::getEloquentQuery()
            ->visibleTo($user)
            ->with(['subscription.plan', 'subscription.user', 'deviceAssignment.device']);
    }
}
