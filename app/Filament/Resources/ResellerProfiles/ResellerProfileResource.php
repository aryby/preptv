<?php

namespace App\Filament\Resources\ResellerProfiles;

use App\Domain\Resellers\Enums\ResellerStatus;
use App\Filament\Resources\ResellerProfiles\Pages\ManageResellerProfiles;
use App\Models\ResellerProfile;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ResellerProfileResource extends Resource
{
    protected static ?string $model = ResellerProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Revendeurs';

    protected static ?string $navigationLabel = 'Revendeurs';

    protected static ?string $modelLabel = 'revendeur';

    protected static ?string $pluralModelLabel = 'revendeurs';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship(
                        'user',
                        'email',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->role('reseller'),
                    )
                    ->searchable()
                    ->preload()
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('business_name')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->options(ResellerStatus::class)
                    ->required(),
                TextInput::make('commission_basis_points')
                    ->label('Commission (points de base)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Compte')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('commission_basis_points')
                    ->label('Commission (points de base)')
                    ->numeric(),
                TextColumn::make('archived_at')
                    ->dateTime()
                    ->placeholder('Actif')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ResellerStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('business_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageResellerProfiles::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return parent::getEloquentQuery()
            ->visibleTo($user)
            ->with('user');
    }
}
