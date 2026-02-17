{{--
    Инструмент для пометки плохих заданий ОГЭ
    Использование: @include('components.task-review-tool', ['topicId' => '06'])

    Для ЕГЭ используй: @include('components.task-review-tool-ege', ['topicId' => '01'])

    Работает двумя способами:
    1. Ручной: добавить класс task-review-item и data-атрибуты к элементам
    2. Авто: скрипт сам найдёт задачи по паттернам в DOM

    Данные хранятся в localStorage: palomatika_reviews_oge_topic_{id}
    Генерирует промпт для Claude с инструкциями по исправлению в JSON файлах.
--}}

<style>
    .review-flag {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
        opacity: 0.4;
        z-index: 10;
    }
    .review-flag:hover {
        opacity: 1;
        transform: scale(1.1);
    }
    .review-flag.flagged {
        opacity: 1;
        background: #ef4444 !important;
    }
    .review-flag.not-flagged {
        background: rgba(100, 116, 139, 0.5);
    }
    .task-container {
        position: relative;
    }
    .review-panel {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .review-btn {
        padding: 12px 20px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    .review-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.4);
    }
    .review-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .review-modal-content {
        background: #1e293b;
        border-radius: 16px;
        padding: 24px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        border: 1px solid #334155;
    }
    .review-textarea {
        width: 100%;
        min-height: 80px;
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 8px;
        padding: 12px;
        color: white;
        font-size: 14px;
        resize: vertical;
    }
    .review-textarea:focus {
        outline: none;
        border-color: #3b82f6;
    }
    .prompt-output {
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 8px;
        padding: 16px;
        color: #94a3b8;
        font-family: monospace;
        font-size: 12px;
        white-space: pre-wrap;
        max-height: 400px;
        overflow-y: auto;
    }
    .badge-count {
        background: #ef4444;
        color: white;
        border-radius: 9999px;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: bold;
    }
    .review-help {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 8px;
        font-size: 12px;
        color: #94a3b8;
    }
    /* Inline report form styles */
    .inline-report-form {
        background: #1e293b;
        border: 1px solid #ef4444;
        border-radius: 12px;
        padding: 16px;
        margin-top: 12px;
        animation: slideDown 0.2s ease-out;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .inline-report-form .form-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        color: #ef4444;
        font-weight: 600;
        font-size: 14px;
    }
    .inline-report-form .review-textarea {
        min-height: 80px;
    }
    .inline-report-form .form-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }
    .inline-report-form .btn-save {
        flex: 1;
        padding: 10px 16px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s;
    }
    .inline-report-form .btn-save:hover {
        background: #dc2626;
    }
    .inline-report-form .btn-cancel {
        padding: 10px 16px;
        background: #334155;
        color: #94a3b8;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s;
    }
    .inline-report-form .btn-cancel:hover {
        background: #475569;
    }
    .inline-report-form .btn-answer-edit {
        padding: 10px 16px;
        background: #1d4ed8;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s;
    }
    .inline-report-form .btn-answer-edit:hover {
        background: #1e40af;
    }
    .task-review-item.has-inline-form {
        border-color: #ef4444 !important;
    }
</style>

{{-- Плавающая панель --}}
<div class="review-panel" id="reviewPanel">
    <div class="review-help">
        <strong class="text-white">🔍 Проверка ОГЭ</strong><br>
        Нажмите 🏳️ рядом с заданием чтобы пометить ошибку
    </div>

    <div id="reviewStats" class="text-sm text-slate-400 text-right mb-1"></div>

    <button onclick="showExportModal()" class="review-btn bg-blue-600 hover:bg-blue-700 text-white">
        <span>📋</span>
        <span>Экспорт для Claude</span>
        <span id="flagCount" class="badge-count" style="display: none;">0</span>
    </button>

    <button onclick="clearAllFlags()" class="review-btn bg-slate-700 hover:bg-slate-600 text-slate-300">
        <span>🗑️</span>
        <span>Очистить пометки</span>
    </button>

    <button onclick="toggleReviewMode()" id="toggleBtn" class="review-btn bg-slate-800 hover:bg-slate-700 text-slate-400">
        <span id="toggleIcon">👁️</span>
        <span id="toggleText">Скрыть панель</span>
    </button>
