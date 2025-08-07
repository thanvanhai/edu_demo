<?php

namespace App\Filament\Clusters\KPI\Resources\KpiTreeResource\Pages;

use App\Filament\Clusters\KPI\Resources\KpiTreeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditKpiTree extends EditRecord
{
    protected static string $resource = KpiTreeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl(); // 👈 Về trang danh sách
    }

    public function getHeading(): string|Htmlable
    {
        $type = $this->record->type ?? 'KPI';

        return 'Chỉnh sửa ' . match ($type) {
            'Phương diện', 'Mục tiêu', 'Tiêu chí' => $type,
            default => 'KPI',
        };
    }
}
