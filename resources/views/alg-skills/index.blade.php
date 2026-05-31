@extends('alg-skills.layout')

@section('title', 'Банк навыков · Алгебра ' . $grade . ' класс')

@section('body')
<div class="wrap">
    <a href="{{ route('alg-topics.show', ['grade' => $grade, 'id' => 0]) }}">← Арифметическая база</a>
    <header class="top">
      <div class="eyebrow">PALOMATIKA · Алгебра · {{ $grade }} класс</div>
      <h1>Банк навыков</h1>
      <div class="muted">Не главы учебника, а отдельные действия ученика. Репетитор выбирает ровно тот навык, который нужно добить на уроке или в домашке.</div>
      <div class="stats">
        <div class="stat"><b>{{ $skillsCount }}</b> навыков</div>
        <div class="stat"><b>{{ $totalTasks }}</b> задач</div>
        <div class="stat"><b>3</b> уровня на каждый навык</div>
      </div>
    </header>
    @foreach ($groups as $group)
    <section>
      <h2 class="group-title">{{ $group['title'] }}</h2>
      <div class="grid">
        @foreach ($group['skills'] as $skill)
        <a class="skill-card" href="{{ route('alg-skills.show', ['grade' => $grade, 'slug' => $skill['slug']]) }}">
          <div class="id">{{ $skill['id'] }}</div>
          <div class="title">{{ $skill['title'] }}</div>
          <div class="group">{{ $skill['group_title'] }}</div>
          <div class="chips">
            <span class="chip">{{ $skill['tasks_count'] }} задач</span>
            <span class="chip">{{ count($skill['homework_sets'] ?? []) }} домашки</span>
            <span class="chip">{{ count($skill['levels'] ?? []) }} уровня</span>
          </div>
        </a>
        @endforeach
      </div>
    </section>
    @endforeach
</div>
@endsection
