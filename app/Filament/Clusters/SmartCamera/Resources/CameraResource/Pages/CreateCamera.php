<?php
namespace App\Filament\Clusters\SmartCamera\Resources\CameraResource\Pages;

use App\Filament\Clusters\SmartCamera\Resources\CameraResource;
use App\Services\SmartCamera\CameraApiService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCamera extends CreateRecord
{
    protected static string $resource = CameraResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            $service = app(CameraApiService::class);
            $result = $service->createCamera($data);

            if ($result) {
                Notification::make()
                    ->title('Thêm camera thành công')
                    ->success()
                    ->send();

                // Create a fake model for return
                $model = new \App\Models\SmartCamera\Camera();
                $model->forceFill($result);
                $model->exists = true;
                return $model;
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi thêm camera')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        // Return empty model if failed
        return new \App\Models\SmartCamera\Camera();
    }
}