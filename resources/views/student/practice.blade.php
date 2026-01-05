@extends('layouts.app')

@section('title', 'Тренировка')
@section('header', 'Тренировка')

@push('styles')
<style>
    .drop-zone {
        min-width: 70px;
        min-height: 40px;
        border: 2px dashed #4b5563;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 4px;
        padding: 4px 12px;
        transition: all 0.2s;
        vertical-align: middle;
    }
    .drop-zone.drag-over {
        border-color: #ff6b6b;
        background: rgba(255, 107, 107, 0.1);
    }
    .drop-zone.filled {
        border-style: solid;
        border-color: #ff6b6b;
        background: rgba(255, 107, 107, 0.2);
    }
    .puzzle-block {
        cursor: grab;
        user-select: none;
        transition: all 0.2s;
    }
    .puzzle-block:active {
        cursor: grabbing;
    }
    .puzzle-block.dragging {
        opacity: 0.5;
        transform: scale(0.95);
    }
    .puzzle-block.used {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .math-display {
        font-size: 1.25rem;
    }
</style>
@endpush

@section('content')
<div x-data="practicePage()" x-init="init()">
    <!-- Topic selection -->
    <div x-show="!currentTask && !loading && !error">
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800 mb-6">
            <h2 class="text-xl font-semibold text-white mb-4">Выберите тему для тренировки</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <button @click="startPractice('all')"
                        class="p-4 border-2 border-coral rounded-xl text-left hover:bg-coral/10 transition">
                    <div class="text-coral font-semibold mb-1">Все темы</div>
                    <div class="text-gray-400 text-sm">Случайные задачи по всем темам</div>
                </button>
                <button @click="startPractice('weak')"
                        class="p-4 border-2 border-amber-500 rounded-xl text-left hover:bg-amber-500/10 transition">
                    <div class="text-amber-500 font-semibold mb-1">Слабые места</div>
                    <div class="text-gray-400 text-sm">Навыки с низкой точностью</div>
                </button>
                <button @click="startPractice('smart')"
                        class="p-4 border-2 border-green-500 rounded-xl text-left hover:bg-green-500/10 transition">
                    <div class="text-green-500 font-semibold mb-1">Умный подбор</div>
                    <div class="text-gray-400 text-sm">AI подберёт задачи для вас</div>
                </button>
            </div>
        </div>

        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800">
            <h3 class="font-semibold text-white mb-4">Или выберите конкретную тему:</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <template x-for="topic in topics" :key="topic.id">
                    <button @click="startPractice('topic', topic.id)"
                            class="p-3 border border-gray-700 rounded-lg text-left hover:border-coral/50 hover:bg-coral/5 transition">
                        <div class="text-sm font-medium text-gray-200" x-text="topic.name"></div>
                        <div class="text-xs text-gray-500" x-text="'№' + topic.oge_number"></div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 mx-auto text-coral" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p class="text-gray-400 mt-4">Загрузка задачи...</p>
    </div>

    <!-- Error -->
    <div x-show="error && !loading" class="text-center py-12">
        <div class="bg-red-500/10 border border-red-500/50 rounded-2xl p-8 max-w-md mx-auto">
            <svg class="h-16 w-16 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-red-400 text-lg mb-4" x-text="error"></p>
            <button @click="error = null; practiceMode = null; topicId = null;"
                    class="bg-coral text-white px-6 py-2 rounded-lg hover:bg-coral-dark transition">
                Выбрать тему
            </button>
        </div>
    </div>

    <!-- Active Task -->
    <div x-show="currentTask && !loading" x-cloak>
        <!-- Header -->
        <div class="bg-dark-light rounded-t-2xl p-4 border-b border-gray-700 flex items-center justify-between">
            <div class="flex items-center">
                <span class="bg-coral/20 text-coral text-sm font-medium px-3 py-1 rounded"
                      x-text="'№' + (currentTask?.topic?.oge_number || '?')"></span>
                <span class="ml-3 text-gray-400" x-text="currentTask?.topic?.name"></span>
            </div>
            <button @click="skipTask" class="text-gray-500 hover:text-gray-300 transition">
                Пропустить
            </button>
        </div>

        <!-- Content -->
        <div class="bg-dark-light p-6 border-x border-gray-800">
            <!-- Task text -->
            <div class="text-xl text-white mb-6 math-display" x-ref="taskText"></div>

            <!-- Image -->
            <template x-if="currentTask?.image_url">
                <div class="mb-6">
                    <img :src="currentTask.image_url" alt="Задача" class="max-w-full rounded-lg border border-gray-700">
                </div>
            </template>

            <!-- Puzzle Steps -->
            <div x-show="currentTask?.steps?.length > 0" class="space-y-6">
                <template x-for="(step, stepIndex) in currentTask?.steps" :key="step.id">
                    <div class="border rounded-xl p-5 transition-all"
                         :class="currentStep === stepIndex ? 'border-coral/50 bg-coral/5' : 'border-gray-700 bg-dark-lighter/50'">

                        <!-- Step header -->
                        <div class="flex items-center mb-4">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                                  :class="stepResults[stepIndex] === true ? 'bg-green-500 text-white' :
                                         stepResults[stepIndex] === false ? 'bg-red-500 text-white' :
                                         currentStep === stepIndex ? 'bg-coral text-white' : 'bg-gray-700 text-gray-400'"
                                  x-text="stepIndex + 1"></span>
                            <span class="ml-3 text-gray-300 font-medium" x-text="step.instruction"></span>
                        </div>

                        <!-- Template with drop zones -->
                        <div class="text-xl mb-5 text-white flex flex-wrap items-center gap-1"
                             x-show="currentStep >= stepIndex"
                             x-ref="'stepTemplate' + stepIndex">
                        </div>

                        <!-- Draggable blocks -->
                        <div x-show="currentStep === stepIndex && stepResults[stepIndex] === undefined"
                             class="flex flex-wrap gap-3 mb-4 p-4 bg-dark rounded-lg border border-gray-700">
                            <span class="text-gray-500 text-sm mr-2">Перетащите:</span>
                            <template x-for="block in getAvailableBlocks(stepIndex)" :key="block.id">
                                <div class="puzzle-block px-5 py-2.5 rounded-lg font-medium text-lg"
                                     :class="isBlockUsed(stepIndex, block.content) ? 'bg-gray-800 text-gray-500 used' : 'bg-coral text-white hover:bg-coral-dark'"
                                     :draggable="!isBlockUsed(stepIndex, block.content)"
                                     @dragstart="dragStart($event, stepIndex, block)"
                                     @dragend="dragEnd($event)"
                                     x-text="block.content">
                                </div>
                            </template>
                        </div>

                        <!-- Check button -->
                        <button x-show="currentStep === stepIndex && stepResults[stepIndex] === undefined && isStepComplete(stepIndex)"
                                @click="checkStep(stepIndex)"
                                class="bg-green-500 text-white px-8 py-3 rounded-lg font-medium hover:bg-green-600 transition text-lg">
                            Проверить
                        </button>

                        <!-- Feedback -->
                        <div x-show="stepResults[stepIndex] !== undefined" class="mt-4 p-3 rounded-lg"
                             :class="stepResults[stepIndex] ? 'bg-green-500/20' : 'bg-red-500/20'">
                            <div x-show="stepResults[stepIndex] === true" class="text-green-400 font-medium text-lg">
                                ✓ Правильно!
                            </div>
                            <div x-show="stepResults[stepIndex] === false" class="text-red-400">
                                ✗ Неправильно. <span x-text="stepFeedback[stepIndex]"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Simple answer (no steps) -->
            <div x-show="!currentTask?.steps?.length" class="mt-6">
                <label class="block text-sm font-medium text-gray-300 mb-2">Ваш ответ:</label>
                <div class="flex flex-wrap gap-4">
                    <input type="text" x-model="answer"
                           class="flex-1 min-w-[200px] px-4 py-3 bg-dark border border-gray-700 rounded-lg text-white text-lg"
                           placeholder="Введите ответ"
                           @keyup.enter="submitAnswer">
                    <button @click="submitAnswer"
                            class="bg-coral text-white px-6 py-3 rounded-lg font-medium hover:bg-coral-dark transition">
                        Проверить
                    </button>
                </div>
            </div>
        </div>

        <!-- Result footer -->
        <div x-show="taskResult !== null" class="bg-dark-light rounded-b-2xl p-6 border-t border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <div x-show="taskResult === true" class="text-xl font-semibold text-green-400">
                        Отлично! +<span x-text="xpEarned"></span> XP
                    </div>
                    <div x-show="taskResult === false" class="text-xl font-semibold text-red-400">
                        Неправильно. Ответ: <span x-text="currentTask?.correct_answer"></span>
                    </div>
                </div>
                <button @click="nextTask"
                        class="bg-coral text-white px-8 py-3 rounded-lg font-medium hover:bg-coral-dark transition text-lg">
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
        error: null,
        practiceMode: null,
        topicId: null,
        answer: '',
        taskResult: null,
        xpEarned: 0,
        currentStep: 0,
        stepAnswers: {},
        stepResults: {},
        stepFeedback: {},
        draggedBlock: null,

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
            this.error = null;
            this.resetTaskState();

            try {
                let url = '/api/tasks/next';
                const params = new URLSearchParams();
                if (this.topicId) params.append('topic_id', this.topicId);
                if (this.practiceMode === 'weak') params.append('mode', 'weak');
                if (params.toString()) url += '?' + params.toString();

                const response = await fetch(url);
                if (response.ok) {
                    const data = await response.json();
                    this.currentTask = data.task;
                    this.$nextTick(() => this.renderTask());
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    this.error = errorData.message || 'Не удалось загрузить задачу';
                }
            } catch (e) {
                console.error('Failed to load task', e);
                this.error = 'Ошибка сети. Попробуйте позже.';
            }

            this.loading = false;
        },

        renderTask() {
            // Render task text with KaTeX
            if (this.$refs.taskText && this.currentTask) {
                const text = this.currentTask.text_html || this.currentTask.text;
                this.$refs.taskText.innerHTML = text;
                if (window.renderMathInElement) {
                    renderMathInElement(this.$refs.taskText, {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '$', right: '$', display: false},
                            {left: '\\(', right: '\\)', display: false},
                            {left: '\\[', right: '\\]', display: true}
                        ]
                    });
                }
            }

            // Render step templates
            if (this.currentTask?.steps) {
                this.currentTask.steps.forEach((step, i) => {
                    this.$nextTick(() => this.renderStepTemplate(i));
                });
            }
        },

        renderStepTemplate(stepIndex) {
            const step = this.currentTask?.steps?.[stepIndex];
            if (!step) return;

            const container = document.querySelector(`[x-ref="stepTemplate${stepIndex}"]`);
            if (!container) {
                setTimeout(() => this.renderStepTemplate(stepIndex), 100);
                return;
            }

            container.innerHTML = '';
            const template = step.template || '';
            const parts = template.split('[___]');

            parts.forEach((part, i) => {
                // Add text part
                if (part) {
                    const textSpan = document.createElement('span');
                    textSpan.innerHTML = part;
                    container.appendChild(textSpan);
                }

                // Add drop zone (except after last part)
                if (i < parts.length - 1) {
                    const dropZone = document.createElement('div');
                    dropZone.className = 'drop-zone';
                    dropZone.dataset.stepIndex = stepIndex;
                    dropZone.dataset.blankIndex = i;
                    dropZone.textContent = '?';

                    dropZone.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        dropZone.classList.add('drag-over');
                    });

                    dropZone.addEventListener('dragleave', () => {
                        dropZone.classList.remove('drag-over');
                    });

                    dropZone.addEventListener('drop', (e) => {
                        e.preventDefault();
                        dropZone.classList.remove('drag-over');
                        this.handleDrop(stepIndex, i);
                    });

                    // Click to remove
                    dropZone.addEventListener('click', () => {
                        if (this.stepAnswers[stepIndex]?.[i]) {
                            delete this.stepAnswers[stepIndex][i];
                            this.stepAnswers = {...this.stepAnswers};
                            this.updateDropZone(stepIndex, i, null);
                        }
                    });

                    container.appendChild(dropZone);
                }
            });

            // Render math in template
            if (window.renderMathInElement) {
                renderMathInElement(container, {
                    delimiters: [
                        {left: '$$', right: '$$', display: true},
                        {left: '$', right: '$', display: false},
                        {left: '\\(', right: '\\)', display: false},
                        {left: '\\[', right: '\\]', display: true}
                    ]
                });
            }
        },

        dragStart(event, stepIndex, block) {
            if (this.isBlockUsed(stepIndex, block.content)) {
                event.preventDefault();
                return;
            }
            this.draggedBlock = { stepIndex, block };
            event.target.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
        },

        dragEnd(event) {
            event.target.classList.remove('dragging');
            this.draggedBlock = null;
        },

        handleDrop(stepIndex, blankIndex) {
            if (!this.draggedBlock || this.draggedBlock.stepIndex !== stepIndex) return;

            const block = this.draggedBlock.block;

            if (!this.stepAnswers[stepIndex]) {
                this.stepAnswers[stepIndex] = {};
            }

            // Remove from previous position if exists
            Object.entries(this.stepAnswers[stepIndex]).forEach(([k, v]) => {
                if (v === block.content) {
                    delete this.stepAnswers[stepIndex][k];
                    this.updateDropZone(stepIndex, parseInt(k), null);
                }
            });

            // Clear current position if occupied
            if (this.stepAnswers[stepIndex][blankIndex]) {
                const oldValue = this.stepAnswers[stepIndex][blankIndex];
                delete this.stepAnswers[stepIndex][blankIndex];
            }

            // Place in new position
            this.stepAnswers[stepIndex][blankIndex] = block.content;
            this.stepAnswers = {...this.stepAnswers};

            this.updateDropZone(stepIndex, blankIndex, block.content);
            this.draggedBlock = null;
        },

        updateDropZone(stepIndex, blankIndex, value) {
            const dropZone = document.querySelector(`[data-step-index="${stepIndex}"][data-blank-index="${blankIndex}"]`);
            if (dropZone) {
                if (value) {
                    dropZone.textContent = value;
                    dropZone.classList.add('filled');
                } else {
                    dropZone.textContent = '?';
                    dropZone.classList.remove('filled');
                }
            }
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
            this.draggedBlock = null;
        },

        getAvailableBlocks(stepIndex) {
            const step = this.currentTask?.steps?.[stepIndex];
            return step?.blocks || [];
        },

        isBlockUsed(stepIndex, content) {
            const answers = this.stepAnswers[stepIndex] || {};
            return Object.values(answers).includes(content);
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
            const userAnswers = [];

            const blanksCount = (step?.template?.match(/\[___\]/g) || []).length;
            for (let i = 0; i < blanksCount; i++) {
                userAnswers.push(answers[i] || '');
            }

            const isCorrect = JSON.stringify(userAnswers) === JSON.stringify(step.correct_answers);
            this.stepResults[stepIndex] = isCorrect;
            this.stepResults = {...this.stepResults};

            if (!isCorrect) {
                const wrongAnswer = userAnswers.find(a => !step.correct_answers.includes(a));
                const trapBlock = step.blocks?.find(b => b.content === wrongAnswer && b.trap_explanation);
                this.stepFeedback[stepIndex] = trapBlock?.trap_explanation || 'Попробуйте ещё раз';
                this.stepFeedback = {...this.stepFeedback};
            }

            if (isCorrect) {
                if (stepIndex < this.currentTask.steps.length - 1) {
                    this.currentStep = stepIndex + 1;
                    this.$nextTick(() => this.renderStepTemplate(this.currentStep));
                } else {
                    this.completeTask(true);
                }
            }
        },

        submitAnswer() {
            if (!this.answer.trim()) return;
            const isCorrect = this.answer.trim().toLowerCase() === this.currentTask?.correct_answer?.toLowerCase();
            this.completeTask(isCorrect);
        },

        completeTask(isCorrect) {
            this.taskResult = isCorrect;
            this.xpEarned = isCorrect ? 10 : 0;
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
