<?php

namespace App\Filament\Clusters\KPI\Resources\KpiAllocationTreeResource\Pages;

use App\Filament\Clusters\KPI\Resources\KpiAllocationTreeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditKpiAllocationTree extends EditRecord
{
    protected static string $resource = KpiAllocationTreeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    public function getHeading(): string|Htmlable
    {
        $name = $this->record->name ?? 'KPI';
        return 'Chỉnh sửa ' . $name;
    }
}
