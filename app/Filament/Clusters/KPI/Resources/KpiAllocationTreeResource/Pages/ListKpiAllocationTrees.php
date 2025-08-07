<?php

namespace App\Filament\Clusters\KPI\Resources\KpiAllocationTreeResource\Pages;

use App\Filament\Clusters\KPI\Resources\KpiAllocationTreeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListKpiAllocationTrees extends ListRecords
{
    protected static string $resource = KpiAllocationTreeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    public function getTitle(): string
    {
        return 'Danh sách Cây phân bổ KPI';
    }
}
