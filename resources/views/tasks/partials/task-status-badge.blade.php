@php
    $canManageStatus = auth()->check() && auth()->user()?->isAdmin();
    $taskStatus = $task['status'] ?? 'draft';
    $isProduction = $taskStatus === 'production';
    $statusTaskKey = $taskKey ?? '';
    $topicForStatus = isset($topicId) ? str_pad((string) $topicId, 2, '0', STR_PAD_LEFT) : null;
@endphp

@if($canManageStatus && $statusTaskKey !== '' && $topicForStatus !== null)
    <span class="js-task-status-badge inline-flex items-center gap-1 ml-2 cursor-pointer select-none"
          data-task-key="{{ $statusTaskKey }}"
          data-topic-id="{{ $topicForStatus }}"
          data-status="{{ $taskStatus }}"
          onclick="TaskStatusToggle.toggle(this)"
          title="Click to toggle status">
        @if($isProduction)
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
            <span class="text-[10px] text-emerald-400 font-medium uppercase tracking-wide">prod</span>
        @else
            <span class="inline-block w-2 h-2 rounded-full bg-slate-500"></span>
            <span class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">draft</span>
        @endif
    </span>
@endif

@once
    <script>
        window.TaskStatusToggle = window.TaskStatusToggle || {
            async toggle(badge) {
                const taskKey = badge.dataset.taskKey;
                const topicId = badge.dataset.topicId;
                const currentStatus = badge.dataset.status;
                const newStatus = currentStatus === 'production' ? 'draft' : 'production';

                badge.style.opacity = '0.5';
                badge.style.pointerEvents = 'none';

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch(`/api/topics/${topicId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            task_key: taskKey,
                            status: newStatus,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data?.message || 'Failed to update status');
                    }

                    badge.dataset.status = newStatus;
                    const dot = badge.querySelector('span:first-child');
                    const label = badge.querySelector('span:last-child');

                    if (newStatus === 'production') {
                        dot.className = 'inline-block w-2 h-2 rounded-full bg-emerald-400';
                        label.className = 'text-[10px] text-emerald-400 font-medium uppercase tracking-wide';
                        label.textContent = 'prod';
                    } else {
                        dot.className = 'inline-block w-2 h-2 rounded-full bg-slate-500';
                        label.className = 'text-[10px] text-slate-500 font-medium uppercase tracking-wide';
                        label.textContent = 'draft';
                    }
                } catch (e) {
                    console.error('Status toggle error:', e);
                    alert(e.message || 'Error updating status');
                } finally {
                    badge.style.opacity = '1';
                    badge.style.pointerEvents = 'auto';
                }
            },
        };
    </script>
@endonce
