@extends('layouts.pwa')
@section('title', 'ВПР ' . $grade . ' класс — palomatika')

@push('styles')
  .greeting {
    opacity: 0; animation: fadeDown 0.3s ease 0s forwards;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px;
  }
  .greeting-name { font-family: var(--display); font-size: 20px; color: var(--text); }
  .greeting-badge {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 6px 10px; border-radius: 999px;
    border: 1px solid var(--accent-bd); background: var(--accent-bg); color: var(--accent);
    font-size: 11px; font-weight: 800; white-space: nowrap;
  }

  .premium-strip {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 10px;
    font-size: 12px; font-weight: 700;
    opacity: 0; animation: fadeUp 0.3s ease 0.03s forwards;
    text-decoration: none;
  }
  .premium-strip.active {
    background: var(--purple-bg); border: 1px solid var(--purple-bd); color: var(--purple);
  }
  .premium-strip.inactive {
    background: var(--surface); border: 1px solid var(--border); color: var(--muted);
  }
  .premium-strip-dot {
    width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
  }
  .premium-strip.active .premium-strip-dot { background: var(--green); }
  .premium-strip.inactive .premium-strip-dot { background: var(--muted); }

  .resume-banner {
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, rgba(59,130,246,.16), rgba(124,58,237,.12));
    border: 1.5px solid rgba(59,130,246,.35);
    border-radius: var(--r); padding: 14px 16px;
    text-decoration: none; color: inherit;
    opacity: 0; animation: fadeUp 0.3s ease 0.07s forwards;
  }
  .resume-banner:active { opacity: 0.85; }
  .resume-left { display: flex; align-items: center; gap: 12px; }
  .resume-pulse {
    width: 10px; height: 10px; background: var(--green);
    border-radius: 50%; flex-shrink: 0;
    animation: pulse 1.5s ease infinite;
  }
  .resume-title { font-family: var(--display); font-size: 14px; color: var(--text); }
  .resume-sub { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; }
  .resume-btn {
    font-size: 12px; font-weight: 800; color: var(--accent);
    white-space: nowrap;
  }

  .tile-row { display: flex; gap: 10px; }
  .tile-big {
    flex: 1;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--r); padding: 18px 16px;
    cursor: pointer; transition: border-color 0.15s, background 0.15s;
    user-select: none; text-decoration: none; color: inherit;
    opacity: 0; animation: fadeUp 0.3s ease 0.12s forwards;
  }
  .tile-big:active { background: var(--surface2); }
  .tile-purple { border-color: var(--purple-bd); }
  .tile-purple:active { background: var(--purple-bg); }
  .tile-blue { border-color: var(--accent-bd); }
  .tile-blue:active { background: var(--accent-bg); }
  .tile-icon { font-size: 28px; margin-bottom: 10px; }
  .tile-name { font-family: var(--display); font-size: 15px; color: var(--text); margin-bottom: 3px; }
  .tile-desc { font-size: 11px; font-weight: 600; color: var(--muted); line-height: 1.4; }
  .tile-badge {
    display: inline-block; margin-top: 8px;
    font-size: 9px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.08em; padding: 3px 8px; border-radius: 6px;
  }
  .badge-purple { background: var(--purple-bg); color: var(--purple); }
  .badge-blue { background: var(--accent-bg); color: var(--accent); }

  .tiles-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    opacity: 0; animation: fadeUp 0.3s ease 0.16s forwards;
  }
  .tile-sm {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 14px;
    cursor: pointer; transition: background 0.15s;
    user-select: none; text-decoration: none; color: inherit;
    position: relative;
  }
  .tile-sm:active { background: var(--surface2); }
  .tile-sm-icon { font-size: 22px; margin-bottom: 6px; }
  .tile-sm-name { font-size: 13px; font-weight: 800; color: var(--text); margin-bottom: 2px; }
  .tile-sm-desc { font-size: 10px; font-weight: 600; color: var(--muted); line-height: 1.3; }
  .tile-badge-top-right { position: absolute; top: 8px; right: 8px; margin-top: 0; }

  .weak-section {
    opacity: 0; animation: fadeUp 0.3s ease 0.2s forwards;
  }
  .weak-row {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 8px;
  }
  .weak-info { display: flex; align-items: center; gap: 10px; }
  .weak-num { font-family: var(--display); font-size: 14px; color: var(--muted); width: 24px; }
  .weak-name { font-size: 13px; font-weight: 700; color: var(--text); }
  .weak-pct { font-family: var(--display); font-size: 14px; }
  .weak-pct.low { color: var(--red); }
  .weak-pct.mid { color: var(--yellow); }
  .weak-pct.high { color: var(--green); }

  .fv-overlay {
    position: fixed; inset: 0; z-index: 100;
    background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
    display: flex; align-items: flex-end; justify-content: center;
  }
  .fv-sheet {
    background: var(--bg); border-radius: 20px 20px 0 0;
    width: 100%; max-width: 420px; padding: 20px 20px 32px;
  }
  .fv-handle {
    width: 36px; height: 4px; background: var(--border);
    border-radius: 2px; margin: 0 auto 16px;
  }
  .fv-title {
    font-family: var(--display); font-size: 18px; color: var(--text);
    text-align: center; margin-bottom: 16px;
  }
  .fv-option {
    display: flex; align-items: center; gap: 14px;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--r); padding: 16px;
    margin-bottom: 10px; cursor: pointer; transition: border-color 0.15s;
    text-decoration: none; color: inherit;
  }
  .fv-option:active { background: var(--surface2); }
  .fv-opt-icon { font-size: 28px; flex-shrink: 0; }
  .fv-opt-name { font-family: var(--display); font-size: 15px; color: var(--text); }
  .fv-opt-desc { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 2px; line-height: 1.3; }
  .fv-cancel {
    display: block; width: 100%; padding: 14px;
    background: none; border: none; color: var(--muted);
    font-size: 14px; font-weight: 700; cursor: pointer; margin-top: 4px;
  }

  @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.7); } }
