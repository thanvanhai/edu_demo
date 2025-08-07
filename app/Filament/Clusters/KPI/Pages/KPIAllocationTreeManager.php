<?php

namespace App\Filament\Clusters\KPI\Pages;

use App\Filament\Clusters\KPI;
use App\Filament\Clusters\KPI\Resources\KpiTreeResource;
use Filament\Pages\Page;
use App\Models\KPI\{KPITree, KpiPeriod, KpiAllocationTree};
use App\Models\NhanSu\Department;
use Filament\Forms\Form;
use Filament\Forms\Components\{Select, Toggle, Actions};
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Pages\SubNavigationPosition;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Filament\Forms\Components\FileUpload;

class KPIAllocationTreeManager extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static string $view = 'filament.clusters.k-p-i.pages.k-p-i-allocation-tree-manager';
    protected static ?string $cluster = KPI::class;
    protected static ?string $title = 'Phân bổ KPI';
    protected static ?string $navigationLabel = 'Phân bổ';
    protected static ?string $slug = 'kpi-allocationtree';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public ?int $kpi_period_id = null;
    public ?int $department_id = null;
    public array $selected_phuongdien = [];
    public array $treeData = [];
    public ?bool $export_selected_only = true;
    public ?string $import_file = null;
    public bool $isLocked = false;
    public bool $onlyAllocated = false;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public static function canAccess(): bool
    {
        return userCan('xem phân bổ kpi', KpiTreeResource::class);
    }

    public static function canAction(string $action): bool
    {
        return match ($action) {
            'thêm' => userCan('cập nhật phân bổ kpi', KpiTreeResource::class),
            'sửa' => userCan('cập nhật phân bổ kpi', KpiTreeResource::class),
            'xóa' => userCan('cập nhật phân bổ kpi', KpiTreeResource::class),
            default => false,
        };
    }
    public function mount(): void
    {
        $this->form->fill(
            [
                'kpi_period_id' => KpiPeriod::latest('id')->first()?->id,
                'department_id' => $this->department_id,
                'selected_phuongdien' => [],
                'export_selected_only' => true,
                'onlyAllocated' => false,
            ]
        );
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('kpi_period_id')
                ->label('Kỳ đánh giá')
                ->options(KpiPeriod::pluck('name', 'id'))
                ->disabled(fn() => $this->isLocked)
                ->required(),

            Select::make('department_id')
                ->label('Phòng ban')
                ->options(Department::pluck('name', 'id'))
                ->disabled(fn() => $this->isLocked)
                ->required(),

            Select::make('selected_phuongdien')
                ->label('Phương diện')
                ->multiple()
                ->searchable()
                ->placeholder('Chọn phương diện để phân bổ KPI')
                ->options(
                    KPITree::where('type', 'Phương diện')->pluck('name', 'id')
                )
                ->required()
                ->disabled(fn() => $this->isLocked),

            Toggle::make('export_selected_only')
                ->label('Chỉ xuất các dòng đã chọn')
                ->default(true),

            Toggle::make('onlyAllocated')
                ->label('Chỉ hiển thị các dòng đã phân bổ')
                ->default(false),

            Actions::make([
                Action::make('load')
                    ->label('Bắt đầu phân bổ')
                    ->action(fn() => $this->loadTree())
                    ->visible(fn() => !$this->isLocked),

                Action::make('clear')
                    ->label('Xóa dữ liệu / Chỉnh sửa bộ lọc')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn() => $this->clearTree())
                    ->visible(fn() => $this->isLocked),

                Action::make('export_template')
                    ->label('Xuất Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->action(fn() => $this->exportTemplate())
                    ->visible(fn() => $this->isLocked),

                Action::make('import_template')
                    ->label('Nhập từ Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->visible(fn() => $this->canAction('thêm'))
                    ->authorize(fn() => $this->canAction('thêm'))
                    ->form([
                        FileUpload::make('import_file')
                            ->label('Chọn file Excel')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required()
                            ->disk('public')
                            ->directory('imports')
                            ->preserveFilenames(),
                    ])
                    ->modalHeading('Chọn file để nhập dữ liệu KPI')
                    ->modalSubmitActionLabel('Thực hiện nhập')
                    ->action(function (array $data): void {
                        if (empty($data['import_file'])) {
                            Notification::make()
                                ->title('Vui lòng chọn file để import')
                                ->danger()
                                ->send();
                            return;
                        }
                        // Gọi lại hàm importTemplate, truyền tên file
                        $this->importTemplate($data['import_file']);
                    })
                    ->visible(fn() => $this->isLocked),

                Action::make('save_allocation')
                    ->label('Lưu phân bổ')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn() => $this->save())
                    ->visible(fn() => $this->isLocked && $this->canAction('thêm'))
                    ->authorize(fn() => $this->canAction('thêm')),
            ]),
        ]);
    }
    public function clearTree(): void
    {
        $this->treeData = [];
        $this->form->fill([
            'kpi_period_id' => KpiPeriod::latest('id')->first()?->id,
            'department_id' => null,
            'selected_phuongdien' => [],
            'export_selected_only' => true,
            'onlyAllocated' => false,
        ]);
        $this->isLocked = false;
    }
    public function loadTree(): void
    {
        $state = $this->form->getState();
        $this->isLocked = true;
        $this->kpi_period_id = $state['kpi_period_id'] ?? null;
        $this->department_id = $state['department_id'] ?? null;
        $this->selected_phuongdien = $state['selected_phuongdien'] ?? [];
        $this->onlyAllocated = $state['onlyAllocated'] ?? false;

        $this->treeData = [];

        if ($this->onlyAllocated) {
            // Lấy allocation đã phân bổ thuộc các phương diện đã chọn, theo thứ tự id tăng dần (hoặc trường order nếu có)
            $savedAllocations = KpiAllocationTree::where('kpi_period_id', $this->kpi_period_id)
                ->where('department_id', $this->department_id)
                ->whereHas('kpiTree', function ($q) {
                    $q->whereIn('id', $this->selected_phuongdien)
                        ->orWhereIn('parent_id', $this->selected_phuongdien)
                        ->orWhereHas('parent', function ($q2) {
                            $q2->whereIn('parent_id', $this->selected_phuongdien);
                        });
                })
                ->defaultOrder()
                ->get();

            // Build treeData trực tiếp từ $savedAllocations
            $this->treeData = [];
            foreach ($savedAllocations as $item) {
                $this->treeData[] = [
                    'selected' => true,
                    'sortcode' => $item->sortcode,
                    'name' => $item->name,
                    'type' => $item->type,
                    'allocated_value' => $item->allocated_value,
                    'unit' => $item->unit,
                    'weight_direction' => $item->weight_direction,
                    'weight_objective' => $item->weight_objective,
                    'weight_kpi' => $item->weight_kpi,
                    'note' => $item->note,
                    'depth' => $item->depth,
                    'id' => $item->kpi_tree_id,
                ];
            }
        } else {
            // Lấy toàn bộ cây treo KPITree như cũ
            $savedAllocations = KpiAllocationTree::where('kpi_period_id', $this->kpi_period_id)
                ->where('department_id', $this->department_id)
                ->get()
                ->keyBy('kpi_tree_id');

            $kpiTreeList = KPITree::with(['children.children' => fn($q) => $q->where('type', 'Tiêu chí')])
                ->whereIn('id', $this->selected_phuongdien)
                ->defaultOrder()
                ->get();

            foreach ($kpiTreeList as $rootNode) {
                $this->treeData = array_merge($this->treeData, $this->transformTree([$rootNode], 0, $savedAllocations));
            }
        }
    }

    protected function transformTree($nodes, $depth = 0, $savedAllocations = null, $onlyAllocated = false): array
    {
        $output = [];

        foreach ($nodes as $node) {
            $hasAllocation = $savedAllocations && $savedAllocations->has($node->id);

            // Nếu chỉ lấy các dòng đã phân bổ mà node này chưa phân bổ thì bỏ qua
            if ($onlyAllocated && !$hasAllocation) {
                // Tuy nhiên, nếu node cha chưa phân bổ nhưng có con đã phân bổ thì vẫn phải duyệt con
                if ($node->children->isNotEmpty()) {
                    $output = array_merge($output, $this->transformTree($node->children, $depth + 1, $savedAllocations, $onlyAllocated));
                }
                continue;
            }

            $data = [
                'selected' => $hasAllocation,
                'sortcode' => $hasAllocation ? $savedAllocations[$node->id]->sortcode : null, // Map sortcode từ allocationtree,
                'name' => $node->name,
                'type' => $node->type,
                'allocated_value' => $hasAllocation ? $savedAllocations[$node->id]->allocated_value : null,
                'unit' => $hasAllocation ? $savedAllocations[$node->id]->unit : $node->unit,
                'weight_direction' => $hasAllocation ? $savedAllocations[$node->id]->weight_direction : null,
                'weight_objective' => $hasAllocation ? $savedAllocations[$node->id]->weight_objective : null,
                'weight_kpi' => $hasAllocation ? $savedAllocations[$node->id]->weight_kpi : null,
                'note' => $hasAllocation ? $savedAllocations[$node->id]->note : '',
                'depth' => $depth,
                'id' => $node->id,
            ];

            $output[] = $data;

            if ($node->children->isNotEmpty()) {
                $output = array_merge($output, $this->transformTree($node->children, $depth + 1, $savedAllocations, $onlyAllocated));
            }
        }

        return $output;
    }

    protected function propagateWeightToChildren($index, $field)
    {
        $depth = $this->treeData[$index]['depth'];
        $newValue = $this->treeData[$index][$field] ?? null;

        for ($i = $index + 1; $i < count($this->treeData); $i++) {
            if ($this->treeData[$i]['depth'] <= $depth) break;
            $this->treeData[$i][$field] = $newValue;
        }
    }
    // Logic chọn cha/con
    public function updatedTreeData($value, $key): void
    {
        // Giới hạn trọng số không vượt quá 100
        if (
            str_contains($key, 'weight_direction') ||
            str_contains($key, 'weight_objective') ||
            str_contains($key, 'weight_kpi')
        ) {
            if (is_numeric($value) && ($value > 100 || $value < 0)) {
                $value = max(0, min(100, $value));
                data_set($this->treeData, $key, $value);

                // Tách index và field để gán lỗi đúng
                $parts = explode('.', $key);
                if (count($parts) >= 3) {
                    $index = $parts[0];
                    $field = $parts[2];
                    $this->addError("treeData.$index.$field", 'Giá trị phải nằm trong khoảng 0 - 100%');
                }
            }
        }
        // Phần xử lý checkbox
        if (str_ends_with($key, '.selected')) {
            $parts = explode('.', $key);
            $index = (int) $parts[0];
            $selected = $this->treeData[$index]['selected'] ?? false;

            $this->toggleChildrenSelection($index, $selected);

            if ($selected) {
                $this->selectParentIfChildSelected($index);
            } else {
                $this->unselectParentIfAllChildrenUnselected($index);
            }
        }

        // ✅ Xử lý cập nhật trọng số phương diện → con
        if (str_ends_with($key, '.weight_direction')) {
            $parts = explode('.', $key);
            $this->propagateWeightToChildren((int) $parts[0], 'weight_direction');
        }
        // ✅ Xử lý cập nhật trọng số mục tiêu → con
        if (str_ends_with($key, '.weight_objective')) {
            $parts = explode('.', $key);
            $this->propagateWeightToChildren((int) $parts[0], 'weight_objective');
        }
    }

    // Chọn hoặc bỏ chọn tất cả con khi chọn/bỏ chọn cha
    protected function toggleChildrenSelection(int $index, bool $selected): void
    {
        //logger('toggleChildrenSelection', ['index' => $index, 'selected' => $selected]);
        $depth = $this->treeData[$index]['depth'];
        for ($i = $index + 1; $i < count($this->treeData); $i++) {
            if ($this->treeData[$i]['depth'] <= $depth) break;
            $this->treeData[$i]['selected'] = $selected;
        }
        $this->treeData = array_values($this->treeData);
        //logger('after toggleChildrenSelection', ['treeData' => $this->treeData]);
    }

    // Khi chọn con, cha phải được chọn
    protected function selectParentIfChildSelected(int $index): void
    {
        //logger('selectParentIfChildSelected', ['index' => $index]);
        $depth = $this->treeData[$index]['depth'];
        for ($i = $index - 1; $i >= 0; $i--) {
            if ($this->treeData[$i]['depth'] < $depth) {
                $this->treeData[$i]['selected'] = true;
                $depth = $this->treeData[$i]['depth'];
            }
            if ($depth === 0) break;
        }
        $this->treeData = array_values($this->treeData);
        //logger('after selectParentIfChildSelected', ['treeData' => $this->treeData]);
    }

    // Khi bỏ chọn con, nếu tất cả con đều bỏ thì cha cũng bỏ chọn
    protected function unselectParentIfAllChildrenUnselected(int $index): void
    {
        //logger('unselectParentIfAllChildrenUnselected', ['index' => $index]);
        $depth = $this->treeData[$index]['depth'];
        for ($i = $index - 1; $i >= 0; $i--) {
            if ($this->treeData[$i]['depth'] < $depth) {
                $parentIndex = $i;
                $parentDepth = $this->treeData[$parentIndex]['depth'];
                $allUnselected = true;
                for ($j = $parentIndex + 1; $j < count($this->treeData); $j++) {
                    if ($this->treeData[$j]['depth'] <= $parentDepth) break;
                    if ($this->treeData[$j]['depth'] == $parentDepth + 1 && ($this->treeData[$j]['selected'] ?? false)) {
                        $allUnselected = false;
                        break;
                    }
                }
                if ($allUnselected) {
                    $this->treeData[$parentIndex]['selected'] = false;
                    $this->unselectParentIfAllChildrenUnselected($parentIndex);
                }
                break;
            }
        }
        $this->treeData = array_values($this->treeData);
        //logger('after unselectParentIfAllChildrenUnselected', ['treeData' => $this->treeData]);
    }

    public function exportTemplate(): StreamedResponse
    {
        $headers = [
            'Chọn import',
            'Chỉ mục sắp xếp',
            'Tên KPI',
            'Loại',
            'Chỉ tiêu',
            'Đơn vị tính',
            'Trọng số Phương diện',
            'Trọng số Mục tiêu',
            'Trọng số KPI',
            'Ghi chú',
        ];

        $data = $this->treeData;

        if ($this->export_selected_only) {
            $data = array_filter($data, fn($row) => $row['selected'] ?? false);
        }

        // Map lại từng dòng cho đúng thứ tự cột
        $data = array_map(function ($row) {
            return [
                $row['selected'] ?? false,
                $row['sortcode'] ?? '',
                $row['name'] ?? '',
                $row['type'] ?? '',
                $row['allocated_value'] ?? '',
                $row['unit'] ?? '',
                $row['weight_direction'] ?? '',
                $row['weight_objective'] ?? '',
                $row['weight_kpi'] ?? '',
                $row['note'] ?? '',
            ];
        }, $data);

        array_unshift($data, $headers);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'template_kpi_allocation.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }

    protected function buildTreeExport($nodes, $depth = 0): array
    {
        $rows = [];
        foreach ($nodes as $node) {
            $rows[] = [
                'selected' => false,
                'depth' => $depth,
                'name' => $node->name,
                'type' => $node->type,
                'unit' => $node->unit,
                'weight_direction' => null,
                'weight_objective' => null,
                'weight_kpi' => null,
                'allocated_value' => null,
                'note' => '',
            ];
            if ($node->children->isNotEmpty()) {
                $rows = array_merge($rows, $this->buildTreeExport($node->children, $depth + 1));
            }
        }
        return $rows;
    }

    public function save(): void
    {
        DB::transaction(function () {
            $prevNodeByDepth = [];
            $lastNodeByDepth = [];
            foreach ($this->treeData as $index => $row) {
                if (!($row['selected'] ?? false)) {
                    continue;
                }
                // Tìm hoặc tạo node
                $record = KpiAllocationTree::firstOrCreate([
                    'kpi_tree_id' => $row['id'],
                    'department_id' => $this->department_id,
                    'kpi_period_id' => $this->kpi_period_id,
                ]);
                // Cập nhật dữ liệu khác
                $record->fill([
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'allocated_value' => $row['allocated_value'],
                    'weight_direction' => $row['weight_direction'],
                    'weight_objective' => $row['weight_objective'],
                    'weight_kpi' => $row['weight_kpi'],
                    'unit' => $row['unit'],
                    'note' => $row['note'],
                    'depth' => $row['depth'],
                    'status' => 0,
                    'sortcode' => $row['sortcode'] ?? null,
                ]);
                $record->save();

                $depth = $row['depth'];
                $parentId = $lastNodeByDepth[$depth - 1] ?? null;

                if ($parentId) {
                    $parent = KpiAllocationTree::find($parentId);
                    if (!isset($prevNodeByDepth[$depth])) {
                        // Node đầu tiên cùng cấp: gán cha
                        if ($record->parent_id !== $parent->id) {
                            $record->appendToNode($parent)->save();
                        }
                    } else {
                        // Node tiếp theo cùng cấp: sắp xếp sau node trước đó (không gán cha nữa)
                        $prevNode = KpiAllocationTree::find($prevNodeByDepth[$depth]);
                        if ($prevNode && $record->id !== $prevNode->id) {
                            $record->afterNode($prevNode)->save();
                        }
                    }
                } else {
                    // Node gốc
                    if (!isset($prevNodeByDepth[$depth])) {
                        if (!$record->isRoot()) {
                            $record->saveAsRoot();
                        }
                    } else {
                        // Node gốc tiếp theo: sắp xếp sau node gốc trước đó
                        $prevNode = KpiAllocationTree::find($prevNodeByDepth[$depth]);
                        if ($prevNode && $record->id !== $prevNode->id) {
                            $record->afterNode($prevNode)->save();
                        }
                    }
                }

                // Cập nhật node trước cùng depth cho lần lặp tiếp theo
                $prevNodeByDepth[$row['depth']] = $record->id;
                $lastNodeByDepth[$row['depth']] = $record->id;

                // Xóa các cha ở depth lớn hơn (khi chuyển sang node mới cùng hoặc thấp hơn)
                foreach (array_keys($lastNodeByDepth) as $d) {
                    if ($d > $depth) {
                        unset($lastNodeByDepth[$d]);
                        unset($prevNodeByDepth[$d]);
                    }
                }
            }
        });

        Notification::make()->title('Phân bổ KPI thành công')->success()->send();
    }

    public function importTemplate(string $file)
    {
        $path = Storage::disk('public')->path($file);
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        array_shift($rows); // Bỏ dòng tiêu đề

        // Tạo map name => index trong treeData
        $nameMap = collect($this->treeData)
            ->mapWithKeys(fn($row, $index) => [trim($row['name']) => $index]);

        $currentDirectionRow = null;
        $currentObjectiveRow = null;

        foreach ($rows as $row) {
            $selected = (bool)$row[0];
            $sortcode = $row[1] ?? '';
            $name = trim($row[2]);
            $type = trim($row[3]);
            $allocated_value = $row[4] ?? '';
            $unit = $row[5] ?? '';
            $weight_direction = is_numeric($row[6]) ? $row[6] : null;
            $weight_objective = is_numeric($row[7]) ? $row[7] : null;
            $weight_kpi = is_numeric($row[8]) ? $row[8] : null;
            $note = $row[9] ?? '';

            if (!$name || !$nameMap->has($name)) {
                continue; // Bỏ nếu không khớp
            }

            $index = $nameMap[$name];
            $old = $this->treeData[$index];

            // Ghi nhớ dòng cha gần nhất
            if ($type === 'Phương diện') {
                $currentDirectionRow = [
                    'weight_direction' => $weight_direction,
                ];
                $currentObjectiveRow = null;
            } elseif ($type === 'Mục tiêu') {
                $currentObjectiveRow = [
                    'weight_objective' => $weight_objective,
                ];
            }

            // Gán lại giá trị trọng số
            $this->treeData[$index] = [
                ...$old,
                'selected' => $selected,
                'sortcode' => $sortcode,
                'allocated_value' => $allocated_value,
                'unit' => $unit,
                'note' => $note,
                'weight_direction' => match ($type) {
                    'Phương diện' => $weight_direction,
                    'Mục tiêu'    => $currentDirectionRow['weight_direction'] ?? null,
                    'Tiêu chí'    => $currentDirectionRow['weight_direction'] ?? null,
                    default       => null,
                },
                'weight_objective' => match ($type) {
                    'Mục tiêu' => $weight_objective,
                    'Tiêu chí' => $currentObjectiveRow['weight_objective'] ?? null,
                    default    => null,
                },
                'weight_kpi' => $type === 'Tiêu chí' ? $weight_kpi : null,
            ];
        }

        Notification::make()->title('Import thành công')->success()->send();
    }


    public function moveBranchUp($index)
    {
        $currentDepth = $this->treeData[$index]['depth'];
        // Xác định nhánh hiện tại (node + toàn bộ con)
        $end = $index;
        for ($i = $index + 1; $i < count($this->treeData); $i++) {
            if ($this->treeData[$i]['depth'] <= $currentDepth) break;
            $end = $i;
        }
        $branch = array_slice($this->treeData, $index, $end - $index + 1);

        // Tìm node trước cùng cấp để chèn lên trên
        for ($prev = $index - 1; $prev >= 0; $prev--) {
            if ($this->treeData[$prev]['depth'] == $currentDepth) {
                // Xác định nhánh trước đó
                $prevStart = $prev;
                for ($j = $prev - 1; $j >= 0; $j--) {
                    if ($this->treeData[$j]['depth'] < $currentDepth) break;
                    if ($this->treeData[$j]['depth'] == $currentDepth) $prevStart = $j;
                }
                $prevEnd = $prev;
                for ($j = $prev + 1; $j < $index; $j++) {
                    if ($this->treeData[$j]['depth'] <= $currentDepth) break;
                    $prevEnd = $j;
                }
                $before = array_slice($this->treeData, 0, $prevStart);
                $prevBranch = array_slice($this->treeData, $prevStart, $prevEnd - $prevStart + 1);
                $middle = array_slice($this->treeData, $prevEnd + 1, $index - $prevEnd - 1);
                $after = array_slice($this->treeData, $end + 1);

                $this->treeData = array_merge(
                    $before,
                    $branch,
                    $prevBranch,
                    $middle,
                    $after
                );
                $this->treeData = array_values($this->treeData);
                break;
            }
        }
    }

    public function moveBranchDown($index)
    {
        $currentDepth = $this->treeData[$index]['depth'];
        // Xác định nhánh hiện tại (node + toàn bộ con)
        $end = $index;
        for ($i = $index + 1; $i < count($this->treeData); $i++) {
            if ($this->treeData[$i]['depth'] <= $currentDepth) break;
            $end = $i;
        }
        // Tìm node sau cùng cấp để chèn xuống dưới
        $next = $end + 1;
        while ($next < count($this->treeData)) {
            if ($this->treeData[$next]['depth'] == $currentDepth) {
                // Xác định nhánh sau đó
                $nextEnd = $next;
                for ($j = $next + 1; $j < count($this->treeData); $j++) {
                    if ($this->treeData[$j]['depth'] <= $currentDepth) break;
                    $nextEnd = $j;
                }
                $before = array_slice($this->treeData, 0, $index);
                $branch = array_slice($this->treeData, $index, $end - $index + 1);
                $middle = array_slice($this->treeData, $end + 1, $next - $end - 1);
                $nextBranch = array_slice($this->treeData, $next, $nextEnd - $next + 1);
                $after = array_slice($this->treeData, $nextEnd + 1);

                $this->treeData = array_merge(
                    $before,
                    $middle,
                    $nextBranch,
                    $branch,
                    $after
                );
                $this->treeData = array_values($this->treeData);
                break;
            }
            // Nếu không còn node cùng cấp phía dưới thì break
            if ($this->treeData[$next]['depth'] < $currentDepth) break;
            $next++;
        }
    }
}
