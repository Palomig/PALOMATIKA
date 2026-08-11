@extends('layouts.pwa')
@section('title', 'ЕГЭ ' . $gradeLabel . ' класс — palomatika')

@push('styles')
@include('pwa.student.partials.home-styles')
@endpush

@section('body')
{{--
  Домашний экран ЕГЭ. Раньше здесь были заголовок и две кнопки, тогда как
  ОГЭ и ВПР показывали приветствие, незавершённые попытки и плитки
  разделов — ученик попадал будто в другой продукт. Вёрстка и классы те же,
  что у ВПР; отличается только содержимое плиток.
--}}
<div class="home-container" x-data="{ showUnfinished: false }">

  {{-- Урок и домашка — единый инструмент для всех классов, не только ОГЭ. --}}
  @if(!empty($showLessonTile))
    @include('pwa.student.partials.lesson-tile')
  @endif

  {{-- Повторение ОГЭ доступно десятым-одиннадцатым классам. --}}
  @if(in_array((int) ($user->grade_num ?? 0), [10, 11], true))
  <a href="{{ route('pwa.student.oge-dashboard') }}"
     style="display:flex;align-items:center;justify-content:center;gap:8px;
            padding:11px;border-radius:12px;text-decoration:none;font-size:13px;font-weight:700;
            color:var(--accent);background:var(--accent-bg);border:1px solid var(--accent-bd);">
    Переключиться на ОГЭ (повторение) →
  </a>
  @endif

  <div class="greeting">
    <div class="greeting-name">Привет, {{ $user->name ?? 'ученик' }}!</div>
    <div class="greeting-badge">ЕГЭ · {{ $gradeLabel }} класс</div>
  </div>

  @if($user->hasTgPremium())
    <a href="{{ route('pwa.student.profile') }}" class="premium-strip active">
      <span class="premium-strip-dot"></span>
      Premium · {{ now()->diffInDays($user->tg_premium_until) }} дн
    </a>
  @else
    <a href="{{ route('pwa.student.profile') }}" class="premium-strip inactive">
      <span class="premium-strip-dot"></span>
      Нет Premium
    </a>
  @endif

  {{-- Незавершённые попытки --}}
  @if(count($activeList) === 1)
  <a href="{{ route('pwa.student.ege.test', $activeList[0]['id']) }}" class="resume-banner">
    <div class="resume-left">
      <div class="resume-pulse"></div>
      <div>
        <div class="resume-title">{{ $activeList[0]['title'] }}</div>
        <div class="resume-sub">
          Отвечено {{ $activeList[0]['answeredCount'] }} из {{ $activeList[0]['totalCount'] }}
        </div>
      </div>
    </div>
    <div class="resume-btn">Продолжить →</div>
  </a>
  @elseif(count($activeList) > 1)
  <div class="resume-banner" style="cursor:pointer" @click="showUnfinished = true">
    <div class="resume-left">
      <div class="resume-pulse"></div>
      <div>
        <div class="resume-title">У вас {{ count($activeList) }} незавершённых попыток</div>
        <div class="resume-sub">Нажмите, чтобы выбрать</div>
      </div>
    </div>
    <div class="resume-btn">Продолжить →</div>
  </div>
  @endif

  {{-- Мини-варианта у ЕГЭ нет: профиль сдают целиком, и короткой формы
       работы для него не заводили. Плитка одна, во всю ширину. --}}
  <div class="tile-row">
    <form method="POST" action="{{ route('pwa.student.ege.start') }}" style="flex:1">
      @csrf
      <button type="submit" class="tile-big tile-blue" style="width:100%;border:none;text-align:left;cursor:pointer">
        <div class="tile-icon">📝</div>
        <div class="tile-name">Полный вариант</div>
        <div class="tile-desc">Задания 1–{{ $taskCount }}, как на экзамене</div>
      </button>
    </form>
  </div>

  <div class="tiles-grid">
    <a href="{{ route('ege.index') }}" class="tile-sm">
      <div class="tile-sm-icon">📚</div>
      <div class="tile-sm-name">База заданий</div>
      <div class="tile-sm-desc">Все задания ЕГЭ по номерам</div>
    </a>
    <a href="/practice" class="tile-sm">
      <div class="tile-sm-icon">🎮</div>
      <div class="tile-sm-name">Практика</div>
      <div class="tile-sm-desc">Мини-игры и тренажёры</div>
    </a>
    @if($hasTeacher ?? false)
    <a href="{{ route('pwa.student.homework') }}" class="tile-sm">
      <div class="tile-sm-icon">📖</div>
      <div class="tile-sm-name">Домашка</div>
      <div class="tile-sm-desc">Задания от учителя</div>
    </a>
    @endif
    <a href="{{ route('pwa.student.history') }}" class="tile-sm">
      <div class="tile-sm-icon">📊</div>
      <div class="tile-sm-name">История</div>
      <div class="tile-sm-desc">Все попытки</div>
    </a>
    <a href="{{ route('pwa.student.profile') }}" class="tile-sm">
      <div class="tile-sm-icon">👤</div>
      <div class="tile-sm-name">Профиль</div>
      <div class="tile-sm-desc">Premium · Рефералы</div>
      @if($user->hasTgPremium())
      <div class="tile-badge badge-purple tile-badge-top-right" style="font-size:8px;">Premium</div>
      @endif
    </a>
    <a href="{{ route('pwa.student.tutor') }}" class="tile-sm">
      <div class="tile-sm-icon">🎓</div>
      <div class="tile-sm-name">Репетитор</div>
      <div class="tile-sm-desc">Разбор с преподавателем</div>
    </a>
  </div>

  {{-- Выбор незавершённой попытки, когда их несколько: та же шторка, что у ВПР --}}
  <template x-if="showUnfinished">
    <div class="fv-overlay" @click.self="showUnfinished = false">
      <div class="fv-sheet">
        <div class="fv-handle"></div>
        <div class="fv-title">Незавершённые попытки</div>

        @foreach($activeList as $att)
        <a href="{{ route('pwa.student.ege.test', $att['id']) }}" class="fv-option">
          <div class="fv-opt-icon">📝</div>
          <div>
            <div class="fv-opt-name">{{ $att['title'] }}</div>
            <div class="fv-opt-desc">
              Отвечено {{ $att['answeredCount'] }} из {{ $att['totalCount'] }}
            </div>
          </div>
        </a>
        @endforeach

        <button class="fv-cancel" @click="showUnfinished = false">Отмена</button>
      </div>
    </div>
  </template>
</div>
@endsection
