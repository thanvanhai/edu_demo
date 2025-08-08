<?php

namespace App\Filament\Clusters\Helpdesk\Resources;

use App\Filament\Clusters\Helpdesk;
use App\Filament\Clusters\Helpdesk\Resources\TicketVehicleCardResource\Pages;
use App\Filament\Clusters\Helpdesk\Resources\TicketVehicleCardResource\RelationManagers;
use App\Models\Helpdesk\TicketVehicleCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\{SelectColumn, TextColumn, BadgeColumn, ImageColumn};
use Filament\Forms\Components\{TextInput, Select, Textarea, FileUpload, DateTimePicker};
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Filters\SelectFilter;

class TicketVehicleCardResource extends Resource
{
    protected static ?string $model = TicketVehicleCard::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $cluster = Helpdesk::class;
    protected static ?string $title = 'Danh sách Phiếu đăng ký thẻ xe';
    protected static ?string $navigationLabel = 'Danh sách Phiếu đăng ký thẻ xe';
    protected static ?string $slug = 'ticket-vehicle-cards';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('ticket_id')
                ->label('Mã phiếu')
                ->relationship('ticket', 'code')
                ->searchable()
                ->required(),
            TextInput::make('employee_code')
                ->label('Mã nhân viên')
                ->required(),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required(),

            TextInput::make('full_name')
                ->label('Họ tên')
                ->required(),

            TextInput::make('phone')
                ->label('Số điện thoại')
                ->tel()
                ->required(),

            TextInput::make('vehicle_type')
                ->label('Loại xe')
                ->required(),

            TextInput::make('vehicle_color')
                ->label('Màu xe')
                ->required(),

            TextInput::make('plate_number')
                ->label('Biển số')
                ->required(),

            Textarea::make('note')
                ->label('Ghi chú'),

            FileUpload::make('id_card_image')
                ->label('Ảnh CMND/CCCD')
                ->image()
                ->directory('vehicle_cards/id_cards'),

            FileUpload::make('vehicle_image')
                ->label('Ảnh xe')
                ->image()
                ->directory('vehicle_cards/vehicles'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket.code')
                    ->label('Mã phiếu')
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('ticket.status')
                    ->label('Trạng thái')
                    ->colors([
                        'primary' => 'open',
                        'warning' => 'in_progress',
                        'success' => 'resolved',
                        'gray' => 'closed',
                    ])
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'open'        => 'Mở',
                            'in_progress' => 'Đang xử lý',
                            'resolved'    => 'Đã xử lý',
                            'closed'      => 'Đóng',
                            default       => ucfirst($state),
                        };
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('ticket.department.name')
                    ->label('Phòng ban')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('full_name')->label('Họ tên')->searchable(),
                TextColumn::make('employee_code')->label('Mã NV')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')->label('Ngày tạo')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('phone')->label('SĐT')->searchable(),
                TextColumn::make('vehicle_type')->label('Loại xe'),
                TextColumn::make('vehicle_color')->label('Màu xe'),
                TextColumn::make('plate_number')->label('Biển số')->searchable(),
                TextColumn::make('note')->label('Ghi chú')->limit(30),
                ImageColumn::make('id_card_image')
                    ->label('CMND/CCCD')
                    ->square()
                    ->size(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('vehicle_image')
                    ->label('Hình Xe')
                    ->square()
                    ->size(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('ticket_status')
                    ->label('Trạng thái')
                    ->options([
                        'open'        => 'Mở',
                        'in_progress' => 'Đang xử lý',
                        'resolved'    => 'Đã xử lý',
                        'closed'      => 'Đóng',
                    ])
                    ->query(function ($query, $data) {
                        if (! isset($data['value']) || $data['value'] === 'all') {
                            return $query; // không lọc
                        }
                        return $query->whereHas('ticket', function ($q) use ($data) {
                            $q->where('status', $data['value']);
                        });
                    }),
            ])
            ->filtersFormColumns(2) // Số cột hiển thị trong form lọc
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTicketVehicleCards::route('/'),
            'create' => Pages\CreateTicketVehicleCard::route('/create'),
            'edit' => Pages\EditTicketVehicleCard::route('/{record}/edit'),
        ];
    }
}
