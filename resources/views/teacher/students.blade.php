@extends('layouts.teacher')

@section('title', 'Ученики')
@section('header', 'Ученики')

@section('content')
<div x-data="studentsPage()">
    {{-- Actions bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <svg class="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="search" placeholder="Поиск учеников..."
                       class="w-full pl-10 pr-4 py-2.5 bg-dark-light border border-white/[0.06] rounded-xl text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-coral/40 focus:border-coral/30 transition">
            </div>
            <select x-model="statusFilter" class="bg-dark-light border border-white/[0.06] rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-coral/40 focus:border-coral/30 transition">
                <option value="">Все статусы</option>
                <option value="active">Активные</option>
                <option value="inactive">Неактивные</option>
            </select>
        </div>
        <button @click="showInviteModal = true"
                class="inline-flex items-center gap-2 bg-coral text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-coral-dark transition shadow-lg shadow-coral/10 hover:shadow-coral/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Пригласить ученика
        </button>
    </div>

    {{-- Students table --}}
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/[0.06]">
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Ученик</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Прогресс</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Точность</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Стрик</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Подписка</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Последний визит</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    <template x-for="student in filteredStudents" :key="student.id">
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-coral/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-semibold text-coral" x-text="student.name?.charAt(0)"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-white truncate" x-text="student.name"></div>
                                        <div class="text-xs text-gray-500 truncate" x-text="student.email"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-20 bg-white/[0.06] rounded-full h-1.5">
                                        <div class="bg-coral rounded-full h-1.5"
                                             :style="'width: ' + student.progress + '%'"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 tabular-nums" x-text="student.progress + '%'"></span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-sm font-medium tabular-nums"
                                      :class="student.accuracy >= 70 ? 'text-emerald-400' : student.accuracy >= 50 ? 'text-amber-400' : 'text-red-400'"
                                      x-text="student.accuracy + '%'"></span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z"/>
                                    </svg>
                                    <span class="text-sm text-gray-300 tabular-nums" x-text="student.streak"></span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-lg"
                                      :class="student.has_subscription ? 'bg-emerald-500/10 text-emerald-400' : 'bg-white/[0.04] text-gray-500'"
                                      x-text="student.has_subscription ? 'Активна' : 'Нет'"></span>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-500" x-text="student.last_seen"></td>
                            <td class="px-5 py-3.5">
                                <button @click="selectStudent(student)"
                                        class="text-xs font-medium text-coral hover:text-coral-light transition">
                                    Подробнее
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Student detail modal --}}
    <div x-show="selectedStudent" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="selectedStudent = null">
        <div class="bg-dark-light rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-white/[0.08] shadow-2xl"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="p-6 border-b border-white/[0.06]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-coral/10 rounded-xl flex items-center justify-center">
                            <span class="text-coral font-bold text-lg" x-text="selectedStudent?.name?.charAt(0)"></span>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-white" x-text="selectedStudent?.name"></h2>
                            <p class="text-sm text-gray-500" x-text="selectedStudent?.email"></p>
                        </div>
                    </div>
                    <button @click="selectedStudent = null" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:text-white hover:bg-white/[0.06] transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-4 gap-3 mb-6">
                    <div class="bg-dark rounded-xl p-3 text-center">
                        <div class="text-xl font-bold text-coral" x-text="selectedStudent?.progress + '%'"></div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Прогресс</div>
                    </div>
                    <div class="bg-dark rounded-xl p-3 text-center">
                        <div class="text-xl font-bold text-emerald-400" x-text="selectedStudent?.accuracy + '%'"></div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Точность</div>
                    </div>
                    <div class="bg-dark rounded-xl p-3 text-center">
                        <div class="text-xl font-bold text-amber-400" x-text="selectedStudent?.streak"></div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Стрик</div>
                    </div>
                    <div class="bg-dark rounded-xl p-3 text-center">
                        <div class="text-xl font-bold text-blue-400" x-text="selectedStudent?.tasks_solved || 0"></div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Задач</div>
                    </div>
                </div>

                <div class="mb-6" x-show="(selectedStudent?.weak_topics || []).length > 0">
                    <h3 class="text-sm font-medium text-gray-400 mb-2.5">Слабые темы</h3>
                    <div class="space-y-2">
                        <template x-for="topic in (selectedStudent?.weak_topics || [])" :key="topic.id">
                            <div class="flex items-center justify-between p-3 bg-red-500/[0.06] rounded-xl border border-red-500/10">
                                <span class="text-sm text-white" x-text="topic.name"></span>
                                <span class="text-sm font-medium text-red-400 tabular-nums" x-text="topic.accuracy + '%'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="assignHomework(selectedStudent)"
                            class="flex-1 bg-coral text-white py-2.5 rounded-xl text-sm font-medium hover:bg-coral-dark transition shadow-lg shadow-coral/10">
                        Назначить ДЗ
                    </button>
                    <button @click="sendMessage(selectedStudent)"
                            class="flex-1 border border-white/[0.08] text-gray-300 py-2.5 rounded-xl text-sm font-medium hover:bg-white/[0.04] hover:text-white transition">
                        Написать сообщение
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Invite modal --}}
    <div x-show="showInviteModal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         @click.self="showInviteModal = false">
        <div class="bg-dark-light rounded-2xl max-w-md w-full p-6 border border-white/[0.08] shadow-2xl">
            <h2 class="text-lg font-semibold text-white mb-1">Пригласить ученика</h2>
            <p class="text-sm text-gray-500 mb-5">Поделитесь этой ссылкой с учеником:</p>
            <div class="flex items-center gap-0 mb-4">
                <input type="text" readonly :value="$root.referralLink"
                       class="flex-1 px-4 py-2.5 bg-dark border border-white/[0.08] rounded-l-xl text-sm text-white">
                <button @click="$root.copyReferralLink()"
                        class="px-5 py-2.5 bg-coral text-white text-sm font-medium rounded-r-xl hover:bg-coral-dark transition">
                    Копировать
                </button>
            </div>
            <p class="text-xs text-gray-500 mb-5">
                Когда ученик зарегистрируется по этой ссылке, он автоматически будет добавлен к вам.
            </p>
            <button @click="showInviteModal = false"
                    class="w-full py-2.5 border border-white/[0.08] rounded-xl text-sm text-gray-400 hover:text-white hover:bg-white/[0.04] transition">
                Закрыть
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function studentsPage() {
    return {
        students: [],
        search: '',
        statusFilter: '',
        selectedStudent: null,
        showInviteModal: false,

        async init() {
            await this.loadStudents();
        },

        async loadStudents() {
            this.students = [
                { id: 1, name: 'Александр Иванов', email: 'alex@example.com', progress: 75, accuracy: 82, streak: 12, has_subscription: true, last_seen: '5 мин назад', tasks_solved: 150, weak_topics: [{ id: 1, name: 'Системы уравнений', accuracy: 45 }] },
                { id: 2, name: 'Мария Петрова', email: 'maria@example.com', progress: 60, accuracy: 68, streak: 5, has_subscription: true, last_seen: '1 час назад', tasks_solved: 89, weak_topics: [{ id: 2, name: 'Теорема Пифагора', accuracy: 52 }] },
                { id: 3, name: 'Дмитрий Сидоров', email: 'dmitry@example.com', progress: 45, accuracy: 55, streak: 0, has_subscription: false, last_seen: '3 дня назад', tasks_solved: 45, weak_topics: [] },
                { id: 4, name: 'Елена Козлова', email: 'elena@example.com', progress: 90, accuracy: 91, streak: 25, has_subscription: true, last_seen: 'Сейчас', tasks_solved: 230, weak_topics: [] }
            ];
        },

        get filteredStudents() {
            return this.students.filter(s => {
                const matchesSearch = !this.search ||
                    s.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    s.email.toLowerCase().includes(this.search.toLowerCase());
                const matchesStatus = !this.statusFilter ||
                    (this.statusFilter === 'active' && s.streak > 0) ||
                    (this.statusFilter === 'inactive' && s.streak === 0);
                return matchesSearch && matchesStatus;
            });
        },

        selectStudent(student) {
            this.selectedStudent = student;
        },

        assignHomework(student) {
            window.location.href = '/teacher/homework/create?student_id=' + student.id;
        },

        sendMessage(student) {
            alert('Функция сообщений будет добавлена позже');
        }
    }
}
</script>
@endpush
@endsection
