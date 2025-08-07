<?php

namespace App\Filament\Clusters\KPI\Resources;

use App\Filament\Clusters\KPI;
use App\Filament\Clusters\KPI\Resources\KPIProgressResource\Pages;
use App\Filament\Clusters\KPI\Resources\KPIProgressResource\RelationManagers;
use App\Models\KPI\{KPIProgress, KpiPeriod, KpiAllocationTree};
use App\Models\NhanSu\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Pages\SubNavigationPosition;
use Filament\Forms\Components\{Select, TextInput, FileUpload};
use Filament\Tables\Columns\{TextColumn, ViewColumn};
use Filament\Tables\Filters\SelectFilter;
use Spatie\Activitylog\Models\Activity;
use Filament\Tables\Actions\{DeleteBulkAction, BulkActionGroup, DeleteAction, EditAction, Action as TableAction};
use Filament\Tables\Grouping\Group;

class KPIProgressResource extends Resource
{
    protected static ?string $model = KPIProgress::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = KPI::class;
    protected static ?string $label = 'Quản lý tiến độ KPI';
    protected static ?string $title = 'Danh Sách Tiến độ KPI';
    protected static ?string $navigationLabel = 'Danh sách Tiến độ';
    protected static ?string $slug = 'kpi-progresstreelist';
    protected static ?int $navigationSort = 5;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    public static function canAccess(): bool
    {
        return userCan('view', static::class);
    }

