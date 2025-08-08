<?php

namespace App\Filament\Clusters\Helpdesk\Resources\TicketVehicleCardResource\Pages;

use App\Filament\Clusters\Helpdesk\Resources\TicketVehicleCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListTicketVehicleCards extends ListRecords
{
    protected static string $resource = TicketVehicleCardResource::class;

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
        return 'Danh sách Phiếu đăng ký thẻ xe';
    }
}
