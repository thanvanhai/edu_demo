<?php

namespace App\Filament\Clusters\KhaoSat\Resources\SurveyAnswerResource\Pages;

use App\Filament\Clusters\KhaoSat\Resources\SurveyAnswerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListSurveyAnswers extends ListRecords
{
    protected static string $resource = SurveyAnswerResource::class;

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
        return 'Danh sách Câu trả lời khảo sát';
    }
}