@endpush

@section('body')
<div class="page" x-data="vprDashboardPage()">
  <div class="greeting">
    <div class="greeting-name">Привет, {{ $user->name ?? 'ученик' }}!</div>
    <div class="greeting-badge">ВПР · {{ $grade }} класс</div>
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

  @if(count($activeAttemptsList) === 1)
  <a href="{{ route('pwa.student.vpr.test', $activeAttemptsList[0]['id']) }}" class="resume-banner">
    <div class="resume-left">
      <div class="resume-pulse"></div>
      <div>
        <div class="resume-title">{{ $activeAttemptsList[0]['type'] }}</div>
        <div class="resume-sub">
          Отвечено {{ $activeAttemptsList[0]['answeredCount'] }} из {{ $activeAttemptsList[0]['totalCount'] }}
          · начат {{ $activeAttemptsList[0]['startedAt']?->diffForHumans() }}
        </div>
      </div>
    </div>
    <div class="resume-btn">Продолжить →</div>
  </a>
  @elseif(count($activeAttemptsList) > 1)
  <div class="resume-banner" style="cursor:pointer" @click="showUnfinished = true">
    <div class="resume-left">
      <div class="resume-pulse"></div>
      <div>
        <div class="resume-title">У вас {{ count($activeAttemptsList) }} незавершённых попыток</div>
        <div class="resume-sub">Нажмите, чтобы выбрать</div>
      </div>
    </div>
    <div class="resume-btn">Продолжить →</div>
  </div>
  @endif

  <div class="tile-row">
    <a href="#" class="tile-big tile-purple" @click.prevent="startMini()">
      <div class="tile-icon">⚡</div>
      <div class="tile-name">Мини-ВПР</div>
      <div class="tile-desc">Короткая тренировка в формате ВПР</div>
    </a>
    <a href="#" class="tile-big tile-blue" @click.prevent="startFull()">
      <div class="tile-icon">📝</div>
      <div class="tile-name">Полный вариант</div>
      <div class="tile-desc">Как на настоящей работе ВПР</div>
    </a>
  </div>

  <div class="tiles-grid">
    <a href="{{ route('pwa.student.vpr.tasks', ['grade' => $grade]) }}" class="tile-sm">
      <div class="tile-sm-icon">📚</div>
      <div class="tile-sm-name">База заданий</div>
      <div class="tile-sm-desc">Все задания ВПР по номерам</div>
    </a>
    @if($hasTeacher ?? false)
    <a href="{{ route('pwa.student.homework') }}" class="tile-sm">
      <div class="tile-sm-icon">📖</div>
      <div class="tile-sm-name">Домашка</div>
      <div class="tile-sm-desc">Задания от учителя</div>
    </a>
    @else
    <div class="tile-sm" style="opacity:0.5;cursor:default;">
      <div class="tile-sm-icon">🔍</div>
      <div class="tile-sm-name">Разбор ошибок</div>
      <div class="tile-sm-desc">Скоро</div>
      <div class="tile-badge badge-blue tile-badge-top-right" style="font-size:8px;">Soon</div>
    </div>
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

  @if(count($activeAttemptsList) > 1)
  <template x-if="showUnfinished">
    <div class="fv-overlay" @click.self="showUnfinished = false">
      <div class="fv-sheet">
        <div class="fv-handle"></div>
        <div class="fv-title">Незавершённые попытки</div>

        @foreach($activeAttemptsList as $att)
        <a href="{{ route('pwa.student.vpr.test', $att['id']) }}" class="fv-option">
          <div class="fv-opt-icon">{{ str_contains($att['type'], 'Мини') ? '⚡' : '📝' }}</div>
          <div>
            <div class="fv-opt-name">{{ $att['type'] }}</div>
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
function vprDashboardPage() {
  return {
    showShare: false,
    copied: false,
    refLink: '{{ url("/") }}?ref={{ $user->id }}',
    showUnfinished: false,
    startingMini: false,
    startingFull: false,
    grade: {{ $grade }},

    async startMini() {
      if (this.startingMini) return;
      this.startingMini = true;

      try {
        const res = await window.fetchPost('{{ route("pwa.student.vpr.mini.start") }}', { mode: 'mixed', grade: this.grade });
        const data = await res.json();
        if (res.ok && data.redirect) {
          window.location.href = data.redirect;
          return;
        }

        alert(data.error || data.message || 'Ошибка запуска мини-ВПР');
      } catch (e) {
        console.error('startMini error:', e);
        alert('Ошибка соединения: ' + e.message);
      } finally {
        this.startingMini = false;
      }
    },

    async startFull() {
      if (this.startingFull) return;
      this.startingFull = true;

      try {
        const res = await window.fetchPost('{{ route("pwa.student.vpr.start") }}', { grade: this.grade });
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
      const text = encodeURIComponent('Готовлюсь к ВПР по математике на Palomatika — присоединяйся!');
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
