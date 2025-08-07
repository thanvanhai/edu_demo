<?php

namespace App\Filament\Clusters\KPI\Resources\KPIProgressResource\Pages;

use App\Filament\Clusters\KPI\Resources\KPIProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\UploadedFile;

class EditKPIProgress extends EditRecord
{
    protected static string $resource = KPIProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }

    public function getHeading(): string|Htmlable
    {
        $name = $this->record->name ?? 'KPI';
        return 'Chỉnh sửa tiến độ ' . $name;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $storedFiles = [];

        // File mới upload
        $uploadedFiles = request()->file('evidences');
        if (is_array($uploadedFiles)) {
            foreach ($uploadedFiles as $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $storedFiles[] = [
                        'path' => $file->store('KPI', 'public'),
                        'original_name' => $file->getClientOriginalName(),
                    ];
                }
            }
        }

        // Các file cũ từ form (mảng path)
        if (isset($data['evidences']) && is_array($data['evidences'])) {
            foreach ($data['evidences'] as $item) {
                if (is_string($item)) {
                    $storedFiles[] = [
                        'path' => $item,
                        'original_name' => basename($item),
                    ];
                } elseif (is_array($item) && isset($item['path'], $item['original_name'])) {
                    $storedFiles[] = $item;
                }
            }
        }

        $data['evidences'] = $storedFiles;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
