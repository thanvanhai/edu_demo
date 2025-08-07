<?php

namespace App\Filament\Clusters\KhaoSat\Resources;

use App\Filament\Clusters\KhaoSat;
use App\Filament\Clusters\KhaoSat\Resources\SurveyAnswerResource\Pages;
use App\Filament\Clusters\KhaoSat\Resources\SurveyAnswerResource\RelationManagers;
use App\Models\Survey\{SurveyAnswer, Survey};
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\{Select, Textarea};
use Filament\Tables\Filters\SelectFilter;

class SurveyAnswerResource extends Resource
{
    protected static ?string $model = SurveyAnswer::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = KhaoSat::class;
    protected static ?string $label = 'Câu trả lời khảo sát';
    protected static ?string $pluralLabel = 'Danh sách câu trả lời khảo sát';
    protected static ?string $slug = 'survey-answers';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('response_id')
                    ->label('Phản hồi')
                    ->relationship('response', 'id')
                    ->searchable()
                    ->required(),

                Select::make('question_id')
                    ->label('Câu hỏi')
                    ->relationship('question', 'question')
                    ->searchable()
                    ->required(),

                Textarea::make('answer')
                    ->label('Câu trả lời')
                    ->rows(4)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('response.id')
                    ->label('Phản hồi')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('response.survey.title')
                    ->label('Đợt khảo sát')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('question.question')
                    ->label('Câu hỏi')
                    ->searchable(),

                TextColumn::make('answer')
                    ->label('Câu trả lời')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Thời gian tạo')
                    ->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('survey_id')
                    ->label('Lọc theo đợt khảo sát')
                    ->options(fn() => Survey::pluck('title', 'id')->toArray())
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('response', function ($q) use ($data) {
                                $q->where('survey_id', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->placeholder('Tất cả đợt khảo sát'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSurveyAnswers::route('/'),
            'create' => Pages\CreateSurveyAnswer::route('/create'),
            'edit' => Pages\EditSurveyAnswer::route('/{record}/edit'),
        ];
    }
}
