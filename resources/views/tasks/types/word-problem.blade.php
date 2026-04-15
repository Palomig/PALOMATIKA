{{--
    Тип: word_problem (темы 10, 12, 14)
    Текстовые задачи
--}}

@php
    $tasks = $zadanie['tasks'] ?? [];
    $isVariant = $isVariant ?? false;
    $showTaskAnswer = !$isVariant;
    $answerResolver = app(\App\Services\TaskAnswerResolver::class);
@endphp

<div class="space-y-4">
    @foreach($tasks as $task)
        @php
            $taskKey = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_task_{$task['id']}";
            $taskInfo = "Блок {$block['number']}, Задание {$zadanie['number']}, Задача {$task['id']}<br><code>" . substr($task['text'] ?? '', 0, 100) . "...</code>";
        @endphp

        <div class="bg-slate-800/70 rounded-xl p-5 border border-slate-700 task-review-item relative"
             data-task-key="{{ $taskKey }}" data-task-info="{{ $taskInfo }}">

            <div class="flex items-start gap-3">
                @if(!$isVariant)
                    <span class="text-green-400 font-bold text-lg shrink-0">{{ $task['id'] }})</span>
                @endif
                <div class="w-full">
                    @php
                        $imageName = (string) ($task['image'] ?? '');
                    @endphp

                    @if($imageName !== '')
                        <div class="mb-3">
                            @if(\Illuminate\Support\Str::startsWith($imageName, '<svg'))
                                <div class="rounded-lg overflow-hidden border border-slate-700 bg-slate-900/40 p-2 w-full max-w-[560px] h-[320px] flex items-center justify-center [&>svg]:w-full [&>svg]:h-full [&>svg]:object-contain">
                                    {!! $imageName !!}
                                </div>
                            @else
                                <div class="w-full max-w-[360px] h-[220px] rounded-lg bg-white p-1 border border-slate-600 flex items-center justify-center overflow-hidden">
                                    <img src="{{ asset('images/tasks/' . $topicId . '/' . ltrim($imageName, '/')) }}"
                                         alt="Иллюстрация к задаче {{ $task['id'] }}"
                                         class="max-w-full max-h-full object-contain"
                                         loading="lazy"
                                         onerror="this.onerror=null; this.src='{{ asset('images/placeholder.svg') }}';">
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(!empty($task['table']['headers']) && !empty($task['table']['rows']))
                        <div class="mb-4 overflow-x-auto rounded-lg border border-slate-700 bg-slate-900/40">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-800/80">
                                        @foreach($task['table']['headers'] as $header)
                                            <th class="px-4 py-3 text-left font-semibold text-slate-200 border-b border-slate-700">
                                                {{ $header }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($task['table']['rows'] as $row)
                                        <tr class="border-b border-slate-800 last:border-b-0">
                                            @foreach($row as $cell)
                                                <td class="px-4 py-3 text-slate-300">
                                                    {{ $cell }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <p class="text-slate-200 leading-relaxed">{!! nl2br(e($task['text'] ?? '')) !!}</p>
                </div>
            </div>

            @include('tasks.partials.task-answer', [
                'showTaskAnswer' => $showTaskAnswer,
                'taskAnswer' => $answerResolver->resolveFromTaskAndZadanie($zadanie, $task),
            ])
            @include('tasks.partials.task-status-badge')
        </div>
    @endforeach
</div>
