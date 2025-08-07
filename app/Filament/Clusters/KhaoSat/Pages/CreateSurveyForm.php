<?php

namespace App\Filament\Clusters\KhaoSat\Pages;

use App\Filament\Clusters\KhaoSat;
use App\Models\Survey\{Survey, SurveySection, SurveyQuestion};
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Forms\Components\{Wizard, Wizard\Step, TextInput, Textarea, Toggle, Repeater, Select};
use Filament\Pages\SubNavigationPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Facades\Request;

class CreateSurveyForm extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.clusters.khao-sat.pages.create-survey-form';
    protected static ?string $title = 'Tạo khảo sát';
    protected static ?string $slug = 'create-survey';
    protected static ?string $cluster = KhaoSat::class;
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    public ?array $data = [];
    public ?int $surveyId = null; // Thêm biến để lưu surveyId nếu cần cập nhật

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->surveyId = request()->query('record'); // 👈 Lấy record từ query thủ công

        if ($this->surveyId) {
            $survey = Survey::with('sections.questions')->findOrFail($this->surveyId); // ⚠️ Không dùng inject trực tiếp nữa

            $this->data = [
                'title' => $survey->title,
                'description' => $survey->description,
                'is_active' => $survey->is_active,
                'sections' => $survey->sections->map(function ($section) {
                    return [
                        'title' => $section->title,
                        'questions' => $section->questions->map(function ($question) {
                            return [
                                'question' => $question->question,
                                'type' => $question->type,
                                'options' => collect(json_decode($question->options ?? '[]'))
                                    ->map(function ($item) {
                                        if (is_object($item) && isset($item->label)) {
                                            return ['label' => $item->label];
                                        }

                                        if (is_array($item) && isset($item['label'])) {
                                            return ['label' => $item['label']];
                                        }

                                        return ['label' => $item]; // fallback nếu chỉ là chuỗi
                                    })
                                    ->toArray(),
                                'is_required' => $question->is_required,
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];
        }

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Thông tin khảo sát')
                        ->schema([
                            TextInput::make('title')
                                ->label('Tiêu đề khảo sát')
                                ->required(),

                            Textarea::make('description')
                                ->label('Mô tả khảo sát'),

                            Toggle::make('is_active')
                                ->label('Đang hoạt động')
                                ->default(true),
                        ]),

                    Step::make('Nội dung câu hỏi')
                        ->schema([
                            Repeater::make('sections')
                                ->label('Các phần (section)')
                                ->schema([
                                    TextInput::make('title')
                                        ->label('Tên phần')
                                        ->required(),

                                    Repeater::make('questions')
                                        ->label('Danh sách câu hỏi')
                                        ->schema([
                                            TextInput::make('question')
                                                ->label('Nội dung câu hỏi')
                                                ->required(),

                                            Select::make('type')
                                                ->label('Loại câu hỏi')
                                                // ->options([
                                                //     'text' => 'Text',
                                                //     'textarea' => 'Textarea',
                                                //     'select' => 'Select (dropdown)',
                                                //     'radio' => 'Radio',
                                                //     'checkbox' => 'Checkbox',
                                                //     'rating' => 'Rating (stars)',
                                                // ])
                                                ->options([
                                                    'text' => 'Văn bản một dòng',
                                                    'textarea' => 'Văn bản nhiều dòng',
                                                    'select' => 'Chọn từ danh sách',
                                                    'radio' => 'Chọn một đáp án',
                                                    'checkbox' => 'Chọn nhiều đáp án',
                                                    'rating' => 'Đánh giá (ngôi sao)',
                                                ])
                                                ->required()
                                                ->reactive(),

                                            Repeater::make('options')
                                                ->label('Tùy chọn')
                                                ->schema([
                                                    TextInput::make('label')->label('Lựa chọn'),
                                                ])
                                                ->visible(fn($get) => in_array($get('type'), ['select', 'radio', 'checkbox']))
                                                ->defaultItems(2)
                                                ->minItems(1)
                                                ->collapsible(),

                                            Toggle::make('is_required')->label('Bắt buộc')->default(true),
                                        ])
                                        ->minItems(1)
                                        ->collapsible()
                                        ->reorderable(),
                                ])
                                ->minItems(1)
                                ->collapsible()
                                ->reorderable(),
                        ]),
                ])
                    ->submitAction(
                        Action::make('submit')
                            ->label('Tạo khảo sát')
                            ->action('submit')
                            ->color('primary')
                    )
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Nếu có surveyId => cập nhật khảo sát cũ
        if ($this->surveyId) {
            $survey = Survey::findOrFail($this->surveyId);

            // Cập nhật khảo sát
            $survey->update([
                'title' => $data['title'],
                'description' => $data['description'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Xóa dữ liệu cũ: sections và questions
            $survey->sections()->each(function ($section) {
                $section->questions()->delete();
                $section->delete();
            });
        } else {
            // Tạo mới nếu không có surveyId
            $survey = Survey::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'is_active' => $data['is_active'] ?? true,
            ]);
        }

        // Tạo lại sections và questions mới
        foreach ($data['sections'] as $sectionIndex => $sectionData) {
            $section = SurveySection::create([
                'survey_id' => $survey->id,
                'title' => $sectionData['title'],
                'order' => $sectionIndex + 1,
            ]);

            foreach ($sectionData['questions'] as $questionIndex => $q) {
                SurveyQuestion::create([
                    'section_id' => $section->id,
                    'question' => $q['question'],
                    'type' => $q['type'],
                    'options' => in_array($q['type'], ['select', 'radio', 'checkbox']) ? json_encode($q['options']) : null,
                    'is_required' => $q['is_required'] ?? false,
                    'order' => $questionIndex + 1,
                ]);
            }
        }

        Notification::make()
            ->title($this->surveyId ? 'Cập nhật khảo sát thành công' : 'Tạo khảo sát thành công')
            ->success()
            ->send();

        $this->redirect(CreateSurveyForm::getUrl(['record' => $survey->id]));
    }
}
