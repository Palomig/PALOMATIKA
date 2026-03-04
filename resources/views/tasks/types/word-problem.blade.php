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
                                <div class="rounded-lg overflow-hidden border border-slate-700 bg-slate-900/40 p-2">
                                    {!! $imageName !!}
                                </div>
                            @else
                                <img src="{{ asset('images/tasks/' . $topicId . '/' . ltrim($imageName, '/')) }}"
                                     alt="Иллюстрация к задаче {{ $task['id'] }}"
                                     class="max-w-full h-auto rounded-lg bg-white p-1 border border-slate-600"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='{{ asset('images/placeholder.svg') }}';">
                            @endif
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
