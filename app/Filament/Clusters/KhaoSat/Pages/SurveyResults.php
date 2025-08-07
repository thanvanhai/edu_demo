<?php

namespace App\Filament\Clusters\KhaoSat\Pages;

use App\Filament\Clusters\KhaoSat;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use App\Services\KhaoSat\SurveyResultService;
use App\Models\Survey\{Survey};
use Filament\Support\Enums\MaxWidth;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KhaoSat\SurveyResultsExport;
use Filament\Actions\Action;

class SurveyResults extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.clusters.khao-sat.pages.survey-results';
    protected static ?string $title = 'Kết quả khảo sát';
    protected static ?string $slug = 'survey-results';
    protected static ?string $cluster = KhaoSat::class;
    protected static ?int $navigationSort = 3;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    public ?int $surveyId = null;
    public Collection $results;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Xuất Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filename = 'ket_qua_khao_sat_' . now()->format('Ymd_His') . '.xlsx';

                    return export_collection($this->results, $filename);
                }),
        ];
    }
    public function mount(): void
    {
        $this->surveyId = request()->get('surveyId') ?? Survey::first()?->id;
        $this->results = app(SurveyResultService::class)->getAnswersAsColumns($this->surveyId); // 👈 Khởi tạo
        $this->form->fill([
            'surveyId' => $this->surveyId,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('surveyId')
                ->label('Chọn khảo sát')
                ->options(Survey::pluck('title', 'id'))
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->surveyId = $state;
                })
                ->required(),
        ]);
    }
}
