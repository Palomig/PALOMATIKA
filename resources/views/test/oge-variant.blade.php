<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Вариант ОГЭ №{{ $variantNumber ?? 1 }} - PALOMATIKA</title>
    @include('partials.head-config')
    @include('partials.head-katex')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,700&display=swap" rel="stylesheet">

    <style>
        .exam-title { font-family: 'Literata', serif; }
        .number-line { font-family: 'Times New Roman', serif; }
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            user-select: none;
            pointer-events: none;
        }
        .geo-label-bold {
            font-family: 'Times New Roman', serif;
            font-style: normal;
            font-weight: 700;
            user-select: none;
            pointer-events: none;
        }
        .katex { font-size: 1.02em; }
        .task-card .task-review-item {
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
        }
        .save-flash {
            animation: saveBlink 0.4s ease-in-out 5;
        }
        @keyframes saveBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.2; }
        }

        /* Print styles */
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .no-print {
                display: none !important;
            }
            .task-card {
                break-inside: avoid;
                border: 1px solid #ccc !important;
                background: white !important;
            }
            .bg-dark-50, .bg-dark-light {
                background: #f5f5f5 !important;
            }
            .text-white, .text-slate-200, .text-slate-300 {
                color: black !important;
            }
            .text-emerald-400, .text-emerald-300, .text-slate-400 {
                color: #1e40af !important;
            }
        }
    </style>
</head>
@php
    $isLegacyRoute = request()->routeIs('test.*');
    $generatorRoute = $isLegacyRoute ? route('test.oge.generator') : route('oge.generator');
    $variantRouteName = $isLegacyRoute ? 'test.oge.show' : 'oge.show';
    $newHash = substr(base_convert(mt_rand(), 10, 36), 0, 6);
    $footerHash = substr(base_convert(mt_rand(), 10, 36), 0, 6);
    $studentMode = auth()->check() && auth()->user()->role === 'student';
@endphp
<body class="min-h-screen bg-dark-50 text-slate-200">

