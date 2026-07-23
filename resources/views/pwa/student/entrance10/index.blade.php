@extends('layouts.pwa')
@section('title', 'Контрольные для 10 класса — palomatika')

@include('pwa.student.entrance10._assets')

@section('body')
<div class="page">
  <div class="topbar">
    <a href="{{ route('pwa.student.practice.index') }}" class="back-btn">‹</a>
    <div class="topbar-title">Поступление в 10 класс</div>
  </div>

  <div class="e10-intro">
    <div class="e10-intro-title">{{ $meta['title'] ?? 'Вступительная работа в 10 класс' }}</div>
    @if(!empty($meta['subtitle']))<div class="e10-intro-sub">{{ $meta['subtitle'] }}</div>@endif
    <div class="e10-meta">
      @if(!empty($meta['duration_min']))<span class="e10-chip">⏱ {{ $meta['duration_min'] }} минут</span>@endif
      @if(!empty($meta['task_count']))<span class="e10-chip">📋 {{ $meta['task_count'] }} заданий</span>@endif
      @if(!empty($meta['max_score']))<span class="e10-chip">⭐ {{ $meta['max_score'] }} баллов</span>@endif
    </div>
    @if(!empty($meta['rules']))
      <ul class="e10-rules">
        @foreach($meta['rules'] as $rule)<li>{{ $rule }}</li>@endforeach
      </ul>
    @endif
  </div>

  <div class="e10-section-title">Полные варианты</div>
  <div class="e10-cards">
    @foreach($variantNumbers as $vn)
      <a href="{{ route('pwa.student.practice.entrance10.variant', $vn) }}" class="e10-card">
        <div class="e10-card-icon">{{ $vn }}</div>
        <div>
          <div class="e10-card-title">Вариант {{ $vn }}</div>
          <div class="e10-card-desc">Все задания работы · проверка ответов</div>
        </div>
        <div class="e10-card-go">›</div>
      </a>
    @endforeach
  </div>

  <div class="e10-section-title">Отработка по номерам</div>
  <div class="e10-num-grid">
    @foreach($numbers as $num)
      <a href="{{ route('pwa.student.practice.entrance10.number', $num['number']) }}" class="e10-num-cell">
        <span class="e10-num-badge">{{ $num['number'] }}</span>
        <span class="e10-num-label">{{ $num['title'] }}</span>
        <span class="e10-num-tag {{ $num['generatable'] ? 'gen' : 'stat' }}">
          {{ $num['generatable'] ? '∞ генерация' : 'из вариантов' }}
        </span>
      </a>
    @endforeach
  </div>
</div>
@endsection
