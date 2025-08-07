<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class KPITree extends Model
{
    use NodeTrait, LogsActivity, SoftDeletes;
    //protected $connection = 'admin';
    protected $table = 'kpi_tree';

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'type',
    ];

    protected $casts = [
        'parent_id' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
    // === Truy cập các anh chị em ===
    protected function indentedName(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) =>
            // Xác định màu và font-weight theo type
            (function () use ($attributes) {
                $type = $attributes['type'] ?? '';
                $color = match ($type) {
                    'Phương diện' => '#2563eb', // xanh dương
                    'Mục tiêu'    => '#16a34a', // xanh lá
                    'Tiêu chí'    => '#6b7280', // xám
                    default       => '#6b7280',
                };
                $fontWeight = $type === 'Phương diện' ? 'bold' : 'normal';
                $marginLeft = 20 * ($attributes['depth'] ?? 0);
                $name = e($attributes['name'] ?? '');
                return "<div style=\"margin-left: {$marginLeft}px; color: {$color}; font-weight: {$fontWeight};\">{$name}</div>";
            })()
        );
    }
    public function isFirstSibling(): bool
    {
        return $this->isSelf($this->getLeftSibling());
    }

    public function isLastSibling(): bool
    {
        return $this->isSelf($this->getRightSibling());
    }

    protected function isSelf($other): bool
    {
        return $other === null;
    }
    // === Ghi log hoạt động ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('KPITree')
            ->logOnly([
                'code',
                'name',
                'type',
                'parent_id',
                '_lft',
                '_rgt',
                'depth',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $replacements = [
            'code' => 'Code',
            'name' => 'Phương diện/Mục tiêu/Tiêu chí',
            'type' => 'Loại',
            'parent_id' => 'Nhóm cha',
            '_lft' => 'Vị trí trái',
            '_rgt' => 'Vị trí phải',
            'depth' => 'Cấp độ',
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
    public function descendantsAndSelf($id)
    {
        $ids = [$id];
        $children = static::where('parent_id', $id)->pluck('id');
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->descendantsAndSelf($childId));
        }
        return $ids; // Trả về mảng id
    }

    public function ancestorsAndSelf($id)
    {
        $ids = [$id];
        $parent = static::where('id', $id)->value('parent_id');
        if ($parent) {
            $ids = array_merge($ids, $this->ancestorsAndSelf($parent));
        }
        return static::whereIn('id', $ids);
    }
}