</div>

{{-- Модалка для комментария удалена — используем inline форму --}}

{{-- Модалка экспорта --}}
<div id="exportModal" class="review-modal" style="display: none;">
    <div class="review-modal-content" style="max-width: 900px;">
        <h3 class="text-xl font-bold text-white mb-4">📋 Промпт для Claude (ОГЭ)</h3>

        <p class="text-slate-400 text-sm mb-4">Скопируйте этот текст и отправьте Claude для исправления заданий:</p>

        <div id="promptOutput" class="prompt-output"></div>

        <div class="flex gap-3 mt-4">
            <button onclick="copyPrompt()" class="review-btn bg-green-600 hover:bg-green-700 text-white flex-1 justify-center">
                📋 Скопировать
            </button>
            <button onclick="closeExportModal()" class="review-btn bg-slate-700 hover:bg-slate-600 text-slate-300">
                Закрыть
            </button>
        </div>
    </div>
</div>

<script>
const TOPIC_ID = '{{ $topicId ?? "00" }}';
const EXAM_TYPE = 'oge';
const STORAGE_KEY = `palomatika_reviews_${EXAM_TYPE}_topic_${TOPIC_ID}`;

let reviews = {};
let panelHidden = false;
let activeInlineForm = null; // Контейнер с открытой inline формой

// Загрузка сохранённых пометок
function loadReviews() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
        reviews = JSON.parse(stored);
    }
    updateUI();
}

// Сохранение в localStorage
function saveReviews() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(reviews));
    updateUI();
}

// Обновление UI
function updateUI() {
    const count = Object.keys(reviews).length;
    const countEl = document.getElementById('flagCount');
    const statsEl = document.getElementById('reviewStats');

    if (count > 0) {
        countEl.style.display = 'inline';
        countEl.textContent = count;
        statsEl.textContent = `Помечено: ${count} заданий`;
    } else {
        countEl.style.display = 'none';
        statsEl.textContent = '';
    }

    // Обновляем кнопки флагов
    document.querySelectorAll('.review-flag').forEach(btn => {
        const key = btn.dataset.taskKey;
        if (reviews[key]) {
            btn.classList.add('flagged');
            btn.classList.remove('not-flagged');
            btn.textContent = '🚩';
            btn.title = 'Помечено: ' + reviews[key].comment.substring(0, 50) + '...';
        } else {
            btn.classList.remove('flagged');
            btn.classList.add('not-flagged');
            btn.textContent = '🏳️';
            btn.title = 'Пометить как плохое';
        }
    });
}

// Переключение видимости панели
function toggleReviewMode() {
    panelHidden = !panelHidden;
    const panel = document.getElementById('reviewPanel');
    const icon = document.getElementById('toggleIcon');
    const text = document.getElementById('toggleText');

    if (panelHidden) {
        panel.querySelectorAll('.review-btn, .review-help, #reviewStats').forEach(el => {
            if (el.id !== 'toggleBtn') el.style.display = 'none';
        });
        icon.textContent = '👁️‍🗨️';
        text.textContent = 'Показать панель';
    } else {
        panel.querySelectorAll('.review-btn, .review-help, #reviewStats').forEach(el => {
            el.style.display = '';
        });
        icon.textContent = '👁️';
        text.textContent = 'Скрыть панель';
    }
}

