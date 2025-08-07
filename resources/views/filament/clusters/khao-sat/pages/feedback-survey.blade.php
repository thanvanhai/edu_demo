<x-filament::page>
    @if (!$survey)
    <div class="text-center text-red-600 font-bold mt-10">
        Hiện không có khảo sát nào đang mở.
    </div>
    @else
    <div class="w-full mx-auto px-4">
        <h2 class="text-2xl font-bold mb-4">{{ $this->survey->title }}</h2>
        @if ($this->survey->description)
        <p class="mb-6 text-gray-600">{{ $this->survey->description }}</p>
        @endif

        {{ $this->form }}
    </div>
    @endif
</x-filament::page>