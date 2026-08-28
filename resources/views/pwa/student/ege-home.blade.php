@extends('layouts.pwa')
@section('title', 'ЕГЭ ' . $gradeLabel . ' класс — palomatika')

@push('styles')
@include('pwa.student.partials.home-styles')
@endpush

@section('body')
<div class="page" x-data="egeDashboardPage()">

  {{-- LESSON TILE — прикреплённым ученикам и админу (превью).
       Урок один для всех классов, не только для ОГЭ. --}}
  @if(!empty($showLessonTile))
    @include('pwa.student.partials.lesson-tile')
  @endif

  {{-- Повторение ОГЭ — десятым и одиннадцатым классам --}}
  @if(in_array((int)($user->grade_num ?? 0), [10, 11], true))
  <a href="{{ route('pwa.student.oge-dashboard') }}"
     style="display:flex;align-items:center;justify-content:center;gap:8px;
            padding:11px;border-radius:12px;text-decoration:none;font-size:13px;font-weight:700;
            color:var(--accent);background:var(--accent-bg);border:1px solid var(--accent-bd);">
    Переключиться на ОГЭ (повторение) →
  </a>
  @endif

  <div class="greeting">
    <div class="greeting-name">Привет, {{ $user->name ?? 'ученик' }}!</div>
    <div class="greeting-badge">ЕГЭ (П) · {{ $gradeLabel }} класс</div>
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

  @if(count($activeList) === 1)
  <a href="{{ route('pwa.student.ege.test', $activeList[0]['id']) }}" class="resume-banner">
    <div class="resume-left">
      <div class="resume-pulse"></div>
      <div>
        <div class="resume-title">{{ $activeList[0]['title'] }}</div>
        <div class="resume-sub">
          Отвечено {{ $activeList[0]['answeredCount'] }} из {{ $activeList[0]['totalCount'] }}
          · начат {{ $activeList[0]['startedAt']?->diffForHumans() }}
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

  {{-- Мини-варианта у ЕГЭ нет: профиль сдают целиком, короткой формы для
       него не заводили. Поэтому большая плитка одна, во всю ширину. --}}
  <div class="tile-row">
    <a href="#" class="tile-big tile-blue" style="flex:1" @click.prevent="startFull()">
      <div class="tile-icon">📝</div>
      <div class="tile-name">Полный вариант</div>
      <div class="tile-desc">Задания 1–{{ $taskCount }}, как на экзамене</div>
    </a>
  </div>

  <div class="tiles-grid">
    <a href="#" class="tile-sm" @click.prevent="showTaskBase = true">
      <div class="tile-sm-icon">📚</div>
      <div class="tile-sm-name">База заданий</div>
      <div class="tile-sm-desc">1я и 2я части</div>
    </a>
    <a href="/practice" class="tile-sm">
      <div class="tile-sm-icon">🎮</div>
      <div class="tile-sm-name">Практика</div>
      <div class="tile-sm-desc">Мини-игры и тренажёры</div>
      <div class="tile-badge badge-blue tile-badge-top-right" style="font-size:8px;">New</div>
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
      <div class="tile-sm-icon">👨‍🏫</div>
      <div class="tile-sm-name">Репетитор</div>
      <div class="tile-sm-desc">Бесплатный урок</div>
    </a>
    <div class="tile-sm" @click="showShare = true">
      <div class="tile-sm-icon">🎁</div>
      <div class="tile-sm-name">Пригласить друга</div>
      <div class="tile-sm-desc">Поделиться ссылкой</div>
    </div>
  </div>

  @if(count($weakTopics) > 0)
  <div class="weak-section">
    <div class="sec-label">Слабые темы</div>
    @foreach($weakTopics as $wt)
    <div class="weak-row">
      <div class="weak-info">
        <div class="weak-num">{{ $wt['task_number'] }}</div>
        <div class="weak-name">{{ $wt['name'] }}</div>
      </div>
      <div class="weak-pct {{ $wt['pct'] < 40 ? 'low' : ($wt['pct'] < 70 ? 'mid' : 'high') }}">{{ $wt['pct'] }}%</div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- Выбор части экзамена: 1–12 дают краткий ответ, 13–19 — развёрнутый,
       и смотреть их вперемешку неудобно. Так же устроен вход в базу
       заданий ОГЭ. --}}
  <template x-if="showTaskBase">
    <div class="fv-overlay" @click.self="showTaskBase = false">
      <div class="fv-sheet">
        <div class="fv-handle"></div>
        <div class="fv-title">База заданий</div>

        <a href="{{ route('pwa.student.ege.tasks', ['part' => 1]) }}" class="fv-option">
          <div class="fv-opt-icon">📝</div>
          <div>
            <div class="fv-opt-name">1я часть</div>
            <div class="fv-opt-desc">Задания 1–12 · краткий ответ</div>
          </div>
        </a>

        <a href="{{ route('pwa.student.ege.tasks', ['part' => 2]) }}" class="fv-option">
          <div class="fv-opt-icon">✍️</div>
          <div>
            <div class="fv-opt-name">2я часть</div>
            <div class="fv-opt-desc">Задания 13–19 · развёрнутый ответ</div>
          </div>
        </a>

        {{-- Базовый уровень — отдельный банк ФИПИ со своей нумерацией
             (1–21), поэтому он третьим пунктом, а не частью профиля. --}}
        <a href="{{ route('pwa.student.ege.tasks', ['level' => 'base']) }}" class="fv-option">
          <div class="fv-opt-icon">📐</div>
          <div>
            <div class="fv-opt-name">Базовый уровень (Б)</div>
            <div class="fv-opt-desc">Задания 1–21 · краткий ответ</div>
          </div>
        </a>

        <button class="fv-cancel" @click="showTaskBase = false">Отмена</button>
      </div>
    </div>
  </template>

  @if(count($activeList) > 1)
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
              · начат {{ $att['startedAt']?->diffForHumans() }}
            </div>
          </div>
        </a>
        @endforeach

        <button class="fv-cancel" @click="showUnfinished = false">Отмена</button>
      </div>
    </div>
  </template>
  @endif

  <template x-if="showShare">
    <div class="fv-overlay" @click.self="showShare = false">
      <div class="fv-sheet" @click.stop>
        <div class="fv-handle"></div>
        <div style="font-size:13px;font-weight:700;color:var(--muted);text-align:center;margin-bottom:20px;letter-spacing:.04em;text-transform:uppercase;">Поделиться</div>
        <div style="display:flex;align-items:center;gap:10px;background:var(--surface2);border-radius:12px;padding:10px 14px;margin-bottom:20px;">
          <span style="flex:1;font-size:12px;color:var(--accent);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="refLink"></span>
          <button @click="copyLink()" style="flex-shrink:0;background:var(--accent);color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;" x-text="copied ? '✓ Скопировано' : 'Копировать'"></button>
        </div>
        <div style="display:flex;justify-content:center;gap:24px;">
          <div style="display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;" @click="shareTo('telegram')">
            <div style="width:52px;height:52px;border-radius:16px;background:#229ED9;display:flex;align-items:center;justify-content:center;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248-2.04 9.613c-.154.683-.554.85-1.123.528l-3.1-2.285-1.496 1.44c-.165.165-.305.305-.625.305l.223-3.168 5.77-5.213c.25-.222-.055-.346-.39-.124L7.19 14.447l-3.04-.952c-.662-.207-.674-.662.138-.979l11.87-4.576c.552-.2 1.035.134.404.308z"/></svg>
            </div>
            <span style="font-size:11px;color:var(--muted);font-weight:600;">Telegram</span>
          </div>
          <div style="display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;" @click="shareTo('whatsapp')">
            <div style="width:52px;height:52px;border-radius:16px;background:#25D366;display:flex;align-items:center;justify-content:center;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </div>
            <span style="font-size:11px;color:var(--muted);font-weight:600;">WhatsApp</span>
          </div>
          <div style="display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;" @click="shareTo('vk')">
            <div style="width:52px;height:52px;border-radius:16px;background:#0077FF;display:flex;align-items:center;justify-content:center;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-1-1.49-1.135-1.744-1.135-.356 0-.458.102-.458.593v1.575c0 .424-.135.678-1.253.678-1.846 0-3.896-1.118-5.335-3.202C4.624 10.857 4.03 8.57 4.03 8.096c0-.254.102-.491.593-.491h1.744c.44 0 .61.203.78.677.863 2.49 2.303 4.675 2.896 4.675.22 0 .322-.102.322-.66V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.204.17-.407.44-.407h2.743c.373 0 .508.203.508.643v3.473c0 .372.17.508.271.508.22 0 .407-.136.813-.542 1.254-1.406 2.151-3.574 2.151-3.574.119-.254.322-.491.763-.491h1.744c.525 0 .644.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.254.44 0 .78.186.254.796.779 1.203 1.253.745.847 1.32 1.558 1.473 2.05.17.49-.085.744-.576.744z"/></svg>
            </div>
            <span style="font-size:11px;color:var(--muted);font-weight:600;">ВКонтакте</span>
          </div>
          <div style="display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;" @click="shareTo('native')">
            <div style="width:52px;height:52px;border-radius:16px;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </div>
            <span style="font-size:11px;color:var(--muted);font-weight:600;">Ещё</span>
          </div>
        </div>
        <button class="fv-cancel" @click="showShare = false">Закрыть</button>
      </div>
    </div>
  </template>
