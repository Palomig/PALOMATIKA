@extends('layouts.teacher')

@section('title', 'Ученики')
@section('header', 'Ученики')

@section('content')
<div x-data="studentsPage()">
    <!-- Actions -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <div class="relative">
                <input type="text" x-model="search" placeholder="Поиск учеников..."
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select x-model="statusFilter" class="border border-gray-300 rounded-lg px-4 py-2">
                <option value="">Все статусы</option>
                <option value="active">Активные</option>
                <option value="inactive">Неактивные</option>
            </select>
        </div>
        <button @click="showInviteModal = true"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition">
            + Пригласить ученика
        </button>
    </div>

    <!-- Students table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ученик</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Прогресс</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Точность</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Стрик</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Подписка</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Последний визит</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="student in filteredStudents" :key="student.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-600 font-medium" x-text="student.name?.charAt(0)"></span>
                                </div>
                                <div class="ml-3">
                                    <div class="font-medium text-gray-900" x-text="student.name"></div>
                                    <div class="text-sm text-gray-500" x-text="student.email"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-indigo-600 rounded-full h-2"
                                         :style="'width: ' + student.progress + '%'"></div>
                                </div>
                                <span class="text-sm text-gray-600" x-text="student.progress + '%'"></span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm"
                                  :class="student.accuracy >= 70 ? 'text-green-600' : student.accuracy >= 50 ? 'text-yellow-600' : 'text-red-600'"
                                  x-text="student.accuracy + '%'"></span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="text-sm" x-text="student.streak + ' дней'"></span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-2 py-1 text-xs rounded-full"
                                  :class="student.has_subscription ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                                  x-text="student.has_subscription ? 'Активна' : 'Нет'"></span>
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-500" x-text="student.last_seen"></td>
                        <td class="px-4 py-4">
                            <button @click="selectStudent(student)"
                                    class="text-indigo-600 hover:text-indigo-800">
                                Подробнее
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Student detail modal -->
    <div x-show="selectedStudent" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="selectedStudent = null">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-14 h-14 bg-indigo-100 rounded-full flex items-center justify-center">
                            <span class="text-indigo-600 font-medium text-xl"
                                  x-text="selectedStudent?.name?.charAt(0)"></span>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-semibold text-gray-900" x-text="selectedStudent?.name"></h2>
                            <p class="text-gray-500" x-text="selectedStudent?.email"></p>
                        </div>
                    </div>
                    <button @click="selectedStudent = null" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <!-- Stats grid -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-indigo-600" x-text="selectedStudent?.progress + '%'"></div>
                        <div class="text-xs text-gray-500">Прогресс</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600" x-text="selectedStudent?.accuracy + '%'"></div>
                        <div class="text-xs text-gray-500">Точность</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-500" x-text="selectedStudent?.streak"></div>
                        <div class="text-xs text-gray-500">Стрик</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600" x-text="selectedStudent?.tasks_solved || 0"></div>
                        <div class="text-xs text-gray-500">Задач</div>
                    </div>
                </div>

                <!-- Weak topics -->
                <div class="mb-6">
                    <h3 class="font-medium text-gray-900 mb-3">Слабые темы</h3>
                    <div class="space-y-2">
                        <template x-for="topic in (selectedStudent?.weak_topics || [])" :key="topic.id">
                            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                <span class="text-gray-900" x-text="topic.name"></span>
                                <span class="text-red-600 font-medium" x-text="topic.accuracy + '%'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex space-x-4">
                    <button @click="assignHomework(selectedStudent)"
                            class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-medium hover:bg-indigo-700 transition">
                        Назначить ДЗ
                    </button>
                    <button @click="sendMessage(selectedStudent)"
                            class="flex-1 border border-indigo-600 text-indigo-600 py-2 rounded-lg font-medium hover:bg-indigo-50 transition">
                        Написать сообщение
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invite modal -->
    <div x-show="showInviteModal" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showInviteModal = false">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Пригласить ученика</h2>
            <p class="text-gray-500 mb-4">Поделитесь этой ссылкой с учеником:</p>
            <div class="flex items-center mb-4">
                <input type="text" readonly :value="$root.referralLink"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg bg-gray-50">
                <button @click="$root.copyReferralLink()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-r-lg hover:bg-indigo-700">
                    Копировать
                </button>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                Когда ученик зарегистрируется по этой ссылке, он автоматически будет добавлен к вам.
            </p>
            <button @click="showInviteModal = false"
                    class="w-full py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
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
            // Mock data
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
