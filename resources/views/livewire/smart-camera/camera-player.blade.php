<div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 h-full flex flex-col">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-600">
        <div class="flex items-center space-x-2">
            <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                @if($getState()['selectedCamera'])
                    Live: {{ $getState()['selectedCamera']['name'] }}
                @else
                    Chưa chọn camera
                @endif
            </div>
        </div>

        {{-- Controls --}}
        <div class="flex items-center space-x-2">
            @if($getState()['selectedCamera'])
                <button 
                    type="button"
                    onclick="refreshStream()"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>

                <span class="text-xs px-2 py-1 rounded-full font-medium
                    {{ ($getState()['selectedCamera']['status'] ?? null) === 'online' 
                        ? 'bg-green-100 text-green-700 dark:bg-green-800/20 dark:text-green-400' 
                        : 'bg-red-100 text-red-700 dark:bg-red-800/20 dark:text-red-400' }}">
                    {{ strtoupper($getState()['selectedCamera']['status'] ?? 'offline') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Video Container --}}
    <div class="flex-1 relative">
        @if($getState()['selectedCamera'] && $getState()['streamUrl'])
            <div 
                x-data="livePlayerComponent()"
                x-init="load(@js($getState()['streamUrl']))"
                class="relative w-full h-full bg-black rounded-lg overflow-hidden shadow-inner"
            >
                {{-- MJPEG Stream --}}
                <img 
                    x-ref="video"
                    :src="stream"
                    class="w-full h-full object-contain"
                    alt="Live Camera Stream"
                />

                {{-- Overlay Canvas --}}
                <canvas 
                    x-ref="overlay" 
                    class="pointer-events-none absolute inset-0 w-full h-full"
                ></canvas>

                {{-- Loading Overlay --}}
                <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-black/50">
                    <div class="text-white text-sm">Đang tải stream...</div>
                </div>
            </div>
        @else
            {{-- Placeholder --}}
            <div class="w-full h-full bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <div class="text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm font-medium mb-1">
                        {{ $getState()['selectedCamera'] ? 'Không có stream' : 'Chọn camera để xem live' }}
                    </div>
                    <div class="text-xs">
                        {{ $getState()['selectedCamera'] ? 'Stream URL không khả dụng' : 'Chọn một camera từ danh sách bên trái' }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Info Footer --}}
    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
            @if($getState()['selectedCamera'])
                <div class="flex justify-between">
                    <span>Camera ID:</span>
                    <span>{{ $getState()['selectedCamera']['id'] }}</span>
                </div>
                @if($getState()['streamUrl'])
                    <div class="flex justify-between">
                        <span>Stream:</span>
                        <span class="truncate max-w-48 ml-2" title="{{ $getState()['streamUrl'] }}">
                            {{ $getState()['streamUrl'] }}
                        </span>
                    </div>
                @endif
            @else
                <div class="text-center">
                    <span>Detection: sẽ được hiển thị khi có ROS data</span>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Alpine JS --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('livePlayerComponent', () => ({
        stream: null,
        loading: false,

        init() {
            this.$watch('stream', (value) => {
                if (!value) return;
                this.loading = true;
                const img = this.$refs.video;
                if (img) {
                    img.src = value;
                    img.onload = () => this.loading = false;
                    img.onerror = () => this.loading = false;
                }
            });
        },

        load(url) {
            if (!url) return;
            this.stream = url;
        }
    }));
});

// Global refresh function
function refreshStream() {
    const videoContainer = document.querySelector('[x-data*="livePlayerComponent"]');
    if (videoContainer && videoContainer._x_dataStack) {
        const component = videoContainer._x_dataStack[0];
        if (component.load && component.stream) {
            component.load(component.stream);
        }
    }
}
</script>