// Открыть inline форму репорта
function openInlineReportForm(container, taskKey) {
    // Закрываем предыдущую форму, если есть
    if (activeInlineForm && activeInlineForm !== container) {
        closeInlineForm(activeInlineForm);
    }

    // Проверяем, не открыта ли уже форма в этом контейнере
    if (container.querySelector('.inline-report-form')) {
        return;
    }

    // Находим блок с текстом задания
    const contentBlock = container.querySelector('.flex-1') || container.querySelector('.p-5') || container;

    const hasAnswerEditor = !!container.querySelector('.js-task-answer-edit-toggle');

    // Создаём inline форму
    const form = document.createElement('div');
    form.className = 'inline-report-form';
    form.innerHTML = `
        <div class="form-header">
            <span>🚩</span>
            <span>Пометить задание</span>
        </div>
        <textarea class="review-textarea" placeholder="Что не так? Например:&#10;• Неправильный ответ&#10;• Отсутствует картинка&#10;• Опечатка в условии">${reviews[taskKey]?.comment || ''}</textarea>
        <div class="form-actions">
            ${hasAnswerEditor ? '<button class="btn-answer-edit" onclick="openAnswerEditorFromReview(this)">✏️ Править ответ</button>' : ''}
            <button class="btn-save" onclick="saveInlineComment(this, '${taskKey}')">🚩 Сохранить</button>
            <button class="btn-cancel" onclick="closeInlineFormByButton(this)">Отмена</button>
        </div>
    `;

    contentBlock.appendChild(form);
    container.classList.add('has-inline-form');
    activeInlineForm = container;

    // Фокус на textarea
    setTimeout(() => {
        const textarea = form.querySelector('textarea');
        if (textarea) textarea.focus();
    }, 50);
}

