<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use BezhanSalleh\FilamentShield\Support\Utils;

/**
 * Kiểm tra quyền của user (hỗ trợ cả quyền Shield mặc định và quyền custom).
 *
 * @param string $action   - Tên hành động (vd: view, update, delete, approve_salary,...)
 * @param string|null $resource - Resource class (vd: KpiTreeResource::class)
 */
function userCan(string $action, string $resource = null): bool
{
    $user = Auth::user();

    if (! $user) {
        return false;
    }
    // ✅ Trường hợp có resource (chuẩn Shield)
    if ($resource) {
        $identifier = Str::of($resource)
            ->afterLast('Resources\\')
            ->before('Resource')
            ->replace('\\', '')
            ->snake()
            ->replace('_', '::');

        return $user->can($action . '_' . $identifier);
    }

    // ✅ Trường hợp không có resource (quyền custom)
    return $user->can($action);
}


if (! function_exists('format_date')) {
    function format_date($date, $format = 'd/m/Y')
    {
        return Carbon::parse($date)->format($format);
    }
}
