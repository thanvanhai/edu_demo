<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class KpiPeriod extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'kpi_periods';
    //protected $connection = 'admin';

    protected $fillable = [
        'code',
        'name',
        'start_date',
        'end_date',
        'month_list'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    // === Cấu hình log hoạt động ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('KpiPeriod')
            ->logOnly([
                'code',
                'name',
                'start_date',
                'end_date',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $replacements = [
            'code' => 'Mã kỳ đánh giá',
            'name' => 'Tên kỳ đánh giá',
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'month_list' => 'Danh sách tháng'
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
    public function generateMonthList()
    {
        $start = Carbon::parse($this->start_date)->startOfMonth();
        $end = Carbon::parse($this->end_date)->startOfMonth();

        $months = [];
        while ($start <= $end) {
            $months[] = 'Tháng ' . $start->format('m/Y');
            $start->addMonth();
        }

        $this->month_list = implode(', ', $months);
        $this->updated_at = now();
    }

    protected static function booted()
    {
        static::creating(function ($period) {
            $period->generateMonthList();
        });

        static::updating(function ($period) {
            // Chỉ sinh lại nếu start_date hoặc end_date bị thay đổi
            if ($period->isDirty(['start_date', 'end_date'])) {
                $period->generateMonthList();
            }
        });
    }
}
