<?php

namespace App\Models\Survey;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAnswer extends Model
{
    protected $table = 'survey_answers';
    protected $fillable = ['response_id', 'question_id', 'answer'];

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }
    public function survey()
    {
        return $this->hasOneThrough(
            Survey::class,
            SurveyResponse::class,
            'id',            // Foreign key on survey_responses
            'id',            // Foreign key on surveys
            'response_id',   // Local key on survey_answers
            'survey_id'      // Local key on survey_responses
        );
    }
}
