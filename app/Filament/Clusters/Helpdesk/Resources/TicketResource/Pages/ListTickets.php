<?php

namespace App\Filament\Clusters\Helpdesk\Resources\TicketResource\Pages;

use App\Filament\Clusters\Helpdesk\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

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
        return 'Danh sách Phiếu hỗ trợ';
    }
}
