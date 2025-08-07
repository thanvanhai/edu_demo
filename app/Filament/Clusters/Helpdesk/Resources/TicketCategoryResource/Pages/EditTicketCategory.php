<?php

namespace App\Filament\Clusters\Helpdesk\Resources\TicketCategoryResource\Pages;

use App\Filament\Clusters\Helpdesk\Resources\TicketCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicketCategory extends EditRecord
{
    protected static string $resource = TicketCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
