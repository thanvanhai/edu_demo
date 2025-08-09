<?php

namespace App\Filament\Clusters\KhaoSat\Pages;

/**
 * FeedbackSurveyBase.php
 * This file is part of the KhaoSat project.
 * It is subject to the license terms in the LICENSE file found in the top-level directory of
 * this distribution and at
 */

use App\Models\Survey\{Survey, SurveyResponse, SurveyAnswer};
use Filament\Pages\Page;
use App\Filament\Clusters\KhaoSat;
use Filament\Support\Enums\MaxWidth;
use Filament\Pages\SubNavigationPosition;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\{
    Wizard,
    Wizard\Step,
    Actions\Action,
    TextInput,
    Textarea,
    Select,
    Radio,
    CheckboxList,
    Placeholder,
    DateTimePicker,
    DatePicker,
    TimePicker,
    FileUpload
};
use Filament\Notifications\Notification;
use IbrahimBougaoua\FilamentRatingStar\Forms\Components\RatingStar;

abstract class FeedbackSurveyBase extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-hand-thumb-up';
    protected static string $view = 'filament.clusters.khao-sat.pages.feedback-survey-base';
    protected static ?string $title = 'Thực hiện khảo sát';
    protected static ?string $cluster = KhaoSat::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public ?Survey $survey = null;
    public ?array $formData = [];
    protected static ?string $surveySlug = null;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    /**
     * Lấy ID khảo sát từ slug của class con.
     * Mỗi class con sẽ định nghĩa một slug duy nhất.
     */
    // Tự động lấy title từ DB
    public static function getSurveyTitle(): string
    {
        $survey = static::getSurvey();
        return $survey?->title ?? 'Không tìm thấy khảo sát';
    }

    public function getTitle(): string
    {
        return static::getSurveyTitle();
    }

    public static function getNavigationLabel(): string
    {
        return static::getSurveyTitle();
    }
    public static function getSurvey(): ?Survey
    {
        // Lấy khảo sát dựa trên slug đã định nghĩa trong class con
        return Survey::where('slug', static::$slug)->first();
    }

    public function mount(): void
    {
        $this->survey = static::getSurvey();

        if ($this->survey) {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make(
                    $this->survey->sections->map(function ($section) {
                        return Step::make($section->title)
                            ->schema(
                                $section->questions->map(fn($q) => $this->renderQuestion($q))->toArray()
                            );
                    })->toArray()
                )
                    ->submitAction(
                        Action::make('submit')
                            ->label('Gửi khảo sát')
                            ->action('submitSurvey')
                            ->color('primary')
                    ),
            ])
            ->statePath('formData');
    }

    protected function renderQuestion($q)
    {
        $field = "answers.{$q->id}";

        $options = collect(json_decode($q->options ?? '[]'))
            ->pluck('label')
            ->mapWithKeys(fn($v) => [$v => $v])
            ->toArray();

        return match ($q->type) {
            'text' => TextInput::make($field)->label($q->question)->required($q->is_required),
            'textarea' => Textarea::make($field)->label($q->question)->required($q->is_required),
            'select' => Select::make($field)->label($q->question)->options($options)->required($q->is_required),
            'radio' => Radio::make($field)->label($q->question)->options($options)->required($q->is_required),
            'checkbox' => CheckboxList::make($field)->label($q->question)->options($options)->required($q->is_required),
            'number' => TextInput::make($field)
                ->label($q->question)
                ->numeric()
                ->required($q->is_required)
                ->extraInputAttributes([
                    'step' => $q->format === 'integer' ? '1' : ($q->format ?? 'any'),
                ]),
            'datetime' => match ($q->format) {
                'date' => DatePicker::make($field)
                    ->label($q->question)
                    ->required($q->is_required)
                    ->displayFormat('d/m/Y')
                    ->format(getDateTimeFormat('date')),
                'time' => TimePicker::make($field)
                    ->label($q->question)
                    ->required($q->is_required)
                    ->displayFormat('H:i')
                    ->format(getDateTimeFormat('time')),
                default => DateTimePicker::make($field)
                    ->label($q->question)
                    ->required($q->is_required)
                    ->displayFormat('d/m/Y H:i')
                    ->format(getDateTimeFormat('datetime')),
            },
            'rating' => RatingStar::make($field)
                ->label($q->question)->required($q->is_required)
                ->helperText("⭐: Rất không hài lòng ⭐⭐: Không hài lòng ⭐⭐⭐: Bình thường/Không ý kiến ⭐⭐⭐⭐: Khá hài lòng ⭐⭐⭐⭐⭐: Rất hài lòng")
                ->extraAttributes(['style' => 'white-space: pre-line;']),
            'file' => FileUpload::make($field)
                ->label($q->question)
                ->required($q->is_required)
                ->directory('survey_uploads') // thư mục lưu file
                ->maxSize(10240) // tối đa 10MB
                ->preserveFilenames(),
            default => Placeholder::make("unk_{$q->id}")->content('Loại câu hỏi không hỗ trợ'),
        };
    }

    public function submitSurvey()
    {
        $data = $this->form->getState();

        $response = SurveyResponse::create([
            'survey_id' => $this->survey->id,
            'user_id' => auth()->id(),
        ]);

        foreach ($data['answers'] ?? [] as $questionId => $answer) {
            if (blank($answer)) {
                continue;
            }
            SurveyAnswer::create([
                'response_id' => $response->id,
                'question_id' => $questionId,
                'answer' => is_array($answer) ? json_encode($answer) : $answer,
            ]);
        }

        Notification::make()
            ->title('Cảm ơn bạn đã trả lời khảo sát!')
            ->success()
            ->send();

        $this->redirect("/admin/survey/survey-results?surveyId={$this->survey->id}");
    }
}
