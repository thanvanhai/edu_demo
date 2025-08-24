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

class SmartCamera_Manager extends Page implements HasInfolists
{
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static string $view = 'filament.clusters.smart-camera.pages.smart-camera-manager';
    protected static ?string $cluster = SmartCamera::class;
    protected static ?string $title = 'Hệ thống Camera Thông minh';
    protected static ?string $navigationLabel = 'Hệ thống Camera thông minh';
    protected static ?string $slug = 'smart-camera-manager';
    protected static ?int $navigationSort = 0;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    use InteractsWithInfolists;

    public array $cameras = [];
    public ?array $selectedCamera = null;
    public ?string $streamUrl = null;
    protected $listeners = [
        'refreshCameraList' => 'refreshData',
        'cameraUpdated' => 'refreshData'
    ];

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->loadCameras(); // Load lại data

        // Force refresh các ViewEntry
        $this->dispatch('$refresh');
    }
    public function loadCameras(): void
    {
        $response = Http::get(env('SMART_CAMERA_API_URL') . '/cameras/');
        if ($response->ok()) {
            $this->cameras = $response->json();
        }
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
                // Toolbar: dùng Livewire component để quản lý modal
                Section::make()
                    ->schema([
                        Livewire::make(CameraToolbar::class, [
                            'cameras' => $this->cameras,
                            'selectedCamera' => $this->selectedCamera,
                        ])
                            ->key('camera-toolbar-' . count($this->cameras)), // Force re-render khi cameras thay đổi,
                    ])
                    ->columns(1)
                    ->extraAttributes(['class' => 'mb-4']),

                // Layout dưới: camera list + player (vẫn dùng Blade view)
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
