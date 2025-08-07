<x-filament::page>
    {{-- Form chọn kỳ và phòng ban --}}
    {{ $this->form }}

    @if ($treeData)
    <div class="mt-6">
        <h2 class="text-lg font-bold mb-2">Phân bổ KPI theo cấu trúc cây</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-center w-8">✔</th>
                        <th class="px-3 py-2 text-center">Phương diện<br>Mục tiêu<br>Tiêu chí</th>
                        <th class="px-3 py-2 text-center">Chỉ mục<br>sắp xếp</th> <!-- Thêm dòng này -->
                        <th class="px-3 py-2 text-center">Chỉ tiêu</th>
                        <th class="px-3 py-2 text-center">Đơn vị</th>
                        <th class="px-3 py-2 text-center">Trọng số<br>Phương Diện</th>
                        <th class="px-3 py-2 text-center">Trọng số<br>Mục Tiêu</th>
                        <th class="px-3 py-2 text-center">Trọng số<br>KPI</th>
                        <th class="px-3 py-2 text-center">Ghi chú</th>
                        <th class="px-3 py-2 text-center">Di chuyển</th> {{-- Thêm cột di chuyển --}}
                    </tr>
                </thead>
                <tbody>
                    @foreach ($treeData as $index => $row)
                    @continue(empty($row['type']) && empty($row['name'])) {{-- Bỏ qua dòng rỗng hoặc không hợp lệ --}}

                    @php
                    $isDirection = $row['type'] === 'Phương diện';
                    $isObjective = $row['type'] === 'Mục tiêu';
                    $isCriteria = $row['type'] === 'Tiêu chí';
                    @endphp

                    <tr
                        wire:key="tree-row-{{ $row['id'] ?? 'row-'.$index }}"
                        class="{{ $isDirection ? 'bg-blue-50 font-semibold' : ($isObjective ? 'bg-green-50' : '') }}">
                        {{-- Checkbox --}}
                        <td class="text-center">
                            <input
                                type="checkbox"
                                wire:model.lazy="treeData.{{ $index }}.selected"
                                class="form-checkbox" />
                        </td>

                        {{-- Tên KPI --}}
                        <td class="px-3 py-2">
                            <span style="display: block;  padding-left: {{ ($row['depth'] ?? 0) * 20 }}px">
                                {{ $row['name'] ?? 'Không tên' }}
                            </span>
                        </td>

                        {{-- Chỉ mục sắp xếp --}}
                        <td class="px-2 py-1 text-center">
                            <input type="text" class="w-16 rounded border-gray-300 text-center"
                                placeholder="Sort"
                                wire:model.defer="treeData.{{ $index }}.sortcode" />
                        </td>

                        {{-- Cột chỉ tiêu --}}
                        <td class="px-2 py-1 text-center">
                            @if ($isCriteria)
                            <input type="text" class="w-24 rounded border-gray-300" placeholder="Chỉ tiêu"
                                wire:model.defer="treeData.{{ $index }}.allocated_value" />
                            @else
                            <span class="text-gray-400 italic">—</span>
                            @endif
                        </td>

                        {{-- Đơn vị --}}
                        <td class="px-2 py-1 text-center">
                            @if ($isCriteria)
                            <input type="text" class="w-28 rounded border-gray-300" placeholder="Đơn vị"
                                wire:model.defer="treeData.{{ $index }}.unit" />
                            @else
                            <span class="text-gray-400 italic">—</span>
                            @endif
                        </td>

                        {{-- Trọng số Phương diện --}}
                        <td class="px-2 py-1 text-center align-top">
                            @if ($isDirection)
                            <input type="number" step="5" max="100"
                                class="w-24 rounded border-gray-300 text-right"
                                placeholder="Phương diện"
                                wire:model.lazy="treeData.{{ $index }}.weight_direction" />
                            @elseif ($isCriteria)
                            <input type="number" class="w-24 rounded border-gray-300 bg-gray-100 text-gray-600 text-right"
                                value="{{ $row['weight_direction'] ?? '' }}" disabled />
                            @else
                            <span class="text-gray-400 italic">—</span>
                            @endif
                        </td>

                        {{-- Trọng số Mục tiêu --}}
                        <td class="px-2 py-1 text-center">
                            @if ($isObjective)
                            <input type="number" step="5" max="100"
                                class="w-24 rounded border-gray-300"
                                placeholder="Mục tiêu"
                                wire:model.lazy="treeData.{{ $index }}.weight_objective" />
                            @elseif ($isCriteria)
                            <input type="number"
                                class="w-24 rounded border-gray-300 bg-gray-100 text-gray-600"
                                value="{{ $row['weight_objective'] ?? '' }}"
                                disabled />
                            @else
                            <span class="text-gray-400 italic">—</span>
                            @endif
                        </td>

                        {{-- Trọng số KPI --}}
                        <td class="px-2 py-1 text-center">
                            @if ($isCriteria)
                            <input type="number" step="5" max="100"
                                class="w-24 rounded border-gray-300"
                                placeholder="KPI"
                                wire:model.defer="treeData.{{ $index }}.weight_kpi" />
                            @else
                            <span class="text-gray-400 italic">—</span>
                            @endif
                        </td>

                        {{-- Ghi chú --}}
                        <td class="px-2 py-1 text-center">
                            <input type="text" class="w-full rounded border-gray-300"
                                placeholder="Nhập ghi chú"
                                wire:model.defer="treeData.{{ $index }}.note" />
                        </td>

                        {{-- Nút di chuyển nhánh --}}
                        <td class="px-1 py-1 text-center">
                            @if ($this->canAction('sửa'))
                            <button type="button" wire:click="moveBranchUp({{ $index }})" title="Lên" class="text-blue-600 hover:underline" style="padding:0 4px;">⬆️</button>
                            <button type="button" wire:click="moveBranchDown({{ $index }})" title="Xuống" class="text-blue-600 hover:underline" style="padding:0 4px;">⬇️</button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Nút lưu --}}
        <div class="mt-4 flex justify-end">
            @if ($this->isLocked && $this->canAction('thêm'))
            <x-filament::button wire:click="save" color="success">
                💾 Lưu phân bổ KPI
            </x-filament::button>
            @endif
        </div>
    </div>
    @endif
</x-filament::page>