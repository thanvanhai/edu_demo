<?php

namespace App\Filament\Clusters\KhaoSat\Resources\SurveyResource\Pages;

use App\Filament\Clusters\KhaoSat\Resources\SurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSurvey extends EditRecord
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
