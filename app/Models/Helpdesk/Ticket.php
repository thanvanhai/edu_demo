<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Helpdesk\{TicketComment, TicketAttachment, TicketFeedback};
use App\Models\User;
use App\Models\NhanSu\Department;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Ticket extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'tickets';

    protected $fillable = [
        'code',
        'title',
        'description',
        'priority',
        'status',
        'user_id',
        'assigned_to',
        'category_id',
        'department_id',
        'attachment_path',
        'closed_at',
    ];

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(TicketFeedback::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('ticket')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    public function tapActivity(Activity $activity, string $eventName)
    {
        $replacements = [
            'id' => 'Mã sự cố',
            'code' => 'Mã phiếu',
            'title' => 'Tiêu đề',
            'description' => 'Mô tả',
            'priority' => 'Độ ưu tiên',
            'status' => 'Trạng thái',
            'user_id' => 'Người gửi',
            'assigned_to' => 'Người xử lý',
            'category_id' => 'Danh mục',
            'department_id' => 'Phòng ban',
            'attachment_path' => 'Tệp đính kèm',
            'closed_at' => 'Thời gian đóng',
            'created_at' => 'Thời gian tạo',
            'updated_at' => 'Thời gian cập nhật',
        ];

        $valueResolvers = [
            'status' => fn($val) => match ($val) {
                'open' => 'Mở',
                'in_progress' => 'Đang xử lý',
                'resolved' => 'Đã xử lý',
                'closed' => 'Đã đóng',
                default => 'Không rõ',
            },
            'priority' => fn($val) => match ($val) {
                'low' => 'Thấp',
                'medium' => 'Trung bình',
                'high' => 'Cao',
                'urgent' => 'Khẩn cấp',
                default => 'Không rõ',
            },
        ];

        foreach (['attributes', 'old'] as $key) {
            if ($activity->properties->has($key)) {
                $data = collect($activity->properties[$key])->mapWithKeys(function ($value, $field) use ($replacements, $valueResolvers) {
                    $label = $replacements[$field] ?? $field;
                    if (isset($valueResolvers[$field])) {
                        $value = $valueResolvers[$field]($value);
                    }
                    return [$label => $value];
                });

                $activity->properties = $activity->properties->put($key, $data);
            }
        }
    }
}
