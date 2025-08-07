<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Helpdesk\Ticket;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class TicketFeedback extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ticket_feedbacks';

    protected $fillable = ['ticket_id', 'user_id', 'rating', 'comment'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('TicketFeedback')
            ->logOnly([
                'ticket_id',
                'user_id',
                'rating',
                'feedback',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $replacements = [
            'ticket_id' => 'Mã phiếu',
            'user_id' => 'Người phản hồi',
            'rating' => 'Mức đánh giá',
            'feedback' => 'Nội dung phản hồi',
        ];

        foreach (['attributes', 'old'] as $key) {
            if ($activity->properties->has($key)) {
                $data = collect($activity->properties[$key])->mapWithKeys(function ($value, $field) use ($replacements) {
                    $label = $replacements[$field] ?? $field;
                    return [$label => $value];
                });

                $activity->properties = $activity->properties->put($key, $data);
            }
        }
    }
}
