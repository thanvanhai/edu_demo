<?php

namespace App\Models\KPI;

use App\Models\KPI\{KPITree};
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

/**
 * Class KPIImportService
 * Dịch vụ để nhập khẩu các tiêu chí KPI từ file Excel.
 */
class KPIImportService
{
    /**
     * Import KPI tree from an Excel file with specified type.
     *
     * @param string $filePath Path to the Excel file.
     * @param string $type Type of KPI tree ('Phương diện', 'Mục tiêu', 'Chỉ tiêu').
     * @return string|null URL of the log file if there are errors, null if successful.
     * @throws \Exception If there are validation errors or file issues.
     */
    public function importKpiTreeByType(string $filePath, string $type): ?string
    {
        if (!in_array($type, ['Phương diện', 'Mục tiêu', 'Tiêu chí'])) {
            throw new \Exception("Loại không hợp lệ: $type. Chỉ chấp nhận 'Phương diện', 'Mục tiêu' hoặc 'Tiêu chí'.");
        }

        if (!file_exists($filePath)) {
            throw new \Exception("Không tìm thấy file: $filePath");
        }

        $rows = Excel::toArray([], $filePath)[0];

        if (empty($rows)) {
            throw new \Exception("File Excel không có dữ liệu (sheet đầu tiên rỗng).");
        }

        $skipped = [];
        $importedCount = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Bỏ header

            $excelRow = $index + 1;
            $stt       = isset($row[0]) ? trim((string) $row[0]) : null;
            $code      = isset($row[1]) ? trim((string) $row[1]) : '';
            $name      = isset($row[2]) ? trim((string) $row[2]) : '';
            $parentName = isset($row[3]) ? trim((string) $row[3]) : null;

            // Bỏ dòng trống hoàn toàn
            if (!is_array($row) || empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                $skipped[] = "Dòng $excelRow (STT: $stt): Trống hoặc không hợp lệ.";
                continue;
            }

            if (!$name) {
                $skipped[] = "Dòng $excelRow (STT: $stt): Thiếu tên.";
                continue;
            }

            $parentId = null;

            if ($type !== 'Phương diện') {
                if (!$parentName) {
                    $skipped[] = "Dòng $excelRow (STT: $stt): Thiếu tên nhóm cha.";
                    continue;
                }

                $expectedParentType = $type === 'Mục tiêu' ? 'Phương diện' : 'Mục tiêu';

                $parent = KPITree::where('name', $parentName)
                    ->where('type', $expectedParentType)
                    ->first();

                if (!$parent) {
                    $skipped[] = "Dòng $excelRow (STT: $stt): Không tìm thấy {$expectedParentType} cha [$parentName].";
                    continue;
                }

                $parentId = $parent->id;
            }

            if (KPITree::where('name', $name)->exists()) {
                $skipped[] = "Dòng $excelRow (STT: $stt): Trùng tên [$name].";
                continue;
            }

            KPITree::create([
                'code'      => $code,
                'name'      => $name,
                'type'      => $type,
                'parent_id' => $parentId,
            ]);

            $importedCount++;
        }

        if (method_exists(KPITree::class, 'fixTree')) {
            KPITree::fixTree();
        }

        if (count($skipped)) {
            $timestamp = now()->format('Ymd_His');
            $filename = "import-kpitree-{$type}-skipped-{$timestamp}.txt";
            $path = "logs/{$filename}";

            Storage::disk('public')->put($path, implode("\n", $skipped));

            return Storage::disk('public')->url($path);
        }

        return null;
    }
}
