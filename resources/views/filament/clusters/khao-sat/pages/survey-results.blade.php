<x-filament::page>
    {{ $this->form }}

    @php
        $results = app(\App\Services\KhaoSat\SurveyResultService::class)->getAnswersAsColumns($this->surveyId);
    @endphp

    @if($results->isEmpty())
        <div class="text-center text-gray-500 mt-8">Không có dữ liệu khảo sát.</div>
    @else
        <div class="overflow-x-auto mt-8">
            <table class="min-w-full border border-gray-200">
                <thead>
                    <tr>
                        @foreach(array_keys($results->first()) as $key)
                            <th class="px-4 py-2 border">{{ $key }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td class="px-4 py-2 border">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::page>
