<?php

namespace App\Filament\Clusters\KPI\Resources;

use App\Filament\Clusters\KPI;
use App\Filament\Clusters\KPI\Resources\KpiTreeResource;
use App\Filament\Clusters\KPI\Resources\KpiAllocationTreeResource\Pages;
use App\Filament\Clusters\KPI\Resources\KpiAllocationTreeResource\RelationManagers;
use App\Models\KPI\{KPITree, KpiPeriod, KpiAllocationTree};
use App\Models\NhanSu\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Spatie\Activitylog\Models\Activity;
use Filament\Tables\Actions\Action as TableAction;

class KpiAllocationTreeResource extends Resource
{
    protected static ?string $model = KPIAllocationTree::class;
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $cluster = KPI::class;
    protected static ?string $title = 'Danh sách Phân bổ KPI';
    protected static ?string $navigationLabel = 'Danh sách Phân bổ';
    protected static ?string $slug = 'kpi-allocationtrees';
    protected static ?int $navigationSort = 3;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

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
            'xem-log' => userCan('xem log kpi', KpiTreeResource::class),
            default => false,
        };
    }

    protected static function colorizeText(?string $text, $type): string
    {
        $color = match ($type) {
            'Phương diện' => '#2563eb', // xanh dương
            'Mục tiêu'    => '#16a34a', // xanh lá
            'Tiêu chí'    => '#6b7280', // xám
            default       => '#6b7280',
        };
        $fontWeight = $type === 'Phương diện' ? 'bold' : 'normal';

        return "<span style='color: {$color}; display: inline-block; font-weight: {$fontWeight};'>" . e($text) . "</span>";
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('allocated_value')
                    ->label('Chỉ tiêu'),
                //TextInput::make('expected_ratio')->label('Chỉ tiêu')->numeric()->nullable(),

                TextInput::make('unit')
                    ->label('Đơn vị tính')
                    ->maxLength(20),

                TextInput::make('weight_direction')
                    ->label('Trọng số phương diện')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('%'),

                TextInput::make('weight_objective')
                    ->label('Trọng số mục tiêu')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('%')
                    ->step(0.01),

                TextInput::make('weight_kpi')
                    ->label('Trọng số KPI')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('%')
                    ->step(0.01),

                TextInput::make('sortcode')
                    ->label('Chỉ mục sắp xếp'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with('kpiTree')->withDepth()->defaultOrder())
            ->columns([
                TextColumn::make('name_with_depth')
                    ->label('Phương diện/Mục tiêu/Tiêu chí')
                    ->searchable()
                    ->html()
                    ->wrap(),
                TextColumn::make('department.name')->label('Phòng ban')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                TextColumn::make('kpiperiod.name')->label('Kỳ đánh giá')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                //TextColumn::make('kpiTree.name')->label('Tiêu chí KPI')->sortable()->wrap(),
                TextColumn::make('kpiTree.type')->label('Loại')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sortcode')->label('Chỉ mục sắp xếp')
                    ->toggleable()
                    ->toggledHiddenByDefault(false),
                TextColumn::make('allocated_value')->label('Chỉ tiêu'),
                TextColumn::make('unit')->label('Đơn vị'),
                TextColumn::make('weight_direction')->label('Trọng số Phương diện'),
                TextColumn::make('weight_objective')->label('Trọng số Mục tiêu'),
                TextColumn::make('weight_kpi')->label('Trọng số KPI'),
                TextColumn::make('total_progress_percent')
                    ->label('Tiến độ tổng (%)')
                    ->suffix('%')
                    ->color(fn($state) => $state < 80 ? 'danger' : 'success'),
                TextColumn::make('evaluation_score')->label('Điểm đánh giá')->suffix('%')->color('info'),

                TextColumn::make('note')->label('Ghi chú')->wrap(),
            ])
            ->filters([
                SelectFilter::make('kpi_period_id')
                    ->label('Kỳ đánh giá')
                    ->options(
                        KpiPeriod::pluck('name', 'id')
                    ),

                SelectFilter::make('department_id')
                    ->label('Phòng ban')
                    ->searchable()
                    ->options(
                        Department::pluck('name', 'id')
                    ),

                // Bộ lọc phương diện
                SelectFilter::make('phuong_dien')
                    ->label('Phương diện')
                    ->options(
                        KPITree::where('type', 'Phương diện')->pluck('name', 'id')
                    )
                    ->searchable()
                    ->placeholder('Tất cả')
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $node = KPITree::find($data['value']);
                            if ($node) {
                                // Lấy tất cả id của phương diện, mục tiêu, tiêu chí con và chính nó
                                $ids = $node->descendantsAndSelf($node->id);
                                $query->whereHas('kpiTree', function ($q) use ($ids) {
                                    $q->whereIn('id', $ids);
                                });
                            }
                        }
                        return $query;
                    }),

                SelectFilter::make('muc_tieu')
                    ->label('Mục tiêu')
                    ->options(
                        KPITree::where('type', 'Mục tiêu')->pluck('name', 'id')
                    )
                    ->searchable()
                    ->placeholder('Tất cả')
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $node = KPITree::find($data['value']);
                            if ($node) {
                                // Lấy id mục tiêu và tất cả tiêu chí con
                                $ids = $node->descendantsAndSelf($node->id);
                                $query->whereHas('kpiTree', function ($q) use ($ids) {
                                    $q->whereIn('id', $ids);
                                });
                            }
                        }
                        return $query;
                    }),
            ])
            ->filtersFormColumns(2) // Số cột hiển thị trong form lọc
            ->actions([
                Tables\Actions\Action::make('move_up')
                    ->icon('heroicon-o-chevron-up')
                    ->label('')
                    ->tooltip('Di chuyển lên')
                    ->action(fn($record) => $record->up())
                    ->visible(fn() => static::canAction('sửa')) // nếu có phân quyền
                    ->authorize(fn() => static::canAction('sửa')),

                Tables\Actions\Action::make('move_down')
                    ->icon('heroicon-o-chevron-down')
                    ->label('')
                    ->tooltip('Di chuyển xuống')
                    ->action(fn($record) => $record->down())
                    ->visible(fn() => static::canAction('sửa')) // nếu có phân quyền
                    ->authorize(fn() => static::canAction('sửa')),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->visible(fn($record) => $record->type === 'Tiêu chí' && static::canAction('sửa'))
                        ->authorize(fn() => static::canAction('sửa')),
                    Tables\Actions\ViewAction::make()->modalHeading(fn($record) => 'Xem chi tiết phân bổ KPI: ' . ($record->name ?? '')),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn() => static::canAction('xóa')) // nếu có phân quyền
                        ->authorize(fn() => static::canAction('xóa')),
                    TableAction::make('xem_log')
                        ->label('Lịch sử')
                        ->icon('heroicon-o-clock')
                        ->visible(fn() => static::canAction('xem-log')) // nếu có phân quyền
                        ->authorize(fn() => static::canAction('xem-log'))
                        ->modalHeading('Lịch sử phân bổ KPI')
                        ->modalContent(fn($record) => view('edu-log-viewer', [
                            'logs' => Activity::forSubject($record)->latest()->get(),
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Đóng')
                ]),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading('Không có phân bổ KPI nào')
            ->emptyStateDescription('Không tìm thấy phân bổ KPI nào phù hợp với bộ lọc hiện tại.');
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
            'index' => Pages\ListKPIAllocationTrees::route('/'),
            'create' => Pages\CreateKPIAllocationTree::route('/create'),
            'edit' => Pages\EditKPIAllocationTree::route('/{record}/edit'),
        ];
    }
}
