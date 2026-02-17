@if($showTaskAnswer ?? false)
    @php
        $overrides = $answerOverrides ?? [];
        $resolvedAnswer = isset($taskAnswer) ? (string) $taskAnswer : '';
        $sourceLabel = 'AI';
        $resolvedTaskKey = isset($taskKey) ? (string) $taskKey : '';

        if ($resolvedTaskKey !== '' && isset($overrides[$resolvedTaskKey])) {
            $override = $overrides[$resolvedTaskKey];
            $resolvedAnswer = (string) ($override['answer'] ?? $resolvedAnswer);
            $sourceLabel = (string) ($override['source_label'] ?? 'AI');
        }

        $topicForApi = isset($topicId) ? str_pad((string) $topicId, 2, '0', STR_PAD_LEFT) : null;
        $canEditAnswer = auth()->check() && auth()->user()?->isAdmin() && $resolvedTaskKey !== '' && $topicForApi !== null;
    @endphp

    <div class="mt-3 text-xs js-task-answer-block" @if($resolvedTaskKey !== '') data-task-key="{{ $resolvedTaskKey }}" @endif>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-slate-500">Ответ:</span>
            @if($resolvedAnswer !== '')
                <span class="text-emerald-400 font-mono js-task-answer-value">{{ $resolvedAnswer }}</span>
            @else
                <span class="text-amber-400 js-task-answer-value">нет в базе</span>
            @endif
            <span class="text-slate-400 js-task-answer-source">[{{ $sourceLabel }}]</span>

            @if($canEditAnswer)
                <button type="button"
                        class="js-task-answer-edit-toggle px-2 py-0.5 rounded bg-slate-700 text-slate-200 hover:bg-slate-600 transition"
                        data-topic-id="{{ $topicForApi }}"
                        data-task-key="{{ $resolvedTaskKey }}"
                        onclick="TaskAnswerEditor.toggle(this)">
                    ✏️
                </button>
            @endif
        </div>

        @if($canEditAnswer)
            <div class="js-task-answer-editor hidden mt-2 flex flex-wrap items-center gap-2">
                <input type="text"
                       class="js-task-answer-input px-2 py-1 rounded bg-slate-900 border border-slate-700 text-slate-100 focus:outline-none focus:border-blue-500"
                       value="{{ $resolvedAnswer }}"
                       maxlength="255"
                       placeholder="Новый ответ">
                <button type="button"
                        class="px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-500 transition"
                        onclick="TaskAnswerEditor.save(this)">
                    Сохранить
                </button>
                <button type="button"
                        class="px-2 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 transition"
                        onclick="TaskAnswerEditor.cancel(this)">
                    Отмена
                </button>
                <span class="js-task-answer-error text-rose-400 text-[11px] hidden"></span>
            </div>
        @endif
    </div>
@endif

@once
    <script>
        window.TaskAnswerEditor = window.TaskAnswerEditor || {
            toggle(button) {
                const block = button.closest('.js-task-answer-block');
                const editor = block?.querySelector('.js-task-answer-editor');
                if (!editor) return;
                editor.classList.toggle('hidden');
                const input = editor.querySelector('.js-task-answer-input');
                if (!editor.classList.contains('hidden') && input) {
                    input.focus();
                    input.select();
                }
            },

            cancel(button) {
                const editor = button.closest('.js-task-answer-editor');
                if (!editor) return;
                const error = editor.querySelector('.js-task-answer-error');
                if (error) {
                    error.textContent = '';
                    error.classList.add('hidden');
                }
                editor.classList.add('hidden');
            },

            async save(button) {
                const editor = button.closest('.js-task-answer-editor');
                const block = button.closest('.js-task-answer-block');
                const toggle = block?.querySelector('.js-task-answer-edit-toggle');
                const input = editor?.querySelector('.js-task-answer-input');
                const error = editor?.querySelector('.js-task-answer-error');
                const answerValueEl = block?.querySelector('.js-task-answer-value');
                const sourceEl = block?.querySelector('.js-task-answer-source');

                if (!editor || !block || !toggle || !input || !answerValueEl || !sourceEl) {
                    return;
                }

                const answer = input.value.trim();
                const topicId = toggle.dataset.topicId;
                const taskKey = toggle.dataset.taskKey;

                if (!answer) {
                    if (error) {
                        error.textContent = 'Ответ не может быть пустым.';
                        error.classList.remove('hidden');
                    }
                    return;
                }

                if (error) {
                    error.textContent = '';
                    error.classList.add('hidden');
                }

                button.disabled = true;
                button.textContent = '...';

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch(`/api/topics/${topicId}/answers`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            task_key: taskKey,
                            answer,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data?.message || 'Не удалось сохранить ответ.');
                    }

                    answerValueEl.textContent = data.answer;
                    answerValueEl.classList.remove('text-amber-400');
                    answerValueEl.classList.add('text-emerald-400', 'font-mono');
                    sourceEl.textContent = `[${data.source_label ?? 'Ручной'}]`;
                    editor.classList.add('hidden');
                    window.dispatchEvent(new CustomEvent('task-answer-updated', { detail: data }));
                } catch (e) {
                    if (error) {
                        error.textContent = e.message || 'Ошибка сохранения.';
                        error.classList.remove('hidden');
                    }
                } finally {
                    button.disabled = false;
                    button.textContent = 'Сохранить';
                }
            },
        };
    </script>
@endonce
