<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Helpdesk\Ticket;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class TicketAttachment extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ticket_attachments';

    protected $fillable = ['ticket_id', 'file_path', 'type ', 'uploaded_by'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('TicketAttachment')
            ->logOnly([
                'ticket_id',
                'file_path',
                'type',
                'uploaded_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $replacements = [
            'ticket_id' => 'Mã phiếu',
            'file_path' => 'Đường dẫn tệp',
            'type' => 'Loại tạo',
            'uploaded_by' => 'Người tải lên',
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
