<x-filament::page>
    <div class="max-w-md mx-auto">
        <livewire:kpi.kpi-status-pie-chart
            :departmentId="$this->department_id"
            :kpiPeriodId="$this->kpi_period_id"
            wire:key="kpi-status-chart-{{$this->department_id}}-{{$this->kpi_period_id}}" />
    </div>
    {{-- Form lọc --}}
    {{ $this->form }}

    {{-- Bảng phân bổ KPI --}}

    {{ $this->table }}
</x-filament::page>