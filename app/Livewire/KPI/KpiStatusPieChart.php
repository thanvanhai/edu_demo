<?php

namespace App\Livewire\KPI;

use Livewire\Component;
use App\Models\KPI\{KpiAllocationTree, KpiPeriod};

class KpiStatusPieChart extends Component
{
    public ?int $departmentId = null;
    public ?int $kpiPeriodId = null;

    public array $chartData = [];

    public function mount(?int $departmentId = null, ?int $kpiPeriodId = null): void
    {
        // Gán từ prop truyền vào
        $this->departmentId = $departmentId;
        $this->kpiPeriodId = $kpiPeriodId;

        $this->updateChartData();
    }

    public function updateChartData(): void
    {
        $departmentId = $this->departmentId;
        $kpiPeriodId = $this->kpiPeriodId;

        if (empty($departmentId) || empty($kpiPeriodId)) {
            $this->chartData = [];
            return;
        }

        $query = KpiAllocationTree::query()
            ->where('department_id', $departmentId)
            ->where('kpi_period_id', $kpiPeriodId);

        $total = $query->count();

        $completed = (clone $query)->where('total_progress_percent', '>=', 85)->count();
        $inProgress = (clone $query)
            ->where('total_progress_percent', '>', 0)
            ->where('total_progress_percent', '<', 85)
            ->count();
        $notStarted = (clone $query)
            ->where(function ($q) {
                $q->whereNull('total_progress_percent')
                    ->orWhere('total_progress_percent', '=', 0);
            })->count();

        $this->chartData = [
            'labels' => [
                "Đã hoàn thành (>=85%) ({$completed}/{$total})",
                "Đang thực hiện (<85%) ({$inProgress}/{$total})",
                "Chưa thực hiện (0%) ({$notStarted}/{$total})",
            ],
            'datasets' => [[
                'data' => [
                    $total ? round(($completed / $total) * 100, 2) : 0,
                    $total ? round(($inProgress / $total) * 100, 2) : 0,
                    $total ? round(($notStarted / $total) * 100, 2) : 0,
                ],
                'backgroundColor' => ['#16a34a', '#facc15', '#e11d48'],
            ]],
        ];
         $this->dispatch('updated-chart-data', chartData: $this->chartData);       
    }
    public function render()
    {
        return view('livewire.kpi.kpi-status-pie-chart');
    }
}
