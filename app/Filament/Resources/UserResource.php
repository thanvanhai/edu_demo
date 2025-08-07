<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\{User, Role};
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\{Select, TextInput, DateTimePicker, Tabs, Tabs\Tab, Component, Section};
use BezhanSalleh\FilamentShield\FilamentShield;
use BezhanSalleh\FilamentShield\Forms\ShieldSelectAllToggle;
use Illuminate\Support\HtmlString;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use Filament\Tables\Columns\TextColumn;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup  = 'Hệ thống';
    protected static ?string $slug  = 'nguoi-dung';
    protected static ?string $navigationLabel  = 'Người dùng';
    use HasShieldFormComponents; // ✅ Quan trọng

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('UserTabs')
                    ->tabs([
                        Tab::make('Thông tin')
                            ->schema([
                                // ✅ Tab thông tin cơ bả
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(510),
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(510),
                                DateTimePicker::make('email_verified_at'),
                                // TextInput::make('password')
                                //     ->password()
                                //     ->required()
                                //     ->maxLength(510),
                                Select::make('roles')
                                    ->label('Vai trò')
                                    ->multiple()
                                    ->relationship('roles', 'name') // dùng quan hệ Spatie
                                    ->preload()
                                    ->searchable(),
                            ]),

                        // ✅ Tab Phân quyền (giống RoleResource)
                        Tab::make('Phân quyền')
                            ->schema([
                                ShieldSelectAllToggle::make('select_all')
                                    ->onIcon('heroicon-s-shield-check')
                                    ->offIcon('heroicon-s-shield-exclamation')
                                    ->label(__('filament-shield::filament-shield.field.select_all.name'))
                                    ->helperText(
                                        fn(): HtmlString =>
                                        new HtmlString(__('filament-shield::filament-shield.field.select_all.message'))
                                    )
                                    ->dehydrated(fn(bool $state): bool => $state)
                                    ->afterStateHydrated(function ($component, $record) {
                                        if ($record) {
                                            $directPermissions = $record->permissions->pluck('name')->toArray();
                                            $allPermissions = \Spatie\Permission\Models\Permission::pluck('name')->toArray();

                                            // ✅ Check nếu user đã có toàn bộ quyền (chỉ direct permissions)
                                            $component->state(
                                                !array_diff($allPermissions, $directPermissions)
                                            );
                                        }
                                    }),

                                static::getShieldFormComponents(), // ✅ Giao diện phân quyền giống RoleResource
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('roles')
                    ->label('Vai trò')
                    ->getStateUsing(function ($record) {
                        $roles = ($record->getRoleNames()->toArray());
                        $roles = array_diff($roles, [$record->id]);
                        return  $roles;
                    })
                    ->badge(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assignRole')
                    ->label('Gắn vai trò')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->action(function ($record) {
                        // Tạo role riêng nếu chưa có
                        if ($record->roles()->count() === 0) {
                            $roleName = 'user-' . $record->id . '-role';

                            $role = Role::firstOrCreate([
                                'name' => $roleName,
                                'guard_name' => 'web',
                            ]);

                            $record->assignRole($role);
                        } else {
                            $role = $record->roles()->first();
                        }

                        // Redirect đến trang chỉnh sửa Role
                        return redirect()->to(\App\Filament\Resources\RoleResource::getUrl('edit', ['record' => $role]));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
