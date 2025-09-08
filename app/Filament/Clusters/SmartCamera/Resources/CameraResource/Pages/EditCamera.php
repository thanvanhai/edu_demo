<?php

namespace App\Filament\Clusters\SmartCamera\Resources\CameraResource\Pages;

use App\Filament\Clusters\SmartCamera\Resources\CameraResource;
use App\Services\SmartCamera\CameraApiService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCamera extends EditRecord
{
    protected static string $resource = CameraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->action(function () {
                    try {
                        $service = app(CameraApiService::class);
                        $success = $service->deleteCamera($this->record->id);

                        if ($success) {
                            Notification::make()
                                ->title('Xóa thành công')
                                ->success()
                                ->send();

                            return redirect($this->getResource()::getUrl('index'));
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Lỗi xóa camera')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            $service = app(CameraApiService::class);
            $result = $service->updateCamera($record->id, $data);

            if ($result) {
                Notification::make()
                    ->title('Cập nhật thành công')
                    ->success()
                    ->send();

                $record->forceFill($result);
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi cập nhật')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return $record;
    }
}