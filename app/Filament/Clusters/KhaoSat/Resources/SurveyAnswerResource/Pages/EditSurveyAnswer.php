<?php

namespace App\Filament\Clusters\KhaoSat\Resources\SurveyAnswerResource\Pages;

use App\Filament\Clusters\KhaoSat\Resources\SurveyAnswerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSurveyAnswer extends EditRecord
{
    protected static string $resource = SurveyAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
