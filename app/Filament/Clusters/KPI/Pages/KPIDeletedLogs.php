<?php

namespace App\Filament\Clusters\KPI\Pages;

use App\Filament\Clusters\KPI;
use App\Filament\Clusters\KPI\Resources\KpiTreeResource;
use App\Models\KPI\{KPIProgress, KPICriteria, KPIAllocation, KpiAllocationTree, KpiPeriod, KPIGroup, KPITree};
use App\Models\User;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\{TextColumn};
use Filament\Forms\Components\{Select, DatePicker, Grid, Actions};
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class KPIDeletedLogs extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static string $view = 'filament.clusters.k-p-i.pages.k-p-i-deleted-logs';
    protected static ?string $cluster = KPI::class;
    protected static ?string $title = 'Log xóa KPI';
    protected static ?string $navigationLabel = 'Log xóa';
    protected static ?string $slug = 'kpi-log-deleted';
    protected static ?int $navigationSort = 8;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public ?string $model_type = null;
    public ?string $causer_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;

    public array $modelMap = [];
    public $users;
    public array $data = [];
    public bool $filtersApplied = false; // Trigger reload bảng

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    public static function canAccess(): bool
    {
        return userCan('xem log kpi', KpiTreeResource::class);
    }

    protected function canAction(string $action): bool
    {
        return match ($action) {
            'xoa-kpi' => userCan('xóa dữ liệu kpi', KpiTreeResource::class),
            default => false,
        };
    }
    public function mount(): void
    {
        $this->modelMap = [
            // KPIAllocation::class => 'Phân bổ KPI',
            KPIProgress::class  => 'Tiến độ KPI',
            KpiAllocationTree::class => 'Phân bổ KPI',
            // KPICriteria::class => 'Tiêu chí đánh giá KPI',
            // KPIGroup::class  => 'Phương diện/Mục tiêu KPI',
            KPITree::class  => 'Phương diện/Mục tiêu/Tiêu chí KPI',
            KpiPeriod::class  => 'Kỳ đánh giá KPI',
        ];

        $this->users = User::orderBy('name')->get();

        $this->form->fill([
            'model_type' => null,
            'causer_id' => null,
            'date_from' => now()->subMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)->schema([
                    Select::make('model_type')
                        ->label('Bảng bị xóa')
                        ->options($this->modelMap)
                        ->searchable()
                        ->columnSpan(1),
                    // ->live(false),

                    Select::make('causer_id')
                        ->label('Người xóa')
                        ->options($this->users->pluck('name', 'id'))
                        ->searchable()
                        ->columnSpan(1),
                    // ->live(false),

                    DatePicker::make('date_from')
                        ->label('Từ ngày')
                        ->columnSpan(1)
                        ->closeOnDateSelection(),

                    DatePicker::make('date_to')
                        ->label('Đến ngày')
                        ->columnSpan(1)
                        ->closeOnDateSelection(),
                ]),

                // Thêm nút Tìm
                Actions::make([
                    Action::make('filter')
                        ->label('Tìm')
                        ->action('filterDeletedLogs') // Gọi tới method Livewire
                        ->color('danger')
                        ->icon('heroicon-o-magnifying-glass'),

                    Action::make('reset')
                        ->label('Xóa lọc')
                        ->action('resetFilters')
                        ->color('warning')
                        ->icon('heroicon-o-x-mark'),

                    // Thêm nút xóa toàn bộ dữ liệu KPI
                    Action::make('deleteAllKpi')
                        ->label('Xóa toàn bộ KPI')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Xác nhận xóa toàn bộ dữ liệu KPI (Phương diện/Mục tiêu, chỉ tiêu, phân bổ, tiến độ)?')
                        ->modalDescription('Thao tác này sẽ xóa tất cả bảng KPI (gồm Phương diện/Mục tiêu, chỉ tiêu, phân bổ, tiến độ).')
                        ->action(fn() => $this->deleteKpiData(true))
                        ->visible(fn() => $this->canAction('xoa-kpi'))
                        ->icon('heroicon-o-trash'),

                    Action::make('deleteTableKpi')
                        ->label('Xóa theo bảng')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Xóa bảng KPI cụ thể?')
                        ->modalDescription('Chỉ xóa dữ liệu của bảng bạn đã chọn. Xóa theo thứ tự bảng: Tiến độ, Phân bổ, Chỉ tiêu, Phương diện/Mục tiêu')
                        ->form([
                            Select::make('table')
                                ->label('Chọn bảng cần xóa:')
                                ->options([
                                    'kpi_progress' => 'Tiến độ KPI',                                
                                    'kpi_allocation_tree' => 'Phân bổ KPI',                                   
                                    'kpi_tree' => 'Phương diện/Mục tiêu/Tiêu chí',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data) {
                            $this->deleteKpiData(false, $data['table']);
                        })
                        ->visible(fn() => $this->canAction('xoa-kpi'))
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->statePath('data');
    }

    public function resetFilters(): void
    {
        $this->form->fill([
            'model_type' => null,
            'causer_id' => null,
            'date_from' => now()->subMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);

        $this->filtersApplied = !$this->filtersApplied;
        $this->dispatch('$refresh'); // Bắt Livewire re-render bảng
    }

    public function filterDeletedLogs(): void
    {
        $this->filtersApplied = !$this->filtersApplied;
        $this->dispatch('$refresh'); // Bắt Livewire re-render bảng
    }

    public function getDeletedLogsQuery()
    {
        $state = $this->form->getState();

        $query = Activity::query()
            ->with('causer')
            ->where('event', 'deleted');

        if ($state['model_type']) {
            $query->where('subject_type', $state['model_type']);
        } else {
            $query->whereIn('subject_type', array_keys($this->modelMap));
        }

        if ($state['causer_id']) {
            $query->where('causer_id', $state['causer_id']);
        }

        if ($state['date_from']) {
            $query->whereDate('created_at', '>=', $state['date_from']);
        }

        if ($state['date_to']) {
            $query->whereDate('created_at', '<=', $state['date_to']);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            // ->query($this->getDeletedLogsQuery())
            ->query(fn() => $this->filtersApplied && $this->form ? $this->getDeletedLogsQuery() : $this->getDeletedLogsQuery())
            ->columns([
                TextColumn::make('subject_type')
                    ->label('Bảng')
                    ->formatStateUsing(fn($state) => $this->modelMap[$state] ?? class_basename($state))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('causer.name')
                    ->label('Người xóa')
                    ->default('Hệ thống')
                    ->searchable()
                    ->sortable()
                    ->toggleable(), //cho phép chọn ẩn hiện cột

                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                // Chuyển properties sang JSON format đẹp
                TextColumn::make('properties')
                    ->label('Dữ liệu chi tiết')
                    ->formatStateUsing(function ($state) {
                        if (!$state || !is_array($state)) {
                            $state = is_object($state) ? $state->toArray() : json_decode($state, true);
                        }

                        return '<pre class="text-xs whitespace-pre-wrap">'
                            . e(json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            . '</pre>';
                    })
                    ->html() // cho phép hiển thị HTML như <pre>
                    ->wrap()
                    ->toggleable(),

            ])
            ->emptyStateHeading('Không có bản ghi xóa log nào')
            ->emptyStateDescription('Không tìm thấy log bị xóa phù hợp với bộ lọc hiện tại.')
            ->paginationPageOptions([10, 25, 50]);
    }
    /**
     * Xóa toàn bộ dữ liệu KPI
     */
    public function deleteKpiData(bool $deleteAll = true, ?string $tableName = null): void
    {
        try {
            if ($deleteAll) {
                DB::connection('sqlsrv')->statement('EXEC sp_delete_kpi_data @deleteAll = 1');
                Notification::make()
                    ->title('Thành công')
                    ->body('Đã xóa toàn bộ dữ liệu KPI.')
                    ->success()
                    ->send();
            } elseif ($tableName) {
                DB::connection('sqlsrv')->statement("EXEC sp_delete_kpi_data @deleteAll = 0, @tableName = '$tableName'");
                Notification::make()
                    ->title('Thành công')
                    ->body("Đã xóa dữ liệu trong bảng: $tableName.")
                    ->success()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Lỗi')
                ->body('Không thể xóa dữ liệu KPI: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
