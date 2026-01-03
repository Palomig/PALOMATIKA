@extends('layouts.app')

@section('title', 'Темы ОГЭ')
@section('header', 'Темы ОГЭ')

@section('content')
<div x-data="topicsPage()">
    <!-- Stats header -->
    <div class="bg-white rounded-2xl p-6 shadow-sm mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
                <div class="text-3xl font-bold text-purple-600" x-text="overallProgress + '%'"></div>
                <div class="text-gray-500 text-sm">общий прогресс</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-green-600" x-text="completedTopics"></div>
                <div class="text-gray-500 text-sm">тем пройдено</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-blue-600" x-text="totalTasks"></div>
                <div class="text-gray-500 text-sm">задач решено</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-orange-500" x-text="avgAccuracy + '%'"></div>
                <div class="text-gray-500 text-sm">средняя точность</div>
            </div>
        </div>
    </div>

    <!-- Topics grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="topic in topics" :key="topic.id">
            <a :href="'/topics/' + topic.id" class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition cursor-pointer block">
                <div class="flex items-center justify-between mb-3">
                    <span class="bg-purple-100 text-purple-600 text-sm font-medium px-2 py-1 rounded"
                          x-text="'№' + topic.oge_number"></span>
                    <div class="flex items-center">
                        <template x-if="topic.progress >= 100">
                            <span class="text-green-500 text-xl">✓</span>
                        </template>
                        <template x-if="topic.progress > 0 && topic.progress < 100">
                            <span class="text-yellow-500 text-xl">◐</span>
                        </template>
                        <template x-if="topic.progress === 0">
                            <span class="text-gray-300 text-xl">○</span>
                        </template>
                    </div>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2" x-text="topic.name"></h3>
                <p class="text-gray-500 text-sm mb-4" x-text="topic.description"></p>
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-500">Прогресс</span>
                    <span class="font-medium" x-text="topic.progress + '%'"></span>
                </div>
                <div class="bg-gray-200 rounded-full h-2">
                    <div class="rounded-full h-2 transition-all"
                         :class="topic.progress >= 100 ? 'bg-green-500' : 'bg-purple-600'"
                         :style="'width: ' + topic.progress + '%'"></div>
                </div>
                <div class="mt-3 flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span x-text="topic.tasks_count + ' задач'"></span>
                </div>
            </a>
        </template>
    </div>

    <!-- Loading state -->
    <div x-show="loading" class="text-center py-12">
        <svg class="animate-spin h-8 w-8 mx-auto text-purple-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p class="text-gray-500 mt-2">Загрузка тем...</p>
    </div>
</div>

@push('scripts')
<script>
function topicsPage() {
    return {
        topics: [],
        loading: true,
        overallProgress: 0,
        completedTopics: 0,
        totalTasks: 0,
        avgAccuracy: 0,

        async init() {
            await this.loadTopics();
            this.loading = false;
        },

        async loadTopics() {
            try {
                const response = await fetch('/api/topics');
                if (response.ok) {
                    const data = await response.json();
                    this.topics = (data.topics || []).map(t => ({
                        ...t,
                        progress: Math.floor(Math.random() * 100),
                        tasks_count: Math.floor(Math.random() * 50) + 10
                    }));

                    // Calculate stats
                    this.overallProgress = Math.floor(this.topics.reduce((sum, t) => sum + t.progress, 0) / this.topics.length);
                    this.completedTopics = this.topics.filter(t => t.progress >= 100).length;
                    this.totalTasks = this.topics.reduce((sum, t) => sum + t.tasks_count, 0);
                    this.avgAccuracy = Math.floor(Math.random() * 30) + 60;
                }
            } catch (e) {
                console.error('Failed to load topics', e);
            }
        }
    }
}
</script>
@endpush
@endsection
