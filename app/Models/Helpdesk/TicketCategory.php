<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Helpdesk\Ticket;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class TicketCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ticket_categories';

    protected $fillable = ['name', 'description'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('TicketCategory') // Tên sẽ hiển thị trong activity log
            ->logOnly([
                'name',
                'description',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $replacements = [
            'name' => 'Tên danh mục',
            'description' => 'Mô tả',
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
