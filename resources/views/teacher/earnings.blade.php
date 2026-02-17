@extends('layouts.teacher')

@section('title', 'Заработок')
@section('header', 'Заработок')

@section('content')
<div x-data="earningsPage()" class="space-y-5">
    <section class="relative overflow-hidden rounded-2xl tsh-card p-5 sm:p-6">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-rose-50 to-transparent pointer-events-none"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="tsh-page-kicker">Finance</p>
                <h2 class="tsh-page-title">Доход и выплаты</h2>
                <p class="tsh-page-subtitle">Контролируйте баланс, выплаты и динамику реферального дохода в одном месте.</p>
            </div>
            <a href="/teacher" class="tsh-action-secondary text-xs">
                Назад к обзору
            </a>
        </div>
    </section>

    {{-- Balance card --}}
    <div class="relative overflow-hidden tsh-card p-6">
        <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full blur-2xl" style="background: #edf2ff"></div>
        <div class="absolute -left-8 -bottom-8 w-28 h-28 rounded-full blur-xl" style="background: #fff1f2"></div>
        <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div class="text-xs font-medium uppercase tracking-wider mb-1" style="color: var(--tsh-subtle)">Доступно к выводу</div>
                <div class="text-4xl font-bold tracking-tight" style="color: var(--tsh-text)" x-text="formatMoney(balance.available)"></div>
            </div>
            <button @click="showPayoutModal = true"
                    :disabled="balance.available < 1000"
                    class="tsh-action-primary disabled:opacity-50 disabled:cursor-not-allowed">
                Вывести средства
            </button>
        </div>
        <div class="relative mt-5 pt-4 flex flex-wrap gap-x-8 gap-y-2 text-sm" style="border-top: 1px solid var(--tsh-border-soft)">
            <div>
                <span style="color: var(--tsh-muted)">Заморожено:</span>
                <span class="font-medium ml-1" style="color: var(--tsh-text)" x-text="formatMoney(balance.pending)"></span>
            </div>
            <div>
                <span style="color: var(--tsh-muted)">Всего заработано:</span>
                <span class="font-medium ml-1" style="color: var(--tsh-text)" x-text="formatMoney(balance.total)"></span>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="tsh-card tsh-stat-green rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <span class="text-xs font-medium uppercase tracking-wider" style="color: var(--tsh-muted)">Этот месяц</span>
            </div>
            <div class="text-2xl font-bold" style="color: var(--tsh-text)" x-text="formatMoney(stats.this_month)"></div>
        </div>
        <div class="tsh-card tsh-stat-blue rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium uppercase tracking-wider" style="color: var(--tsh-muted)">Рефералы</span>
            </div>
            <div class="text-2xl font-bold" style="color: var(--tsh-text)" x-text="stats.active_referrals"></div>
        </div>
        <div class="tsh-card tsh-stat-coral rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium uppercase tracking-wider" style="color: var(--tsh-muted)">Комиссия</span>
            </div>
            <div class="text-2xl font-bold" style="color: var(--tsh-text)" x-text="stats.commission_rate + '%'"></div>
        </div>
    </div>

    {{-- How it works --}}
    <div class="tsh-card rounded-2xl p-6">
        <h3 class="font-semibold text-[15px] mb-5" style="color: var(--tsh-text)">Как это работает</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5" style="background: var(--tsh-surface-soft)">
                    <svg class="w-4.5 h-4.5" style="color: var(--tsh-blue)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-medium mb-0.5" style="color: var(--tsh-text)">Поделитесь ссылкой</div>
                    <div class="text-xs leading-relaxed" style="color: var(--tsh-muted)">Дайте ученикам вашу реферальную ссылку</div>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5" style="background: var(--tsh-surface-soft)">
                    <svg class="w-4.5 h-4.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-medium mb-0.5" style="color: var(--tsh-text)">Ученик оформляет подписку</div>
                    <div class="text-xs leading-relaxed" style="color: var(--tsh-muted)">Вы получаете % от каждого платежа</div>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5" style="background: var(--tsh-surface-soft)">
                    <svg class="w-4.5 h-4.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-medium mb-0.5" style="color: var(--tsh-text)">Получайте выплаты</div>
                    <div class="text-xs leading-relaxed" style="color: var(--tsh-muted)">Выводите заработок на карту</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions history --}}
    <div class="tsh-card rounded-2xl overflow-hidden">
        <div class="px-5 py-4" style="border-bottom: 1px solid var(--tsh-border-soft)">
            <h3 class="font-semibold text-[15px]" style="color: var(--tsh-text)">История операций</h3>
        </div>
        <div>
            <template x-for="tx in transactions" :key="tx.id">
                <div class="flex items-center gap-3 px-5 py-3.5 transition-colors"
                     style="border-bottom: 1px solid var(--tsh-border-soft)"
                     onmouseover="this.style.background='var(--tsh-surface-soft)'"
                     onmouseout="this.style.background='transparent'">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                         :class="tx.type === 'earning' ? 'bg-emerald-100 text-emerald-500' : 'bg-blue-100 text-blue-500'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="tx.type === 'earning' ? 'M12 4v16m0-16l-4 4m4-4l4 4' : 'M17 8l4 4m0 0l-4 4m4-4H3'"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" style="color: var(--tsh-text)" x-text="tx.description"></div>
                        <div class="text-[11px]" style="color: var(--tsh-subtle)" x-text="tx.date"></div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-semibold tabular-nums"
                             :class="tx.type === 'earning' ? 'text-emerald-500' : 'text-blue-500'"
                             x-text="(tx.type === 'earning' ? '+' : '-') + formatMoney(tx.amount)"></div>
                        <div class="text-[11px]" style="color: var(--tsh-subtle)" x-text="tx.status_text"></div>
                    </div>
                </div>
            </template>
            <div x-show="transactions.length === 0" class="px-5 py-10 text-center text-sm" style="color: var(--tsh-subtle)">
                Пока нет операций
            </div>
        </div>
    </div>

    {{-- Payout modal --}}
    <div x-show="showPayoutModal" x-cloak
         class="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         @click.self="showPayoutModal = false">
        <div class="tsh-card rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--tsh-text)">Вывод средств</h2>
            <div class="space-y-4 mb-5">
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--tsh-muted)">Сумма вывода</label>
                    <input type="number" x-model="payoutAmount"
                           :max="balance.available"
                           class="w-full px-4 py-2.5 rounded-xl text-sm focus:ring-2 transition"
                           style="background: white; border: 1px solid var(--tsh-border); color: var(--tsh-text); outline: none"
                           onfocus="this.style.borderColor='var(--tsh-blue)'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                           onblur="this.style.borderColor='var(--tsh-border)'; this.style.boxShadow='none'">
                    <div class="text-[11px] mt-1" style="color: var(--tsh-subtle)">Минимум: 1 000 ₽</div>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--tsh-muted)">Номер карты</label>
                    <input type="text" x-model="cardNumber" placeholder="0000 0000 0000 0000"
                           class="w-full px-4 py-2.5 rounded-xl text-sm focus:ring-2 transition"
                           style="background: white; border: 1px solid var(--tsh-border); color: var(--tsh-text); outline: none"
                           onfocus="this.style.borderColor='var(--tsh-blue)'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                           onblur="this.style.borderColor='var(--tsh-border)'; this.style.boxShadow='none'">
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="showPayoutModal = false"
                        class="tsh-action-secondary flex-1 justify-center text-sm">
                    Отмена
                </button>
                <button @click="requestPayout"
                        class="tsh-action-primary flex-1 justify-center text-sm font-medium">
                    Вывести
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function earningsPage() {
    return {
        balance: {
            available: 12500,
            pending: 2000,
            total: 45000
        },
        stats: {
            this_month: 8500,
            active_referrals: 18,
            commission_rate: 30
        },
        transactions: [
            { id: 1, type: 'earning', description: 'Подписка: Александр И.', amount: 240, date: '15 января', status_text: 'Начислено' },
            { id: 2, type: 'earning', description: 'Подписка: Мария П.', amount: 240, date: '14 января', status_text: 'Начислено' },
            { id: 3, type: 'payout', description: 'Вывод на карту *4532', amount: 10000, date: '10 января', status_text: 'Выполнено' }
        ],
        showPayoutModal: false,
        payoutAmount: 0,
        cardNumber: '',

        formatMoney(amount) {
            return amount.toLocaleString('ru-RU') + ' ₽';
        },

        requestPayout() {
            alert('Заявка на вывод отправлена!');
            this.showPayoutModal = false;
        }
    }
}
</script>
@endpush
@endsection
