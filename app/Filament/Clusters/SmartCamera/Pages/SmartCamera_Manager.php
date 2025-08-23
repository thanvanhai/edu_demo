<?php

namespace App\Filament\Clusters\SmartCamera\Pages;

use App\Filament\Clusters\SmartCamera;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Filament\Pages\SubNavigationPosition;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;

class SmartCamera_Manager extends Page implements HasInfolists
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
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

    public function mount(): void
    {
        $response = Http::get(env('SMART_CAMERA_API_URL') . '/cameras/');
        if ($response->ok()) {
            $this->cameras = $response->json();
        }
    }

    public function selectCamera($cameraId): void
    {
        // Lấy thông tin camera từ danh sách hiện có
        $camera = collect($this->cameras)->firstWhere('camera_id', $cameraId);
        if ($camera) {
            $this->selectedCamera = $camera;

            // Build URL stream đúng với backend
            $this->streamUrl = env('SMART_CAMERA_API_URL')
                . '/cameras/'
                . $camera['camera_id']
                . '/stream';
        }
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([
                'cameras' => $this->cameras,
                'selectedCamera' => $this->selectedCamera,
                'streamUrl' => $this->streamUrl
            ])
            ->schema([
                Section::make()
                    ->schema([
                        // CỘT TRÁI: Danh sách Camera
                        ViewEntry::make('camera_list')
                            ->view('livewire.smart-camera.camera-sidebar')
                            ->state([
                                'cameras' => $this->cameras,
                                'selectedCamera' => $this->selectedCamera
                            ])
                            ->columnSpan(3),

                        // CỘT PHẢI: Video Player + Controls
                        ViewEntry::make('video_player')
                            ->view('livewire.smart-camera.camera-player')
                            ->state([
                                'selectedCamera' => $this->selectedCamera,
                                'streamUrl' => $this->streamUrl
                            ])
                            ->columnSpan(9),
                    ])
                    ->columns(12)
                    ->extraAttributes(['class' => 'min-h-[80vh]']),
            ]);
    }
}
