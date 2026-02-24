@extends('layouts.teacher')

@section('title', 'Аудит')
@section('header', 'Журнал аудита')

@section('content')
<div x-data="auditPage()" x-init="init()" class="space-y-4">
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] p-4 space-y-3">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <input x-model="filters.from" type="date" class="bg-dark border border-white/[0.08] rounded-lg px-3 py-2 text-sm text-white">
            <input x-model="filters.to" type="date" class="bg-dark border border-white/[0.08] rounded-lg px-3 py-2 text-sm text-white">
            <input x-model="filters.actor_query" type="text" placeholder="По пользователю" class="bg-dark border border-white/[0.08] rounded-lg px-3 py-2 text-sm text-white col-span-2 md:col-span-1">
            <input x-model="filters.subject_query" type="text" placeholder="По ученику" class="bg-dark border border-white/[0.08] rounded-lg px-3 py-2 text-sm text-white col-span-2 md:col-span-1">
        </div>
        <div class="flex flex-wrap gap-2">
            <button @click="preset('today')" class="px-3 py-1.5 text-xs rounded-lg bg-dark border border-white/[0.08] text-gray-300">Сегодня</button>
            <button @click="preset('7d')" class="px-3 py-1.5 text-xs rounded-lg bg-dark border border-white/[0.08] text-gray-300">7 дней</button>
            <button @click="preset('30d')" class="px-3 py-1.5 text-xs rounded-lg bg-dark border border-white/[0.08] text-gray-300">30 дней</button>
            <button @click="preset('90d')" class="px-3 py-1.5 text-xs rounded-lg bg-dark border border-white/[0.08] text-gray-300">90 дней</button>
            <button @click="load()" class="px-3 py-1.5 text-xs rounded-lg bg-coral/20 border border-coral/40 text-coral">Применить</button>
            <button @click="exportCsv()" class="px-3 py-1.5 text-xs rounded-lg bg-emerald-600/20 border border-emerald-500/40 text-emerald-300">CSV</button>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-2">
        <template x-for="row in rows" :key="row.id">
            <div @click="openDetails(row.id)" class="bg-dark-light rounded-xl border border-white/[0.06] p-3 active:bg-white/[0.02] cursor-pointer">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <span class="text-xs font-medium text-white truncate" x-text="row.event_type"></span>
                    <span class="text-[10px] text-gray-500 flex-shrink-0" x-text="row.occurred_at"></span>
                </div>
                <div class="flex items-center justify-between gap-2 text-[11px]">
                    <span class="text-gray-400 truncate" x-text="(row.actor_name || '') + (row.actor_email ? ' (' + row.actor_email + ')' : '')"></span>
                    <span class="text-gray-500 px-1.5 py-0.5 rounded bg-white/[0.03] flex-shrink-0" x-text="row.category || '—'"></span>
                </div>
            </div>
        </template>
        <div x-show="rows.length === 0" class="bg-dark-light rounded-xl border border-white/[0.06] p-6 text-center text-gray-600 text-sm">Нет событий по фильтру</div>
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full text-xs">
                <thead>
                <tr class="border-b border-white/[0.06] text-gray-500 uppercase tracking-wider">
                    <th class="text-left px-3 py-2">Время</th>
                    <th class="text-left px-3 py-2">Событие</th>
                    <th class="text-left px-3 py-2">Категория</th>
                    <th class="text-left px-3 py-2">Actor</th>
                    <th class="text-left px-3 py-2">Subject</th>
                    <th class="text-left px-3 py-2">IP</th>
                    <th class="text-left px-3 py-2">Payload</th>
                    <th class="text-left px-3 py-2">Детали</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                <template x-for="row in rows" :key="row.id">
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-3 py-2 text-gray-300" x-text="row.occurred_at"></td>
                        <td class="px-3 py-2 text-white" x-text="row.event_type"></td>
                        <td class="px-3 py-2 text-gray-300" x-text="row.category"></td>
                        <td class="px-3 py-2 text-gray-300" x-text="(row.actor_name || '') + ' ' + (row.actor_email || '')"></td>
                        <td class="px-3 py-2 text-gray-300" x-text="(row.subject_name || '') + ' ' + (row.subject_email || '')"></td>
                        <td class="px-3 py-2 text-gray-300" x-text="row.ip || '—'"></td>
                        <td class="px-3 py-2 text-gray-500" x-text="payloadPreview(row.payload_json)"></td>
                        <td class="px-3 py-2"><button @click="openDetails(row.id)" class="text-coral">Открыть</button></td>
                    </tr>
                </template>
                <tr x-show="rows.length === 0"><td colspan="8" class="px-3 py-6 text-center text-gray-600">Нет событий по фильтру</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="selected" x-cloak class="bg-dark-light rounded-2xl border border-white/[0.06] p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-white font-semibold">Детали события</h3>
            <button @click="selected = null" class="text-gray-400">Закрыть</button>
        </div>
        <pre class="text-xs text-gray-300 overflow-auto max-w-full" x-text="JSON.stringify(selected, null, 2)"></pre>
    </div>
</div>

<script>
function auditPage() {
    return {
        rows: [],
        selected: null,
        filters: {
            from: '',
            to: '',
            actor_query: '',
            subject_query: '',
        },
        init() {
            this.preset('30d');
            this.load();
        },
        preset(kind) {
            const now = new Date();
            const to = now.toISOString().slice(0, 10);
            let fromDate = new Date(now);
            if (kind === 'today') {
                fromDate = now;
            } else if (kind === '7d') {
                fromDate.setDate(now.getDate() - 7);
            } else if (kind === '30d') {
                fromDate.setDate(now.getDate() - 30);
            } else if (kind === '90d') {
                fromDate.setDate(now.getDate() - 90);
            }
            this.filters.from = fromDate.toISOString().slice(0, 10);
            this.filters.to = to;
        },
        queryParams() {
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([k, v]) => {
                if (v) params.set(k, v);
            });
            return params.toString();
        },
        async load() {
            const res = await fetch(`/api/audit/events?${this.queryParams()}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.rows = data.data || [];
        },
        async openDetails(id) {
            const res = await fetch(`/api/audit/events/${id}`, { headers: { Accept: 'application/json' } });
            this.selected = await res.json();
        },
        exportCsv() {
            window.location.href = `/api/audit/events/export?${this.queryParams()}`;
        },
        payloadPreview(payload) {
            const txt = JSON.stringify(payload || {});
            return txt.length > 120 ? txt.slice(0, 120) + '…' : txt;
        }
    }
}
</script>
@endsection
