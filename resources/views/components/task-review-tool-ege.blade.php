{{--
    Инструмент для пометки плохих заданий ЕГЭ
    Использование: @include('components.task-review-tool-ege', ['topicId' => '01'])

    Для ОГЭ используй: @include('components.task-review-tool', ['topicId' => '06'])

    Работает двумя способами:
    1. Ручной: добавить класс task-review-item и data-атрибуты к элементам
    2. Авто: скрипт сам найдёт задачи по паттернам в DOM

    Данные хранятся в localStorage: palomatika_reviews_ege_topic_{id}
    Генерирует промпт для Claude с инструкциями по исправлению в JSON файлах.

    Структура данных ЕГЭ:
    - storage/app/tasks/ege/topic_{id}.json
    - storage/app/tasks/ege/topic_{id}_geometry.json (для геометрии)
    - public/images/tasks/ege/{topic}/ (PNG изображения)
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
        background: #dc2626 !important;
    }
    .review-flag.not-flagged {
        background: rgba(100, 116, 139, 0.5);
    }
    .task-container {
        position: relative;
    }
    .review-panel-ege {
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
        border-color: #8b5cf6;
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
    .badge-count-ege {
        background: #8b5cf6;
        color: white;
        border-radius: 9999px;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: bold;
    }
    .review-help-ege {
        background: #1e1b4b;
        border: 1px solid #4c1d95;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 8px;
        font-size: 12px;
        color: #a78bfa;
    }
    /* Inline report form styles */
    .inline-report-form {
        background: #1e293b;
        border: 1px solid #8b5cf6;
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
        color: #8b5cf6;
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
        background: #8b5cf6;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s;
    }
    .inline-report-form .btn-save:hover {
        background: #7c3aed;
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
    .task-review-item.has-inline-form {
        border-color: #8b5cf6 !important;
    }
</style>

{{-- Плавающая панель --}}
<div class="review-panel-ege" id="reviewPanelEge">
    <div class="review-help-ege">
        <strong class="text-purple-300">🎓 Проверка ЕГЭ</strong><br>
        Нажмите 🏳️ рядом с заданием чтобы пометить ошибку
    </div>

    <div id="reviewStatsEge" class="text-sm text-purple-400 text-right mb-1"></div>

    <button onclick="showExportModalEge()" class="review-btn bg-purple-600 hover:bg-purple-700 text-white">
        <span>📋</span>
        <span>Экспорт для Claude</span>
        <span id="flagCountEge" class="badge-count-ege" style="display: none;">0</span>
    </button>

    <button onclick="clearAllFlagsEge()" class="review-btn bg-slate-700 hover:bg-slate-600 text-slate-300">
        <span>🗑️</span>
        <span>Очистить пометки</span>
    </button>

    <button onclick="toggleReviewModeEge()" id="toggleBtnEge" class="review-btn bg-slate-800 hover:bg-slate-700 text-slate-400">
        <span id="toggleIconEge">👁️</span>
        <span id="toggleTextEge">Скрыть панель</span>
    </button>
</div>

{{-- Модалка экспорта --}}
<div id="exportModalEge" class="review-modal" style="display: none;">
    <div class="review-modal-content" style="max-width: 900px;">
        <h3 class="text-xl font-bold text-white mb-4">📋 Промпт для Claude (ЕГЭ)</h3>

        <p class="text-slate-400 text-sm mb-4">Скопируйте этот текст и отправьте Claude для исправления заданий:</p>

        <div id="promptOutputEge" class="prompt-output"></div>

        <div class="flex gap-3 mt-4">
            <button onclick="copyPromptEge()" class="review-btn bg-purple-600 hover:bg-purple-700 text-white flex-1 justify-center">
                📋 Скопировать
            </button>
            <button onclick="closeExportModalEge()" class="review-btn bg-slate-700 hover:bg-slate-600 text-slate-300">
                Закрыть
            </button>
        </div>
    </div>
</div>

<script>
const TOPIC_ID_EGE = '{{ $topicId ?? "00" }}';
const EXAM_TYPE_EGE = 'ege';
const STORAGE_KEY_EGE = `palomatika_reviews_${EXAM_TYPE_EGE}_topic_${TOPIC_ID_EGE}`;

let reviewsEge = {};
let panelHiddenEge = false;
let activeInlineFormEge = null;

// Загрузка сохранённых пометок
function loadReviewsEge() {
    const stored = localStorage.getItem(STORAGE_KEY_EGE);
    if (stored) {
        reviewsEge = JSON.parse(stored);
    }
    updateUIEge();
}

// Сохранение в localStorage
function saveReviewsEge() {
    localStorage.setItem(STORAGE_KEY_EGE, JSON.stringify(reviewsEge));
    updateUIEge();
}

// Обновление UI
function updateUIEge() {
    const count = Object.keys(reviewsEge).length;
    const countEl = document.getElementById('flagCountEge');
    const statsEl = document.getElementById('reviewStatsEge');

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
        if (reviewsEge[key]) {
            btn.classList.add('flagged');
            btn.classList.remove('not-flagged');
            btn.textContent = '🚩';
            btn.title = 'Помечено: ' + reviewsEge[key].comment.substring(0, 50) + '...';
        } else {
            btn.classList.remove('flagged');
            btn.classList.add('not-flagged');
            btn.textContent = '🏳️';
            btn.title = 'Пометить как плохое';
        }
    });
}

// Переключение видимости панели
function toggleReviewModeEge() {
    panelHiddenEge = !panelHiddenEge;
    const panel = document.getElementById('reviewPanelEge');
    const icon = document.getElementById('toggleIconEge');
    const text = document.getElementById('toggleTextEge');

    if (panelHiddenEge) {
        panel.querySelectorAll('.review-btn, .review-help-ege, #reviewStatsEge').forEach(el => {
            if (el.id !== 'toggleBtnEge') el.style.display = 'none';
        });
        icon.textContent = '👁️‍🗨️';
        text.textContent = 'Показать панель';
    } else {
        panel.querySelectorAll('.review-btn, .review-help-ege, #reviewStatsEge').forEach(el => {
            el.style.display = '';
        });
        icon.textContent = '👁️';
        text.textContent = 'Скрыть панель';
    }
}

// Открыть inline форму репорта
function openInlineReportFormEge(container, taskKey) {
    if (activeInlineFormEge && activeInlineFormEge !== container) {
        closeInlineFormEge(activeInlineFormEge);
    }

    if (container.querySelector('.inline-report-form')) {
        return;
    }

    const contentBlock = container.querySelector('.flex-1') || container.querySelector('.p-5') || container;

    const form = document.createElement('div');
    form.className = 'inline-report-form';
    form.innerHTML = `
        <div class="form-header">
            <span>🚩</span>
            <span>Пометить задание ЕГЭ</span>
        </div>
        <textarea class="review-textarea" placeholder="Что не так? Например:&#10;• Неправильный ответ&#10;• Отсутствует картинка&#10;• Опечатка в условии&#10;• SVG не соответствует условию">${reviewsEge[taskKey]?.comment || ''}</textarea>
        <div class="form-actions">
            <button class="btn-save" onclick="saveInlineCommentEge(this, '${taskKey}')">🚩 Сохранить</button>
            <button class="btn-cancel" onclick="closeInlineFormByButtonEge(this)">Отмена</button>
        </div>
    `;

    contentBlock.appendChild(form);
    container.classList.add('has-inline-form');
    activeInlineFormEge = container;

    setTimeout(() => {
        const textarea = form.querySelector('textarea');
        if (textarea) textarea.focus();
    }, 50);
}

// Закрыть inline форму
function closeInlineFormEge(container) {
    const form = container.querySelector('.inline-report-form');
    if (form) {
        form.remove();
    }
    container.classList.remove('has-inline-form');
    if (activeInlineFormEge === container) {
        activeInlineFormEge = null;
    }
}

// Закрыть форму по кнопке
function closeInlineFormByButtonEge(button) {
    const container = button.closest('.task-review-item');
    if (container) {
        closeInlineFormEge(container);
    }
}

// Сохранить комментарий из inline формы
function saveInlineCommentEge(button, taskKey) {
    const container = button.closest('.task-review-item');
    const textarea = container.querySelector('.inline-report-form textarea');
    const comment = textarea.value.trim();

    if (!comment) {
        alert('Пожалуйста, опишите проблему с заданием');
        textarea.focus();
        return;
    }

    reviewsEge[taskKey] = {
        comment: comment,
        timestamp: new Date().toISOString(),
        topicId: TOPIC_ID_EGE,
        examType: EXAM_TYPE_EGE
    };

    saveReviewsEge();
    closeInlineFormEge(container);
}

// Удалить пометку
function removeFlagEge(taskKey) {
    delete reviewsEge[taskKey];
    saveReviewsEge();
}

// Показать модалку экспорта
function showExportModalEge() {
    const allReviews = getAllReviewsEge();

    if (Object.keys(allReviews).length === 0) {
        alert('Нет помеченных заданий ЕГЭ для экспорта');
        return;
    }

    const prompt = generatePromptEge(allReviews);
    document.getElementById('promptOutputEge').textContent = prompt;
    document.getElementById('exportModalEge').style.display = 'flex';
}

// Закрыть модалку экспорта
function closeExportModalEge() {
    document.getElementById('exportModalEge').style.display = 'none';
}

// Собрать все пометки со всех тем ЕГЭ (темы 01-19 + возможно больше)
function getAllReviewsEge() {
    const allReviews = {};

    // ЕГЭ может иметь до 19 заданий (или больше), проверяем все возможные
    for (let i = 1; i <= 25; i++) {
        const topicId = i.toString().padStart(2, '0');
        const key = `palomatika_reviews_${EXAM_TYPE_EGE}_topic_${topicId}`;
        const stored = localStorage.getItem(key);
        if (stored) {
            const topicReviews = JSON.parse(stored);
            Object.assign(allReviews, topicReviews);
        }
    }

    return allReviews;
}

// Генерация промпта для Claude (ЕГЭ)
function generatePromptEge(allReviews) {
    const reviewsByTopic = {};

    // Группируем по темам
    for (const [key, data] of Object.entries(allReviews)) {
        const topicId = data.topicId;
        if (!reviewsByTopic[topicId]) {
            reviewsByTopic[topicId] = [];
        }
        reviewsByTopic[topicId].push({ key, ...data });
    }

    // Геометрические темы ЕГЭ (номера могут отличаться от ОГЭ)
    // Обычно: планиметрия ~13-16, стереометрия ~14
    const geometryTopics = ['13', '14', '15', '16'];
    const hasGeometryTopics = Object.keys(reviewsByTopic).some(t => geometryTopics.includes(t));

    let prompt = `# Исправление заданий ЕГЭ в PALOMATIKA

Найдены следующие проблемы с заданиями в базе данных.

**Архитектура данных ЕГЭ:**
- Основные данные: \`storage/app/tasks/ege/topic_{id}.json\`
- Геометрия: \`storage/app/tasks/ege/topic_{id}_geometry.json\` → затем \`php artisan svg:bake-ege {id}\`
- Изображения: \`public/images/tasks/ege/{topic}/\`
- Сервис доступа: \`TaskDataService\` (метод \`getEgeBlocks()\`)

---

`;

    for (const [topicId, topicReviews] of Object.entries(reviewsByTopic).sort()) {
        const isGeometry = geometryTopics.includes(topicId);
        prompt += `## Задание ${topicId}${isGeometry ? ' (геометрия)' : ''}\n\n`;
        prompt += `**Файл:** \`storage/app/tasks/ege/topic_${topicId}${isGeometry ? '_geometry' : ''}.json\`\n\n`;

        for (const review of topicReviews) {
            // Парсим ключ: ege_topic_01_block_1_zadanie_2_task_5
            const keyMatch = review.key.match(/ege_topic_(\d+)_block_(\d+)_zadanie_(\d+)_task_(\d+)/);
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

## Инструкции для исправления ЕГЭ

### Для обычных заданий:
1. Открой файл \`storage/app/tasks/ege/topic_{id}.json\`
2. Найди указанный блок → задание → задачу по номерам
3. Исправь данные согласно описанию проблемы
4. Очисти кэш: \`php artisan cache:clear\`

### Для геометрических заданий:
1. Открой файл \`storage/app/tasks/ege/topic_{id}_geometry.json\`
2. Найди указанный блок → задание → задачу по номерам
3. Исправь данные (координаты точек, параметры SVG и т.д.)
4. Перегенерируй SVG: \`php artisan svg:bake-ege {id}\`
5. Очисти кэш: \`php artisan cache:clear\`

### Правила для SVG (см. GEOMETRY_SPEC в CLAUDE.md):
- viewBox стандартный: \`0 0 220 200\`
- max-w фиксированный: \`250px\`
- Фигура заполняет ~85% viewBox
- Используй функции: \`labelPos()\`, \`makeAngleArc()\`, \`rightAnglePath()\`

### Добавление изображений из PDF:
1. Сохрани PNG в \`public/images/tasks/ege/{topic}/\`
2. В JSON укажи путь: \`"image": "filename.png"\`

После каждого исправления подтверди изменение.
`;

    return prompt;
}

// Копирование промпта
function copyPromptEge() {
    const prompt = document.getElementById('promptOutputEge').textContent;
    navigator.clipboard.writeText(prompt).then(() => {
        alert('Промпт скопирован в буфер обмена!');
    }).catch(() => {
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
function clearAllFlagsEge() {
    if (Object.keys(reviewsEge).length === 0) {
        alert('Нет пометок для удаления');
        return;
    }

    if (confirm(`Удалить все ${Object.keys(reviewsEge).length} пометок для задания ЕГЭ ${TOPIC_ID_EGE}?`)) {
        reviewsEge = {};
        saveReviewsEge();
    }
}

// Функция для добавления кнопки флага к задаче
function addFlagButtonEge(container, taskKey, taskInfo) {
    if (container.querySelector('.review-flag')) return;

    const btn = document.createElement('button');
    btn.className = 'review-flag not-flagged';
    btn.dataset.taskKey = taskKey;
    btn.textContent = '🏳️';
    btn.title = 'Пометить как плохое';
    btn.onclick = function(e) {
        e.stopPropagation();
        e.preventDefault();
        if (reviewsEge[taskKey]) {
            if (confirm('Удалить пометку?')) {
                removeFlagEge(taskKey);
                closeInlineFormEge(container);
            }
        } else {
            openInlineReportFormEge(container, taskKey);
        }
    };
    container.style.position = 'relative';
    container.appendChild(btn);
}

// Автоматический поиск задач на странице
function autoFindTasksEge() {
    let currentBlock = 1;
    let currentZadanie = 1;
    let taskCounter = 0;

    // 1. Элементы с классом task-review-item (ручная разметка)
    document.querySelectorAll('.task-review-item').forEach(item => {
        const taskKey = item.dataset.taskKey;
        const taskInfo = item.dataset.taskInfo;
        if (taskKey) {
            addFlagButtonEge(item, taskKey, taskInfo);
            taskCounter++;
        }
    });

    // 2. Автоматический поиск по паттернам DOM
    document.querySelectorAll('[class*="mb-12"], [class*="mb-10"]').forEach(section => {
        const blockHeader = section.querySelector('p[class*="text-lg"]');
        if (blockHeader) {
            const blockMatch = blockHeader.textContent.match(/Блок\s*(\d+)/i);
            if (blockMatch) {
                currentBlock = parseInt(blockMatch[1]);
            }
        }

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
                let blockNum = currentBlock;
                let zadanieNum = currentZadanie;

                let parent = container.parentElement;
                while (parent && parent.tagName !== 'BODY') {
                    const blockP = parent.querySelector('p[class*="text-lg"]');
                    if (blockP) {
                        const match = blockP.textContent.match(/Блок\s*(\d+)/i);
                        if (match) blockNum = parseInt(match[1]);
                    }

                    const zadanieH3 = parent.querySelector('h3');
                    if (zadanieH3) {
                        const match = zadanieH3.textContent.match(/Задание\s*(\d+)/i);
                        if (match) zadanieNum = parseInt(match[1]);
                    }

                    parent = parent.parentElement;
                }

                // Ключ для ЕГЭ отличается от ОГЭ
                const taskKey = `ege_topic_${TOPIC_ID_EGE}_block_${blockNum}_zadanie_${zadanieNum}_task_${taskId}`;

                let expression = '';
                const nextSibling = span.nextSibling || span.nextElementSibling;
                if (nextSibling) {
                    expression = nextSibling.textContent?.trim() || '';
                }

                const taskInfo = `ЕГЭ Задание ${TOPIC_ID_EGE}, Блок ${blockNum}, Задание ${zadanieNum}, Задача ${taskId}<br>` +
                    (expression ? `<code>${expression.substring(0, 100)}</code>` : '');

                addFlagButtonEge(container, taskKey, taskInfo);
                taskCounter++;
            }
        }
    });

    console.log(`[TaskReview EGE] Найдено задач: ${taskCounter}`);
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    loadReviewsEge();

    setTimeout(autoFindTasksEge, 500);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeExportModalEge();
            if (activeInlineFormEge) {
                closeInlineFormEge(activeInlineFormEge);
            }
        }
    });

    document.getElementById('exportModalEge').addEventListener('click', function(e) {
        if (e.target === this) closeExportModalEge();
    });
});

// Экспортируем функцию для использования в шаблонах
window.TaskReviewEge = {
    addFlagButton: addFlagButtonEge,
    loadReviews: loadReviewsEge,
    autoFindTasks: autoFindTasksEge
};
</script>
