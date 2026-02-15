@extends('layouts.teacher')

@section('title', 'Группы учеников')
@section('header', 'Группы учеников')

@section('content')
<div x-data="studentGroupsPage()" class="space-y-6">
    <section class="bg-dark-light rounded-xl border border-gray-800 p-4 sm:p-5">
        <div class="flex flex-wrap items-center gap-3">
            <input x-model="newGroupName" type="text" placeholder="Название группы"
                   class="min-w-[220px] flex-1 px-4 py-2 bg-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-coral focus:border-transparent">
            <input x-model="newGroupDescription" type="text" placeholder="Краткое описание (опционально)"
                   class="min-w-[220px] flex-1 px-4 py-2 bg-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-coral focus:border-transparent">
            <button @click="createGroup()" :disabled="!newGroupName.trim()"
                    :class="!newGroupName.trim() ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-4 py-2 rounded-lg bg-coral text-white font-medium hover:bg-coral-dark transition">
                Создать группу
            </button>
        </div>
        <p x-show="errorMessage" x-text="errorMessage" class="mt-3 text-sm text-red-400"></p>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div x-show="loading" class="text-sm text-gray-400">Загрузка групп...</div>

            <template x-for="group in groups" :key="group.id">
                <article class="bg-dark-light rounded-xl border border-gray-800 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-white font-semibold text-lg" x-text="group.name"></h2>
                            <p class="text-sm text-gray-500 mt-1" x-text="group.description || 'Без описания'"></p>
                            <p class="text-xs text-gray-500 mt-2">
                                Учеников:
                                <span class="text-coral font-medium" x-text="group.students_count"></span>
                            </p>
                        </div>
                        <button @click="deleteGroup(group.id)"
                                class="px-3 py-1.5 rounded-lg border border-red-500/30 text-red-400 hover:bg-red-500/10 transition text-sm">
                            Удалить
                        </button>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Состав группы</p>
                        <div x-show="group.students.length === 0" class="text-sm text-gray-500">Пока пусто</div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="student in group.students" :key="student.id">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-dark border border-gray-700 text-sm text-gray-200">
                                    <span x-text="student.name"></span>
                                    <button @click="removeStudent(group.id, student.id)" class="text-gray-500 hover:text-red-400">✕</button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 items-center">
                        <select x-model.number="studentToAdd[group.id]"
                                class="min-w-[220px] px-3 py-2 bg-dark border border-gray-700 rounded-lg text-white">
                            <option value="">Добавить ученика...</option>
                            <template x-for="student in availableStudentsForGroup(group)" :key="student.id">
                                <option :value="student.id" x-text="student.name + (student.email ? ' (' + student.email + ')' : '')"></option>
                            </template>
                        </select>
                        <button @click="addStudent(group.id)"
                                :disabled="!studentToAdd[group.id]"
                                :class="!studentToAdd[group.id] ? 'opacity-50 cursor-not-allowed' : ''"
                                class="px-3 py-2 rounded-lg bg-coral text-white hover:bg-coral-dark transition text-sm">
                            Добавить
                        </button>
                    </div>
                </article>
            </template>
        </div>

        <aside class="bg-dark-light rounded-xl border border-gray-800 p-4 h-fit">
            <h3 class="text-white font-semibold mb-3">Ученики преподавателя</h3>
            <div x-show="students.length === 0" class="text-sm text-gray-500">Связанных учеников пока нет.</div>
            <div class="space-y-2">
                <template x-for="student in students" :key="student.id">
                    <div class="px-3 py-2 rounded-lg bg-dark border border-gray-700">
                        <div class="text-sm text-gray-200" x-text="student.name"></div>
                        <div class="text-xs text-gray-500" x-text="student.email || 'Без e-mail'"></div>
                    </div>
                </template>
            </div>
        </aside>
    </section>
</div>

@push('scripts')
<script>
function studentGroupsPage() {
    return {
        loading: false,
        groups: [],
        students: [],
        studentToAdd: {},
        newGroupName: '',
        newGroupDescription: '',
        errorMessage: '',

        async init() {
            await Promise.all([this.fetchGroups(), this.fetchStudents()]);
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        async fetchGroups() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('teacher.groups.data') }}', { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                this.groups = Array.isArray(payload.groups) ? payload.groups : [];
            } catch (error) {
                console.error(error);
                this.errorMessage = 'Не удалось загрузить группы.';
            } finally {
                this.loading = false;
            }
        },

        async fetchStudents() {
            try {
                const response = await fetch('{{ route('teacher.groups.students') }}', { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                this.students = Array.isArray(payload.students) ? payload.students : [];
            } catch (error) {
                console.error(error);
                this.errorMessage = 'Не удалось загрузить список учеников.';
            }
        },

        availableStudentsForGroup(group) {
            const selectedIds = new Set((group.students || []).map(student => student.id));
            return this.students.filter(student => !selectedIds.has(student.id));
        },

        async createGroup() {
            if (!this.newGroupName.trim()) return;
            this.errorMessage = '';

            try {
                const response = await fetch('{{ route('teacher.groups.store') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify({
                        name: this.newGroupName.trim(),
                        description: this.newGroupDescription.trim() || null,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Failed to create group');
                }

                this.newGroupName = '';
                this.newGroupDescription = '';
                await this.fetchGroups();
            } catch (error) {
                console.error(error);
                this.errorMessage = 'Не удалось создать группу.';
            }
        },

        async addStudent(groupId) {
            const studentId = this.studentToAdd[groupId];
            if (!studentId) return;

            try {
                const response = await fetch(`/teacher/groups/${groupId}/students`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify({ student_id: studentId }),
                });

                if (!response.ok) {
                    throw new Error('Failed to add student');
                }

                this.studentToAdd[groupId] = '';
                await this.fetchGroups();
            } catch (error) {
                console.error(error);
                this.errorMessage = 'Не удалось добавить ученика в группу.';
            }
        },

        async removeStudent(groupId, studentId) {
            try {
                const response = await fetch(`/teacher/groups/${groupId}/students/${studentId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to remove student');
                }

                await this.fetchGroups();
            } catch (error) {
                console.error(error);
                this.errorMessage = 'Не удалось удалить ученика из группы.';
            }
        },

        async deleteGroup(groupId) {
            if (!confirm('Удалить группу?')) return;

            try {
                const response = await fetch(`/teacher/groups/${groupId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to delete group');
                }

                await this.fetchGroups();
            } catch (error) {
                console.error(error);
                this.errorMessage = 'Не удалось удалить группу.';
            }
        },
    };
}
</script>
@endpush
@endsection
