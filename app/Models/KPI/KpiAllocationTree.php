<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Arr;
use App\Models\NhanSu\Department;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class KpiAllocationTree extends Model
{
    use HasFactory, LogsActivity, NodeTrait, SoftDeletes;

    protected $table = 'kpi_allocation_tree';
    // protected $connection = 'admin';

    protected $fillable = [
        'kpi_tree_id',
        'parent_id',
        'name',
        'code',
        'department_id',
        'type',
        'kpi_period_id',
        'allocated_value',
        'unit',
        'weight_direction',
        'weight_objective',
        'weight_kpi',
        'expected_ratio',
        'evaluation_score',
        'total_progress_percent',
        'status',
        'note',
        'sortreport',
        'sortcode',
        'depth',
    ];

    protected $casts = [
        'weight_direction'       => 'decimal:2',
        'weight_objective'       => 'decimal:2',
        'weight_kpi'             => 'decimal:2',
        'expected_ratio'         => 'decimal:2',
        'evaluation_score'       => 'decimal:2',
        'total_progress_percent' => 'decimal:2',
        'status'                 => 'integer',
        'sortreport'             => 'integer',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    // === Ghi log hoạt động ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('KpiAllocationTree')
            ->logOnly([
                'id',
                'parent_id',
                'name',
                'code',
                'type',
                'kpi_tree_id',
                'department_id',
                'kpi_period_id',
                'allocated_value',
                'unit',
                'weight_direction',
                'weight_objective',
                'weight_kpi',
                'expected_ratio',
                'evaluation_score',
                'total_progress_percent',
                'status',
                'note',
                'sortreport',
                'sortcode'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $replacements = [
            'id' => 'Mã phân bổ KPI',
            'parent_id' => 'Mã phân bổ KPI cha',
            'name' => 'Tên phân bổ KPI',
            'code'  => 'Mã phân bổ KPI',
            'type'  => 'Loại phân bổ KPI',
            'kpi_tree_id' => 'Mã cây KPI',
            'department_id' => 'Đơn vị',
            'kpi_period_id' => 'Kỳ đánh giá',
            'allocated_value' => 'Chỉ tiêu',
            'unit' => 'Đơn vị tính',
            'weight_direction' => 'Trọng số định hướng',
            'weight_objective' => 'Trọng số mục tiêu',
            'weight_kpi' => 'Trọng số KPI',
            'expected_ratio' => 'Tỷ lệ kỳ vọng',
            'evaluation_score' => 'Điểm đánh giá',
            'total_progress_percent' => 'Tiến độ tổng',
            'status' => 'Trạng thái',
            'note' => 'Ghi chú',
            'sortreport'   => 'Thứ tự sắp xếp báo cáo',
            'sortcode' => 'Mã sắp xếp',
        ];

        $valueResolvers = [
            'status' => fn($val) => match ((int) $val) {
                0 => 'Chưa thực hiện',
                1 => 'Đang thực hiện',
                2 => 'Hoàn thành',
                3 => 'Đã hủy',
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
    // === Trả về tên với độ thụt lề ===
    protected function indentedName(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) =>
            Str::of("\u{00A0}")
                ->repeat(10 * ($attributes['depth'] ?? 0)) // ✅ thêm ngoặc cho đúng logic
                . $attributes['name'],
        );
    }

    public function getNameWithDepthAttribute(): string
    {
        $color = match ($this->type) {
            'Phương diện' => '#2563eb', // Xanh dương
            'Mục tiêu'    => '#16a34a', // Xanh lá
            'Tiêu chí'    => '#6b7280', // Xám
            default       => '#6b7280',
        };

        $fontWeight = $this->type === 'Phương diện' ? 'bold' : 'normal';
        $marginLeft = $this->depth * 25;
        $escapedName = e($this->name);

        return "<div style='margin-left: {$marginLeft}px; color: {$color}; font-weight: {$fontWeight};'>{$escapedName}</div>";
    }

    public function kpiTree()
    {
        return $this->belongsTo(KPITree::class, 'kpi_tree_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function kpiperiod()
    {
        return $this->belongsTo(KpiPeriod::class, 'kpi_period_id');
    }

    public function kpiprogresses()
    {
        return $this->hasMany(KPIProgress::class, 'kpi_allocation_tree_id');
    }
    public function parent()
    {
        return $this->belongsTo(KpiAllocationTree::class, 'parent_id');
    }
    // ✅ Trả về tiến độ từ bảng kpiprogresses
    public function getCurrentProgressValueAttribute()
    {
        return $this->kpiprogresses->first()?->progress_percent ?? 0;
    }

    public function getCurrentNoteAttribute()
    {
        return $this->kpiprogresses->first()?->note;
    }


    public function updateTotalProgressPercent(): void
    {
        $average = $this->kpiprogresses()->sum('progress_percent') ?? 0;
        $this->update(['total_progress_percent' => round($average, 2)]);
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            $model->evaluation_score =
                ($model->total_progress_percent ?? 0)
                * ($model->weight_kpi / 100 ?? 0)
                * ($model->weight_objective / 100 ?? 0);
        });
    }

    // Accessor cho input_progress (không lưu vào DB)
    public function getInputProgressAttribute()
    {
        return null; // Luôn trả về null, chỉ để Filament không báo lỗi
    }

    public function setInputProgressAttribute($value)
    {
        // Không làm gì cả, hoặc có thể lưu vào biến tạm nếu muốn
    }

    // Accessor cho input_note (không lưu vào DB)
    public function getInputNoteAttribute()
    {
        return null;
    }

    public function setInputNoteAttribute($value)
    {
        // Không làm gì cả
    }

    // Accessor cho input_evidence (không lưu vào DB)
    public function getInputEvidenceAttribute()
    {
        return null; // Luôn trả về null, chỉ để Filament không báo lỗi
    }

    public function setInputEvidenceAttribute($value)
    {
        // Không làm gì cả, hoặc có thể lưu vào biến tạm nếu muốn
    }
}
