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

    protected $listeners = ['refreshCameraList'];

    public function mount($cameras = [], $selectedCamera = null): void
    {
        $this->cameras = $cameras;
        $this->selectedCamera = $selectedCamera;
    }

    public function refreshCameraList(): void
    {
        // Toolbar không load dữ liệu trực tiếp, page sẽ làm việc này
    }

    public function openCreateCamera(): void
    {
        $this->reset('formData');
        $this->formData['camera_type'] = 'ip_camera';
        $this->showCreateModal = true;
    }

    public function saveCamera(): void
    {
        $this->validate([
            'formData.name' => 'required|min:3|max:50',
            'formData.stream_url' => 'required|url',
            'formData.location' => 'required|min:3|max:100',
        ]);

        try {
            $response = Http::timeout(10)->post(env('SMART_CAMERA_API_URL') . '/cameras/', $this->formData);

            if ($response->ok()) {
                $cameraName = $this->formData['name']; // Lưu tạm
                $this->showCreateModal = false;
                $this->reset('formData');

                // Dispatch event để page xử lý reload
                $this->dispatch('cameraCreated');
                $this->dispatch('refreshCameraList');

                Notification::make()
                    ->title('Thêm camera thành công')
                    ->body('Camera "' . $cameraName . '" đã được thêm.')
                    ->success()
                    ->send();

            } else {
                $errorMessage = $response->json()['message'] ?? 'Lỗi không xác định';
                throw new \Exception($errorMessage);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Livewire sẽ hiển thị validation errors tự động
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
