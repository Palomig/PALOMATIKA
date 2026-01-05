@extends('layouts.app')

@section('title', 'Рейтинг')
@section('header', 'Рейтинг')

@section('content')
<div x-data="leaderboardPage()">
    <!-- Tabs -->
    <div class="bg-dark-light rounded-xl p-1 mb-6 inline-flex border border-gray-800">
        <button @click="activeTab = 'weekly'"
                :class="activeTab === 'weekly' ? 'bg-coral text-white' : 'text-gray-400 hover:text-white'"
                class="px-4 py-2 rounded-lg font-medium transition">
            Неделя
        </button>
        <button @click="activeTab = 'alltime'"
                :class="activeTab === 'alltime' ? 'bg-coral text-white' : 'text-gray-400 hover:text-white'"
                class="px-4 py-2 rounded-lg font-medium transition">
            Всё время
        </button>
        <button @click="activeTab = 'leagues'"
                :class="activeTab === 'leagues' ? 'bg-coral text-white' : 'text-gray-400 hover:text-white'"
                class="px-4 py-2 rounded-lg font-medium transition">
            Лиги
        </button>
    </div>

    <!-- Weekly/All-time leaderboard -->
    <div x-show="activeTab !== 'leagues'">
        <!-- User position card -->
        <div class="bg-gradient-to-r from-coral to-coral-dark rounded-2xl p-6 text-white mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-white/70 text-sm mb-1">Твоё место</div>
                    <div class="text-4xl font-bold" x-text="'#' + (userPosition || '—')"></div>
                </div>
                <div class="text-right">
                    <div class="text-white/70 text-sm mb-1">Очков</div>
                    <div class="text-2xl font-bold" x-text="userPoints || 0"></div>
                </div>
            </div>
        </div>

        <!-- Top 3 -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <!-- 2nd place -->
            <div class="bg-dark-light rounded-xl p-4 text-center border border-gray-800 order-1">
                <div class="text-3xl mb-2">🥈</div>
                <div class="w-12 h-12 bg-gray-700 rounded-full mx-auto mb-2 flex items-center justify-center">
                    <span class="text-gray-300 font-medium" x-text="leaders[1]?.name?.charAt(0) || '?'"></span>
                </div>
                <div class="font-medium text-white truncate" x-text="leaders[1]?.name || '—'"></div>
                <div class="text-gray-400 text-sm" x-text="(leaders[1]?.points || 0) + ' очков'"></div>
            </div>
            <!-- 1st place -->
            <div class="bg-amber-500/10 border-2 border-amber-500 rounded-xl p-4 text-center transform scale-105 order-0 md:order-1">
                <div class="text-4xl mb-2">🥇</div>
                <div class="w-14 h-14 bg-amber-500/30 rounded-full mx-auto mb-2 flex items-center justify-center">
                    <span class="text-amber-400 font-medium text-lg" x-text="leaders[0]?.name?.charAt(0) || '?'"></span>
                </div>
                <div class="font-semibold text-white truncate" x-text="leaders[0]?.name || '—'"></div>
                <div class="text-gray-400 text-sm" x-text="(leaders[0]?.points || 0) + ' очков'"></div>
            </div>
            <!-- 3rd place -->
            <div class="bg-dark-light rounded-xl p-4 text-center border border-gray-800 order-2">
                <div class="text-3xl mb-2">🥉</div>
                <div class="w-12 h-12 bg-orange-500/20 rounded-full mx-auto mb-2 flex items-center justify-center">
                    <span class="text-orange-400 font-medium" x-text="leaders[2]?.name?.charAt(0) || '?'"></span>
                </div>
                <div class="font-medium text-white truncate" x-text="leaders[2]?.name || '—'"></div>
                <div class="text-gray-400 text-sm" x-text="(leaders[2]?.points || 0) + ' очков'"></div>
            </div>
        </div>

        <!-- Rest of leaderboard -->
        <div class="bg-dark-light rounded-2xl border border-gray-800 overflow-hidden">
            <template x-for="(user, index) in leaders.slice(3)" :key="user.id">
                <div class="flex items-center p-4 border-b border-gray-800 last:border-b-0"
                     :class="user.is_current_user ? 'bg-coral/10' : ''">
                    <div class="w-8 text-center font-medium text-gray-500" x-text="index + 4"></div>
                    <div class="w-10 h-10 bg-gray-700 rounded-full mx-4 flex items-center justify-center">
                        <span class="text-gray-300 font-medium" x-text="user.name?.charAt(0) || '?'"></span>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-white" x-text="user.name"></div>
                        <div class="text-sm text-gray-500" x-text="'Уровень ' + (user.level || 1)"></div>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold text-white" x-text="user.points"></div>
                        <div class="text-xs text-gray-500">очков</div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Leagues -->
    <div x-show="activeTab === 'leagues'">
        <!-- Current league -->
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800 mb-6">
            <div class="flex items-center">
                <div class="text-5xl mr-4" x-text="currentLeague?.icon || '🏆'"></div>
                <div>
                    <div class="text-xl font-semibold text-white" x-text="currentLeague?.name || 'Бронзовая лига'"></div>
                    <div class="text-gray-400">Ваша текущая лига</div>
                </div>
                <div class="ml-auto text-right">
                    <div class="text-2xl font-bold text-coral" x-text="'#' + (leaguePosition || '—')"></div>
                    <div class="text-gray-500 text-sm">место в лиге</div>
                </div>
            </div>

            <!-- Promotion/demotion info -->
            <div class="mt-4 flex gap-4">
                <div class="flex-1 bg-green-500/10 rounded-lg p-3 border border-green-500/20">
                    <div class="text-green-400 font-medium text-sm">Повышение</div>
                    <div class="text-green-300">Топ <span x-text="currentLeague?.promote_top || 10"></span></div>
                </div>
                <div class="flex-1 bg-red-500/10 rounded-lg p-3 border border-red-500/20">
                    <div class="text-red-400 font-medium text-sm">Понижение</div>
                    <div class="text-red-300">Последние <span x-text="currentLeague?.demote_bottom || 5"></span></div>
                </div>
            </div>
        </div>

        <!-- League leaderboard -->
        <div class="bg-dark-light rounded-2xl border border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-700">
                <h3 class="font-semibold text-white">Участники лиги</h3>
            </div>
            <template x-for="(user, index) in leagueParticipants" :key="user.id">
                <div class="flex items-center p-4 border-b border-gray-800 last:border-b-0"
                     :class="{
                         'bg-green-500/10': index < (currentLeague?.promote_top || 10),
                         'bg-red-500/10': index >= leagueParticipants.length - (currentLeague?.demote_bottom || 5),
                         'bg-coral/10': user.is_current_user
                     }">
                    <div class="w-8 text-center font-medium text-gray-500" x-text="index + 1"></div>
                    <div class="w-10 h-10 rounded-full mx-4 flex items-center justify-center"
                         :style="'background-color: ' + (currentLeague?.color || '#CD7F32') + '20'">
                        <span class="font-medium" :style="'color: ' + (currentLeague?.color || '#CD7F32')"
                              x-text="user.name?.charAt(0) || '?'"></span>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-white" x-text="user.name"></div>
                    </div>
                    <div class="text-right font-semibold text-white" x-text="user.weekly_xp + ' XP'"></div>
                </div>
            </template>
        </div>

        <!-- All leagues -->
        <div class="mt-6">
            <h3 class="font-semibold text-white mb-4">Все лиги</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <template x-for="league in allLeagues" :key="league.id">
                    <div class="bg-dark-light rounded-xl p-4 text-center border border-gray-800"
                         :class="league.id === currentLeague?.id ? 'ring-2 ring-coral' : ''">
                        <div class="text-3xl mb-2" x-text="league.icon"></div>
                        <div class="font-medium text-white text-sm" x-text="league.name"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function leaderboardPage() {
    return {
        activeTab: 'weekly',
        leaders: [],
        userPosition: null,
        userPoints: 0,
        currentLeague: null,
        leaguePosition: null,
        leagueParticipants: [],
        allLeagues: [],

        async init() {
            await Promise.all([
                this.loadWeeklyLeaderboard(),
                this.loadLeagues()
            ]);
        },

        async loadWeeklyLeaderboard() {
            try {
                const response = await fetch('/api/leaderboard/weekly', {
                    headers: this.$root.getAuthHeaders()
                });
                if (response.ok) {
                    const data = await response.json();
                    this.leaders = data.leaders || [];
                    this.userPosition = data.user_position;
                    this.userPoints = data.user_points;
                }
            } catch (e) {
                // Mock data
                this.leaders = [
                    { id: 1, name: 'Александр', points: 1250, level: 15 },
                    { id: 2, name: 'Мария', points: 1180, level: 14 },
                    { id: 3, name: 'Дмитрий', points: 1050, level: 12 },
                    { id: 4, name: 'Анна', points: 980, level: 11 },
                    { id: 5, name: 'Иван', points: 920, level: 10 },
                    { id: 6, name: 'Елена', points: 850, level: 9 },
                    { id: 7, name: 'Сергей', points: 780, level: 8 }
                ];
                this.userPosition = 12;
                this.userPoints = 450;
            }
        },

        async loadLeagues() {
            try {
                const response = await fetch('/api/leaderboard/leagues');
                if (response.ok) {
                    const data = await response.json();
                    this.allLeagues = data.leagues || [];
                    this.currentLeague = data.current_league;
                    this.leaguePosition = data.position;
                    this.leagueParticipants = data.participants || [];
                }
            } catch (e) {
                // Mock data
                this.allLeagues = [
                    { id: 1, name: 'Бронзовая', icon: '🥉', color: '#CD7F32', promote_top: 10, demote_bottom: 0 },
                    { id: 2, name: 'Серебряная', icon: '🥈', color: '#C0C0C0', promote_top: 10, demote_bottom: 5 },
                    { id: 3, name: 'Золотая', icon: '🥇', color: '#FFD700', promote_top: 10, demote_bottom: 5 },
                    { id: 4, name: 'Платиновая', icon: '💠', color: '#E5E4E2', promote_top: 5, demote_bottom: 5 },
                    { id: 5, name: 'Алмазная', icon: '💎', color: '#B9F2FF', promote_top: 3, demote_bottom: 5 },
                    { id: 6, name: 'Мастер', icon: '👑', color: '#9B30FF', promote_top: 0, demote_bottom: 10 }
                ];
                this.currentLeague = this.allLeagues[0];
                this.leaguePosition = 5;
                this.leagueParticipants = [
                    { id: 1, name: 'Игрок 1', weekly_xp: 500 },
                    { id: 2, name: 'Игрок 2', weekly_xp: 480 },
                    { id: 3, name: 'Игрок 3', weekly_xp: 450, is_current_user: true },
                    { id: 4, name: 'Игрок 4', weekly_xp: 420 },
                    { id: 5, name: 'Игрок 5', weekly_xp: 380 }
                ];
            }
        }
    }
}
</script>
@endpush
@endsection
