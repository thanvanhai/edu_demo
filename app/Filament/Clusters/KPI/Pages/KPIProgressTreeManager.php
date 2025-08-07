<?php

namespace App\Filament\Clusters\KPI\Pages;

use App\Filament\Clusters\KPI;
use App\Filament\Clusters\KPI\Resources\KpiTreeResource;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\{TextColumn, TextInputColumn};
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\{Actions, Select, TextInput};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\KPI\{KpiPeriod, KpiProgress, KpiAllocationTree};
use App\Models\NhanSu\{Department};

class KPIProgressTreeManager extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static string $view = 'filament.clusters.k-p-i.pages.k-p-i-progress-tree-manager';
    protected static ?string $cluster = KPI::class;
    protected static ?string $title = 'Tiến độ KPI';
    protected static ?string $navigationLabel = 'Tiến độ';
    protected static ?string $slug = 'kpi-progresstree';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public ?int $kpi_period_id = null;
    public ?int $department_id = null;
    public ?int $month = null;
    public ?int $year = null;

    public array $departments = [];
    public array $inputRows = [];
    public bool $isLocked = false;
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public static function canAccess(): bool
    {
        return userCan('xem tiến độ kpi', KpiTreeResource::class);
    }

    protected function canAction(string $action): bool
    {
        return match ($action) {
            'thêm' => userCan('cập nhật tiến độ kpi', KpiTreeResource::class),
            'sửa' => userCan('cập nhật tiến độ kpi', KpiTreeResource::class),
            'xóa' => userCan('cập nhật tiến độ kpi', KpiTreeResource::class),
            default => false,
        };
    }

    public function mount(): void
    {
        $user = Auth::user();
        $nhansu = optional($user->NhanSu);

        $this->departments = Department::pluck('name', 'id')->toArray();

        $this->department_id ??= array_key_first($this->departments);
        $this->month ??= now()->month;
        $this->year ??= now()->year;
        $this->kpi_period_id ??= KpiPeriod::latest('id')->first()?->id;

        $this->form->fill([
            'department_id' => $this->department_id,
            'kpi_period_id' => $this->kpi_period_id,
            'month' => $this->month,
            'year' => $this->year,
        ]);
        $this->isLocked = false; // 👈 Bảng sẽ chưa load
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('kpi_period_id')
                ->label('Kỳ đánh giá')
                ->options(KpiPeriod::pluck('name', 'id'))
                ->searchable()
                ->required()
                ->disabled(fn() => $this->isLocked),

            Select::make('department_id')
                ->label('Phòng ban')
                ->options($this->departments)
                ->searchable()
                ->required()
                ->disabled(fn() => $this->isLocked),

            Select::make('month')
                ->label('Tháng')
                ->options(array_combine(range(1, 12), range(1, 12)))
                ->required()
                ->disabled(fn() => $this->isLocked),

            TextInput::make('year')
                ->label('Năm')
                ->numeric()
                ->required()
                ->disabled(fn() => $this->isLocked),

            Actions::make([
                Actions\Action::make('loadTree')
                    ->label('Tải dữ liệu')
                    ->action('loadTree')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn() => !$this->isLocked), // Ẩn khi đã khóa

                Actions\Action::make('resetFilter')
                    ->label('Xóa dữ liệu lọc')
                    ->color('danger')
                    ->action('resetFilter')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn() => $this->isLocked), // Chỉ hiện khi đã khóa

                Actions\Action::make('saveAll')
                    ->label('Lưu toàn bộ bảng')
                    ->color('success')
                    ->action('saveProgress')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn() => $this->isLocked), // Chỉ hiện khi đã khóa
            ])->columnSpanFull(),
        ];
    }

    public function loadTree(): void
    {
        $this->form->fill([
            'department_id' => $this->department_id,
            'kpi_period_id' => $this->kpi_period_id,
            'month' => $this->month,
            'year' => $this->year,
        ]);

        $this->inputRows = [];
        $this->isLocked = true;
        $this->resetTable(); // 🔥 BẮT BUỘC để cập nhật query() với biến mới
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(function () {
                // ✅ Nếu chưa bấm nút "Tải dữ liệu" → không load gì cả (bảng trắng)
                if (! $this->isLocked || !$this->department_id || !$this->kpi_period_id || !$this->month || !$this->year) {
                    return KpiAllocationTree::query()->whereRaw('1 = 0'); // bảng trắng
                }

                return KpiAllocationTree::query()
                    ->where('department_id', $this->department_id)
                    ->where('kpi_period_id', $this->kpi_period_id)
                    ->with([
                        'kpiTree',
                        'kpiprogresses' => fn($q) => $q
                            ->where('month', $this->month)
                            ->where('year', $this->year),
                    ])
                    ->defaultOrder();
            })
            ->columns([
                TextColumn::make('name_with_depth')
                    ->label('Phương diện/Mục tiêu/Tiêu chí')
                    ->html()
                    ->wrap(),

                TextColumn::make('allocated_value')->label('Chỉ tiêu'),
                TextColumn::make('unit')->label('Đơn vị tính'),
                TextColumn::make('weight_direction')->label('Trọng số Phương diện'),
                TextColumn::make('weight_objective')->label('Trọng số Mục tiêu'),
                TextColumn::make('weight_kpi')->label('Trọng số KPI'),

                TextInputColumn::make('input_progress')
                    ->label('Tiến độ (%)')
                    ->type('number')
                    ->rules(['nullable', 'numeric', 'min:0', 'max:100'])
                    ->state(fn($record) => $this->inputRows[$record->id]['input_progress'] ?? $record->kpiprogresses->first()?->progress_percent)
                    ->afterStateUpdated(function ($state, $record, $livewire) {
                        $livewire->inputRows[$record->id]['input_progress'] = $state;
                    })
                    ->disabled(fn($record) => $record->type !== 'Tiêu chí'),

                TextInputColumn::make('input_note')
                    ->label('Mô tả kết quả thực hiện')
                    ->rules(['nullable', 'string', 'max:255'])
                    ->state(fn($record) => $this->inputRows[$record->id]['input_note'] ?? $record->kpiprogresses->first()?->note)
                    ->afterStateUpdated(function ($state, $record, $livewire) {
                        $livewire->inputRows[$record->id]['input_note'] = $state;
                    })
                    ->disabled(fn($record) => $record->type !== 'Tiêu chí')->extraAttributes([
                        'class' => 'whitespace-normal break-words',
                    ]),
            ])
            ->recordAction(null)
            ->actions([
                Action::make('saveProgress')
                    ->label('Lưu tiến độ')
                    ->action(fn() => $this->saveProgress())
                    ->visible(fn() => $this->canAction('sửa'))
                    ->authorize(fn() => $this->canAction('sửa')),
            ])
            ->emptyStateHeading('Không có tiến độ KPI nào')
            ->emptyStateDescription('Không tìm thấy tiến độ KPI nào phù hợp với bộ lọc hiện tại.')
            ->paginated(false);
    }

    public function saveProgress(): void
    {
        foreach ($this->inputRows as $id => $row) {
            $record = KpiAllocationTree::find($id);
            if (!$record || $record->type !== 'Tiêu chí') continue;

            unset($row['id']); // trước khi gọi updateOrCreate

            KpiProgress::updateOrCreate(
                [
                    'kpi_allocation_tree_id' => $id,
                    'month' => $this->month,
                    'year' => $this->year,
                ],
                [
                    'progress_percent' => $row['input_progress'] ?? null,
                    'note' => $row['input_note'] ?? null,
                    'kpi_period_id' => $this->kpi_period_id,
                ]
            );
        }

        Notification::make()
            ->title('Đã lưu tiến độ KPI thành công')
            ->success()
            ->send();
    }

    public function resetFilter(): void
    {
        $this->isLocked = false;
        $this->inputRows = [];

        // Đảm bảo $this->departments luôn có dữ liệu
        if (empty($this->departments)) {
            $user = Auth::user();
            $nhansu = optional($user->NhanSu);

            $this->departments =   Department::pluck('name', 'id')->toArray();
        }

        $this->department_id = array_key_first($this->departments);
        $this->month = now()->month;
        $this->year = now()->year;
        $this->kpi_period_id = KpiPeriod::latest('id')->first()?->id;

        $this->form->fill([
            'department_id' => $this->department_id,
            'kpi_period_id' => $this->kpi_period_id,
            'month' => $this->month,
            'year' => $this->year,
        ]);
    }
}
