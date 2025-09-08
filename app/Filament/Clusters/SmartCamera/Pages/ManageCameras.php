<?php

namespace App\Filament\Clusters\SmartCamera\Pages;

use App\Filament\Clusters\SmartCamera;
use App\Models\SmartCamera\Camera;
use App\Services\SmartCamera\CameraApiService;
use Filament\Forms\Components\{TextInput, Toggle, Select, Textarea};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\{Action as TableAction, EditAction, DeleteAction, ViewAction, BulkAction};
use Filament\Tables\Columns\{TextColumn, IconColumn, BadgeColumn};
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\{Filter, SelectFilter, TernaryFilter};
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class ManageCameras extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $cluster = SmartCamera::class;
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static string $view = 'filament.clusters.smart-camera.pages.manage-cameras';
    protected static ?string $navigationLabel = 'Quản lý Camera';
    protected static ?string $title = 'Danh sách Camera';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'camerasmmm';
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    // Properties for handling API data
    public ?Collection $apiCameras = null;
    public bool $isLoading = false;
    public string $searchTerm = '';

    public function mount(): void
    {
        $this->loadCameras();
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    /**
     * Load cameras from API
     */
    #[Computed]
    public function loadCameras(): void
    {
        $this->isLoading = true;
        
        try {
            $service = app(CameraApiService::class);
            $cameras = $service->getCameras();
            
            $this->apiCameras = collect($cameras)->map(function ($camera) {
                return (object) array_merge($camera, [
                    'status_badge' => $camera['isActive'] ? 'Hoạt động' : 'Offline',
                    'status_color' => $camera['isActive'] ? 'success' : 'danger',
                    'created_at' => $camera['createdAt'] ?? now(),
                    'updated_at' => $camera['updatedAt'] ?? now(),
                ]);
            });

        } catch (\Exception $e) {
            $this->apiCameras = collect();
            
            Notification::make()
                ->title('Lỗi kết nối API')
                ->body('Không thể tải danh sách camera: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
        
        $this->isLoading = false;
    }

    /**
     * Get table query - using collection instead of Eloquent
     */
    public function getTableQuery(): Builder
    {
        // Return empty builder since we're using collection
        return Camera::query()->whereRaw('1 = 0');
    }

    /**
     * Get table records from API
     */
    public function getTableRecords(): EloquentCollection
    {
        if ($this->apiCameras === null) {
            $this->loadCameras();
        }

        // Convert collection to Eloquent models for Filament compatibility
        return new EloquentCollection(
            $this->apiCameras->map(function ($camera) {
                $model = new Camera();
                $model->forceFill((array) $camera);
                $model->exists = true;
                return $model;
            })
        );
    }

    /**
     * Configure the table
     */
    public function table(Table $table): Table
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

                BadgeColumn::make('status_badge')
                    ->label('Trạng thái')
                    ->colors([
                        'success' => 'Hoạt động',
                        'danger' => 'Offline',
                    ])
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('isActive')
                    ->label('Trạng thái')
                    ->placeholder('Tất cả')
                    ->trueLabel('Hoạt động')
                    ->falseLabel('Offline')
                    ->queries(
                        true: fn (Builder $query) => $query->where('isActive', true),
                        false: fn (Builder $query) => $query->where('isActive', false),
                    ),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Tạo từ ngày'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Tạo đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->headerActions([
                TableAction::make('create')
                    ->label('Thêm Camera')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form($this->getCameraForm())
                    ->action(fn (array $data) => $this->createCamera($data))
                    ->successNotificationTitle('Camera đã được thêm thành công'),

                TableAction::make('refresh')
                    ->label('Làm mới')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(fn () => $this->refreshData())
                    ->successNotificationTitle('Đã làm mới danh sách'),

                TableAction::make('test_connection')
                    ->label('Kiểm tra kết nối API')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(fn () => $this->testApiConnection()),
            ])
            ->actions([
                ViewAction::make()
                    ->form($this->getCameraForm())
                    ->modalHeading('Chi tiết Camera'),

                EditAction::make()
                    ->form($this->getCameraForm())
                    ->fillForm(fn ($record): array => [
                        'name' => $record->name,
                        'location' => $record->location,
                        'rtspUrl' => $record->rtspUrl,
                        'isActive' => $record->isActive ?? true,
                        'description' => $record->description ?? '',
                    ])
                    ->using(fn ($record, array $data) => $this->updateCamera($record, $data))
                    ->successNotificationTitle('Camera đã được cập nhật'),

                TableAction::make('test_camera')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(fn ($record) => $this->testCameraConnection($record))
                    ->requiresConfirmation()
                    ->modalHeading('Kiểm tra kết nối Camera')
                    ->modalDescription('Bạn có muốn kiểm tra kết nối đến camera này?'),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Xác nhận xóa Camera')
                    ->modalDescription('Bạn có chắc chắn muốn xóa camera này? Hành động này không thể hoàn tác.')
                    ->successNotificationTitle('Camera đã được xóa')
                    ->action(fn ($record) => $this->deleteCamera($record)),
            ])
            ->bulkActions([
                BulkAction::make('activate')
                    ->label('Kích hoạt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Collection $records) => $this->bulkActivate($records))
                    ->requiresConfirmation(),

                BulkAction::make('deactivate')
                    ->label('Tắt')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(fn (Collection $records) => $this->bulkDeactivate($records))
                    ->requiresConfirmation(),

                BulkAction::make('delete')
                    ->label('Xóa')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(fn (Collection $records) => $this->bulkDelete($records))
                    ->requiresConfirmation()
                    ->modalHeading('Xóa nhiều camera')
                    ->modalDescription('Bạn có chắc chắn muốn xóa các camera đã chọn?'),
            ])
            ->defaultSort('id', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->deferLoading()
            ->emptyStateHeading('Không có camera nào')
            ->emptyStateDescription('Bắt đầu bằng cách thêm camera đầu tiên của bạn.')
            ->emptyStateIcon('heroicon-o-video-camera')
            ->emptyStateActions([
                TableAction::make('create_first')
                    ->label('Thêm Camera đầu tiên')
                    ->url(fn (): string => static::getUrl())
                    ->icon('heroicon-o-plus'),
            ]);
    }

    /**
     * Get camera form schema
     */
    private function getCameraForm(): array
    {
        return [
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
                ->helperText('URL để truy cập stream camera (bắt đầu bằng rtsp://)')
                ->rules([
                    'regex:/^rtsp:\/\/.*/',
                ]),

            Toggle::make('isActive')
                ->label('Kích hoạt Camera')
                ->default(true)
                ->helperText('Camera sẽ được giám sát khi được kích hoạt'),

            Textarea::make('description')
                ->label('Mô tả')
                ->rows(3)
                ->placeholder('Mô tả chi tiết về camera...')
                ->helperText('Thông tin bổ sung về camera (tùy chọn)'),
        ];
    }

    /**
     * Create new camera
     */
    private function createCamera(array $data): void
    {
        try {
            $service = app(CameraApiService::class);
            $result = $service->createCamera($data);

            if ($result) {
                $this->refreshData();
                
                Notification::make()
                    ->title('Thành công')
                    ->body("Camera '{$data['name']}' đã được thêm thành công")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi khi thêm camera')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Update camera
     */
    private function updateCamera($record, array $data): void
    {
        try {
            $service = app(CameraApiService::class);
            $result = $service->updateCamera($record->id, $data);

            if ($result) {
                $this->refreshData();
                
                Notification::make()
                    ->title('Cập nhật thành công')
                    ->body("Camera '{$data['name']}' đã được cập nhật")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi khi cập nhật')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Delete camera
     */
    private function deleteCamera($record): void
    {
        try {
            $service = app(CameraApiService::class);
            $success = $service->deleteCamera($record->id);

            if ($success) {
                $this->refreshData();
                
                Notification::make()
                    ->title('Xóa thành công')
                    ->body("Camera '{$record->name}' đã được xóa")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi khi xóa')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Test API connection
     */
    private function testApiConnection(): void
    {
        try {
            $service = app(CameraApiService::class);
            $isConnected = $service->testConnection();

            if ($isConnected) {
                Notification::make()
                    ->title('Kết nối thành công')
                    ->body('API đang hoạt động bình thường')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Kết nối thất bại')
                    ->body('Không thể kết nối đến API')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi kết nối')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Test individual camera connection
     */
    private function testCameraConnection($record): void
    {
        try {
            $service = app(CameraApiService::class);
            $result = $service->testCameraConnection($record->id);

            if ($result['success']) {
                Notification::make()
                    ->title('Kết nối camera thành công')
                    ->body($result['message'] ?? "Camera '{$record->name}' đang hoạt động bình thường")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Kết nối camera thất bại')
                    ->body($result['message'] ?? 'Không thể kết nối đến camera')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi kiểm tra camera')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Bulk activate cameras
     */
    private function bulkActivate(Collection $records): void
    {
        try {
            $service = app(CameraApiService::class);
            $updates = $records->map(fn ($record) => [
                'id' => $record->id,
                'data' => ['isActive' => true]
            ])->toArray();

            $results = $service->batchUpdateCameras($updates);
            $successful = collect($results)->where('success', true)->count();

            $this->refreshData();

            Notification::make()
                ->title('Kích hoạt thành công')
                ->body("Đã kích hoạt {$successful}/{$records->count()} camera")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi kích hoạt')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Bulk deactivate cameras
     */
    private function bulkDeactivate(Collection $records): void
    {
        try {
            $service = app(CameraApiService::class);
            $updates = $records->map(fn ($record) => [
                'id' => $record->id,
                'data' => ['isActive' => false]
            ])->toArray();

            $results = $service->batchUpdateCameras($updates);
            $successful = collect($results)->where('success', true)->count();

            $this->refreshData();

            Notification::make()
                ->title('Tắt thành công')
                ->body("Đã tắt {$successful}/{$records->count()} camera")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi khi tắt')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Bulk delete cameras
     */
    private function bulkDelete(Collection $records): void
    {
        $successful = 0;
        $service = app(CameraApiService::class);

        foreach ($records as $record) {
            try {
                if ($service->deleteCamera($record->id)) {
                    $successful++;
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Log::error("Failed to delete camera {$record->id}: " . $e->getMessage());
            }
        }

        $this->refreshData();

        Notification::make()
            ->title('Xóa hoàn tất')
            ->body("Đã xóa {$successful}/{$records->count()} camera")
            ->success()
            ->send();
    }

    /**
     * Refresh data from API
     */
    private function refreshData(): void
    {
        $this->apiCameras = null;
        $this->loadCameras();
        
        // Reset table to refresh display
        $this->resetTable();
    }

    /**
     * Get page actions
     */
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('statistics')
                ->label('Thống kê')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->action(fn () => $this->showStatistics()),
        ];
    }

    /**
     * Show API statistics
     */
    private function showStatistics(): void
    {
        try {
            $service = app(CameraApiService::class);
            $stats = $service->getStatistics();

            $message = "📊 **Thống kê Camera**\n\n" .
                      "• Tổng số camera: **{$stats['total_cameras']}**\n" .
                      "• Camera hoạt động: **{$stats['active_cameras']}**\n" .
                      "• Camera offline: **{$stats['offline_cameras']}**\n" .
                      "• Tổng số recording: **{$stats['total_recordings']}**\n" .
                      "• Recording hôm nay: **{$stats['today_recordings']}**\n\n" .
                      "🔄 Cập nhật lúc: {$stats['last_updated']}";

            Notification::make()
                ->title('Thống kê hệ thống')
                ->body($message)
                ->info()
                ->duration(8000)
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi lấy thống kê')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}