{{--
    Partial для отображения задания
    Диспетчеризует на нужный тип
    @param array $zadanie - данные задания
    @param array $block - данные блока
    @param string $topicId - ID темы
    @param string $color - цвет темы
--}}

@php
    $type = $zadanie['type'] ?? 'expression';
@endphp

<div class="mb-10">
    {{-- Zadanie Header --}}
    @php
        $isAdminForZadanie = auth()->check() && auth()->user()?->isAdmin();
        $hasStatements = !empty($zadanie['statements']) && empty($zadanie['tasks']);
        $zadanieItems = $hasStatements ? ($zadanie['statements'] ?? []) : ($zadanie['tasks'] ?? []);
        $itemKeyType = $hasStatements ? 'statement' : 'task';
        $zadanieTaskKeys = [];
        foreach ($zadanieItems as $zt) {
            $ztId = $zt['id'] ?? null;
            if ($ztId !== null) {
                $zadanieTaskKeys[] = "topic_{$topicId}_block_{$block['number']}_zadanie_{$zadanie['number']}_{$itemKeyType}_{$ztId}";
            }
        }
        $allProduction = !empty($zadanieItems) && collect($zadanieItems)->every(fn($t) => ($t['status'] ?? 'draft') === 'production');
    @endphp
    <div class="bg-slate-800 rounded-xl p-4 mb-6 border-l-4 border-{{ $color }}-500">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-lg font-semibold text-white">
                @if(!empty($zadanie['label']))
                    {{ $zadanie['label'] }}. {{ $zadanie['instruction'] }}
                @else
                    Задание {{ $zadanie['number'] }}. {{ $zadanie['instruction'] }}
                @endif
            </h3>
            <div class="flex items-center gap-2 shrink-0">
                @if($isAdminForZadanie && !empty($zadanieTaskKeys))
                    <button type="button"
                            class="js-zadanie-status-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition text-xs font-medium {{ $allProduction ? 'bg-emerald-700 text-emerald-100 hover:bg-emerald-600' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}"
                            data-topic-id="{{ str_pad($topicId, 2, '0', STR_PAD_LEFT) }}"
                            data-task-keys="{{ implode(',', $zadanieTaskKeys) }}"
                            data-current-status="{{ $allProduction ? 'production' : 'draft' }}"
                            onclick="ZadanieStatusToggle.toggle(this)"
                            title="{{ $allProduction ? 'Перевести все задачи в draft' : 'Перевести все задачи в production' }}">
                        <span class="inline-block w-2 h-2 rounded-full {{ $allProduction ? 'bg-emerald-300' : 'bg-slate-500' }}"></span>
                        <span class="js-zadanie-status-label">{{ $allProduction ? 'Все в prod' : 'Все в prod' }}</span>
                    </button>
                @endif
                <a href="{{ route('topics.export', ['id' => (int) $topicId, 'block' => $block['number'] ?? null, 'zadanie' => $zadanie['number']]) }}"
                   class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-700 text-slate-200 hover:bg-slate-600 transition text-xs font-medium"
                   title="Скачать JSON для нейросети">
                    <span>Экспорт JSON</span>
                </a>
            </div>
        </div>
        @if(isset($zadanie['section']))
            <p class="text-slate-400 text-sm mt-1">{{ $zadanie['section'] }}</p>
        @endif
    </div>

    {{-- Задачи по типу --}}
    @switch($type)
        @case('expression')
            @include('tasks.types.expression', compact('zadanie', 'block', 'topicId'))
            @break

        @case('choice')
        @case('simple_choice')
        @case('fraction_choice')
        @case('interval_choice')
        @case('between_fractions')
        @case('segment_choice')
        @case('fraction_options')
        @case('decimal_choice')
        @case('sqrt_choice')
        @case('sqrt_interval')
        @case('sqrt_segment')
        @case('sqrt_options')
        @case('comparison')
        @case('power_choice')
        @case('compare_fractions')
        @case('false_statements')
        @case('ordering')
        @case('point_value')
        @case('fraction_point')
        @case('count_integers')
        @case('negative_segment')
        @case('negative_interval')
            @include('tasks.types.choice', compact('zadanie', 'block', 'topicId'))
            @break

        @case('word_problem')
            @include('tasks.types.word-problem', compact('zadanie', 'block', 'topicId'))
            @break

        @case('matching')
        @case('matching_signs')
        @case('matching_4')
            @include('tasks.types.matching', compact('zadanie', 'block', 'topicId'))
            @break

        @case('geometry')
            @include('tasks.types.geometry', compact('zadanie', 'block', 'topicId'))
            @break

        @case('grid_image')
        @case('grid_image_with_question')
            @include('tasks.types.grid', compact('zadanie', 'block', 'topicId'))
            @break

        @case('statements')
            @include('tasks.types.statements', compact('zadanie', 'block', 'topicId'))
            @break

        @case('graph_statements')
            @include('tasks.types.graph-statements', compact('zadanie', 'block', 'topicId'))
            @break

        @case('graphic')
            @include('tasks.types.graphic', compact('zadanie', 'block', 'topicId'))
            @break

        @default
            @include('tasks.types.expression', compact('zadanie', 'block', 'topicId'))
    @endswitch
