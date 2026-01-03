@extends('layouts.app')

@section('title', 'Тренировка')
@section('header', 'Тренировка')

@section('content')
<div x-data="practicePage()">
    <!-- No active task - show selection -->
    <div x-show="!currentTask && !loading">
        <div class="bg-white rounded-2xl p-6 shadow-sm mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Выберите тему для тренировки</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <button @click="startPractice('all')"
                        class="p-4 border-2 border-purple-600 rounded-xl text-left hover:bg-purple-50 transition">
                    <div class="text-purple-600 font-semibold mb-1">Все темы</div>
                    <div class="text-gray-500 text-sm">Случайные задачи по всем темам</div>
                </button>
                <button @click="startPractice('weak')"
                        class="p-4 border-2 border-orange-500 rounded-xl text-left hover:bg-orange-50 transition">
                    <div class="text-orange-500 font-semibold mb-1">Слабые места</div>
                    <div class="text-gray-500 text-sm">Навыки с низкой точностью</div>
                </button>
                <button @click="startPractice('smart')"
                        class="p-4 border-2 border-green-600 rounded-xl text-left hover:bg-green-50 transition">
                    <div class="text-green-600 font-semibold mb-1">Умный подбор</div>
                    <div class="text-gray-500 text-sm">AI подберёт задачи для вас</div>
                </button>
            </div>
        </div>

        <!-- Topics selection -->
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="font-semibold text-gray-900 mb-4">Или выберите конкретную тему:</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <template x-for="topic in topics" :key="topic.id">
                    <button @click="startPractice('topic', topic.id)"
                            class="p-3 border border-gray-200 rounded-lg text-left hover:border-purple-300 hover:bg-purple-50 transition">
                        <div class="text-sm font-medium text-gray-900" x-text="topic.name"></div>
                        <div class="text-xs text-gray-500" x-text="'№' + topic.oge_number"></div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Loading task -->
    <div x-show="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 mx-auto text-purple-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p class="text-gray-500 mt-4">Загрузка задачи...</p>
    </div>

    <!-- Active task -->
    <div x-show="currentTask && !loading" x-cloak>
        <!-- Task header -->
        <div class="bg-white rounded-t-2xl p-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center">
                <span class="bg-purple-100 text-purple-600 text-sm font-medium px-2 py-1 rounded"
                      x-text="'№' + (currentTask?.topic?.oge_number || '?')"></span>
                <span class="ml-3 text-gray-500" x-text="currentTask?.topic?.name"></span>
            </div>
            <button @click="skipTask" class="text-gray-400 hover:text-gray-600">
                Пропустить
            </button>
        </div>

        <!-- Task content -->
        <div class="bg-white p-6 shadow-sm">
            <div class="text-lg text-gray-900 mb-6" x-html="currentTask?.text_html || currentTask?.text"></div>

            <!-- Puzzle steps -->
            <div x-show="currentTask?.steps?.length > 0" class="space-y-6">
                <template x-for="(step, stepIndex) in currentTask?.steps" :key="step.id">
                    <div class="border rounded-lg p-4" :class="currentStep === stepIndex ? 'border-purple-300 bg-purple-50' : 'border-gray-200'">
                        <div class="flex items-center mb-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-sm font-medium"
                                  :class="stepResults[stepIndex] === true ? 'bg-green-500 text-white' :
                                         stepResults[stepIndex] === false ? 'bg-red-500 text-white' :
                                         currentStep === stepIndex ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-600'"
                                  x-text="stepIndex + 1"></span>
                            <span class="ml-2 text-gray-700" x-text="step.instruction"></span>
                        </div>

                        <!-- Step template with blanks -->
                        <div class="text-lg mb-4" x-show="currentStep >= stepIndex">
                            <template x-for="(part, i) in parseTemplate(step.template)" :key="i">
                                <span>
                                    <template x-if="part.type === 'text'">
                                        <span x-text="part.content"></span>
                                    </template>
                                    <template x-if="part.type === 'blank'">
                                        <span class="inline-block min-w-[60px] px-3 py-1 mx-1 border-2 rounded text-center"
                                              :class="stepAnswers[stepIndex]?.[part.index] ?
                                                     'border-purple-500 bg-purple-50' : 'border-dashed border-gray-300'"
                                              x-text="stepAnswers[stepIndex]?.[part.index] || '___'"></span>
                                    </template>
                                </span>
                            </template>
                        </div>

                        <!-- Blocks to drag -->
                        <div x-show="currentStep === stepIndex && stepResults[stepIndex] === undefined" class="flex flex-wrap gap-2 mb-4">
                            <template x-for="block in getAvailableBlocks(stepIndex)" :key="block.id">
                                <button @click="selectBlock(stepIndex, block)"
                                        class="px-4 py-2 rounded-lg font-medium transition"
                                        :class="block.selected ? 'bg-purple-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                                        x-text="block.content"></button>
                            </template>
                        </div>

                        <!-- Check button -->
                        <button x-show="currentStep === stepIndex && stepResults[stepIndex] === undefined && isStepComplete(stepIndex)"
                                @click="checkStep(stepIndex)"
                                class="bg-purple-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-purple-700 transition">
                            Проверить
                        </button>

                        <!-- Feedback -->
                        <div x-show="stepResults[stepIndex] !== undefined" class="mt-3">
                            <div x-show="stepResults[stepIndex] === true" class="text-green-600 font-medium">
                                ✓ Правильно!
                            </div>
                            <div x-show="stepResults[stepIndex] === false" class="text-red-600">
                                ✗ Неправильно. <span x-text="stepFeedback[stepIndex]"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Simple answer input -->
            <div x-show="!currentTask?.steps?.length" class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ваш ответ:</label>
                <input type="text" x-model="answer"
                       class="w-full max-w-xs px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                       placeholder="Введите ответ"
                       @keyup.enter="submitAnswer">
                <button @click="submitAnswer"
                        class="ml-4 bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 transition">
                    Проверить
                </button>
            </div>
        </div>

        <!-- Task footer with result -->
        <div x-show="taskResult !== null" class="bg-white rounded-b-2xl p-6 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <div x-show="taskResult === true" class="text-xl font-semibold text-green-600">
                        🎉 Отлично! +<span x-text="xpEarned"></span> XP
                    </div>
                    <div x-show="taskResult === false" class="text-xl font-semibold text-red-600">
                        Неправильно. Правильный ответ: <span x-text="currentTask?.correct_answer"></span>
                    </div>
                </div>
                <button @click="nextTask"
                        class="bg-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-700 transition">
                    Следующая задача
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function practicePage() {
    return {
        topics: [],
        currentTask: null,
        loading: false,
        practiceMode: null,
        topicId: null,
        answer: '',
        taskResult: null,
        xpEarned: 0,

        // Puzzle state
        currentStep: 0,
        stepAnswers: {},
        stepResults: {},
        stepFeedback: {},

        async init() {
            await this.loadTopics();
        },

        async loadTopics() {
            try {
                const response = await fetch('/api/topics');
                if (response.ok) {
                    const data = await response.json();
                    this.topics = data.topics || [];
                }
            } catch (e) {
                console.error('Failed to load topics', e);
            }
        },

        async startPractice(mode, topicId = null) {
            this.practiceMode = mode;
            this.topicId = topicId;
            await this.loadNextTask();
        },

        async loadNextTask() {
            this.loading = true;
            this.resetTaskState();

            try {
                let url = '/api/tasks/next';
                if (this.topicId) {
                    url += '?topic_id=' + this.topicId;
                } else if (this.practiceMode === 'weak') {
                    url += '?mode=weak';
                }

                const response = await fetch(url, {
                    headers: this.$root.getAuthHeaders()
                });

                if (response.ok) {
                    const data = await response.json();
                    this.currentTask = data.task;
                }
            } catch (e) {
                console.error('Failed to load task', e);
                // Mock task for demo
                this.currentTask = {
                    id: 1,
                    text: 'Решите уравнение x² - 5x + 6 = 0',
                    text_html: 'Решите уравнение x² - 5x + 6 = 0',
                    correct_answer: '2;3',
                    topic: { oge_number: '08', name: 'Уравнения' },
                    steps: [
                        {
                            id: 1,
                            instruction: 'Определите коэффициенты a, b, c',
                            template: 'a = [___], b = [___], c = [___]',
                            correct_answers: ['1', '-5', '6'],
                            blocks: [
                                { id: 1, content: '1', is_correct: true },
                                { id: 2, content: '-5', is_correct: true },
                                { id: 3, content: '6', is_correct: true },
                                { id: 4, content: '5', is_trap: true, trap_explanation: 'Знак b отрицательный!' },
                                { id: 5, content: '-6', is_trap: true }
                            ]
                        },
                        {
                            id: 2,
                            instruction: 'Вычислите дискриминант',
                            template: 'D = [___] - [___] = [___]',
                            correct_answers: ['25', '24', '1'],
                            blocks: [
                                { id: 6, content: '25', is_correct: true },
                                { id: 7, content: '24', is_correct: true },
                                { id: 8, content: '1', is_correct: true },
                                { id: 9, content: '-25', is_trap: true },
                                { id: 10, content: '49' }
                            ]
                        }
                    ]
                };
            }

            this.loading = false;
        },

        resetTaskState() {
            this.currentTask = null;
            this.answer = '';
            this.taskResult = null;
            this.xpEarned = 0;
            this.currentStep = 0;
            this.stepAnswers = {};
            this.stepResults = {};
            this.stepFeedback = {};
        },

        parseTemplate(template) {
            if (!template) return [];
            const parts = [];
            let blankIndex = 0;
            const regex = /\[___\]/g;
            let lastIndex = 0;
            let match;

            while ((match = regex.exec(template)) !== null) {
                if (match.index > lastIndex) {
                    parts.push({ type: 'text', content: template.slice(lastIndex, match.index) });
                }
                parts.push({ type: 'blank', index: blankIndex++ });
                lastIndex = regex.lastIndex;
            }

            if (lastIndex < template.length) {
                parts.push({ type: 'text', content: template.slice(lastIndex) });
            }

            return parts;
        },

        getAvailableBlocks(stepIndex) {
            const step = this.currentTask?.steps?.[stepIndex];
            if (!step?.blocks) return [];

            const usedBlocks = Object.values(this.stepAnswers[stepIndex] || {});
            return step.blocks.map(b => ({
                ...b,
                selected: usedBlocks.includes(b.content)
            }));
        },

        selectBlock(stepIndex, block) {
            if (!this.stepAnswers[stepIndex]) {
                this.stepAnswers[stepIndex] = {};
            }

            const answers = this.stepAnswers[stepIndex];
            const step = this.currentTask?.steps?.[stepIndex];
            const blanksCount = (step?.template?.match(/\[___\]/g) || []).length;

            // Find next empty slot or toggle
            const existingSlot = Object.entries(answers).find(([k, v]) => v === block.content);
            if (existingSlot) {
                delete answers[existingSlot[0]];
            } else {
                for (let i = 0; i < blanksCount; i++) {
                    if (!answers[i]) {
                        answers[i] = block.content;
                        break;
                    }
                }
            }

            this.stepAnswers[stepIndex] = { ...answers };
        },

        isStepComplete(stepIndex) {
            const step = this.currentTask?.steps?.[stepIndex];
            const blanksCount = (step?.template?.match(/\[___\]/g) || []).length;
            const answers = this.stepAnswers[stepIndex] || {};
            return Object.keys(answers).length === blanksCount;
        },

        checkStep(stepIndex) {
            const step = this.currentTask?.steps?.[stepIndex];
            const answers = this.stepAnswers[stepIndex] || {};
            const userAnswers = Object.keys(answers)
                .sort((a, b) => a - b)
                .map(k => answers[k]);

            const isCorrect = JSON.stringify(userAnswers) === JSON.stringify(step.correct_answers);
            this.stepResults[stepIndex] = isCorrect;

            if (!isCorrect) {
                // Find trap feedback
                const wrongAnswer = userAnswers.find(a => !step.correct_answers.includes(a));
                const trapBlock = step.blocks?.find(b => b.content === wrongAnswer && b.trap_explanation);
                this.stepFeedback[stepIndex] = trapBlock?.trap_explanation || 'Попробуй ещё раз';
            }

            if (isCorrect) {
                // Move to next step or complete task
                if (stepIndex < this.currentTask.steps.length - 1) {
                    this.currentStep = stepIndex + 1;
                } else {
                    this.completeTask(true);
                }
            }
        },

        async submitAnswer() {
            if (!this.answer.trim()) return;

            const isCorrect = this.answer.trim().toLowerCase() === this.currentTask?.correct_answer?.toLowerCase();
            this.completeTask(isCorrect);
        },

        completeTask(isCorrect) {
            this.taskResult = isCorrect;
            this.xpEarned = isCorrect ? 10 : 0;

            // Submit to API
            // fetch('/api/attempts/' + attemptId + '/submit', ...)
        },

        nextTask() {
            this.loadNextTask();
        },

        skipTask() {
            this.loadNextTask();
        }
    }
}
</script>
@endpush
@endsection