</div>

@include('pwa.student.partials.gift-overlay')
@endsection

@push('scripts')
<script>
function egeDashboardPage() {
  return {
    showShare: false,
    copied: false,
    refLink: '{{ url("/") }}?ref={{ $user->id }}',
    showUnfinished: false,
    showTaskBase: false,
    startingMini: false,
    startingFull: false,
    grade: {{ $grade }},

    async startFull() {
      if (this.startingFull) return;
      this.startingFull = true;

      try {
        const res = await window.fetchPost('{{ route("pwa.student.ege.start") }}', {});
        const data = await res.json();
        if (res.ok && data.redirect) {
          window.location.href = data.redirect;
          return;
        }

        alert(data.error || data.message || 'Ошибка запуска полного варианта');
      } catch (e) {
        console.error('startFull error:', e);
        alert('Ошибка соединения: ' + e.message);
      } finally {
        this.startingFull = false;
      }
    },

    copyLink() {
      navigator.clipboard.writeText(this.refLink).then(() => {
        this.copied = true;
        setTimeout(() => { this.copied = false; }, 2000);
      });
    },

    shareTo(platform) {
      const url = encodeURIComponent(this.refLink);
      const text = encodeURIComponent('Готовлюсь к ЕГЭ по математике на Palomatika — присоединяйся!');
      const links = {
        telegram: `https://t.me/share/url?url=${url}&text=${text}`,
        whatsapp: `https://wa.me/?text=${text}%20${url}`,
        vk: `https://vk.com/share.php?url=${url}&title=${text}`,
      };

      if (platform === 'native' && navigator.share) {
        navigator.share({ title: 'Palomatika', text: decodeURIComponent(text), url: this.refLink });
        return;
      }
      if (platform === 'native') {
        this.copyLink();
        return;
      }

      const tg = window.Telegram?.WebApp;
      if (platform === 'telegram' && tg?.openTelegramLink) {
        tg.openTelegramLink(links.telegram);
      } else {
        window.open(links[platform] || links.telegram, '_blank');
      }
      this.showShare = false;
    },
  };
}
</script>
@endpush

@push('install-prompt')
@include('pwa.shared.ios-install-prompt')
@endpush
