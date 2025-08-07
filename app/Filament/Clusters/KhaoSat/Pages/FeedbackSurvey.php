<?php

namespace App\Filament\Clusters\KhaoSat\Pages;

use App\Filament\Clusters\KhaoSat;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Pages\SubNavigationPosition;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Survey\{Survey, SurveyResponse, SurveyAnswer};
use Filament\Forms\Form;
use Filament\Forms\Components\{Wizard, Wizard\Step, Actions\Action, TextInput, Textarea, Select, Radio, CheckboxList, Placeholder};
use Filament\Notifications\Notification;
use IbrahimBougaoua\FilamentRatingStar\Forms\Components\RatingStar;

class FeedbackSurvey extends Page implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-hand-thumb-up';
    protected static string $view = 'filament.clusters.khao-sat.pages.feedback-survey';
    protected static ?string $title = 'Thực hiện khảo sát';
    protected static ?string $slug = 'feedback-survey';
    protected static ?string $cluster = KhaoSat::class;
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    public ?Survey $survey = null;
    public ?array $formData = [];

    use InteractsWithForms;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->survey = Survey::where('is_open', true)->first();
        // Không redirect nữa, chỉ cần giữ $this->survey = null nếu không có khảo sát
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
    public function renderQuestion($q)
    {
        $field = "answers.{$q->id}";

        $options = collect(json_decode($q->options ?? '[]'))->pluck('label')->mapWithKeys(fn($v) => [$v => $v])->toArray();

        return match ($q->type) {
            'text' => TextInput::make($field)->label($q->question)->required($q->is_required),
            'textarea' => Textarea::make($field)->label($q->question)->required($q->is_required),
            'select' => Select::make($field)->label($q->question)->options($options)->required($q->is_required),
            'radio' => Radio::make($field)->label($q->question)->options($options)->required($q->is_required),
            'checkbox' => CheckboxList::make($field)->label($q->question)->options($options)->required($q->is_required),
            'rating' => RatingStar::make($field)
                ->label($q->question)->required($q->is_required),
            default => Placeholder::make("unk_{$q->id}")->content('Loại câu hỏi không hỗ trợ'),
        };
    }

    public function submitSurvey()
    {
        $data = $this->form->getState();

        $response = SurveyResponse::create([
            'survey_id' => $this->survey->id,
            'user_id' => auth()->id(), // hoặc null nếu không đăng nhập
        ]);

        foreach ($data['answers'] ?? [] as $questionId => $answer) {
            // Bỏ qua nếu không có câu trả lời
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
