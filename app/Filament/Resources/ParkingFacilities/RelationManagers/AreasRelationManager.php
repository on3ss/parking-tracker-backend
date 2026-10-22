<?php

namespace App\Filament\Resources\ParkingFacilities\RelationManagers;

use App\Enums\VehicleType;
use App\Models\ParkingArea;
use App\Models\ParkingFacility;
use App\Models\User;
use App\Support\Rbac\Permission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

class AreasRelationManager extends RelationManager
{
    protected static string $relationship = 'areas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Area Information'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label(__('Code'))
                            ->maxLength(255),

                        Select::make('vehicle_type')
                            ->label(__('Vehicle type'))
                            ->options(VehicleType::class)
                            ->default(VehicleType::ALL)
                            ->native(false)
                            ->required(),

                        TextInput::make('capacity')
                            ->label(__('Capacity'))
                            ->numeric()
                            ->integer()
                            ->minValue(0),

                        Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Area Information'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('Name'))
                            ->placeholder(__('—')),

                        TextEntry::make('code')
                            ->label(__('Code'))
                            ->placeholder(__('—')),

                        TextEntry::make('vehicle_type')
                            ->label(__('Vehicle type'))
                            ->badge(),

                        TextEntry::make('capacity')
                            ->label(__('Capacity'))
                            ->numeric()
                            ->placeholder(__('—')),

                        IconEntry::make('is_active')
                            ->label(__('Active'))
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make(__('Record Information'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('Created'))
                            ->dateTime()
                            ->placeholder(__('—')),

                        TextEntry::make('updated_at')
                            ->label(__('Updated'))
                            ->dateTime()
                            ->placeholder(__('—')),

                        TextEntry::make('deleted_at')
                            ->label(__('Deleted'))
                            ->dateTime()
                            ->placeholder(__('—'))
                            ->visible(
                                fn (ParkingArea $record): bool => $record->trashed()
                            ),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label(__('Code'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('vehicle_type')
                    ->label(__('Vehicle type'))
                    ->badge(),

                TextColumn::make('capacity')
                    ->label(__('Capacity'))
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label(__('Deleted'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(
                        fn (): bool => $this->allowsAreaOperation(
                            Permission::AreaCreate,
                        ),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize(
                        fn (ParkingArea $record): bool => $this->allowsAreaOperation(
                            Permission::AreaView,
                            $record,
                        ),
                    ),

                EditAction::make()
                    ->authorize(
                        fn (ParkingArea $record): bool => $this->allowsAreaOperation(
                            Permission::AreaUpdate,
                            $record,
                        ),
                    ),

                DeleteAction::make()
                    ->authorize(
                        fn (ParkingArea $record): bool => $this->allowsAreaOperation(
                            Permission::AreaDelete,
                            $record,
                        ),
                    ),

                RestoreAction::make()
                    ->authorize(
                        fn (ParkingArea $record): bool => $this->allowsAreaOperation(
                            Permission::AreaDelete,
                            $record,
                        ),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(
                            fn (): bool => $this->allowsAreaOperation(
                                Permission::AreaDelete,
                            ),
                        ),

                    RestoreBulkAction::make()
                        ->authorize(
                            fn (): bool => $this->allowsAreaOperation(
                                Permission::AreaDelete,
                            ),
                        ),
                ]),
            ])
            ->modifyQueryUsing(
                fn (Builder $query) => $query->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
            );
    }

    private function allowsAreaOperation(
        Permission $permission,
        ?ParkingArea $area = null,
    ): bool {
        $user = auth()->user();
        $facility = $this->getOwnerRecord();

        if (
            ! $user instanceof User
            || ! $facility instanceof ParkingFacility
        ) {
            return false;
        }

        // A record action must not escape the parent facility's scope.
        if (
            $area !== null
            && (string) $area->parking_facility_id
            !== (string) $facility->getKey()
        ) {
            return false;
        }

        $gate = Gate::forUser($user);

        if ($area !== null) {
            $ability = match ($permission) {
                Permission::AreaView => 'view',
                Permission::AreaUpdate => 'update',
                Permission::AreaDelete => 'delete',
                default => null,
            };

            return $ability !== null
                && $gate->allows($ability, $area);
        }

        $ability = match ($permission) {
            Permission::AreaCreate => 'createForFacility',
            Permission::AreaDelete => 'deleteForFacility',
            default => null,
        };

        return $ability !== null
            && $gate->allows(
                $ability,
                [ParkingArea::class, $facility],
            );
    }
}
