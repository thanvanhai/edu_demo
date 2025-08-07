<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Helpdesk\Ticket;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class TicketComment extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ticket_comments';

    protected $fillable = ['ticket_id', 'user_id', 'content'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('TicketComment')
            ->logOnly([
                'ticket_id',
                'user_id',
                'comment',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $replacements = [
            'ticket_id' => 'Mã phiếu',
            'user_id' => 'Người bình luận',
            'comment' => 'Nội dung bình luận',
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
