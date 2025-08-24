{{-- camera-toolbar.blade.php - FIXED: Chỉ có 1 root element --}}
<div>
    {{-- Toolbar --}}
    <div class="flex items-center justify-between w-full">
        <div class="flex space-x-2">
            {{-- Nút thêm camera --}}
            <button wire:click="openCreateCamera" 
                    class="px-3 py-2 rounded-lg bg-primary-500 text-white hover:bg-primary-600 text-sm transition-colors">
                ➕ Thêm Camera
            </button>

            {{-- Nút reload --}}
            <button wire:click="loadCameras" 
                    wire:loading.attr="disabled"
                    wire:target="loadCameras"
                    class="px-3 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm transition-colors disabled:opacity-50">
                <span wire:loading.remove wire:target="loadCameras">⟳ Tải lại</span>
                <span wire:loading wire:target="loadCameras">⟳ Đang tải...</span>
            </button>
        </div>

        {{-- Search --}}
        <div>
            <input type="text" 
                   placeholder="Tìm camera..."
                   class="border rounded-lg px-3 py-1 text-sm w-48 dark:bg-gray-800 dark:border-gray-600">
        </div>
    </div>

    {{-- Modal thêm camera - BÂY GIỜ NẰM TRONG CÙNG 1 ROOT ELEMENT --}}
    @if($showCreateModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black/50 z-50" 
             wire:click.self="$set('showCreateModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-[500px] p-6"
                 @click.stop>
                <h2 class="text-lg font-semibold mb-4">Thêm Camera mới</h2>
                
                <form wire:submit="saveCamera">
                    <div class="space-y-3">
                        <div>
                            <input type="text" 
                                   wire:model="formData.name"
                                   placeholder="Tên camera"
                                   class="w-full border rounded px-3 py-2 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        </div>

                        <div>
                            <select wire:model="formData.camera_type"
                                    class="w-full border rounded px-3 py-2 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                <option value="ip_camera">IP Camera</option>
                                <option value="usb_camera">USB Camera</option>
                                <option value="rtsp_camera">RTSP Camera</option>
                            </select>
                        </div>

                        <div>
                            <input type="text" 
                                   wire:model="formData.stream_url"
                                   placeholder="Stream URL"
                                   class="w-full border rounded px-3 py-2 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        </div>

                        <div>
                            <input type="text" 
                                   wire:model="formData.location"
                                   placeholder="Vị trí"
                                   class="w-full border rounded px-3 py-2 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        </div>

                        <div>
                            <textarea wire:model="formData.description"
                                      placeholder="Mô tả"
                                      rows="3"
                                      class="w-full border rounded px-3 py-2 resize-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6 space-x-2">
                        <button type="button" 
                                wire:click="$set('showCreateModal', false)"
                                class="px-4 py-2 rounded-lg bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                            Hủy
                        </button>
                        
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="saveCamera"
                                class="px-4 py-2 rounded-lg bg-primary-500 text-white hover:bg-primary-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="saveCamera">Lưu</span>
                            <span wire:loading wire:target="saveCamera">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Đang lưu...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>