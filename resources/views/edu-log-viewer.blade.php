@php
    $actionMap = [
        'created' => 'Tạo mới',
        'updated' => 'Cập nhật',
        'deleted' => 'Xóa',
    ];
@endphp

<div class="space-y-4">
    @forelse ($logs as $log)
        <div class="border rounded-md p-3 bg-gray-50">
            <div class="font-semibold">
                {{ $actionMap[$log->description] ?? ucfirst($log->description) }}
            </div>
            <div class="text-xs text-gray-500">
                Thời gian: {{ $log->created_at->format('d/m/Y H:i') }}<br>
                Người thực hiện: {{ $log->causer?->fullname ?? 'Hệ thống' }}
            </div>

            @if ($log->properties?->get('attributes'))
                <details class="text-sm mt-2 text-gray-600">
                    <summary class="cursor-pointer">Chi tiết</summary>
                    <ul class="list-disc ml-5 mt-1">
                        @foreach ($log->properties['attributes'] as $key => $value)
                            <li>
                                <strong>{{ $key }}</strong>:
                                {{ $log->properties['old'][$key] ?? 'N/A' }} → {{ $value }}
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    @empty
        <div class="text-gray-500 text-sm">Không có lịch sử thay đổi.</div>
    @endforelse
</div>
