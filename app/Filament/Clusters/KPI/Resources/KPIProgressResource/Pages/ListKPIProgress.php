<?php

namespace App\Filament\Clusters\KPI\Resources\KPIProgressResource\Pages;

use App\Filament\Clusters\KPI\Resources\KPIProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListKPIProgress extends ListRecords
{
    protected static string $resource = KPIProgressResource::class;

  
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
    public function getTitle(): string
    {
        return 'Danh sách Tiến độ KPI';
    }
}
