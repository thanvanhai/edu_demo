<?php

namespace App\Filament\Clusters\Helpdesk\Resources;

use App\Filament\Clusters\Helpdesk;
use App\Filament\Clusters\Helpdesk\Resources\TicketCategoryResource\Pages;
use App\Filament\Clusters\Helpdesk\Resources\TicketCategoryResource\RelationManagers;
use App\Models\Helpdesk\TicketCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Columns\{SelectColumn, TextColumn, BadgeColumn};
use Filament\Forms\Components\{TextInput};
use Filament\Notifications\Notification;
use Spatie\Activitylog\Models\Activity;
use Filament\Tables\Actions\{DeleteBulkAction, BulkActionGroup, DeleteAction, EditAction, Action as TableAction, CreateAction};

class TicketCategoryResource extends Resource
{
    protected static ?string $model = TicketCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = Helpdesk::class;
    protected static ?string $title = 'Danh sách Loại sự cố';
    protected static ?string $navigationLabel = 'Danh sách Loại sự cố';
    protected static ?string $slug = 'ticket-categories';
    protected static ?int $navigationSort = 0;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start


    public static function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Tên loại sự cố')
                ->required()
                ->maxLength(255),

            TextInput::make('description')
                ->label('Mô tả')
                ->maxLength(255),
        ];
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tên loại')->searchable(),
                TextColumn::make('description')->label('Mô tả')->wrap(),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('d/m/Y H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Sửa loại sự cố'),
                Tables\Actions\DeleteAction::make(),
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
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Thêm loại sự cố')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->modalHeading('Thêm loại sự cố')
                    ->modalButton('Lưu loại sự cố')
                    ->createAnother(false) // 🔴 tắt nút "Lưu và tạo tiếp" mặc định
                    ->successNotification(
                        Notification::make()
                            ->title('Thành công')
                            ->body('Loại sự cố đã được thêm thành công.')
                            ->success()
                            ->icon('heroicon-o-check-circle')
                    ),
            ])
            ->emptyStateHeading('Không có loại sự cố nào được tìm thấy')
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
            'index' => Pages\ListTicketCategories::route('/'),
            //'create' => Pages\CreateTicketCategory::route('/create'),
            //'edit' => Pages\EditTicketCategory::route('/{record}/edit'),
        ];
    }
}
