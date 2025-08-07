<?php

namespace App\Filament\Clusters\KPI\Pages;

use App\Filament\Clusters\KPI;
use App\Filament\Clusters\KPI\Resources\KpiTreeResource;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use App\Models\KPI\KpiPeriod;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\{TextInput, DatePicker};
use Spatie\Activitylog\Models\Activity;
use Filament\Support\Enums\MaxWidth;

class KpiPeriods extends Page implements HasForms, HasActions, HasTable
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';
    protected static string $view = 'filament.clusters.k-p-i.pages.kpi-periods';
    protected static ?string $cluster = KPI::class;
    protected static ?string $title = 'Đợt đánh giá KPI';
    protected static ?string $navigationLabel = 'Đợt đánh giá';
    protected static ?string $slug = 'kpi-periods';
    protected static ?int $navigationSort = 0;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start
    use InteractsWithTable, InteractsWithForms, InteractsWithActions;

    public ?KpiPeriod $record = null;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public static function canAccess(): bool
    {
        return userCan('xem đợt đánh giá kpi', KpiTreeResource::class);
    }
    protected function canAction(string $action): bool
    {
        return match ($action) {
            'thêm' => userCan('cập nhật đợt đánh giá kpi', KpiTreeResource::class),
            'xóa' => userCan('cập nhật đợt đánh giá kpi', KpiTreeResource::class),
            'sửa' => userCan('cập nhật đợt đánh giá kpi', KpiTreeResource::class),
            'xem-log' => userCan('xem log kpi', KpiTreeResource::class),
            default => false, // mặc định không có quyền nếu action không hợp lệ
        };
    }
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('code')
                ->label('Mã')
                ->required()
                ->maxLength(20),
            TextInput::make('name')
                ->label('Tên')
                ->required()
                ->maxLength(255),
            DatePicker::make('start_date')
                ->label('Ngày bắt đầu')
                ->required(),
            DatePicker::make('end_date')
                ->label('Ngày kết thúc')
                ->required(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(KpiPeriod::query())
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->sortable(),
                TextColumn::make('name')->label('Tên')->searchable(),
                TextColumn::make('start_date')->label('Bắt đầu')->date('d/m/Y'),
                TextColumn::make('end_date')->label('Kết thúc')->date('d/m/Y'),
            ])
            ->emptyStateHeading('Không có kỳ đánh giá KPI nào')
            ->emptyStateDescription('Không tìm thấy kỳ đánh giá KPI phù hợp với bộ lọc hiện tại.')
            ->actions([
                TableAction::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->label('Sửa')
                    ->visible(fn() => $this->canAction('sửa'))
                    ->authorize(fn() => $this->canAction('sửa'))
                    ->fillForm(fn($record) => $record->toArray()) // ✅ Load dữ liệu vào form
                    ->action(fn($record, array $data) => $record->update($data))
                    ->form(fn(TableAction $action) => $this->getFormSchema()),
                TableAction::make('delete')
                    ->label('Xóa')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn() => $this->canAction('xóa'))
                    ->authorize(fn() => $this->canAction('xóa'))
                    ->action(fn(KpiPeriod $record) => $record->delete())
                    ->requiresConfirmation(),
                TableAction::make('xem_log')
                    ->label('Lịch sử')
                    ->icon('heroicon-o-clock')
                    ->modalHeading('Lịch sử đợt đánh giá KPI')
                    ->modalContent(fn(KpiPeriod $record) => view('edu-log-viewer', [
                        'logs' => Activity::forSubject($record)->latest()->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->visible(fn() => $this->canAction('xem-log'))
                    ->authorize(fn() => $this->canAction('xem-log')),
            ])
            ->headerActions([
                TableAction::make('create')
                    ->label('Thêm mới đợt đánh giá KPI')
                    ->visible(fn() => $this->canAction('thêm'))
                    ->authorize(fn() => $this->canAction('thêm'))
                    ->action(function (array $data) {
                        KpiPeriod::create($data);
                    })
                    ->form(fn() => $this->getFormSchema()),
            ]);
    }
}
