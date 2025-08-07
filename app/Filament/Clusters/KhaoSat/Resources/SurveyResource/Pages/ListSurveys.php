<?php

namespace App\Filament\Clusters\KhaoSat\Resources\SurveyResource\Pages;

use App\Filament\Clusters\KhaoSat\Resources\SurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListSurveys extends ListRecords
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    public function getTitle(): string
    {
        return 'Danh sách Khảo sát';
    }
}
