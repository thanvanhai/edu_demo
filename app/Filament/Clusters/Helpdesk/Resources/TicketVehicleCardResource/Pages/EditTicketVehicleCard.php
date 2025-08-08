<?php

namespace App\Filament\Clusters\Helpdesk\Resources\TicketVehicleCardResource\Pages;

use App\Filament\Clusters\Helpdesk\Resources\TicketVehicleCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicketVehicleCard extends EditRecord
{
    protected static string $resource = TicketVehicleCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
