<?php

namespace App\Filament\Clusters\SmartCamera\Resources;

use App\Filament\Clusters\SmartCamera;
use App\Models\SmartCamera\Camera;
use App\Services\SmartCamera\CameraApiService;
use Filament\Forms\Components\{TextInput, Toggle, Textarea};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\{EditAction, DeleteAction, ViewAction, Action};
use Filament\Tables\Columns\{TextColumn, IconColumn, BadgeColumn};
use Filament\Tables\Filters\{TernaryFilter};
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Filament\Clusters\SmartCamera\Resources\CameraResource\Pages;
use Filament\Pages\SubNavigationPosition;

class CameraResource extends Resource
{
    protected static ?string $model = Camera::class;
    protected static ?string $cluster = SmartCamera::class;
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'Danh sách Camera';
    protected static ?string $modelLabel = 'Camera';
    protected static ?string $pluralModelLabel = 'Cameras';
    protected static ?int $navigationSort = 2;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Tên Camera')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('VD: Camera sảnh chính')
                    ->helperText('Nhập tên để dễ nhận diện camera'),

                TextInput::make('location')
                    ->label('Vị trí')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('VD: Tầng 1 - Sảnh chính')
                    ->helperText('Mô tả vị trí đặt camera'),

                TextInput::make('rtspUrl')
                    ->label('RTSP URL')
                    ->required()
                    ->url()
                    ->placeholder('rtsp://192.168.1.100:554/stream')
                    ->helperText('URL để truy cập stream camera')
                    ->rules(['regex:/^rtsp:\/\/.*/']),

                Toggle::make('isActive')
                    ->label('Kích hoạt Camera')
                    ->default(true)
                    ->helperText('Camera sẽ được giám sát khi được kích hoạt'),

                Textarea::make('description')
                    ->label('Mô tả')
                    ->rows(3)
                    ->placeholder('Mô tả chi tiết về camera...')
                    ->helperText('Thông tin bổ sung (tùy chọn)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Tên Camera')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                TextColumn::make('location')
                    ->label('Vị trí')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                TextColumn::make('rtspUrl')
                    ->label('RTSP URL')
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    })
                    ->copyable()
                    ->copyMessage('Đã copy RTSP URL'),

                IconColumn::make('isActive')
                    ->label('Trạng thái')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('isActive')
                    ->label('Trạng thái')
                    ->placeholder('Tất cả')
                    ->trueLabel('Hoạt động')
                    ->falseLabel('Offline'),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Xem'),

                EditAction::make()
                    ->label('Sửa'),

                Action::make('test_camera')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function ($record) {
                        try {
                            $service = app(CameraApiService::class);
                            $result = $service->testCameraConnection($record->id);

                            if ($result['success']) {
                                Notification::make()
                                    ->title('Kết nối thành công')
                                    ->body($result['message'] ?? 'Camera hoạt động bình thường')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Kết nối thất bại')
                                    ->body($result['message'] ?? 'Không thể kết nối camera')
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Lỗi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                DeleteAction::make()
                    ->label('Xóa'),
            ])
            ->headerActions([
                Action::make('refresh')
                    ->label('Làm mới')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        Notification::make()
                            ->title('Đã làm mới')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCameras::route('/'),
            'create' => Pages\CreateCamera::route('/create'),
            // 'view' => Pages\ViewCamera::route('/{record}'),
            'edit' => Pages\EditCamera::route('/{record}/edit'),
        ];
    }
}
