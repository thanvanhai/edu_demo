<x-filament::page>
    <div class="max-w-md mx-auto">
        <livewire:kpi.kpi-status-pie-chart
            :departmentId="$this->donvi_id"
            :kpiPeriodId="$this->period_id"
            wire:key="kpi-status-chart-{{$this->donvi_id}}-{{$this->period_id}}" />
    </div>
    {{ $this->form }}

    <div class="flex justify-center mb-4">
        <h2 class="text-xl font-bold text-center">
            @if ($this->donviName && $this->periodName)
            {{ $this->donviName }} ({{ $this->periodName }})
            @endif
        </h2>
    </div>

    @if (!empty($this->data))
    @php
    $columns = array_filter(array_keys((array) $data[0]), fn($col) =>
    !Str::startsWith($col, 'sort_') && $col !== 'Phương diện' && $col !== 'Mục tiêu' && $col !== 'MỤC' && $col !== 'THỨ TỰ BÁO CÁO' && $col !== 'CHỈ TIÊU ĐÁNH GIÁ (KPI)'
    );

    $rowspanMap = collect($data)->countBy('Mục tiêu');

    $renderedMuctieu = [];
    $renderedMuc = [];
    @endphp

    <div class="overflow-x-auto">
        <table class="w-full text-xs border border-gray-300 text-left table-auto">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-2 py-1 border border-gray-300 whitespace-nowrap text-xs text-center align-middle">
                        MỤC
                    </th>
                    <th class="px-2 py-1 border border-gray-300 whitespace-nowrap text-xs text-center align-middle">
                        PHƯƠNG DIỆN/<br />MỤC TIÊU
                    </th>
                    {{-- Gộp 2 cột lại thành 1 tiêu đề --}}
                    <th colspan="2" class="px-2 py-1 border text-xs text-center align-middle">
                        CHỈ TIÊU ĐÁNH GIÁ (KPI)
                    </th>
                    @foreach ($columns as $col)
                    <th class="px-2 py-1 border border-gray-300 text-xs text-center break-words max-w-[150px]">
                        {{ $col }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $row)
                <tr class="{{ $row['sort_order'] == 0 ? 'bg-gray-200 font-bold' : '' }}">
                    @php
                    $muc = $row['MỤC'];
                    $mucTieu = $row['Mục tiêu'];
                    @endphp

                    <td class="px-2 py-1 border border-gray-300 text-xs text-center align-middle whitespace-nowrap">
                        {{ $muc }}
                    </td>

                    @if (!in_array($mucTieu, $renderedMuctieu))
                    <td class="px-2 py-1 border border-gray-300 whitespace-nowrap text-xs text-center align-middle" rowspan="{{ $rowspanMap[$mucTieu] }}">
                        {{ $mucTieu }}
                    </td>
                    @endif

                    {{-- Cột 1 trong nhóm CHỈ TIÊU ĐÁNH GIÁ (KPI): Thứ tự --}}
                    <td class="px-2 py-1 border border-gray-300 text-xs text-center align-middle whitespace-nowrap">
                        {{ $row['THỨ TỰ BÁO CÁO'] ?? '' }}
                    </td>

                    {{-- Cột 2 trong nhóm CHỈ TIÊU ĐÁNH GIÁ (KPI): Nội dung --}}
                    <td class="px-2 py-1 border border-gray-300 text-xs align-top whitespace-nowrap">
                        {{ $row['CHỈ TIÊU ĐÁNH GIÁ (KPI)'] ?? '' }}
                    </td>

                    {{-- Các cột còn lại --}}
                    @foreach ($columns as $col)
                    @if ($col === 'TRỌNG SỐ MỤC TIÊU')
                    @if (!in_array($mucTieu, $renderedMuctieu))
                    <td class="px-2 py-1 border border-gray-300 text-xs text-center align-middle" rowspan="{{ $rowspanMap[$mucTieu] }}">
                        {{ $row[$col] }}
                    </td>
                    @endif
                    @else
                    <td class="px-2 py-1 border border-gray-300  whitespace-nowrap  text-xs align-top">
                        @if (Str::startsWith($col, 'Kết quả đánh giá'))
                        {{ number_format((float) $row[$col], 2) }}
                        @else
                        {{ $row[$col] }}
                        @endif
                    </td>
                    @endif
                    @endforeach

                    @php
                    $renderedMuctieu[] = $mucTieu;
                    @endphp
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-gray-500">Không có dữ liệu để hiển thị.</p>
    @endif
</x-filament::page>