</div>

@once
<script>
    window.ZadanieStatusToggle = window.ZadanieStatusToggle || {
        async toggle(button) {
            const topicId = button.dataset.topicId;
            const taskKeys = button.dataset.taskKeys.split(',');
            const currentStatus = button.dataset.currentStatus;
            const newStatus = currentStatus === 'production' ? 'draft' : 'production';

            button.style.opacity = '0.5';
            button.style.pointerEvents = 'none';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch(`/api/topics/${topicId}/status/bulk`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        task_keys: taskKeys,
                        status: newStatus,
                    }),
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data?.message || 'Failed to update');
                }

                // Update button appearance
                button.dataset.currentStatus = newStatus;
                const dot = button.querySelector('span:first-child');
                if (newStatus === 'production') {
                    button.className = button.className
                        .replace('bg-slate-700', 'bg-emerald-700')
                        .replace('text-slate-300', 'text-emerald-100')
                        .replace('hover:bg-slate-600', 'hover:bg-emerald-600');
                    dot.className = dot.className.replace('bg-slate-500', 'bg-emerald-300');
                    button.title = 'Перевести все задачи в draft';
                } else {
                    button.className = button.className
                        .replace('bg-emerald-700', 'bg-slate-700')
                        .replace('text-emerald-100', 'text-slate-300')
                        .replace('hover:bg-emerald-600', 'hover:bg-slate-600');
                    dot.className = dot.className.replace('bg-emerald-300', 'bg-slate-500');
                    button.title = 'Перевести все задачи в production';
                }

                // Update individual task badges within this zadanie section
                const zadanieSection = button.closest('.mb-10');
                if (zadanieSection) {
                    zadanieSection.querySelectorAll('.js-task-status-badge').forEach(badge => {
                        badge.dataset.status = newStatus;
                        const badgeDot = badge.querySelector('span:first-child');
                        const badgeLabel = badge.querySelector('span:last-child');
                        if (newStatus === 'production') {
                            badgeDot.className = 'inline-block w-2 h-2 rounded-full bg-emerald-400';
                            badgeLabel.className = 'text-[10px] text-emerald-400 font-medium uppercase tracking-wide';
                            badgeLabel.textContent = 'prod';
                        } else {
                            badgeDot.className = 'inline-block w-2 h-2 rounded-full bg-slate-500';
                            badgeLabel.className = 'text-[10px] text-slate-500 font-medium uppercase tracking-wide';
                            badgeLabel.textContent = 'draft';
                        }
                    });
                }
            } catch (e) {
                console.error('Zadanie status toggle error:', e);
                alert(e.message || 'Error updating status');
            } finally {
                button.style.opacity = '1';
                button.style.pointerEvents = 'auto';
            }
        },
    };
</script>
@endonce
