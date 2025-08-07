<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Helpdesk\Ticket;

class TicketStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Thống kê trạng thái phiếu hỗ trợ';

    protected function getData(): array
    {
        $statusCounts = Ticket::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $labels = ['open' => 'Mới', 'in_progress' => 'Đang xử lý', 'resolved' => 'Đã hoàn tất'];

        return [
            'datasets' => [
                [
                    'label' => 'Số lượng ticket',
                    'data' => [
                        $statusCounts['open'] ?? 0,
                        $statusCounts['in_progress'] ?? 0,
                        $statusCounts['resolved'] ?? 0,
                    ],
                    'backgroundColor' => ['#f87171', '#facc15', '#4ade80'],
                ],
            ],
            'labels' => array_values($labels),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
