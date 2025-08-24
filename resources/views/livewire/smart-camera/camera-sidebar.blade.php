{{-- resources/views/livewire/smart-camera/camera-sidebar.blade.php --}}
<div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 h-full overflow-y-auto">
    <div class="space-y-2">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">
            Danh sách Camera ({{ count($getState()['cameras']) }})
        </h3>
        
        @foreach($getState()['cameras'] as $camera)
            <div 
                wire:click="selectCamera('{{ $camera['camera_id'] }}')"
                class="group relative p-3 rounded-lg border cursor-pointer transition-all duration-200 hover:shadow-sm
                    {{ isset($getState()['selectedCamera']) && $getState()['selectedCamera']['camera_id'] === $camera['camera_id'] 
                        ? 'border-primary-300 bg-primary-50 dark:bg-primary-900/20 dark:border-primary-600' 
                        : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 hover:border-gray-300' }}"
            >
                {{-- Camera Name --}}
                <div class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-1">
                    {{ $camera['name'] ?? 'Camera ' . $camera['id'] }}
                </div>
                
                {{-- Status + Short ID --}}
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        ID: {{ $camera['id'] }}
                    </span>
                    
                    @if(!empty($camera['status']))
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $camera['status'] === 'online' 
                                ? 'bg-green-100 text-green-800 dark:bg-green-800/20 dark:text-green-400' 
                                : 'bg-red-100 text-red-800 dark:bg-red-800/20 dark:text-red-400' }}">
                            <div class="w-1.5 h-1.5 rounded-full mr-1 
                                {{ $camera['status'] === 'online' ? 'bg-green-400' : 'bg-red-400' }}">
                            </div>
                            {{ strtoupper($camera['status']) }}
                        </span>
                    @endif
                </div>
                
                {{-- Selected Indicator --}}
                @if(isset($getState()['selectedCamera']) && $getState()['selectedCamera']['camera_id'] === $camera['camera_id'])
                    <div class="absolute inset-y-0 left-0 w-1 bg-primary-500 rounded-l-lg"></div>
                @endif
            </div>
        @endforeach
        
        @if(empty($getState()['cameras']))
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <div class="text-sm">Không có camera nào</div>
            </div>
        @endif
    </div>
</div>
