@extends('layouts.teacher')

@section('title', 'Группы учеников')
@section('header', 'Группы учеников')

@section('content')
<div x-data="studentGroupsPage()" class="space-y-5">
    <section class="relative overflow-hidden rounded-2xl tsh-card p-5 sm:p-6">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-blue-50 to-transparent pointer-events-none"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="tsh-page-kicker">Group orchestration</p>
                <h2 class="tsh-page-title">Группы учеников</h2>
                <p class="tsh-page-subtitle">Собирайте потоки и назначайте учеников пакетно для более удобного контроля программы.</p>
            </div>
            <a href="/teacher/students" class="tsh-action-secondary text-xs">
                К списку учеников
            </a>
        </div>
    </section>

    {{-- Create group --}}
    <section class="tsh-card rounded-2xl p-5">
        <div class="flex flex-wrap items-center gap-3">
            <input x-model="newGroupName" type="text" placeholder="Название группы"
                   class="min-w-[200px] flex-1 px-4 py-2.5 rounded-xl text-sm transition focus:ring-2 focus:outline-none"
                   style="border: 1px solid var(--tsh-border); color: var(--tsh-text); background: white"
                   onfocus="this.style.borderColor='var(--tsh-accent)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)'"
                   onblur="this.style.borderColor='var(--tsh-border)'; this.style.boxShadow='none'">
            <input x-model="newGroupDescription" type="text" placeholder="Описание (опционально)"
                   class="min-w-[200px] flex-1 px-4 py-2.5 rounded-xl text-sm transition focus:ring-2 focus:outline-none"
                   style="border: 1px solid var(--tsh-border); color: var(--tsh-text); background: white"
                   onfocus="this.style.borderColor='var(--tsh-accent)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)'"
                   onblur="this.style.borderColor='var(--tsh-border)'; this.style.boxShadow='none'">
            <button @click="createGroup()" :disabled="!newGroupName.trim()"
                    :class="!newGroupName.trim() ? 'opacity-50 cursor-not-allowed' : ''"
                    class="tsh-action-primary text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Создать группу
            </button>
        </div>
        <p x-show="errorMessage" x-text="errorMessage" class="mt-3 text-xs text-red-500"></p>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Groups list --}}
        <div class="lg:col-span-2 space-y-4">
            <div x-show="loading" class="text-sm" style="color: var(--tsh-muted)">Загрузка групп...</div>

            <template x-for="group in groups" :key="group.id">
                <article class="tsh-card rounded-2xl overflow-hidden transition-shadow hover:shadow-md">
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <h2 class="font-semibold" style="color: var(--tsh-text)" x-text="group.name"></h2>
                                <p class="text-xs mt-0.5" style="color: var(--tsh-muted)" x-text="group.description || 'Без описания'"></p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-full bg-blue-50 text-blue-600 tabular-nums"
                                      x-text="group.students_count + ' уч.'"></span>
                                <button @click="deleteGroup(group.id)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center transition"
                                        style="color: var(--tsh-muted)"
                                        onmouseover="this.style.color='#dc2626'; this.style.background='#fee2e2'"
                                        onmouseout="this.style.color='var(--tsh-muted)'; this.style.background='transparent'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Students in group --}}
                        <div class="mb-3">
                            <div x-show="group.students.length === 0" class="text-xs" style="color: var(--tsh-subtle)">Пока пусто</div>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="student in group.students" :key="student.id">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs tsh-card-soft"
                                         style="border: 1px solid var(--tsh-border-soft); color: var(--tsh-text)">
                                        <span x-text="student.name"></span>
                                        <button @click="removeStudent(group.id, student.id)"
                                                class="transition ml-0.5"
                                                style="color: var(--tsh-subtle)"
                                                onmouseover="this.style.color='#dc2626'"
                                                onmouseout="this.style.color='var(--tsh-subtle)'">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Add student --}}
                        <div class="flex flex-wrap gap-2 items-center pt-3" style="border-top: 1px solid var(--tsh-border-soft)">
                            <select x-model.number="studentToAdd[group.id]"
                                    class="min-w-[200px] flex-1 px-3 py-2 rounded-xl text-sm transition focus:ring-2 focus:outline-none"
                                    style="border: 1px solid var(--tsh-border); color: var(--tsh-text); background: white"
                                    onfocus="this.style.borderColor='var(--tsh-accent)'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)'"
                                    onblur="this.style.borderColor='var(--tsh-border)'; this.style.boxShadow='none'">
                                <option value="">Добавить ученика...</option>
                                <template x-for="student in availableStudentsForGroup(group)" :key="student.id">
                                    <option :value="student.id" x-text="student.name + (student.email ? ' (' + student.email + ')' : '')"></option>
                                </template>
                            </select>
                            <button @click="addStudent(group.id)"
                                    :disabled="!studentToAdd[group.id]"
                                    :class="!studentToAdd[group.id] ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="tsh-action-primary text-sm font-medium">
                                Добавить
                            </button>
                        </div>
                    </div>
                </article>
            </template>
        </div>

        {{-- Students sidebar --}}
        <aside class="tsh-card rounded-2xl p-5 h-fit">
            <h3 class="font-semibold text-[15px] mb-3" style="color: var(--tsh-text)">Ученики преподавателя</h3>
            <div x-show="students.length === 0" class="text-xs" style="color: var(--tsh-subtle)">Связанных учеников пока нет.</div>
            <div class="space-y-1.5">
                <template x-for="student in students" :key="student.id">
                    <div class="px-3 py-2.5 rounded-xl tsh-card-soft" style="border: 1px solid var(--tsh-border-soft)">
                        <div class="text-sm" style="color: var(--tsh-text)" x-text="student.name"></div>
                        <div class="text-[11px]" style="color: var(--tsh-subtle)" x-text="student.email || 'Без e-mail'"></div>
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
