<?php

namespace App\Filament\Clusters\SmartCamera\Resources\CameraResource\Pages;

use App\Filament\Clusters\SmartCamera\Resources\CameraResource;
use App\Models\SmartCamera\Camera;
use App\Services\SmartCamera\CameraApiService;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Filament\Notifications\Notification;

class ListCameras extends ListRecords
{
    protected static string $resource = CameraResource::class;

    public function getTableQuery(): Builder
    {
        // Dummy query để Filament không query DB cameras_fake
        return Camera::query()->whereRaw('1=0');
    }

    public function getTableRecords(): EloquentCollection
    {
        $service = app(CameraApiService::class);

        try {
            $cameras = $service->getCameras(); // trả về mảng camera từ API
            return new EloquentCollection(
                collect($cameras)->map(fn ($cam) => new Camera($cam))
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Không tải được danh sách camera')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return new EloquentCollection();
        }
    }
}
