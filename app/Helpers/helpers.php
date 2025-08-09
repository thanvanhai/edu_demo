<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\CollectionExport;


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

if (!function_exists('export_collection')) {
    function export_collection(Collection $data, string $filename = 'export.xlsx')
    {
        return Excel::download(
            new CollectionExport($data),
            $filename
        );
    }
}

if (!function_exists('getDateTimeFormat')) {
    /**
     * Trả về định dạng PHP cho DateTimePicker dựa theo key format.
     *
     * @param string|null $formatKey
     * @return string
     */
    function getDateTimeFormat(?string $formatKey): string
    {
        $formatMap = [
            'date' => 'Y-m-d',
            'time' => 'H:i',
            'datetime' => 'Y-m-d H:i',
        ];

        return $formatMap[$formatKey] ?? 'Y-m-d H:i';
    }
}