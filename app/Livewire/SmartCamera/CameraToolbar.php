<?php

namespace App\Livewire\SmartCamera;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;

class CameraToolbar extends Component
{
    public array $cameras = [];
    public ?array $selectedCamera = null;
    public bool $showCreateModal = false;
    public array $formData = [
        'name' => '',
        'camera_type' => 'ip_camera',
        'stream_url' => '',
        'location' => '',
        'description' => '',
    ];

    protected $listeners = ['refreshCameraList' => 'loadCameras'];

    public function mount($cameras = [], $selectedCamera = null): void
    {
        $this->cameras = $cameras;
        $this->selectedCamera = $selectedCamera;
    }

    public function loadCameras(): void
    {
        try {
            $response = Http::timeout(10)->get(env('SMART_CAMERA_API_URL') . '/cameras/');
            
            if ($response->ok()) {
                $this->cameras = $response->json();
                
                // Dispatch event để parent component biết data đã được update
                $this->dispatch('camerasUpdated', cameras: $this->cameras);
                
                Notification::make()
                    ->title('Đã tải lại danh sách camera')
                    ->success()
                    ->send();
            } else {
                throw new \Exception('API returned error: ' . $response->status());
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Không thể tải danh sách camera')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function openCreateCamera(): void
    {
        $this->reset('formData');
        $this->formData['camera_type'] = 'ip_camera';
        $this->showCreateModal = true;
    }

    public function saveCamera(): void
    {
        // Validate basic fields
        $this->validate([
            'formData.name' => 'required|min:3|max:50',
            'formData.stream_url' => 'required|url',
            'formData.location' => 'required|min:3|max:100',
        ]);

        try {
            $response = Http::timeout(10)->post(env('SMART_CAMERA_API_URL') . '/cameras/', $this->formData);
            
            if ($response->ok()) {
                $this->showCreateModal = false;
                $this->reset('formData');
                
                // Dispatch event để parent component biết cần refresh
                $this->dispatch('cameraCreated');
                $this->dispatch('refreshCameraList');
                
                Notification::make()
                    ->title('Thêm camera thành công')
                    ->body('Camera "' . $this->formData['name'] . '" đã được thêm.')
                    ->success()
                    ->send();
                    
                // Auto reload cameras sau khi thêm thành công
                $this->loadCameras();
                
            } else {
                $errorMessage = $response->json()['message'] ?? 'Lỗi không xác định';
                throw new \Exception($errorMessage);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors sẽ được hiển thị tự động
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Không thể thêm camera')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function closeModal(): void
    {
        $this->showCreateModal = false;
        $this->reset('formData');
    }

    public function render()
    {
        return view('livewire.smart-camera.camera-toolbar');
    }
}