<div class="max-w-5xl mx-auto px-4 py-8">
    @if(!$studentMode)
        {{-- Navigation --}}
        <div class="no-print flex flex-wrap justify-between items-center mb-8 text-sm bg-dark-light/40 rounded-xl p-4 border border-slate-800 gap-3">
            <a href="{{ $generatorRoute }}" class="text-slate-300 hover:text-white transition">← К генератору</a>
            <div class="flex gap-2">
                <a href="{{ route($variantRouteName, ['hash' => $newHash]) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 transition">Новый вариант</a>
                <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-700 text-white hover:bg-emerald-600 transition">Печать</button>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <header class="mb-8">
        <div class="flex justify-between items-center text-sm text-slate-500 mb-3">
            <span>ОГЭ. Тренировочный вариант</span>
            <span>palomatika.ru</span>
        </div>
        <h1 class="exam-title text-3xl sm:text-4xl text-white mb-2">Вариант № {{ $variantNumber ?? rand(1, 99) }}</h1>
        <p class="text-slate-400">Задания 6–19</p>
    </header>

    {{-- Instructions --}}
    <div class="bg-dark-light/40 rounded-xl p-5 mb-8 border border-slate-800">
        <p class="text-slate-300 text-sm leading-relaxed">
            <strong class="text-white">Инструкция:</strong> Ответами к заданиям 6–19 являются число или последовательность цифр.
            Запишите ответ в поле ответа. Если ответом является последовательность цифр, то запишите её без пробелов, запятых и других дополнительных символов.
        </p>
    </div>

    {{-- Stats --}}
    <div class="no-print flex justify-center gap-4 mb-10">
        <div class="bg-dark-light/40 px-5 py-2.5 rounded-lg border border-slate-800">
            <span class="text-emerald-300 font-semibold text-lg">{{ count($tasks) }}</span>
            <span class="text-slate-400 ml-2 text-sm">заданий</span>
        </div>
        <div class="bg-dark-light/40 px-5 py-2.5 rounded-lg border border-slate-800">
            <span class="text-emerald-300 font-semibold text-lg">{{ now()->format('d.m.Y') }}</span>
            <span class="text-slate-400 ml-2 text-sm">дата</span>
        </div>
    </div>

    {{-- Tasks - используем унифицированный адаптер --}}
    @foreach($tasks as $index => $taskData)
        @php
            $taskNumber = (int) ($taskData['task_number'] ?? (6 + $index));
            $topicId = $taskData['topic_id'] ?? '';

            // Определяем цвет акцента для разных тем
            $accentColors = [
                '06' => 'blue',
                '07' => 'cyan',
                '08' => 'violet',
                '09' => 'pink',
                '10' => 'orange',
                '11' => 'rose',
                '12' => 'lime',
                '13' => 'teal',
                '14' => 'indigo',
                '15' => 'emerald',
                '16' => 'amber',
                '17' => 'fuchsia',
                '18' => 'sky',
                '19' => 'red',
            ];
            $color = $accentColors[$topicId] ?? 'blue';
        @endphp

        @php
            try {
                echo view('tasks.variant-task', [
                    'taskData' => $taskData,
                    'taskNumber' => $taskNumber,
                    'color' => $color,
                    'studentMode' => $studentMode,
                ])->render();
            } catch (\Throwable $e) {
                \Log::warning('OGE variant task card render failed', [
                    'variant_hash' => $variantHash ?? null,
                    'task_number' => $taskNumber,
                    'topic_id' => $topicId ?? null,
                    'error' => $e->getMessage(),
                ]);

                echo '<div class="mb-8 rounded-xl border border-amber-700/50 bg-amber-900/20 p-4 text-sm text-amber-200">Одно из заданий не удалось отобразить. Остальная часть варианта доступна.</div>';
            }
        @endphp
    @endforeach

    @if($studentMode)
        <div id="attempt-bottom-bar" class="no-print sticky bottom-4 z-30 mt-6">
            <div class="rounded-xl border border-slate-700 bg-slate-900/95 backdrop-blur p-4 flex flex-wrap items-center justify-between gap-3 shadow-lg">
                <div>
                    <p class="text-white text-sm font-medium">Ответы сохраняются по кнопке «ОК»</p>
                    <p class="text-slate-500 text-xs">После «Завершить вариант» редактирование будет заблокировано.</p>
                </div>
                <button id="finish-attempt-btn"
                        class="px-5 py-2.5 rounded-lg bg-emerald-700 text-white hover:bg-emerald-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Завершить вариант
                </button>
            </div>
        </div>
    @endif

    @if(!$studentMode)
        {{-- Footer --}}
        <div class="no-print text-center mt-10">
            <div class="bg-dark-light/40 rounded-xl p-6 border border-slate-800">
                <p class="text-slate-400 mb-2">Вариант: <code class="bg-slate-800 px-2 py-1 rounded text-emerald-300">{{ $variantHash ?? 'unknown' }}</code></p>
                <p class="text-slate-500 text-sm mb-4">Ссылка на этот вариант сохраняется — можно поделиться</p>
                <div class="flex justify-center gap-4">
                    <button onclick="window.print()" class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition-colors">
                        Распечатать
                    </button>
                    <a href="{{ route($variantRouteName, ['hash' => $footerHash]) }}" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-600 text-white rounded-lg transition-colors">
                        Новый вариант
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

@if($studentMode)
<script type="module">
import {
    domRectToPlain,
    viewportRectFromWindow,
    pickLeadingTask,
    isScrollSettled,
} from '/js/oge-task-visibility.mjs';

