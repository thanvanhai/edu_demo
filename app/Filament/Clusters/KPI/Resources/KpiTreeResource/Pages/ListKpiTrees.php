<?php

namespace App\Filament\Clusters\KPI\Resources\KpiTreeResource\Pages;

use App\Filament\Clusters\KPI\Resources\KpiTreeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListKpiTrees extends ListRecords
{
    protected static string $resource = KpiTreeResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Ẩn tất cả nút ở đầu trang
    }
    
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    public function getTitle(): string
    {
        return 'Danh sách Cây Danh mục KPI';
    }
}
