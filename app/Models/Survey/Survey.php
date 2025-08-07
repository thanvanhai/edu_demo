<?php

namespace App\Models\Survey;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Survey extends Model
{
    protected $table = 'surveys';
    protected $fillable = ['title', 'description', 'is_active', 'is_open'];
    protected $casts = [
        'is_open' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(SurveySection::class);
    }

    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyQuestion::class, SurveySection::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