document.addEventListener('DOMContentLoaded', async () => {
    const variantHash = @json($variantHash ?? '');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const finishButton = document.getElementById('finish-attempt-btn');
    const bottomBar = document.getElementById('attempt-bottom-bar');

    let attemptId = null;
    let locked = false;
    let activeTask = null;
    let candidateTask = null;
    let candidateSinceMs = 0;
    let lastScrollAtMs = performance.now();
    let hiddenSinceMs = document.hidden ? Date.now() : null;
    let pendingAwayMs = 0;

    const SCROLL_SETTLE_MS = 400;
    const FOCUS_DELAY_MS = 5000;
    const TRACKER_TICK_MS = 250;
    const MIN_VISIBLE_RATIO = 0.15;

    const cards = [...document.querySelectorAll('.task-card[data-task-number]')];

    const api = {
        start: `/api/oge/variants/${variantHash}/attempt/start`,
        focus: (id, task) => `/api/oge/attempts/${id}/tasks/${task}/focus`,
        blur: (id, task) => `/api/oge/attempts/${id}/tasks/${task}/blur`,
        commit: (id, task) => `/api/oge/attempts/${id}/tasks/${task}/commit`,
        heartbeat: (id) => `/api/oge/attempts/${id}/heartbeat`,
        submit: (id) => `/api/oge/attempts/${id}/submit`,
    };

    async function post(url, body = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            throw new Error(`Request failed: ${response.status}`);
        }

        return response.json();
    }

    function setCardLocked(card, isLocked) {
        const input = card.querySelector('.js-answer-input');
        const ok = card.querySelector('.js-answer-ok');
        const edit = card.querySelector('.js-answer-edit');
        if (input) input.disabled = isLocked;
        if (ok) ok.disabled = isLocked;
        if (edit) edit.disabled = isLocked;
    }

    function setAllLocked(isLocked) {
        cards.forEach(card => setCardLocked(card, isLocked));
        if (finishButton) finishButton.disabled = isLocked;
    }

    async function activateTask(taskNumber) {
        if (activeTask === taskNumber || !attemptId || locked) return;

        try {
            await post(api.focus(attemptId, taskNumber), { client_ts: new Date().toISOString() });
            activeTask = taskNumber;
        } catch (e) {
            console.error('focus failed', e);
        }
    }

    async function deactivateTask(taskNumber = activeTask) {
        if (!taskNumber || !attemptId || locked) return;

        try {
            await post(api.blur(attemptId, taskNumber), { client_ts: new Date().toISOString() });
        } catch (e) {
            console.error('blur failed', e);
        } finally {
            if (activeTask === taskNumber) {
                activeTask = null;
            }
        }
    }

    function getBlockerRects() {
        if (!bottomBar || document.hidden) return [];
        const rect = bottomBar.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) return [];
        if (rect.bottom <= 0 || rect.top >= window.innerHeight) return [];
        return [domRectToPlain(rect)];
    }

    function pickCurrentLeader() {
        const viewport = viewportRectFromWindow(window);
        const blockers = getBlockerRects();
        const tasks = cards.map(card => ({
            taskNumber: Number(card.dataset.taskNumber),
            rect: domRectToPlain(card.getBoundingClientRect()),
        }));

        const lead = pickLeadingTask(tasks, viewport, blockers);
        if (!lead || lead.visibleRatio < MIN_VISIBLE_RATIO) {
            return null;
        }

        return lead;
    }

    async function processVisibilityTracking() {
        if (!attemptId || locked || document.hidden) {
            candidateTask = null;
            candidateSinceMs = 0;
            await deactivateTask();
            return;
        }

        const nowMs = performance.now();
        if (!isScrollSettled(nowMs, lastScrollAtMs, SCROLL_SETTLE_MS)) {
            candidateTask = null;
            candidateSinceMs = 0;
            await deactivateTask();
            return;
        }

        const leader = pickCurrentLeader();

        if (!leader) {
            candidateTask = null;
            candidateSinceMs = 0;
            await deactivateTask();
            return;
        }

        if (candidateTask !== leader.taskNumber) {
            candidateTask = leader.taskNumber;
            candidateSinceMs = nowMs;
            await deactivateTask();
            return;
        }

        if (!activeTask && nowMs - candidateSinceMs >= FOCUS_DELAY_MS) {
            await activateTask(candidateTask);
        }
    }

    try {
        const startRes = await post(api.start, { client_ts: new Date().toISOString() });
        attemptId = startRes.attempt_id;
        locked = !!startRes.locked;

        if (locked) {
            setAllLocked(true);
            if (finishButton) {
                finishButton.textContent = 'Вариант уже отправлен';
            }
            return;
        }
    } catch (e) {
        console.error('Failed to start attempt', e);
        setAllLocked(true);
        return;
    }

    cards.forEach(card => {
        const taskNumber = Number(card.dataset.taskNumber);
        const input = card.querySelector('.js-answer-input');
        const ok = card.querySelector('.js-answer-ok');
        const edit = card.querySelector('.js-answer-edit');
        const status = card.querySelector('.js-save-status');
        let statusResetTimer = null;

        function showStatus(message, mode = 'info') {
            if (!status) return;
            if (statusResetTimer) {
                clearTimeout(statusResetTimer);
                statusResetTimer = null;
            }

            status.textContent = message;
            status.classList.remove('opacity-0', 'save-flash', 'text-emerald-400', 'text-red-400', 'text-slate-500');
            status.classList.add('opacity-100');

            if (mode === 'success') {
                status.classList.add('text-emerald-400', 'save-flash');
                statusResetTimer = setTimeout(() => {
                    status.classList.remove('save-flash');
                    status.classList.add('opacity-0');
                }, 2000);
                return;
            }

            if (mode === 'error') {
                status.classList.add('text-red-400');
                return;
            }

            status.classList.add('text-slate-400');
        }

        if (!input || !ok || !edit) return;

        ok.addEventListener('click', async () => {
            const answer = input.value.trim();
            if (!answer) return;

            ok.disabled = true;
            showStatus('Сохраняем...');

            try {
                await post(api.commit(attemptId, taskNumber), {
                    answer,
                    client_ts: new Date().toISOString(),
                });
                input.disabled = true;
                edit.classList.remove('hidden');
                showStatus('Сохранено', 'success');
            } catch (e) {
                console.error('commit failed', e);
                showStatus('Ошибка сохранения', 'error');
            } finally {
                ok.disabled = false;
            }
        });

        edit.addEventListener('click', () => {
            if (locked) return;
            input.disabled = false;
            input.focus();
            showStatus('Режим редактирования');
        });
    });

    window.addEventListener('scroll', () => {
        lastScrollAtMs = performance.now();
        candidateTask = null;
        candidateSinceMs = 0;
    }, { passive: true });

    window.addEventListener('resize', () => {
        candidateTask = null;
        candidateSinceMs = 0;
    });

    document.addEventListener('visibilitychange', async () => {
        if (document.hidden) {
            hiddenSinceMs = Date.now();
            candidateTask = null;
            candidateSinceMs = 0;
            await deactivateTask();
            return;
        }

        if (hiddenSinceMs) {
            pendingAwayMs += Math.max(0, Date.now() - hiddenSinceMs);
            hiddenSinceMs = null;
        }

        lastScrollAtMs = performance.now();
    });

    if (finishButton) {
        finishButton.addEventListener('click', async () => {
            if (locked) return;

            const confirmed = window.confirm('Подтвердите завершение варианта. После отправки ответы нельзя будет изменить.');
            if (!confirmed) return;

            finishButton.disabled = true;
            finishButton.textContent = 'Отправляем...';

            try {
                await deactivateTask();
                await post(api.submit(attemptId), { client_ts: new Date().toISOString() });
                locked = true;
                setAllLocked(true);
                finishButton.textContent = 'Вариант отправлен';
            } catch (e) {
                console.error('submit failed', e);
                finishButton.disabled = false;
                finishButton.textContent = 'Завершить вариант';
            }
        });
    }

    setInterval(() => {
        processVisibilityTracking().catch((e) => {
            console.error('tracking failed', e);
        });
    }, TRACKER_TICK_MS);

    setInterval(async () => {
        if (!attemptId || locked) return;
        try {
            const awayMs = pendingAwayMs;
            await post(api.heartbeat(attemptId), {
                active_task: activeTask,
                visible: !document.hidden,
                away_ms: awayMs,
                client_ts: new Date().toISOString(),
            });
            pendingAwayMs = Math.max(0, pendingAwayMs - awayMs);
        } catch (e) {
            console.error('heartbeat failed', e);
        }
    }, 15000);
});
</script>
@endif

</body>
</html>
