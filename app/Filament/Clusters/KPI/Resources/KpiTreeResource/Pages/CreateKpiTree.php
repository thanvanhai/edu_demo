<?php

namespace App\Filament\Clusters\KPI\Resources\KpiTreeResource\Pages;

use App\Filament\Clusters\KPI\Resources\KpiTreeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateKpiTree extends CreateRecord
{
    protected static string $resource = KpiTreeResource::class;

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl(); // 👈 Về trang danh sách
    }

    public function getHeading(): string|Htmlable
    {
        $type = request()->query('type', 'KPI');

        return 'Tạo mới ' . match ($type) {
            'Phương diện', 'Mục tiêu', 'Tiêu chí' => $type,
            default => 'KPI',
        };
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Lưu') // Nút "Lưu và tạo mới"
                ->color('success')
                ->icon('heroicon-o-plus')
                ->submit('createForm'), // ✅ submit đúng form mặc định tên là `createForm`
            Actions\Action::make('cancel')
                ->label('Hủy bỏ')
                ->color('primary')
                ->url(static::$resource::getUrl())
                ->icon('heroicon-o-arrow-uturn-left'),
        ];
    }
}
