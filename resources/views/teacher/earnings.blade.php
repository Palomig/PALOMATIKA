@extends('layouts.teacher')

@section('title', 'Заработок')
@section('header', 'Заработок')

@section('content')
<div x-data="earningsPage()">
    <!-- Balance card -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 text-white mb-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-indigo-200 text-sm mb-1">Доступно к выводу</div>
                <div class="text-4xl font-bold" x-text="formatMoney(balance.available)"></div>
            </div>
            <button @click="showPayoutModal = true"
                    :disabled="balance.available < 1000"
                    class="bg-white text-indigo-600 px-6 py-3 rounded-xl font-semibold hover:bg-indigo-50 transition disabled:opacity-50 disabled:cursor-not-allowed">
                Вывести
            </button>
        </div>
        <div class="mt-4 pt-4 border-t border-white/20 flex items-center justify-between text-sm">
            <div>
                <span class="text-indigo-200">Заморожено:</span>
                <span class="font-medium" x-text="formatMoney(balance.pending)"></span>
            </div>
            <div>
                <span class="text-indigo-200">Всего заработано:</span>
                <span class="font-medium" x-text="formatMoney(balance.total)"></span>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="text-2xl font-bold text-green-600" x-text="formatMoney(stats.this_month)"></div>
            <div class="text-gray-500 text-sm">за этот месяц</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="text-2xl font-bold text-indigo-600" x-text="stats.active_referrals"></div>
            <div class="text-gray-500 text-sm">активных рефералов</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="text-2xl font-bold text-purple-600" x-text="stats.commission_rate + '%'"></div>
            <div class="text-gray-500 text-sm">ваша комиссия</div>
        </div>
    </div>

    <!-- How it works -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="font-semibold text-gray-900 mb-4">Как это работает</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="text-2xl">🔗</span>
                </div>
                <div class="font-medium text-gray-900 mb-1">Поделитесь ссылкой</div>
                <div class="text-sm text-gray-500">Дайте ученикам вашу реферальную ссылку</div>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="text-2xl">💳</span>
                </div>
                <div class="font-medium text-gray-900 mb-1">Ученик оформляет подписку</div>
                <div class="text-sm text-gray-500">Вы получаете % от каждого платежа</div>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="text-2xl">💰</span>
                </div>
                <div class="font-medium text-gray-900 mb-1">Получайте выплаты</div>
                <div class="text-sm text-gray-500">Выводите заработок на карту</div>
            </div>
        </div>
    </div>

    <!-- Transactions history -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">История операций</h3>
        </div>
        <div class="divide-y divide-gray-100">
            <template x-for="tx in transactions" :key="tx.id">
                <div class="flex items-center p-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                         :class="tx.type === 'earning' ? 'bg-green-100' : 'bg-blue-100'">
                        <span :class="tx.type === 'earning' ? 'text-green-600' : 'text-blue-600'"
                              x-text="tx.type === 'earning' ? '+' : '→'"></span>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="font-medium text-gray-900" x-text="tx.description"></div>
                        <div class="text-sm text-gray-500" x-text="tx.date"></div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium"
                             :class="tx.type === 'earning' ? 'text-green-600' : 'text-blue-600'"
                             x-text="(tx.type === 'earning' ? '+' : '-') + formatMoney(tx.amount)"></div>
                        <div class="text-xs text-gray-400" x-text="tx.status_text"></div>
                    </div>
                </div>
            </template>
            <div x-show="transactions.length === 0" class="p-8 text-center text-gray-500">
                Пока нет операций
            </div>
        </div>
    </div>

    <!-- Payout modal -->
    <div x-show="showPayoutModal" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showPayoutModal = false">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Вывод средств</h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Сумма вывода</label>
                <input type="number" x-model="payoutAmount"
                       :max="balance.available"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <div class="text-xs text-gray-500 mt-1">Минимум: 1 000 ₽</div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Номер карты</label>
                <input type="text" x-model="cardNumber" placeholder="0000 0000 0000 0000"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex space-x-4">
                <button @click="showPayoutModal = false"
                        class="flex-1 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Отмена
                </button>
                <button @click="requestPayout"
                        class="flex-1 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition">
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
