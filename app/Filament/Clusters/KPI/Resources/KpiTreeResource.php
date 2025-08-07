<?php

namespace App\Filament\Clusters\KPI\Resources;

use App\Filament\Clusters\KPI;
use App\Filament\Clusters\KPI\Resources\KpiTreeResource\Pages;
use App\Filament\Clusters\KPI\Resources\KpiTreeResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\KPI\{KPITree, KPIImportService};
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\{Select, TextInput, FileUpload, Hidden};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\{DeleteBulkAction, BulkActionGroup, DeleteAction, EditAction, Action as TableAction};
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Filament\Pages\SubNavigationPosition;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use Spatie\Activitylog\Models\Activity;

class KpiTreeResource extends Resource  
{
    protected static ?string $model = KpiTree::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = KPI::class;
    protected static ?string $label = 'Quản lý KPI';
    protected static ?string $title = 'Phương Diện/Mục tiêu/Tiêu chí KPI';
    protected static ?string $navigationLabel = 'Phương Diện/Mục Tiêu/Tiêu chí';
    protected static ?string $slug = 'kpi-tree';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start


    public static function canViewAny(): bool
    {
        return userCan('xem danh mục kpi', KpiTreeResource::class);
    }

    public static function canAction(string $action): bool
    {
        return match ($action) {
            'thêm' => userCan('cập nhật danh mục kpi', KpiTreeResource::class),
            'sửa' => userCan('cập nhật danh mục kpi', KpiTreeResource::class),
            'xóa' => userCan('cập nhật danh mục kpi', KpiTreeResource::class),
            'xem-log' => userCan('xem log kpi', KpiTreeResource::class),
            default => false, // mặc định không có quyền nếu action không hợp lệ
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

    public static function getHeaderActions(): array
    {
        return [
            TableAction::make('download_template')
                ->label('Tải file mẫu')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(fn() => static::canAction('thêm'))
                ->authorize(fn() => static::canAction('thêm'))
                ->form([
                    Select::make('filename')
                        ->label('Chọn file mẫu')
                        ->options(function () {
                            $priority = [
                                'phuong_dien' => ['order' => 1, 'label' => 'Phương diện'],
                                'muc_tieu'    => ['order' => 2, 'label' => 'Mục tiêu'],
                                'tieu_chi'    => ['order' => 3, 'label' => 'Tiêu chí'],
                            ];

                            $files = Storage::disk('public')->files('KPI/templates');

                            return collect($files)
                                ->map(fn($path) => basename($path))
                                ->sortBy(function ($filename) use ($priority) {
                                    $key = strtolower($filename);
                                    foreach ($priority as $prefix => $meta) {
                                        if (str_contains($key, $prefix)) {
                                            return $meta['order'];
                                        }
                                    }
                                    return 99;
                                })
                                ->mapWithKeys(function ($file) use ($priority) {
                                    $lower = strtolower($file);
                                    foreach ($priority as $prefix => $meta) {
                                        if (str_contains($lower, $prefix)) {
                                            $rest = str_replace(['_', '-'], ' ', str($file)->replaceFirst($prefix, '')->replace('.xlsx', '')->trim());
                                            return [$file => $meta['label'] . ' – ' . $rest . '.xlsx'];
                                        }
                                    }
                                    return [$file => 'Khác – ' . $file];
                                })
                                ->toArray();
                        })
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $filename = $data['filename'];
                    $url = asset("storage/KPI/templates/{$filename}");

                    // Redirect tới file tải luôn
                    return redirect($url);
                })
                ->modalSubmitActionLabel('Tải')
                ->modalCancelActionLabel('Hủy'),

            TableAction::make('import_kpi_tree')
                ->label('Import KPI')
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn() => static::canAction('thêm')) // nếu có phân quyền
                ->authorize(fn() => static::canAction('thêm'))
                ->form([
                    Select::make('type')
                        ->label('Loại KPI')
                        ->options([
                            'Phương diện' => 'Phương diện',
                            'Mục tiêu'    => 'Mục tiêu',
                            'Tiêu chí'    => 'Tiêu chí',
                        ])
                        ->required()
                        ->placeholder('Chọn loại KPI...'),

                    FileUpload::make('file')
                        ->label('File Excel')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required()
                        ->preserveFilenames(),
                ])
                ->action(function (array $data) {
                    try {
                        // ✅ Lấy đường dẫn vật lý file đã upload
                        $path = Storage::disk('local')->path($data['file']);

                        // ✅ Gọi service import — có thể trả về link file log nếu có lỗi
                        $logUrl = app(KPIImportService::class)->importKpiTreeByType($path, $data['type']);
                        // ✅ Thông báo thành công
                        $notification = Notification::make()
                            ->title('Import hoàn tất')
                            ->success();

                        // ✅ Nếu có lỗi => đính kèm link tải file log
                        if ($logUrl) {
                            $notification->body("Một số dòng bị bỏ qua. [Tải file log]($logUrl)")
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('open-log')
                                        ->label('Mở file lỗi')
                                        ->url($logUrl) // đường dẫn tới file log trong `storage/app/public/...`
                                        ->openUrlInNewTab(),
                                ]);
                        }
                        $notification->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Import lỗi')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalSubmitActionLabel('Nhập')
                ->modalCancelActionLabel('Hủy')
                ->color('success'),

            TableAction::make('create_aspect')
                ->label('Thêm Phương diện')
                ->color('gray')
                ->icon('heroicon-o-plus-circle')
                ->action(fn() => redirect(KpiTreeResource::getUrl('create', ['type' => 'Phương diện'])))
                ->visible(fn() => static::canAction('thêm')) // nếu có phân quyền
                ->authorize(fn() => static::canAction('thêm')),

            TableAction::make('create_objective')
                ->label('Thêm Mục tiêu')
                ->color('info')
                ->icon('heroicon-o-plus-circle')
                ->action(fn() => redirect(KpiTreeResource::getUrl('create', ['type' => 'Mục tiêu'])))
                ->visible(fn() => static::canAction('thêm')) // nếu có phân quyền
                ->authorize(fn() => static::canAction('thêm')),

            TableAction::make('create_criteria')
                ->label('Thêm Tiêu chí')
                ->color('success')
                ->icon('heroicon-o-plus-circle')
                ->action(fn() => redirect(KpiTreeResource::getUrl('create', ['type' => 'Tiêu chí'])))
                ->visible(fn() => static::canAction('thêm')) // nếu có phân quyền
                ->authorize(fn() => static::canAction('thêm')),
        ];
    }

    public static function form(Form $form): Form
    {
        $record = $form->getRecord();
        $isEdit = filled($record?->id); // Nếu có ID tức là đang edit
        $type = $isEdit
            ? $record->type // Sửa thì giữ nguyên type của bản ghi
            : request()->query('type', 'Phương diện'); // Thêm mới thì lấy từ URL

        return $form->schema([
            Select::make('parent_id')
                ->label(fn(callable $get) => match ($get('type')) {
                    'Mục tiêu' => 'Nhóm phương diện',
                    'Tiêu chí' => 'Nhóm mục tiêu',
                    default => 'Nhóm cha',
                })
                ->searchable()
                ->preload()
                ->nullable()
                ->visible(fn(callable $get) => $get('type') !== 'Phương diện')
                ->default(request()->query('parent_id')) // <--- Thêm dòng này
                ->options(function (callable $get) use ($record) {
                    $type = $get('type');

                    if ($type === 'Mục tiêu') {
                        // Không cần group, chỉ lấy các phương diện
                        return KpiTree::where('type', 'Phương diện')->pluck('name', 'id')->toArray();
                    }

                    if ($type === 'Tiêu chí') {
                        // Lấy các Mục tiêu (type = 'Mục tiêu'), group theo Phương diện (cha)
                        return KpiTree::where('type', 'Mục tiêu')
                            ->with('parent') // parent = phương diện
                            ->get()
                            ->groupBy(fn($item) => optional($item->parent)->name ?? 'Không rõ phương diện')
                            ->mapWithKeys(function ($items, $groupName) {
                                return [
                                    $groupName => $items->pluck('name', 'id')->toArray()
                                ];
                            })
                            ->toArray();
                    }

                    return [];
                })
                ->reactive() // bắt buộc để callback chạy lại khi `type` đổi (dù disable)
                ->disabled(fn() => $form->getRecord() !== null), // không cho đổi cha khi đã có bản ghi


            TextInput::make('code')
                ->label(fn(callable $get) => match ($get('type')) {
                    'Phương diện' => 'Mã Phương diện',
                    'Mục tiêu'    => 'Mã Mục tiêu',
                    'Tiêu chí'    => 'Mã Tiêu chí',
                })
                ->maxLength(255)
                ->nullable(),

            TextInput::make('name')
                ->label(fn(callable $get) => match ($get('type')) {
                    'Phương diện' => 'Tên Phương diện',
                    'Mục tiêu'    => 'Tên Mục tiêu',
                    'Tiêu chí'    => 'Tên Tiêu chí',
                })
                ->required(),

            Hidden::make('type')
                ->default($type)
                ->dehydrated(true)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->withDepth()->defaultOrder())
            ->columns([
                TextColumn::make('indentedName')
                    ->label('Tên KPI')
                    ->searchable()
                    // ->formatStateUsing(
                    //     fn($state, $record) =>
                    //     KpiTreeResource::colorizeText($state, $record->type)
                    // )
                    ->html()
                    ->wrap(),

                TextColumn::make('code')->label('Mã')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        KpiTreeResource::colorizeText($state, $record->type)
                    )
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('type')
                    ->label('Loại')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        KpiTreeResource::colorizeText($state, $record->type)
                    )
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Loại KPI')
                    ->options([
                        'Phương diện' => 'Phương diện',
                        'Mục tiêu' => 'Mục tiêu',
                        'Tiêu chí' => 'Tiêu chí',
                    ]),
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
                                $ids = $node->descendantsAndSelf($node->id); // Trả về mảng id
                                $query->whereIn('id', $ids);
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
                                $ids = [$node->id];
                                $criteriaIds = KPITree::where('parent_id', $node->id)->pluck('id')->toArray();
                                $ids = array_merge($ids, $criteriaIds);
                                $query->whereIn('id', $ids);
                            }
                        }
                        return $query;
                    }),
            ])
            ->filtersFormColumns(2) // Số cột hiển thị trong form lọc
            ->actions([
                // Các action riêng lẻ
                TableAction::make('move_up')
                    ->label('')
                    ->icon('heroicon-o-chevron-up')
                    ->tooltip('Di chuyển lên')
                    ->color('info')
                    ->action(fn(KpiTree $record) => $record->up())
                    ->visible(fn() => static::canAction('sửa')) // nếu có phân quyền
                    ->authorize(fn() => static::canAction('sửa')),

                TableAction::make('move_down')
                    ->label('')
                    ->icon('heroicon-o-chevron-down')
                    ->tooltip('Di chuyển xuống')
                    ->color('info')
                    ->action(fn(KpiTree $record) => $record->down())
                    ->visible(fn() => static::canAction('sửa')) // nếu có phân quyền
                    ->authorize(fn() => static::canAction('sửa')),

                // Các action còn lại gom vào nhóm
                Tables\Actions\ActionGroup::make([
                    TableAction::make('add_objective')
                        ->label('Thêm mục tiêu')
                        ->icon('heroicon-o-plus-circle')
                        ->color('info')
                        ->visible(function (KpiTree $record) {
                            return $record->type === 'Phương diện' && static::canAction('thêm');
                        })
                        ->authorize(fn() => static::canAction('thêm'))
                        ->action(
                            fn(KpiTree $record) =>
                            redirect(KpiTreeResource::getUrl('create', [
                                'type' => 'Mục tiêu',
                                'parent_id' => $record->id,
                            ]))
                        ),

                    TableAction::make('add_criteria')
                        ->label('Thêm tiêu chí')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->visible(function (KpiTree $record) {
                            return $record->type === 'Mục tiêu' && static::canAction('thêm');
                        })
                        ->authorize(fn() => static::canAction('thêm'))
                        // ->action(
                        //     fn(KpiTree $record) =>
                        //     redirect(KpiTreeResource::getUrl('create', [
                        //         'type' => 'Tiêu chí',
                        //         'parent_id' => $record->id,
                        //     ]))
                        // ),
                        ->url(fn(KpiTree $record) => KpiTreeResource::getUrl('create', [
                            'type' => 'Tiêu chí',
                            'parent_id' => $record->id,
                        ])),

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
                        ->modalHeading('Lịch sử danh mục KPI')
                        ->modalContent(fn($record) => view('edu-log-viewer', [
                            'logs' => Activity::forSubject($record)->latest()->get(),
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Đóng')
                        ->visible(fn() => static::canAction('xem-log'))
                        ->authorize(fn() => static::canAction('xem-log')),
                ]),
            ])
            ->bulkActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateActions([
                TableAction::make('create_aspect')
                    ->label('Thêm Phương diện')
                    ->color('gray')
                    ->icon('heroicon-o-plus-circle')
                    ->action(fn() => redirect(KpiTreeResource::getUrl('create', ['type' => 'Phương diện'])))
                    ->visible(fn() => static::canAction('thêm')) // nếu có phân quyền
                    ->authorize(fn() => static::canAction('thêm')),

                TableAction::make('create_objective')
                    ->label('Thêm Mục tiêu')
                    ->color('info')
                    ->icon('heroicon-o-plus-circle')
                    ->action(fn() => redirect(KpiTreeResource::getUrl('create', ['type' => 'Mục tiêu'])))
                    ->visible(fn() => static::canAction('thêm')) // nếu có phân quyền
                    ->authorize(fn() => static::canAction('thêm')),

                TableAction::make('create_criteria')
                    ->label('Thêm Tiêu chí')
                    ->color('success')
                    ->icon('heroicon-o-plus-circle')
                    ->action(fn() => redirect(KpiTreeResource::getUrl('create', ['type' => 'Tiêu chí'])))
                    ->visible(fn() => static::canAction('thêm')) // nếu có phân quyền
                    ->authorize(fn() => static::canAction('thêm')),
            ])
            ->headerActions(static::getHeaderActions())
            ->paginationPageOptions([
                25 => '25',
                50 => '50',
                100 => '100',
                -1 => 'Tất cả',
            ])
            ->emptyStateHeading('Không có Phương diên/Mục tiêu/Tiêu chí đánh giá KPI nào')
            ->emptyStateDescription('Không tìm thấy dữ liệu phù hợp với bộ lọc hiện tại.');
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
            'index' => Pages\ListKpiTrees::route('/'),
            'create' => Pages\CreateKpiTree::route('/create'),
            'edit' => Pages\EditKpiTree::route('/{record}/edit'),
        ];
    }
}
