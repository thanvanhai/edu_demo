<?php

namespace App\Filament\Clusters\SmartCamera\Pages;

use App\Filament\Clusters\SmartCamera;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Filament\Pages\SubNavigationPosition;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Support\Enums\MaxWidth;
use App\Livewire\SmartCamera\CameraToolbar;
use Filament\Notifications\Notification;

class SmartCamera_Manager extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static string $view = 'filament.clusters.smart-camera.pages.smart-camera-manager';
    protected static ?string $cluster = SmartCamera::class;
    protected static ?string $title = 'Hệ thống Camera Thông minh';
    protected static ?string $navigationLabel = 'Hệ thống Camera thông minh';
    protected static ?string $slug = 'smart-camera-manager';
    protected static ?int $navigationSort = 0;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public array $cameras = [];
    public ?array $selectedCamera = null;
    public ?string $streamUrl = null;

    protected $listeners = [
        'refreshCameraList' => 'refreshData',
        'cameraUpdated' => 'refreshData',
    ];

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->loadCameras();
        $this->dispatch('$refresh');
    }

    public function loadCameras(): void
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(5)
                ->get(rtrim(env('SMART_CAMERA_API_URL'), '/') . '/cameras/');

            if ($response->ok()) {
                $this->cameras = $response->json();
            } else {
                $this->cameras = [];
                Notification::make()
                    ->title('Không thể tải danh sách camera')
                    ->body('API trả về lỗi: ' . $response->status())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->cameras = [];
            Notification::make()
                ->title('Không thể kết nối tới API Camera')
                ->body('Vui lòng kiểm tra server API và thử lại.')
                ->danger()
                ->send();
        }
    }

    public function refreshData(): void
    {
        $this->loadCameras();
        $this->dispatch('$refresh');
    }

    public function selectCamera($cameraId): void
    {
        $camera = collect($this->cameras)->firstWhere('camera_id', $cameraId);
        if ($camera) {
            $cameraId = trim($camera['camera_id']);
            $this->selectedCamera = $camera;
            $this->streamUrl = rtrim(env('SMART_CAMERA_API_URL'), '/')
                . '/cameras/' . $cameraId . '/stream';
        }
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        Livewire::make(CameraToolbar::class, [
                            'cameras' => $this->cameras,
                            'selectedCamera' => $this->selectedCamera,
                        ])->key('camera-toolbar-' . count($this->cameras)),
                    ])
                    ->columns(1)
                    ->extraAttributes(['class' => 'mb-4']),

                Section::make()
                    ->schema([
                        ViewEntry::make('camera_list')
                            ->view('livewire.smart-camera.camera-sidebar')
                            ->state(fn() => [
                                'cameras' => $this->cameras,
                                'selectedCamera' => $this->selectedCamera,
                            ])
                            ->columnSpan(3),

                        ViewEntry::make('video_player')
                            ->view('livewire.smart-camera.camera-player')
                            ->state(fn() => [
                                'selectedCamera' => $this->selectedCamera,
                                'streamUrl' => $this->streamUrl,
                            ])
                            ->columnSpan(9),
                    ])
                    ->columns(12)
                    ->extraAttributes(['class' => 'min-h-[80vh]']),
            ]);
    }
}
