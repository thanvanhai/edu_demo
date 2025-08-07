<x-filament::widget>
    <x-filament::card>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <h2 class="text-lg font-semibold">📊 Tổng lượt truy cập</h2>
                <p class="text-2xl text-primary-600 font-bold">{{ $totalVisits }}</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold">👤 Đang online</h2>
                <p class="text-2xl text-primary-600 font-bold">{{ $activeUsers }}</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold">📊 Lượt truy cập hôm nay</h2>
                <p class="text-2xl text-primary-600 font-bold">{{ $visitsToday }}</p>
            </div>          
        </div>
    </x-filament::card>
</x-filament::widget>