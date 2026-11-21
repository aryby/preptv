<?php

namespace App\Filament\Resources\Users;

use App\Domain\Identity\Actions\SyncUserRoles;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Administration et audit';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->minLength(12)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->separator(','),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('syncRoles')
                    ->label('Gérer les rôles')
                    ->schema([
                        Select::make('roles')
                            ->multiple()
                            ->options(function (): array {
                                $query = Role::query()->where('guard_name', 'web');
                                $actor = Filament::auth()->user();

                                if (! $actor instanceof User || ! $actor->hasRole('super_admin')) {
                                    $query->where('name', '!=', 'super_admin');
                                }

                                return $query->orderBy('name')->pluck('name', 'name')->all();
                            })
                            ->required(),
                    ])
                    ->fillForm(fn (User $record): array => [
                        'roles' => $record->getRoleNames()->all(),
                    ])
                    ->visible(function (User $record): bool {
                        $actor = Filament::auth()->user();

                        return $actor instanceof User
                            && $actor->can('assignRoles', $record)
                            && (! $record->hasRole('super_admin') || $actor->hasRole('super_admin'));
                    })
                    ->action(function (User $record, array $data): void {
                        $actor = Filament::auth()->user();
                        abort_unless($actor instanceof User, 403);

                        app(SyncUserRoles::class)->execute($actor, $record, $data['roles']);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('roles');
    }
}
