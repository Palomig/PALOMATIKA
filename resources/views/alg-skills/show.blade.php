@extends('alg-skills.layout')

@section('title', $skill['title'] . ' · Алгебра ' . $grade . ' класс')

@php
    $levelClass = ['simple' => 'level-simple', 'medium' => 'level-medium', 'high' => 'level-high'];
    $levelName  = ['simple' => 'Простой',      'medium' => 'Средний',     'high' => 'Высокий'];
@endphp

@section('body')
<div class="wrap skill-page" data-task-view="types">
    <a href="{{ route('alg-skills.index', ['grade' => $grade]) }}">← Все навыки {{ $grade }} класса</a>
    <header class="top">
      <div class="eyebrow">PALOMATIKA · Алгебра · {{ $grade }} класс · {{ $skill['group_title'] }}</div>
      <h1>{{ $skill['id'] }}. {{ $skill['title'] }}</h1>
      <div class="muted">Одна страница тренирует один конкретный навык. Уровни отличаются только сложностью чисел и количеством шагов, а не типом задания.</div>
      <div class="stats">
        <div class="stat"><b>{{ $skill['tasks_count'] }}</b> задач</div>
        <div class="stat"><b>{{ count($levels) }}</b> уровня</div>
        <div class="stat"><b>{{ $skill['task_type'] }}</b> тип</div>
      </div>
      <div class="view-switch" aria-label="Режим просмотра заданий">
        <button class="is-active" type="button" data-view-button="types">Типы примеров</button>
        <button type="button" data-view-button="all">Все задания</button>
      </div>
    </header>

    <div class="types-view">
      <section class="panel">
        <h2>Типы примеров</h2>
        <div class="muted">Показаны разные шаблоны заданий без повторов числовых вариантов. Этот режим нужен для быстрой оценки разнообразия.</div>
      </section>
      @foreach ($levels as $level)
        @include('alg-skills.partials.level', [
            'level'      => $level,
            'tasks'      => $level['representative_tasks'],
            'levelClass' => $levelClass,
            'levelName'  => $levelName,
        ])
      @endforeach
    </div>

    <div class="all-view">
      @if (!empty($homeworkSets))
      <section class="panel">
        <h2>Готовые домашки</h2>
        <div class="homework-list">
          @foreach ($homeworkSets as $set)
          <article class="homework-card">
            <h3>{{ $set['title'] }}</h3>
            <p>{{ $set['tasks_count'] }} заданий · {{ $set['target_minutes'] }} мин · простой {{ $set['mix']['simple'] ?? 0 }}, средний {{ $set['mix']['medium'] ?? 0 }}, высокий {{ $set['mix']['high'] ?? 0 }}</p>
            <details>
              <summary>Показать задания</summary>
              <ol>
                @foreach ($set['tasks'] ?? [] as $task)
                <li>{!! \App\Services\AlgTaskDataService::mathText($task['expression'] ?? '') !!}</li>
                @endforeach
              </ol>
            </details>
          </article>
          @endforeach
        </div>
      </section>
      @endif
      @foreach ($levels as $level)
        @include('alg-skills.partials.level', [
            'level'      => $level,
            'tasks'      => $level['tasks'] ?? [],
            'levelClass' => $levelClass,
            'levelName'  => $levelName,
        ])
      @endforeach
    </div>
</div>
@endsection
