<?php

namespace App\Filament\Clusters\KhaoSat\Resources;

use App\Filament\Clusters\KhaoSat;
use App\Filament\Clusters\KhaoSat\Resources\SurveyResource\Pages;
use App\Filament\Clusters\KhaoSat\Resources\SurveyResource\RelationManagers;
use App\Models\Survey\Survey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Pages\SubNavigationPosition;
use Filament\Forms\Components\{TextInput, Textarea, Toggle, Select};
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Actions\{Action as TableAction, EditAction, ActionGroup};
use Illuminate\Support\HtmlString;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = KhaoSat::class;
    protected static ?string $label = 'Khảo sát';
    protected static ?string $pluralLabel = 'Danh sách khảo sát';
    protected static ?string $slug = 'surveys';
    protected static ?int $navigationSort = 0;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Tiêu đề')
                ->required(),

            Textarea::make('description')
                ->label('Mô tả'),

            Select::make('slug')
                ->label('Đường dẫn (slug)')
                ->required()
                ->unique(ignoreRecord: true)
                ->options([
                    'feedback-survey-1' => 'Khảo sát số 1',
                    'feedback-survey-2' => 'Khảo sát số 2',
                    'feedback-survey-3' => 'Khảo sát số 3',
                    'feedback-survey-4' => 'Khảo sát số 4',
                    'feedback-survey-5' => 'Khảo sát số 5',
                ])
                ->helperText('Chọn đường dẫn sử dụng trong URL, ví dụ: /surveys/feedback-survey-1')
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Đang hoạt động')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Tiêu đề')->searchable(),
                TextColumn::make('description')->label('Mô tả')->limit(30),
                TextColumn::make('slug')
                    ->label('Đường dẫn (slug)')
                    ->formatStateUsing(fn($state) => new HtmlString(
                        '<a href="' . url('/admin/survey/' . $state) . '" target="_blank" style="color:#2563eb !important; text-decoration: underline;">
        🔗 ' . e($state) . '
    </a>'
                    ))
                    ->html(),
                IconColumn::make('is_active')->label('Kích hoạt')->boolean(),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                TableAction::make('toggle_lock')
                    ->label(fn($record) => $record->is_open ? 'Đóng khảo sát' : 'Mở khảo sát')
                    ->icon(fn($record) => $record->is_open ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn($record) => $record->is_open ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Nếu đang bật, thì tắt
                        if ($record->is_open) {
                            $record->is_open = false;
                            $record->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Khảo sát đã được đóng')
                                ->success()
                                ->send();

                            return;
                        }

                        // Nếu đang tắt, kiểm tra có khảo sát nào khác đang bật không
                        $alreadyOpen = \App\Models\Survey\Survey::where('is_open', true)
                            ->where('id', '!=', $record->id)
                            ->exists();

                        if ($alreadyOpen) {
                            \Filament\Notifications\Notification::make()
                                ->title('Chỉ được mở một khảo sát tại một thời điểm')
                                ->danger()
                                ->send();

                            return; // Quan trọng: KHÔNG throw!
                        }

                        // Cho phép mở
                        $record->is_open = true;
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Khảo sát đã được mở')
                            ->success()
                            ->send();
                    }),
                ActionGroup::make([
                    EditAction::make()->label('Chỉnh sửa'),
                    // Thêm nút chỉnh sửa tùy chỉnh
                    TableAction::make('editQuestions')
                        ->label('Chỉnh sửa câu hỏi')
                        ->icon('heroicon-o-pencil-square')
                        ->url(fn($record) => \App\Filament\Clusters\KhaoSat\Pages\CreateSurveyForm::getUrl(['record' => $record->id]))
                        //->openUrlInNewTab(),
                        ->color('secondary'),
                ])
            ]);
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
            'index' => Pages\ListSurveys::route('/'),
            'create' => Pages\CreateSurvey::route('/create'),
            'edit' => Pages\EditSurvey::route('/{record}/edit'),
        ];
    }
}
