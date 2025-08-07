<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KPIProgress extends Model
{
    use HasFactory, LogsActivity;
    // protected $connection = 'admin';
    protected $table = 'kpi_progress';

    protected $fillable = [
        'kpi_allocation_id',
        'kpi_allocation_tree_id',
        'year',
        'month',
        'self_evaluation',
        'actual_result',
        'council_evaluation',
        'progress_percent',
        'total_progress_percent',
        'note',
        'status',
        'evidences', //lưu trữ tên file các minh chứng
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'integer',
        'evidences' => 'array',
    ];
    // === Ghi log hoạt động ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('kpi_progress')
            ->logOnly([
                'kpi_allocation_id',
                'kpi_allocation_tree_id',
                'year',
                'month',
                'self_evaluation',
                'actual_result',
                'council_evaluation',
                'progress_percent',
                'total_progress_percent',
                'note',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    // === Tùy chỉnh hiển thị log ===
    public function tapActivity(Activity $activity, string $eventName)
    {
        $replacements = [
            'kpi_allocation_id' => 'Phân bổ KPI',
            'kpi_allocation_tree_id' => 'Phân bổ KPI (Cây)',
            'year' => 'Năm',
            'month' => 'Tháng',
            'self_evaluation' => 'Tự đánh giá',
            'actual_result' => 'Kết quả thực tế',
            'council_evaluation' => 'Đánh giá hội đồng',
            'progress_percent' => 'Tiến độ (%)',
            'total_progress_percent' => 'Tiến độ tổng (%)',
            'note' => 'Mô tả kết quả thực hiện',
            'status' => 'Trạng thái',
        ];

        $valueResolvers = [
            'kpi_allocation_tree_id' => fn($id) => $this->allocationTree()->find($id)
                ? $this->allocationTree()->find($id)->department?->name . ' - ' . $this->allocationTree()->find($id)->kpiTree?->name
                : 'Không rõ',
            'status' => fn($val) => match ($val) {
                self::STATUS_PENDING => 'Nháp',
                self::STATUS_APPROVED => 'Đã gửi',
                default => 'Không rõ',
            },
        ];

        foreach (['attributes', 'old'] as $keyType) {
            if ($activity->properties->has($keyType)) {
                $data = collect($activity->properties[$keyType])->mapWithKeys(function ($value, $key) use ($replacements, $valueResolvers) {
                    $label = $replacements[$key] ?? $key;

                    if (array_key_exists($key, $valueResolvers)) {
                        $value = $valueResolvers[$key]($value);
                    }

                    return [$label => $value];
                });

                $activity->properties = $activity->properties->put($keyType, $data);
            }
        }
    }
    public function getKpiCriteriaNameAttribute()
    {
        return optional($this->allocationTree?->kpiTree)->name;
    }
    // === Quan hệ ===

    public function allocationTree()
    {
        return $this->belongsTo(KpiAllocationTree::class, 'kpi_allocation_tree_id');
    }
    // Truy cập nhanh department và criteria từ allocation
    public function department()
    {
        return $this->allocationTree?->department();
    }

    public function kpiTree()
    {
        return $this->allocationTree?->kpiTree();
    }
    // === Accessors / Helpers (tuỳ chọn) ===

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Nháp',
            self::STATUS_APPROVED => 'Đã gửi',
            default => 'Không rõ',
        };
    }
    //cập nhật cột tổng tiến độ lên bảng KpiAllocation
    protected static function booted()
    {
        static::saved(function ($progress) {
            $progress->updateTotalProgressUpToSelf();
            $progress->allocationTree?->updateTotalProgressPercent();
        });

        static::deleted(function ($progress) {
            // Nếu xóa, vẫn cần cập nhật lại tích lũy cho các dòng sau
            $progress->recalculateAllTotalsForTree();
            $progress->allocationTree?->updateTotalProgressPercent();
        });

        static::deleting(function ($progress) {
            if (is_array($progress->evidences)) {
                foreach ($progress->evidences as $file) {
                    try {
                        if (isset($file['path']) && Storage::disk('public')->exists($file['path'])) {
                            Storage::disk('public')->delete($file['path']);
                        }
                    } catch (Throwable $e) {
                        Log::warning('Không thể xoá file: ' . ($file['path'] ?? 'N/A'));
                    }
                }
            }
        });
    }
    // Cập nhật tổng tiến độ cho bản ghi hiện tại và tất cả các bản ghi trước đó trong cùng một cây phân bổ KPI
    public function updateTotalProgressUpToSelf(): void
    {
        $sum = self::where('kpi_allocation_tree_id', $this->kpi_allocation_tree_id)
            ->where(function ($query) {
                $query->where('year', '<', $this->year)
                    ->orWhere(function ($q) {
                        $q->where('year', $this->year)
                            ->where('month', '<=', $this->month);
                    });
            })
            ->sum('progress_percent');

        // $this->refresh()->updateQuietly(['total_progress_percent' => $sum]);
        // Gọi lại find để đảm bảo không dirty field
        self::where('id', $this->id)->update([
            'total_progress_percent' => $sum,
        ]);
    }
    // Cập nhật lại tổng tiến độ cho tất cả các bản ghi trong cùng một cây phân bổ KPI
    public function recalculateAllTotalsForTree(): void
    {
        $all = self::where('kpi_allocation_tree_id', $this->kpi_allocation_tree_id)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        foreach ($all as $progress) {
            $progress->updateTotalProgressUpToSelf();
        }
    }
}