    public static function canAction(string $action): bool
    {
        return match ($action) {
            // ✅ Các hành động chuẩn Shield (dùng quyền update/delete/view)
            'thêm'  => userCan('create', static::class),
            'sửa'   => userCan('update', static::class),
            'xóa'   => userCan('delete', static::class),

            // ✅ Hành động custom riêng (tự đặt tên)
            'xem-log' => userCan('xem log kpi', static::class),

            default => false, // Mặc định không có quyền
        };
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('kpi_allocation_tree_id')
                    ->label('Chỉ tiêu phân bổ')
                    ->relationship('allocationTree', 'name')
                    ->searchable()
                    ->disabled()
                    ->required(),

                TextInput::make('total_progress_percent')
                    ->label('Tiến độ đến tháng (%)')
                    ->numeric()
                    ->maxValue(100)
                    ->disabled(),


                TextInput::make('month')
                    ->required()->disabled(),
                TextInput::make('year')
                    ->required()->disabled(),

                TextInput::make('progress_percent')
                    ->label('Tiến độ (%)')
                    ->numeric()
                    ->maxValue(100),
                TextInput::make('note')
                    ->label('Mô tả kết quả thực hiện')
                    ->maxLength(255)
                    ->nullable(),
                FileUpload::make('evidences')
                    ->label('Minh chứng')
                    ->multiple()
                    ->directory('KPI')
                    ->preserveFilenames()
                    ->reorderable()
                    ->openable()
                    ->downloadable()
                    ->maxSize(5120)
                    ->dehydrated(true) // Quan trọng để dữ liệu đi xuống mutateFormDataBeforeSave
                    ->formatStateUsing(function ($state) {
                        // Nếu là mảng ['path' => ..., 'original_name' => ...], chỉ trả về mảng path
                        return collect($state)->map(function ($file) {
                            return is_array($file) && isset($file['path']) ? $file['path'] : $file;
                        })->toArray();
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('allocationTree.parent.parent.name')
                    ->label('Phương diện')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('allocationTree.parent.name')
                    ->label('Mục tiêu')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('allocationTree.name')->label('KPI'),

                TextColumn::make('allocationTree.department.name')
                    ->label('Đơn vị')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('month')
                    ->label("Tháng")
                    ->searchable(),
                TextColumn::make('year')
                    ->label("Nămn"),
                TextColumn::make('note')
                    ->label("Mô tả kết quả thực hiện")
                    ->wrap()
                    ->searchable(),
                TextColumn::make('progress_percent')
                    ->label('% thực hiện trong tháng')
                    ->suffix('%')
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('total_progress_percent')
                    ->label('% thực hiện đến tháng')
                    ->suffix('%')
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('allocationTree.total_progress_percent')
                    ->label('Tổng % hiện tại')
                    ->suffix('%')
                    ->color(fn($state) => $state < 80 ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: false),
                ViewColumn::make('evidences')
                    ->label('Minh chứng')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->view('filament.clusters.k-p-i.kpi-evidences'),
            ])
            ->filters([
                SelectFilter::make('kpi_period_id')
                    ->label('Đợt đánh giá')
                    ->searchable()
                    ->options(fn() => KpiPeriod::pluck('name', 'id')->toArray())
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('allocationTree', function ($q) use ($data) {
                                $q->where('kpi_period_id', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->placeholder('Tất cả đợt đánh giá'),
                SelectFilter::make('department_id')
                    ->label('Phòng ban')
                    ->options(
                        Department::pluck('name', 'id')
                    )
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('allocationTree', function ($q) use ($data) {
                                $q->where('department_id', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->placeholder('Tất cả phòng ban/khoa'),
                SelectFilter::make('perspective_name')
                    ->label('Phương diện')
                    ->options(function () {
                        return KpiAllocationTree::where('depth', 0)
                            ->pluck('name') // chỉ lấy danh sách tên
                            ->unique()
                            ->mapWithKeys(fn($name) => [$name => $name]) // key = value = name
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('allocationTree.parent.parent', function ($q) use ($data) {
                                $q->where('name', $data['value']); // lọc theo tên
                            });
                        }
                    })
                    ->placeholder('Tất cả phương diện'),
                SelectFilter::make('objective_name')
                    ->label('Mục tiêu')
                    ->options(function () {
                        return KpiAllocationTree::where('depth', 1)
                            ->pluck('name')
                            ->unique()
                            ->mapWithKeys(fn($name) => [$name => $name])
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('allocationTree.parent', function ($q) use ($data) {
                                $q->where('name', $data['value']); // lọc theo tên
                            });
                        }
                    })
                    ->placeholder('Tất cả mục tiêu'),
            ])
            ->filtersFormColumns(2) // Số cột hiển thị trong form lọc
            ->actions([
                Tables\Actions\ActionGroup::make([
                    EditAction::make()
                        ->visible(fn() => static::canAction('sửa')) // nếu có phân quyền
                        ->authorize(fn() => static::canAction('sửa')),
                    DeleteAction::make()
                        ->requiresConfirmation() // ✅ Hiển thị popup xác nhận
                        ->visible(fn() => static::canAction('xóa')) // nếu có phân quyền
                        ->authorize(fn() => static::canAction('xóa')),

                    TableAction::make('xem_log')
                        ->label('Lịch sử')
                        ->icon('heroicon-o-clock')
                        ->modalHeading('Lịch sử tiến độ KPI')
                        ->modalContent(fn($record) => view('edu-log-viewer', [
                            'logs' => Activity::forSubject($record)->latest()->get(),
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Đóng')
                        ->visible(fn() => static::canAction('xem-log'))
                        ->authorize(fn() => static::canAction('xem-log')),
                ])
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->groups([
                Group::make('allocationTree.parent.parent.name') // hoặc theo quan hệ bạn dùng
                    ->label('Phương diện')
                    ->getTitleFromRecordUsing(
                        fn($record) =>
                        $record->allocationTree?->parent?->parent?->name ?? 'Chưa rõ'
                    ),
                Group::make('allocationTree.parent.name')
                    ->label('Mục tiêu')
                    ->getTitleFromRecordUsing(
                        fn($record) =>
                        $record->allocationTree?->parent?->name ?? 'Chưa rõ'
                    ),
            ])
            ->emptyStateHeading('Không có tiến độ KPI nào')
            ->emptyStateDescription('Không tìm thấy tiến độ KPI nào phù hợp với bộ lọc hiện tại.');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKPIProgress::route('/'),
            'create' => Pages\CreateKPIProgress::route('/create'),
            'edit' => Pages\EditKPIProgress::route('/{record}/edit'),
        ];
    }
}
