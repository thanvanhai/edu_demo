<?php

namespace App\Services\KhaoSat;

use App\Models\Survey\{SurveyQuestion, SurveySection, SurveyAnswer};
use Illuminate\Support\Collection;

class SurveyResultService
{
    public function getAnswersAsColumns(int $surveyId): Collection
    {
        $questions = SurveyQuestion::whereHas('section', function ($q) use ($surveyId) {
            $q->where('survey_id', $surveyId);
        })->orderBy('id')->get();

        $answers = SurveyAnswer::with(['question', 'response'])
            ->whereHas('question.section', function ($q) use ($surveyId) {
                $q->where('survey_id', $surveyId);
            })
            ->get();

        $grouped = $answers->groupBy('response_id');

        $pivoted = $grouped->map(function ($group) use ($questions) {
            $firstAnswer = $group->first();
            $response = $firstAnswer->response;

            $row = [
                'response_id' => $response->id,
                'Ngày khảo sát' => optional($response->created_at)->format('d/m/Y H:i'), // 👈 thêm cột này
            ];
            foreach ($questions as $question) {
                $key = "{$question->question}";
                $answer = $group->firstWhere('question_id', $question->id)?->answer;

                // ✅ Xử lý checkbox (JSON array)
                $decoded = json_decode($answer, true);
                if (is_array($decoded)) {
                    $answer = implode(', ', $decoded);
                }

                $row[$key] = $answer ?? '';
            }
            return $row;
        });

        return $pivoted->values();
    }
}
