{{--
    Тип: matching для варианта ОГЭ (тема 11)
    Отображает 3 графика (А, Б, В) и 3 формулы (1, 2, 3) в официальном формате ОГЭ

    @param array $taskData - данные из getRandomMatchingSet
    @param int $taskNumber - номер задания в варианте
    @param string $color - цвет акцента
--}}

@php
    $tasks = $taskData['tasks'] ?? [];
    $formulas = $taskData['formulas'] ?? [];
    $topicId = $taskData['topic_id'] ?? '11';
    $graphLabels = ['А', 'Б', 'В'];
@endphp

<div class="space-y-6">
    {{-- ГРАФИКИ секция --}}
    <div>
        <h4 class="text-slate-400 text-sm font-medium mb-4 uppercase tracking-wide">Графики</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($tasks as $index => $task)
                @php
                    $hasSvg = !empty($task['svg']);
                    $hasImage = !empty($task['image']);
                    $imageName = $task['image'] ?? '';
                    $imageUrl = $imageName ? asset("images/tasks/{$topicId}/{$imageName}") : null;
                @endphp

                <div class="bg-slate-800/70 rounded-xl border border-slate-700 overflow-hidden">
                    {{-- Метка графика --}}
                    <div class="bg-slate-700/50 px-3 py-2 flex items-center gap-2">
                        <span class="text-cyan-400 font-bold text-lg">{{ $graphLabels[$index] ?? ($index + 1) }})</span>
                    </div>

                    {{-- Изображение графика --}}
                    <div class="p-3 bg-white min-h-[150px] flex items-center justify-center">
                        @if($hasSvg)
                            {{-- Предзаготовленный SVG (Static SVG System) --}}
                            {!! $task['svg'] !!}
                        @elseif($hasImage && str_starts_with($imageName, '<svg'))
                            {{-- Inline SVG --}}
                            {!! $imageName !!}
                        @elseif($hasImage)
                            {{-- PNG/JPEG изображение --}}
                            <img src="{{ $imageUrl }}"
                                 alt="График {{ $graphLabels[$index] ?? ($index + 1) }}"
                                 class="max-w-full max-h-36 object-contain"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'text-slate-500 text-sm\'>Изображение не загружено</span>';">
                        @else
                            <span class="text-slate-400 text-sm">Нет изображения</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ФОРМУЛЫ секция --}}
    <div>
        <h4 class="text-slate-400 text-sm font-medium mb-4 uppercase tracking-wide">Формулы</h4>
        <div class="flex flex-wrap gap-4 justify-center">
            @foreach($formulas as $index => $formula)
                <div class="bg-slate-700/50 rounded-lg px-5 py-3">
                    <span class="text-amber-400 font-bold">{{ $index + 1 }})</span>
                    <span class="text-slate-200 math-serif ml-2">${{ $formula }}$</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Таблица ответов --}}
    <div class="flex items-center justify-center gap-4">
        <span class="text-slate-400 text-sm font-medium">В ответе укажите соответствующие номера формул:</span>
    </div>

    <div class="flex justify-center">
        <table class="border-collapse">
            <thead>
                <tr>
                    @foreach($graphLabels as $index => $label)
                        @if($index < count($tasks))
                            <th class="w-14 h-10 border border-slate-600 text-center text-cyan-400 font-medium bg-slate-800/50">
                                {{ $label }}
                            </th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach($tasks as $index => $task)
                        <td class="w-14 h-10 border border-slate-600 text-center bg-slate-700/30">
                            <input type="text"
                                   maxlength="1"
                                   class="w-full h-full text-center bg-transparent text-white focus:outline-none focus:bg-slate-600/50"
                                   placeholder="">
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
