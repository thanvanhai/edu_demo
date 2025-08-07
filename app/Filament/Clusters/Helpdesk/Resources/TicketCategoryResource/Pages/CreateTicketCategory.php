<?php

namespace App\Filament\Clusters\Helpdesk\Resources\TicketCategoryResource\Pages;

use App\Filament\Clusters\Helpdesk\Resources\TicketCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketCategory extends CreateRecord
{
    protected static string $resource = TicketCategoryResource::class;
}
