<?php

namespace App\Filament\Clusters\KPI\Pages;

use App\Filament\Clusters\KPI;
use App\Filament\Clusters\KPI\Resources\KpiTreeResource;
use App\Models\KPI\{KpiAllocation, KpiCriteria, KpiPeriod};
use App\Models\NhanSu\Department;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Actions\ButtonAction;
use Filament\Forms\Components\{Select, DatePicker, Grid, Actions};
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Filament\Forms\Components\Actions\Action;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Alignment, Font, NumberFormat, Color};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class KPIReportPage extends Page  implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.clusters.k-p-i.pages.k-p-i-report-page';
    protected static ?string $cluster = KPI::class;
    protected static ?string $title = 'Báo cáo tổng hợp KPI';
    protected static ?string $navigationLabel = 'Báo cáo tổng hợp';
    protected static ?string $slug = 'kpi-reports';
    protected static ?int $navigationSort = 6;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start
    use InteractsWithForms;
    public array $departments = [];
    public ?int $donvi_id = null;
    public ?int $period_id = null;
    public ?string $periodName = null;
    public ?string $donviName = null;

    public static function canAccess(): bool
    {
        return userCan('xem báo cáo kpi', KpiTreeResource::class);
    }

    public array $data = [];

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->resetFilters();
    }

    public function resetFilters(): void
    {
        $user = Auth::user();
        $nhansu = optional($user->NhanSu);

        $this->departments =  Department::pluck('name', 'id')->toArray();

        $this->period_id = KpiPeriod::latest('id')->value('id');
        $this->donvi_id = array_key_first($this->departments);

        $this->form->fill([
            'period_id' => $this->period_id,
            'donvi_id' => $this->donvi_id, // đúng key trong form
        ]);

        $this->data = []; // hoặc $this->treeData = [] nếu bạn dùng tree
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)->schema([
                    Select::make('period_id')
                        ->label('Kỳ đánh giá')
                        ->options(KPIPeriod::pluck('name', 'id'))
                        ->required()
                        ->live()
                        ->searchable()
                        ->columnSpan(2)
                        ->default(fn() => KpiPeriod::orderByDesc('id')->value('id')),

                    Select::make('donvi_id')
                        ->label('Phòng ban')
                        ->options($this->departments)
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn($state) => $this->donvi_id = $state),
                ]),

                Actions::make([
                    Action::make('xem')
                        ->label('Xem báo cáo')
                        ->color('primary')
                        ->icon('heroicon-o-magnifying-glass')
                        ->action(fn() => $this->loadData()),

                    Action::make('reset')
                        ->label('Xóa lọc')
                        ->color('info')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn() => $this->resetFilters()),

                    Action::make('export')
                        ->label('Xuất Excel')
                        ->color('success')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action('exportExcel'),
                ]),
            ]);
    }

    public function loadData()
    {
        // Nếu chưa chọn kỳ hoặc phòng ban thì không làm gì
        if (empty($this->period_id) || empty($this->donvi_id)) {
            $this->data = []; // Xoá dữ liệu cũ nếu có
            Notification::make()
                ->title('Report tổng hợp KPI Đơn vị')
                ->body('Vui lòng chọn đầy đủ Kỳ đánh giá và Phòng ban để xem báo cáo')
                ->danger()
                ->send();
            return;
        }

        $this->periodName = KPIPeriod::find($this->period_id)?->name;
        $this->donviName = Department::find($this->donvi_id)?->name;

        // $results = DB::connection('admin')->select('EXEC sp_kpi_report_pivot_dynamic_title @department_id = ?, @period_id = ?', [
        //     $this->donvi_id,
        //     $this->period_id,
        // ]);
        $results = DB::connection('sqlsrv')->select('EXEC sp_kpi_report_pivot_from_tree @department_id = ?, @period_id = ?', [
            $this->donvi_id,
            $this->period_id,
        ]);
        $this->data = collect($results)->map(fn($row) => (array) $row)->toArray();
    }

    public function getColumns(): array
    {
        return !empty($this->data)
            ? array_keys($this->data[0])
            : [];
    }

    public function exportExcel()
    {
        if (empty($this->data)) {
            Notification::make()
                ->title('Report tổng hợp KPI Đơn vị')
                ->body('Không có dữ liệu để xuất!')
                ->danger()
                ->send();
            return;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Báo cáo KPI');

        // 1. Các cột cố định
        $staticHeaders = [
            'MỤC',
            'PHƯƠNG DIỆN/ MỤC TIÊU',
            'THỨ TỰ BÁO CÁO',
            'CHỈ TIÊU ĐÁNH GIÁ (KPI)',
            'CHỈ TIÊU',
            'ĐƠN VỊ TÍNH',
            'TRỌNG SỐ PHƯƠNG DIỆN',
            'TRỌNG SỐ MỤC TIÊU',
            'TRỌNG SỐ KPI',
        ];

        // 2. Tạo danh sách các cột động theo tháng
        $columns = array_keys($this->data[0]);

        $dynamicMonths = collect($columns)
            ->filter(fn($col) => str_starts_with($col, 'Mô tả kết quả thực hiện '))
            ->map(fn($col) => trim(Str::after($col, 'Mô tả kết quả thực hiện ')))
            ->values()
            ->unique()
            ->toArray();

        $dynamicCols = [];
        foreach ($dynamicMonths as $month) {
            $dynamicCols[] = [
                'desc' => "Mô tả kết quả thực hiện $month",
                'percent' => "Tỷ lệ % kết quả thực hiện được so với chỉ tiêu KPI $month",
                'score' => "Kết quả đánh giá $month",
            ];
        }

        // === HEADER ===
        $col = 1;

        // MỤC
        $sheet->mergeCellsByColumnAndRow($col, 8, $col, 10);
        $sheet->setCellValueByColumnAndRow($col++, 8, 'MỤC');

        // PHƯƠNG DIỆN / MỤC TIÊU
        $sheet->mergeCellsByColumnAndRow($col, 8, $col, 10);
        $sheet->setCellValueByColumnAndRow($col++, 8, 'PHƯƠNG DIỆN/ MỤC TIÊU');

        // CHỈ TIÊU ĐÁNH GIÁ (KPI) – trộn 2 cột (Thứ tự & Nội dung KPI)
        $sheet->mergeCellsByColumnAndRow($col, 8, $col + 1, 10);
        $sheet->setCellValueByColumnAndRow($col, 8, 'CHỈ TIÊU ĐÁNH GIÁ (KPI)');
        $col += 2; // nhảy qua 2 cột: THỨ TỰ và NỘI DUNG

        // CHỈ TIÊU
        $sheet->mergeCellsByColumnAndRow($col, 8, $col, 10);
        $sheet->setCellValueByColumnAndRow($col++, 8, 'CHỈ TIÊU');

        // ĐƠN VỊ TÍNH
        $sheet->mergeCellsByColumnAndRow($col, 8, $col, 10);
        $sheet->setCellValueByColumnAndRow($col++, 8, 'ĐƠN VỊ TÍNH');

        // TRỌNG SỐ PHƯƠNG DIỆN
        $sheet->mergeCellsByColumnAndRow($col, 8, $col, 10);
        $sheet->setCellValueByColumnAndRow($col++, 8, 'TRỌNG SỐ PHƯƠNG DIỆN');

        // TRỌNG SỐ MỤC TIÊU
        $sheet->mergeCellsByColumnAndRow($col, 8, $col, 10);
        $sheet->setCellValueByColumnAndRow($col++, 8, 'TRỌNG SỐ MỤC TIÊU');

        // TRỌNG SỐ KPI
        $sheet->mergeCellsByColumnAndRow($col, 8, $col, 10);
        $sheet->setCellValueByColumnAndRow($col++, 8, 'TRỌNG SỐ KPI');

        // Ghi lại vị trí bắt đầu của các cột động
        $startDynamicCol = $col;

        // KẾT QUẢ THỰC HIỆN KPI (gộp dòng 8)
        $totalDynamicCols = count($dynamicCols) * 3;
        $sheet->mergeCellsByColumnAndRow($startDynamicCol, 8, $startDynamicCol + $totalDynamicCols - 1, 8);
        $sheet->setCellValueByColumnAndRow($startDynamicCol, 8, 'KẾT QUẢ THỰC HIỆN KPI');

        // Dòng 9: Tên các tháng
        $monthCol = $startDynamicCol;
        foreach ($dynamicCols as $group) {
            $sheet->mergeCellsByColumnAndRow($monthCol, 9, $monthCol + 2, 9);
            $sheet->setCellValueByColumnAndRow($monthCol, 9, Str::after($group['desc'], 'Mô tả kết quả thực hiện '));
            $monthCol += 3;
        }

        // Dòng 10: Mô tả – Tỷ lệ – Kết quả
        $col = $startDynamicCol;
        foreach ($dynamicCols as $group) {
            $sheet->setCellValueByColumnAndRow($col++, 10, 'Mô tả');
            $sheet->setCellValueByColumnAndRow($col++, 10, 'Tỷ lệ %');
            $sheet->setCellValueByColumnAndRow($col++, 10, 'Kết quả đánh giá');
        }

        // Style header
        $lastCol = $col - 1;
        $sheet->getStyle("A8:" . Coordinate::stringFromColumnIndex($lastCol) . "10")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEEEEEE'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        // === DỮ LIỆU ===
        $rowIdx = 11;
        $renderedMuctieu = [];
        $rowspanMap = collect($this->data)
            ->filter(fn($item) => $item['sort_order'] != 0)
            ->countBy('Mục tiêu');

        foreach ($this->data as $item) {
            $col = 1;

            if ($item['sort_order'] == 0) {
                // Dòng nhóm MỤC (cấp cao nhất)
                $sheet->setCellValueByColumnAndRow($col++, $rowIdx, $item['MỤC']);
                //$sheet->setCellValueByColumnAndRow($col++, $rowIdx, $item['Phương diện']);
                $sheet->setCellValueByColumnAndRow($col++, $rowIdx, $item['Mục tiêu']); // Mục tiêu
                $sheet->setCellValueByColumnAndRow($col++, $rowIdx, ''); // THỨ TỰ BÁO CÁO
                $sheet->setCellValueByColumnAndRow($col++, $rowIdx, ''); // KPI
                $sheet->setCellValueByColumnAndRow($col++, $rowIdx, ''); // Chỉ tiêu
                $sheet->setCellValueByColumnAndRow($col++, $rowIdx, ''); // Đơn vị tính

                // Trọng số phương diện
                $value = isset($item['TRỌNG SỐ PHƯƠNG DIỆN']) ? $item['TRỌNG SỐ PHƯƠNG DIỆN'] / 100 : null;
                $sheet->setCellValueByColumnAndRow($col, $rowIdx, $value);
                $sheet->getStyleByColumnAndRow($col, $rowIdx)
                    ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                $col++;

                // Trống tiếp các cột
                $sheet->setCellValueByColumnAndRow($col++, $rowIdx, ''); // Trọng số Mục tiêu
                $sheet->setCellValueByColumnAndRow($col++, $rowIdx, ''); // Trọng số KPI

                // Cột động theo tháng
                foreach ($dynamicCols as $group) {
                    // Cột mô tả: để trống
                    $sheet->setCellValueByColumnAndRow($col++, $rowIdx, '');
                    // Cột tỷ lệ %: để trống
                    $sheet->setCellValueByColumnAndRow($col++, $rowIdx, '');
                    // Cột kết quả đánh giá: hiển thị tổng nếu có
                    $sum = $item[$group['score']] ?? null; // giả sử đã tính tổng sẵn ở DB
                    if ($sum !== null) {
                        $valScore = (float)$sum / 100;
                        $sheet->setCellValueByColumnAndRow($col, $rowIdx, $valScore);
                        $sheet->getStyleByColumnAndRow($col, $rowIdx)
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                    } else {
                        $sheet->setCellValueByColumnAndRow($col, $rowIdx, '');
                    }
                    $col++;
                }

                // Tô nền dòng MỤC
                $sheet->getStyle("A{$rowIdx}:" . Coordinate::stringFromColumnIndex($col - 1) . "{$rowIdx}")
                    ->applyFromArray([
                        'font' => ['bold' => true,  'color' => ['argb' => Color::COLOR_WHITE],],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF228B22']],
                    ]);

                $rowIdx++;
                continue;
            }

            // Dữ liệu thường
            $sheet->setCellValueByColumnAndRow($col++, $rowIdx, $item['MỤC']);
            //$sheet->setCellValueByColumnAndRow($col++, $rowIdx, $item['Phương diện']);

            // Mục tiêu (merge nếu chưa render)
            if (!in_array($item['Mục tiêu'], $renderedMuctieu)) {
                $span = $rowspanMap[$item['Mục tiêu']];
                $sheet->mergeCellsByColumnAndRow($col, $rowIdx, $col, $rowIdx + $span - 1);
                $sheet->setCellValueByColumnAndRow($col, $rowIdx, $item['Mục tiêu']);
                // Cho phép xuống dòng + căn giữa
                $style = $sheet->getStyleByColumnAndRow($col, $rowIdx)->getAlignment();
                $style->setWrapText(true);
                $style->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $style->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
            $col++;

            $sheet->setCellValueByColumnAndRow($col++, $rowIdx, $item['THỨ TỰ BÁO CÁO']);

            $text = str_replace('|', "\n", $item['CHỈ TIÊU ĐÁNH GIÁ (KPI)'] ?? '');
            $sheet->setCellValueByColumnAndRow($col, $rowIdx, $text);
            $sheet->getStyleByColumnAndRow($col, $rowIdx)
                ->getAlignment()
                ->setWrapText(true);
            $col++;
            $sheet->setCellValueByColumnAndRow($col++, $rowIdx, $item['CHỈ TIÊU'] ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $rowIdx, $item['ĐƠN VỊ TÍNH'] ?? '');

            // Trọng số phương diện
            $val = isset($item['TRỌNG SỐ PHƯƠNG DIỆN']) ? $item['TRỌNG SỐ PHƯƠNG DIỆN'] / 100 : null;
            $sheet->setCellValueByColumnAndRow($col, $rowIdx, $val);
            $sheet->getStyleByColumnAndRow($col, $rowIdx)
                ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
            $col++;

            // Trọng số mục tiêu (merge theo Mục tiêu)
            if (!in_array($item['Mục tiêu'], $renderedMuctieu)) {
                $span = $rowspanMap[$item['Mục tiêu']];
                $sheet->mergeCellsByColumnAndRow($col, $rowIdx, $col, $rowIdx + $span - 1);

                $val = isset($item['TRỌNG SỐ MỤC TIÊU']) ? $item['TRỌNG SỐ MỤC TIÊU'] / 100 : null;
                $sheet->setCellValueByColumnAndRow($col, $rowIdx, $val);
                $sheet->getStyleByColumnAndRow($col, $rowIdx)
                    ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
            }
            $col++;

            // Trọng số KPI
            $val = isset($item['TRỌNG SỐ KPI']) ? $item['TRỌNG SỐ KPI'] / 100 : null;
            $sheet->setCellValueByColumnAndRow($col, $rowIdx, $val);
            $sheet->getStyleByColumnAndRow($col, $rowIdx)
                ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
            $col++;

            // Cột động tháng
            foreach ($dynamicCols as $group) {
                // Cột Mô tả
                $sheet->setCellValueByColumnAndRow($col, $rowIdx, $item[$group['desc']] ?? '');
                $sheet->getStyleByColumnAndRow($col, $rowIdx)
                    ->getAlignment()->setWrapText(true);
                $col++;

                // Cột Tỷ lệ %
                $value = isset($item[$group['percent']]) ? (float)$item[$group['percent']] / 100 : null;
                $sheet->setCellValueByColumnAndRow($col, $rowIdx, $value);
                $sheet->getStyleByColumnAndRow($col, $rowIdx)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                $col++;

                // Cột Kết quả đánh giá
                $valScore = isset($item[$group['score']]) ? (float)$item[$group['score']] / 100 : null;
                $sheet->setCellValueByColumnAndRow($col, $rowIdx, $valScore);
                $sheet->getStyleByColumnAndRow($col, $rowIdx)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                $col++;
            }

            $renderedMuctieu[] = $item['Mục tiêu']; // Chỉ đánh dấu sau khi xử lý merge
            $rowIdx++;
        }

        // Auto width
        // foreach (range(1, $lastCol) as $c) {
        //     $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        // }
        // Thiết lập độ rộng cột cố định
        $columnWidths = [
            'A' => 6,    // MỤC
            'B' => 25,   // PHƯƠNG DIỆN/ MỤC TIÊU
            'C' => 6,   // THỨ TỰ BÁO CÁO
            'D' => 50,   // CHỈ TIÊU ĐÁNH GIÁ (KPI)
            'E' => 8,   // CHỈ TIÊU
            'F' => 12,   //ĐƠN VỊ TÍNH
            'G' => 8,   // TRỌNG SỐ PHƯƠNG DIỆN
            'H' => 8,   //  TRỌNG SỐ MỤC TIÊU
            'I' => 8,   // TRỌNG SỐ KPI
        ];

        foreach ($columnWidths as $colLetter => $width) {
            $sheet->getColumnDimension($colLetter)->setWidth($width);
        }

        // Cột động (bắt đầu từ cột J => index = 10)
        // Cột động (bắt đầu từ cột J = index 10)
        $startDynamicCol = 10;
        $dynamicColCount = count($dynamicCols) * 3;

        for ($i = 0; $i < $dynamicColCount; $i++) {
            $colIndex = $startDynamicCol + $i;

            // Tính loại cột: mô tả / tỷ lệ / đánh giá
            $positionInGroup = $i % 3;
            $width = match ($positionInGroup) {
                0 => 20,  // Mô tả
                1 => 8,  // Tỷ lệ %
                2 => 8,  // Kết quả đánh giá
            };

            $sheet->getColumnDimensionByColumn($colIndex)->setWidth($width);
        }
        // Ghi file
        $filename = 'bao_cao_kpi_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = storage_path("app/public/KPI/{$filename}");

        (new Xlsx($spreadsheet))->save($tempPath);
        return response()->download($tempPath)->deleteFileAfterSend();
    }
}