function openAnswerEditorFromReview(button) {
    const container = button.closest('.task-review-item');
    if (!container) return;

    const editToggle = container.querySelector('.js-task-answer-edit-toggle');
    if (!editToggle) return;

    editToggle.click();
    closeInlineForm(container);
    editToggle.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Закрыть inline форму
function closeInlineForm(container) {
    const form = container.querySelector('.inline-report-form');
    if (form) {
        form.remove();
    }
    container.classList.remove('has-inline-form');
    if (activeInlineForm === container) {
        activeInlineForm = null;
    }
}

// Закрыть форму по кнопке
function closeInlineFormByButton(button) {
    const container = button.closest('.task-review-item');
    if (container) {
        closeInlineForm(container);
    }
}

// Сохранить комментарий из inline формы
function saveInlineComment(button, taskKey) {
    const container = button.closest('.task-review-item');
    const textarea = container.querySelector('.inline-report-form textarea');
    const comment = textarea.value.trim();

    if (!comment) {
        alert('Пожалуйста, опишите проблему с заданием');
        textarea.focus();
        return;
    }

    reviews[taskKey] = {
        comment: comment,
        timestamp: new Date().toISOString(),
        topicId: TOPIC_ID
    };

    saveReviews();
    closeInlineForm(container);
}

// Удалить пометку
function removeFlag(taskKey) {
    delete reviews[taskKey];
    saveReviews();
}

// Показать модалку экспорта
function showExportModal() {
    const allReviews = getAllReviews();

    if (Object.keys(allReviews).length === 0) {
        alert('Нет помеченных заданий для экспорта');
        return;
    }

    const prompt = generatePrompt(allReviews);
    document.getElementById('promptOutput').textContent = prompt;
    document.getElementById('exportModal').style.display = 'flex';
}

// Закрыть модалку экспорта
function closeExportModal() {
    document.getElementById('exportModal').style.display = 'none';
}

// Собрать все пометки со всех тем ОГЭ (темы 06-19)
function getAllReviews() {
    const allReviews = {};

    for (let i = 6; i <= 19; i++) {
        const topicId = i.toString().padStart(2, '0');
        const key = `palomatika_reviews_${EXAM_TYPE}_topic_${topicId}`;
        const stored = localStorage.getItem(key);
        if (stored) {
            const topicReviews = JSON.parse(stored);
            Object.assign(allReviews, topicReviews);
        }
    }

    return allReviews;
}

// Генерация промпта для Claude
function generatePrompt(allReviews) {
    const reviewsByTopic = {};

    // Группируем по темам
    for (const [key, data] of Object.entries(allReviews)) {
        const topicId = data.topicId;
        if (!reviewsByTopic[topicId]) {
            reviewsByTopic[topicId] = [];
        }
        reviewsByTopic[topicId].push({ key, ...data });
    }

    // Определяем, есть ли геометрические темы (15, 16, 17, 18)
    const geometryTopics = ['15', '16', '17', '18'];
    const hasGeometryTopics = Object.keys(reviewsByTopic).some(t => geometryTopics.includes(t));

    let prompt = `# Исправление заданий ОГЭ в PALOMATIKA

Найдены следующие проблемы с заданиями в базе данных.

**Архитектура данных:**
- Основные данные: \`storage/app/tasks/topic_{id}.json\`
- Геометрия (темы 15-18): \`storage/app/tasks/topic_{id}_geometry.json\` → затем \`php artisan svg:bake {id}\`
- Сервис доступа: \`TaskDataService\`

---

`;

    for (const [topicId, topicReviews] of Object.entries(reviewsByTopic).sort()) {
        const isGeometry = geometryTopics.includes(topicId);
        prompt += `## Тема ${topicId}${isGeometry ? ' (геометрия)' : ''}\n\n`;
        prompt += `**Файл:** \`storage/app/tasks/topic_${topicId}${isGeometry ? '_geometry' : ''}.json\`\n\n`;

        for (const review of topicReviews) {
            // Парсим ключ: topic_06_block_1_zadanie_2_task_5
            const keyMatch = review.key.match(/topic_(\d+)_block_(\d+)_zadanie_(\d+)_task_(\d+)/);
            if (keyMatch) {
                const [, , blockNum, zadanieNum, taskNum] = keyMatch;
                prompt += `### Блок ${blockNum}, Задание ${zadanieNum}, Задача ${taskNum}\n`;
            } else {
                prompt += `### ${review.key}\n`;
            }
            prompt += `**Проблема:** ${review.comment}\n\n`;
        }
    }

    prompt += `---

## Инструкции для исправления

### Для обычных тем (06-14, 19):
1. Открой файл \`storage/app/tasks/topic_{id}.json\`
2. Найди указанный блок → задание → задачу по номерам
3. Исправь данные согласно описанию проблемы
4. Очисти кэш: \`php artisan cache:clear\`

### Для геометрических тем (15-18):
1. Открой файл \`storage/app/tasks/topic_{id}_geometry.json\`
2. Найди указанный блок → задание → задачу по номерам
3. Исправь данные (координаты точек, параметры SVG и т.д.)
4. Перегенерируй SVG: \`php artisan svg:bake {id}\`
5. Очисти кэш: \`php artisan cache:clear\`

### Правила для SVG (см. GEOMETRY_SPEC в CLAUDE.md):
- viewBox стандартный: \`0 0 220 200\`
- max-w фиксированный: \`250px\`
- Фигура заполняет ~85% viewBox
- Используй функции: \`labelPos()\`, \`makeAngleArc()\`, \`rightAnglePath()\`

После каждого исправления подтверди изменение.
`;

    return prompt;
}

// Копирование промпта
function copyPrompt() {
    const prompt = document.getElementById('promptOutput').textContent;
    navigator.clipboard.writeText(prompt).then(() => {
        alert('Промпт скопирован в буфер обмена!');
    }).catch(() => {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = prompt;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Промпт скопирован!');
    });
}

// Очистить все пометки для текущей темы
function clearAllFlags() {
    if (Object.keys(reviews).length === 0) {
        alert('Нет пометок для удаления');
        return;
    }

    if (confirm(`Удалить все ${Object.keys(reviews).length} пометок для темы ${TOPIC_ID}?`)) {
        reviews = {};
        saveReviews();
    }
}

// Функция для добавления кнопки флага к задаче
function addFlagButton(container, taskKey, taskInfo) {
    // Для задач с горизонтальным скроллом KaTeX флажок визуально ломается (выглядит как "фиолетовая палка").
    // Пропускаем и сами такие контейнеры, и контейнеры с вложенным overflow-блоком.
    if (container.classList.contains('overflow-x-auto') || container.querySelector('.overflow-x-auto')) return;

    // Проверяем, не добавлена ли уже кнопка
    if (container.querySelector('.review-flag')) return;

    const btn = document.createElement('button');
    btn.className = 'review-flag not-flagged';
    btn.dataset.taskKey = taskKey;
    btn.textContent = '🏳️';
    btn.title = 'Пометить как плохое';
    btn.onclick = function(e) {
        e.stopPropagation();
        e.preventDefault();
        if (reviews[taskKey]) {
            if (confirm('Удалить пометку?')) {
                removeFlag(taskKey);
                closeInlineForm(container);
            }
        } else {
            openInlineReportForm(container, taskKey);
        }
    };
    container.style.position = 'relative';
    container.appendChild(btn);
}

// Автоматический поиск задач на странице
function autoFindTasks() {
    let currentBlock = 1;
    let currentZadanie = 1;
    let taskCounter = 0;

    // Ищем все элементы, которые могут быть задачами
    // 1. Элементы с классом task-review-item (ручная разметка)
    document.querySelectorAll('.task-review-item').forEach(item => {
        const taskKey = item.dataset.taskKey;
        const taskInfo = item.dataset.taskInfo;
        if (taskKey) {
            addFlagButton(item, taskKey, taskInfo);
            taskCounter++;
        }
    });

    // 2. Автоматический поиск по паттернам DOM
    // Ищем блоки
    document.querySelectorAll('[class*="mb-12"], [class*="mb-10"]').forEach(section => {
        // Ищем заголовок блока
        const blockHeader = section.querySelector('p[class*="text-lg"]');
        if (blockHeader) {
            const blockMatch = blockHeader.textContent.match(/Блок\s*(\d+)/i);
            if (blockMatch) {
                currentBlock = parseInt(blockMatch[1]);
            }
        }

        // Ищем заголовки заданий
        const zadanieHeaders = section.querySelectorAll('h3[class*="font-semibold"]');
        zadanieHeaders.forEach(header => {
            const zadanieMatch = header.textContent.match(/Задание\s*(\d+)/i);
            if (zadanieMatch) {
                currentZadanie = parseInt(zadanieMatch[1]);
            }
        });
    });

    // 3. Ищем элементы задач по ID паттерну "1)", "2)" и т.д.
    document.querySelectorAll('span[class*="font-bold"]').forEach(span => {
        const text = span.textContent.trim();
        const taskMatch = text.match(/^(\d+)\)$/);

        if (taskMatch) {
            const taskId = parseInt(taskMatch[1]);
            const container = span.closest('div[class*="rounded"]');

            if (container && !container.querySelector('.review-flag')) {
                // Ищем контекст (блок и задание)
                let blockNum = currentBlock;
                let zadanieNum = currentZadanie;

                // Пытаемся найти контекст выше по DOM
                let parent = container.parentElement;
                while (parent && parent.tagName !== 'BODY') {
                    // Ищем заголовок блока
                    const blockP = parent.querySelector('p[class*="text-lg"]');
                    if (blockP) {
                        const match = blockP.textContent.match(/Блок\s*(\d+)/i);
                        if (match) blockNum = parseInt(match[1]);
                    }

                    // Ищем заголовок задания
                    const zadanieH3 = parent.querySelector('h3');
                    if (zadanieH3) {
                        const match = zadanieH3.textContent.match(/Задание\s*(\d+)/i);
                        if (match) zadanieNum = parseInt(match[1]);
                    }

                    parent = parent.parentElement;
                }

                const taskKey = `topic_${TOPIC_ID}_block_${blockNum}_zadanie_${zadanieNum}_task_${taskId}`;

                // Собираем информацию о задаче
                let expression = '';
                const nextSibling = span.nextSibling || span.nextElementSibling;
                if (nextSibling) {
                    expression = nextSibling.textContent?.trim() || '';
                }

                const taskInfo = `Блок ${blockNum}, Задание ${zadanieNum}, Задача ${taskId}<br>` +
                    (expression ? `<code>${expression.substring(0, 100)}</code>` : '');

                addFlagButton(container, taskKey, taskInfo);
                taskCounter++;
            }
        }
    });

    console.log(`[TaskReview] Найдено задач: ${taskCounter}`);
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    loadReviews();

    // Автоматический поиск задач после небольшой задержки (для KaTeX)
    setTimeout(autoFindTasks, 500);

    // Закрытие модалки экспорта по Escape, закрытие inline формы
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeExportModal();
            // Закрываем inline форму
            if (activeInlineForm) {
                closeInlineForm(activeInlineForm);
            }
        }
    });

    // Закрытие модалки экспорта по клику вне
    document.getElementById('exportModal').addEventListener('click', function(e) {
        if (e.target === this) closeExportModal();
    });
});

// Экспортируем функцию для использования в шаблонах
window.TaskReview = {
    addFlagButton: addFlagButton,
    loadReviews: loadReviews,
    autoFindTasks: autoFindTasks
};
</script>
