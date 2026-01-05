@extends('layouts.app')

@section('title', 'Дуэли')
@section('header', 'Дуэли')

@section('content')
<div x-data="duelsPage()">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-green-400" x-text="stats.wins || 0"></div>
            <div class="text-gray-400 text-sm">Побед</div>
        </div>
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-red-400" x-text="stats.losses || 0"></div>
            <div class="text-gray-400 text-sm">Поражений</div>
        </div>
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-coral" x-text="stats.winrate + '%'"></div>
            <div class="text-gray-400 text-sm">Винрейт</div>
        </div>
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-amber-400" x-text="stats.current_streak || 0"></div>
            <div class="text-gray-400 text-sm">Побед подряд</div>
        </div>
    </div>

    <!-- Start duel -->
    <div class="bg-gradient-to-r from-coral to-coral-dark rounded-2xl p-6 text-white mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Начать дуэль</h2>
                <p class="text-white/70">Соревнуйся с другими учениками в решении задач!</p>
            </div>
            <button @click="startMatchmaking"
                    :disabled="isSearching"
                    class="bg-white text-coral px-6 py-3 rounded-xl font-semibold hover:bg-white/90 transition disabled:opacity-50">
                <span x-show="!isSearching">Найти соперника</span>
                <span x-show="isSearching" class="flex items-center">
                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Поиск...
                </span>
            </button>
        </div>
    </div>

    <!-- Active duels -->
    <div x-show="activeDuels.length > 0" class="mb-6">
        <h3 class="font-semibold text-white mb-4">Активные дуэли</h3>
        <div class="space-y-4">
            <template x-for="duel in activeDuels" :key="duel.id">
                <div class="bg-dark-light rounded-xl p-4 border border-gray-800 flex items-center">
                    <div class="flex-1 flex items-center">
                        <div class="w-12 h-12 bg-coral/20 rounded-full flex items-center justify-center">
                            <span class="text-coral font-medium" x-text="duel.opponent?.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="ml-4">
                            <div class="font-medium text-white" x-text="'vs ' + (duel.opponent?.name || 'Соперник')"></div>
                            <div class="text-sm text-gray-500" x-text="duel.topic?.name || 'Все темы'"></div>
                        </div>
                    </div>
                    <div class="text-center mx-4">
                        <div class="text-2xl font-bold text-white" x-text="duel.your_score + ' : ' + duel.opponent_score"></div>
                        <div class="text-xs text-gray-500">Вопрос <span x-text="duel.current_question"></span>/<span x-text="duel.total_questions"></span></div>
                    </div>
                    <a :href="'/duels/' + duel.id"
                       class="bg-coral text-white px-4 py-2 rounded-lg font-medium hover:bg-coral-dark transition">
                        Продолжить
                    </a>
                </div>
            </template>
        </div>
    </div>

    <!-- Recent duels -->
    <div class="bg-dark-light rounded-2xl border border-gray-800">
        <div class="p-4 border-b border-gray-700">
            <h3 class="font-semibold text-white">История дуэлей</h3>
        </div>
        <div x-show="recentDuels.length === 0" class="p-8 text-center text-gray-500">
            У вас пока нет завершённых дуэлей
        </div>
        <template x-for="duel in recentDuels" :key="duel.id">
            <div class="flex items-center p-4 border-b border-gray-800 last:border-b-0">
                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                     :class="duel.is_winner ? 'bg-green-500/20' : 'bg-red-500/20'">
                    <span :class="duel.is_winner ? 'text-green-400' : 'text-red-400'"
                          x-text="duel.is_winner ? 'W' : 'L'"></span>
                </div>
                <div class="ml-4 flex-1">
                    <div class="font-medium text-white" x-text="'vs ' + (duel.opponent?.name || 'Соперник')"></div>
                    <div class="text-sm text-gray-500" x-text="duel.finished_at"></div>
                </div>
                <div class="text-right">
                    <div class="font-semibold" :class="duel.is_winner ? 'text-green-400' : 'text-red-400'"
                         x-text="duel.your_score + ' : ' + duel.opponent_score"></div>
                    <div class="text-sm" :class="duel.is_winner ? 'text-green-500' : 'text-red-500'"
                         x-text="(duel.is_winner ? '+' : '') + duel.xp_change + ' XP'"></div>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function duelsPage() {
    return {
        stats: { wins: 0, losses: 0, winrate: 0, current_streak: 0 },
        activeDuels: [],
        recentDuels: [],
        isSearching: false,

        async init() {
            await this.loadDuels();
        },

        async loadDuels() {
            try {
                const response = await fetch('/api/duels', {
                    headers: this.$root.getAuthHeaders()
                });
                if (response.ok) {
                    const data = await response.json();
                    this.stats = data.stats || this.stats;
                    this.activeDuels = data.active || [];
                    this.recentDuels = data.history || [];
                }
            } catch (e) {
                // Mock data
                this.stats = { wins: 12, losses: 5, winrate: 71, current_streak: 3 };
                this.recentDuels = [
                    { id: 1, opponent: { name: 'Мария' }, your_score: 5, opponent_score: 3, is_winner: true, xp_change: 25, finished_at: 'Вчера' },
                    { id: 2, opponent: { name: 'Дмитрий' }, your_score: 4, opponent_score: 5, is_winner: false, xp_change: -10, finished_at: '2 дня назад' }
                ];
            }
        },

        async startMatchmaking() {
            this.isSearching = true;
            try {
                const response = await fetch('/api/duels/matchmaking', {
                    method: 'POST',
                    headers: this.$root.getAuthHeaders()
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.duel_id) {
                        window.location.href = '/duels/' + data.duel_id;
                    }
                }
            } catch (e) {
                console.error('Matchmaking error', e);
            }
            // Demo: stop after 3 seconds
            setTimeout(() => { this.isSearching = false; }, 3000);
        }
    }
}
</script>
@endpush
@endsection
