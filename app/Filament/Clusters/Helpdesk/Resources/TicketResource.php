<?php

namespace App\Filament\Clusters\Helpdesk\Resources;

use App\Filament\Clusters\Helpdesk;
use App\Filament\Clusters\Helpdesk\Resources\TicketResource\Pages;
use App\Filament\Clusters\Helpdesk\Resources\TicketResource\RelationManagers;
use App\Models\Helpdesk\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\{SelectColumn, TextColumn, BadgeColumn};
use Filament\Forms\Components\{TextInput, Select, Textarea, FileUpload, DateTimePicker};
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Helpdesk\TicketCategory;
use App\Models\NhanSu\Department; // Thêm dòng này nếu chưa có
use Spatie\Activitylog\Models\Activity;
use Filament\Tables\Actions\{DeleteBulkAction, ViewAction, DeleteAction, EditAction, Action as TableAction, CreateAction};

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = Helpdesk::class;
    protected static ?string $title = 'Danh sách Phiếu hỗ trợ';
    protected static ?string $navigationLabel = 'Danh sách Phiếu hỗ trợ';
    protected static ?string $modelLabel = 'Danh sách Phiếu hỗ trợ';
    protected static ?string $pluralModelLabel = 'Danh sách Phiếu hỗ trợ';
    protected static ?string $slug = 'tickets';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Mã phiếu')
                    ->required()
                    ->disabled(fn($record) => $record !== null)
                    ->default('TCK-' . strtoupper(uniqid())),

                TextInput::make('title')
                    ->label('Tiêu đề')
                    ->required(),

                Textarea::make('description')
                    ->label('Mô tả')
                    ->autosize(),

                Select::make('priority')
                    ->label('Độ ưu tiên')
                    ->options([
                        'low' => 'Thấp',
                        'medium' => 'Trung bình',
                        'high' => 'Cao',
                        'urgent' => 'Khẩn cấp',
                    ])
                    ->default('medium')
                    ->required(),

                Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'open' => 'Mở',
                        'in_progress' => 'Đang xử lý',
                        'resolved' => 'Đã xử lý',
                        // 'closed' => 'Đã đóng',
                    ])
                    ->default('open'),

                // Select::make('user_id')
                //     ->label('Người gửi')
                //     ->relationship('user', 'name')
                //     ->searchable(),

                Select::make('assigned_to')
                    ->label('Người xử lý')
                    ->relationship('assignedTo', 'name')
                    ->searchable(),

                Select::make('category_id')
                    ->label('Loại sự cố')
                    ->relationship('category', 'name')
                    ->searchable(),

                Select::make('department_id')
                    ->label('Phòng ban')
                    ->relationship('department', 'name')
                    ->searchable(),

                FileUpload::make('attachment_path')
                    ->label('Tệp đính kèm')
                    ->directory('tickets')
                    ->maxSize(2048)
                    ->preserveFilenames(),

                DateTimePicker::make('closed_at')
                    ->label('Ngày đóng')
                    ->seconds(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Mã'),
                TextColumn::make('title')->label('Tiêu đề')->searchable(),
                TextColumn::make('category.name') // 👉 Quan hệ + trường
                    ->label('Loại sự cố')
                    ->searchable(),
                TextColumn::make('department.name') // 👉 Thêm cột phòng ban
                    ->label('Phòng ban')
                    ->searchable(),
                TextColumn::make('title')->label('Tiêu đề')->searchable(),
                TextColumn::make('description')
                    ->label('Mô tả')
                    ->searchable(),
                BadgeColumn::make('priority')->label('Ưu tiên'),
                BadgeColumn::make('status')->label('Trạng thái')->colors([
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
                    }),
                TextColumn::make('user.name')->label('Người gửi')->searchable(),
                TextColumn::make('assigned.name')->label('Người xử lý')->searchable(),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Loại sự cố')
                    ->options(TicketCategory::pluck('name', 'id')->toArray()),
                SelectFilter::make('department_id')
                    ->label('Phòng ban')
                    ->options(Department::pluck('name', 'id')->toArray()),
            ])
            ->filtersFormColumns(2) // Số cột hiển thị trong form lọc
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                TableAction::make('xem_log')
                    ->label('Lịch sử')
                    ->icon('heroicon-o-clock')
                    ->modalHeading('Lịch sử hoạt động')
                    ->modalContent(fn($record) => view('edu-log-viewer', [
                        'logs' => Activity::forSubject($record)->latest()->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
                // ->visible(fn() => static::canAction('xem-log'))
                // ->authorize(fn() => static::canAction('xem-log')),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Không có phiếu hỗ trợ nào được tìm thấy')
            ->emptyStateDescription('Không tìm thấy dữ liệu phù hợp với bộ lọc hiện tại.');
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
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
