@extends('layouts.pwa')
@section('title', $title . ' — palomatika')

@push('katex')
@include('partials.head-katex')
@endpush

@push('head')
<link rel="preload" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/fonts/KaTeX_Main-Regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/fonts/KaTeX_Math-Italic.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/fonts/KaTeX_Size2-Regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
@endpush

@push('styles')
  /* ───── TEST PAGE LAYOUT ───── */
  .test-page {
    min-height: 100dvh;
    max-width: 480px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    padding-top: calc(var(--safe-top));
  }

  /* ───── TOP BAR ───── */
  .test-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px 14px;
    border-bottom: 1px solid var(--border);
    background: var(--bg);
    flex-shrink: 0;
  }
  .test-back-btn {
    width: 36px; height: 36px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    color: var(--muted);
    cursor: pointer;
    transition: background 0.15s;
    flex-shrink: 0;
    -webkit-appearance: none;
    appearance: none;
  }
  .test-back-btn:active { background: var(--surface2, var(--border)); }

  .test-topbar-center { text-align: center; flex: 1; min-width: 0; }
  .test-variant-title {
    font-family: var(--display);
    font-size: 14px;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .test-variant-sub {
    font-size: 11px;
    color: var(--muted);
    font-weight: 600;
    margin-top: 1px;
  }

  .test-timer {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 6px 12px;
    font-family: var(--display);
    font-size: 14px;
    color: var(--text);
    min-width: 70px;
    text-align: center;
    flex-shrink: 0;
    transition: color 0.3s, border-color 0.3s;
  }
  .test-timer.warn { color: var(--red); border-color: var(--red-bd, rgba(240,90,90,0.4)); }

  /* ───── PROGRESS ───── */
  .test-progress {
    padding: 12px 16px 0;
    background: var(--bg);
    flex-shrink: 0;
  }
  .test-progress-info {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    margin-bottom: 7px;
  }
  .test-progress-track {
    height: 4px;
    background: var(--border);
    border-radius: 99px;
    overflow: hidden;
  }
  .test-progress-fill {
    height: 100%;
    background: var(--accent);
    border-radius: 99px;
    transition: width 0.35s ease;
  }

  /* ───── NAV DOTS ───── */
  .test-nav {
    padding: 12px 16px;
    overflow-x: auto;
    display: flex;
    gap: 6px;
    scrollbar-width: none;
    background: var(--bg);
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
  }
  .test-nav::-webkit-scrollbar { display: none; }

  .test-dot {
    width: 32px; height: 32px;
    border-radius: 9px;
    border: 1.5px solid var(--border);
    background: var(--surface);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px;
    font-weight: 800;
    color: var(--muted);
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.15s;
    user-select: none;
    -webkit-user-select: none;
    position: relative;
  }
  .test-dot.answered {
    background: var(--accent-bg, rgba(79,142,247,0.1));
    border-color: var(--accent);
    color: var(--accent);
  }
  .test-dot.active {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
  }
  .test-dot .dot-check {
    position: absolute;
    top: -4px; right: -4px;
    width: 14px; height: 14px;
    background: var(--green);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 8px;
    color: #fff;
    line-height: 1;
  }

  /* ───── QUESTION AREA ───── */
  .test-question {
    flex: 1;
    padding: 20px 16px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
    -webkit-overflow-scrolling: touch;
  }

  .q-meta {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .q-num {
    font-family: var(--display);
    font-size: 13px;
    color: var(--muted);
  }
  .q-topic-badge {
    background: var(--accent-bg, rgba(79,142,247,0.08));
    color: var(--accent);
    font-size: 10px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 6px;
    letter-spacing: 0.04em;
  }

  .q-text {
    font-size: 16px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.6;
  }
  .inline-frac {
    display:inline-flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    line-height:1;
    vertical-align:middle;
    margin: 0 2px;
    transform: translateY(-1px);
  }
  .inline-frac .top,
  .inline-frac .bottom {
    display:block;
    padding: 0 3px;
    font-size: .92em;
  }
  .inline-frac .bottom {
    border-top: 1.5px solid currentColor;
    margin-top: 2px;
    padding-top: 2px;
  }

  /* KaTeX expression block */
  .q-expression {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 20px 16px;
    text-align: center;
    font-size: 20px;
    color: var(--text);
    overflow-x: auto;
  }

  /* SVG image */
  .q-svg-wrap {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .q-svg-wrap svg {
    max-width: 100%;
    height: auto;
  }
  .q-svg-wrap.q-svg-wide {
    justify-content: flex-start;
  }
  .q-svg-wrap.q-svg-wide svg {
    width: 880px;
    min-width: 880px;
    max-width: none;
    height: auto;
  }

  /* ───── ANSWER SECTION ───── */
  .answer-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 16px;
  }
  .answer-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 10px;
  }

  /* Choice options */
  .test-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .test-option {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 15px 16px;
    background: var(--bg);
    border: 1.5px solid var(--border);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.15s;
    user-select: none;
    -webkit-user-select: none;
  }
  .test-option:active { transform: scale(0.98); }
  .test-option.selected {
    background: var(--accent-bg, rgba(79,142,247,0.08));
    border-color: var(--accent);
  }
  .test-option-letter {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: var(--surface);
    border: 1.5px solid var(--muted2, var(--border));
    display: flex; align-items: center; justify-content: center;
    font-family: var(--display);
    font-size: 12px;
    color: var(--muted);
    flex-shrink: 0;
    transition: all 0.15s;
  }
  .test-option.selected .test-option-letter {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
  }
  .test-option-text {
    font-size: 17px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.45;
  }
  .test-option-text .katex {
    font-size: 1.28em;
  }
  .test-option-text svg {
    width: 100%; max-width: 320px; height: auto;
  }

  /* Text input */
  .answer-input {
    width: 100%;
    background: var(--bg);
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 14px 16px;
    font-family: var(--body);
    font-size: 20px;
    font-weight: 800;
    color: var(--text);
    text-align: center;
    letter-spacing: 0.1em;
    outline: none;
    -webkit-appearance: none;
    appearance: none;
    transition: border-color 0.2s;
  }
  .answer-input:focus { border-color: var(--accent); }
  .answer-input::placeholder {
    color: var(--muted2, var(--muted));
    font-size: 14px;
    letter-spacing: 0;
    font-weight: 600;
  }
  .answer-hint {
    font-size: 11px;
    color: var(--muted);
    font-weight: 600;
    text-align: center;
    margin-top: 8px;
  }

  /* ───── PHOTO UPLOAD ───── */
  .photo-upload-area { margin-top: 12px; }
  .photo-btn {
    width: 100%; padding: 12px; border-radius: var(--r);
    border: 1.5px dashed var(--border); background: var(--surface);
    color: var(--muted); font-size: 13px; font-weight: 700;
    cursor: pointer; transition: all 0.15s;
  }
  .photo-btn:active { background: var(--surface2); }
  .photo-btn.has-photo {
    border-color: var(--green); border-style: solid;
    color: var(--green); background: rgba(34, 197, 94, 0.08);
  }
  .photo-hint {
    font-size: 10px; font-weight: 600; color: var(--muted);
    text-align: center; margin-top: 4px; opacity: 0.7;
  }

  /* ───── BOTTOM BAR ───── */
  .test-bottom {
    padding: 12px 16px calc(12px + var(--safe-bottom));
    background: var(--bg);
    border-top: 1px solid var(--border);
    display: flex;
    gap: 10px;
    flex-shrink: 0;
  }
  .test-btn-prev {
    flex: 0 0 52px;
    height: 52px;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--r);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    color: var(--muted);
    cursor: pointer;
    transition: background 0.15s;
    -webkit-appearance: none;
    appearance: none;
  }
  .test-btn-prev:active { background: var(--surface2, var(--border)); }
  .test-btn-prev:disabled { opacity: 0.35; cursor: default; }

  .test-btn-next {
    flex: 1;
    height: 52px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: var(--r);
    font-family: var(--body);
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    transition: opacity 0.15s, transform 0.12s;
    -webkit-appearance: none;
    appearance: none;
    display: flex; align-items: center; justify-content: center; gap: 6px;
  }
  .test-btn-next:active { transform: scale(0.98); }
  .test-btn-next.finish { background: var(--green); }

  /* ───── CONFIRM MODAL ───── */
  .test-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 100;
    display: flex; align-items: flex-end;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s;
  }
  .test-modal-overlay.open {
    opacity: 1;
    pointer-events: all;
  }
  .test-modal {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    background: var(--surface);
    border-radius: 20px 20px 0 0;
    padding: 24px 20px calc(24px + var(--safe-bottom));
    transform: translateY(100%);
    transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
  }
  .test-modal-overlay.open .test-modal { transform: translateY(0); }

  .test-modal-handle {
    width: 36px; height: 4px;
    background: var(--border);
    border-radius: 99px;
    margin: 0 auto 20px;
  }
  .test-modal-title {
    font-family: var(--display);
    font-size: 20px;
    color: var(--text);
    margin-bottom: 8px;
    text-align: center;
  }
  .test-modal-sub {
    font-size: 13px;
    color: var(--muted);
    font-weight: 600;
    text-align: center;
    margin-bottom: 24px;
    line-height: 1.5;
  }
  .test-modal-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 20px;
  }
  .test-modal-stat {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px;
    text-align: center;
  }
  .test-modal-stat-val {
    font-family: var(--display);
    font-size: 22px;
    color: var(--text);
  }
  .test-modal-stat-lbl {
    font-size: 11px;
    color: var(--muted);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 2px;
  }
  .test-modal-confirm {
    width: 100%;
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: var(--r);
    padding: 16px;
    font-family: var(--body);
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    margin-bottom: 10px;
    -webkit-appearance: none;
  }
  .test-modal-confirm:active { opacity: 0.85; }
  .test-modal-confirm:disabled { opacity: 0.6; cursor: default; }

  .test-modal-cancel {
    width: 100%;
    background: transparent;
    color: var(--muted);
    border: 1.5px solid var(--border);
    border-radius: var(--r);
    padding: 14px;
    font-family: var(--body);
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    -webkit-appearance: none;
  }
  .test-modal-cancel:active { background: var(--surface2, var(--border)); }

  /* Battle start overlay */
  .battle-overlay {
    position: fixed;
    inset: 0;
    z-index: 120;
    background:
      radial-gradient(1100px 520px at 15% -10%, rgba(108,182,238,0.18), transparent 55%),
      radial-gradient(900px 460px at 110% 110%, rgba(79,142,247,0.14), transparent 50%),
      rgba(10, 22, 40, 0.86);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .battle-card {
    width: 100%;
    max-width: 380px;
    background: linear-gradient(180deg, rgba(19,35,56,0.96), rgba(14,27,44,0.96));
    border: 1px solid rgba(108, 182, 238, 0.28);
    border-radius: 20px;
    padding: 22px 18px 18px;
    text-align: center;
    box-shadow: 0 22px 46px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.04);
    animation: battleCardIn .32s cubic-bezier(.2,.8,.2,1);
  }
  .battle-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #9ec8ff;
    background: rgba(79,142,247,0.14);
    border: 1px solid rgba(79,142,247,0.35);
    border-radius: 999px;
    padding: 6px 10px;
    margin-bottom: 10px;
  }
  .battle-card h3 {
    margin: 0 0 8px;
    color: #fff;
    font-family: var(--display);
    font-size: 22px;
    line-height: 1.2;
  }
  .battle-card p {
    margin: 0 0 16px;
    color: #b4c2d8;
    font-size: 13px;
    line-height: 1.5;
  }
  .battle-cta {
    width: 100%;
    height: 50px;
    border: 0;
    border-radius: 14px;
    background: linear-gradient(135deg, #4f8ef7, #5ca8ff);
    color: #fff;
    font-weight: 800;
    font-size: 15px;
    letter-spacing: .01em;
    box-shadow: 0 10px 24px rgba(79,142,247,0.4);
    transition: transform .16s ease, box-shadow .2s ease, filter .2s ease;
    animation: battlePulse 1.8s ease-in-out infinite;
  }
  .battle-cta:hover { box-shadow: 0 14px 28px rgba(79,142,247,0.5); filter: brightness(1.03); }
  .battle-cta:active { transform: translateY(1px) scale(.995); }
  .battle-meta {
    margin-top: 10px;
    font-size: 11px;
    color: #7f95b3;
  }

  @keyframes battleCardIn {
    from { opacity: 0; transform: translateY(10px) scale(.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }
  @keyframes battlePulse {
    0%, 100% { box-shadow: 0 10px 24px rgba(79,142,247,0.36); }
    50% { box-shadow: 0 14px 30px rgba(79,142,247,0.55); }
  }

  /* transition helper */
  .q-anim {
    opacity: 0;
    animation: fadeUp 0.3s ease forwards;
  }
@endpush

@section('body')
<div class="test-page"
     x-data="testApp()"
     x-init="init()"
     x-cloak>

  <template x-if="battleMode && !battleStarted">
    <div class="battle-overlay">
      <div class="battle-card">
        <div class="battle-chip">⚔️ Battle mode</div>
        <h3>Батл-вариант</h3>
        <p>После нажатия «Начать» включится таймер и начнётся попытка. Удачи, покажи лучший результат!</p>
        <button class="battle-cta" @click="startBattle()">Начать</button>
        <div class="battle-meta">Время и точность влияют на место в таблице</div>
      </div>
    </div>
  </template>

  {{-- ───── TOP BAR ───── --}}
  <div class="test-topbar">
    <button class="test-back-btn" @click="confirmExit()">&#8249;</button>
    <div class="test-topbar-center">
      <div class="test-variant-title">{{ $title }}</div>
      <div class="test-variant-sub">{{ $mode === 'full' ? 'ОГЭ' : 'Мини-тест' }} &middot; Математика &middot; 2026</div>
    </div>
    <div class="test-timer" :class="{ warn: elapsed >= 3600 }" x-text="timerText"></div>
  </div>

  {{-- ───── PROGRESS ───── --}}
  <div class="test-progress">
    <div class="test-progress-info">
      <span x-text="'Задание ' + (current + 1) + ' из ' + total"></span>
      <span x-text="'отвечено: ' + answeredCount"></span>
    </div>
    <div class="test-progress-track">
      <div class="test-progress-fill" :style="'width:' + progressPct + '%'"></div>
    </div>
  </div>

  {{-- ───── NAV DOTS ───── --}}
  <div class="test-nav" x-ref="navScroll">
    <template x-for="(task, idx) in tasks" :key="idx">
      <div class="test-dot"
           :class="{
             'active': idx === current,
             'answered': idx !== current && answers[taskKey(task)] !== undefined && answers[taskKey(task)] !== ''
           }"
           @click="goTo(idx)">
        <span x-text="displayTaskNumber(task)"></span>
        <template x-if="answers[taskKey(task)] !== undefined && answers[taskKey(task)] !== '' && idx !== current">
          <div class="dot-check">&#10003;</div>
        </template>
      </div>
    </template>
  </div>

  {{-- ───── QUESTION AREA ───── --}}
  <div class="test-question" x-ref="questionArea">
    <template x-if="currentTask">

      <div :key="current">
        {{-- Meta --}}
        <div class="q-meta q-anim" :style="'animation-delay: 0s'">
          <div class="q-num" x-text="'Задание ' + displayTaskNumber(currentTask)"></div>
          <div class="q-topic-badge" x-text="topicName(currentTask.topic_id)"></div>
          <div class="q-topic-badge" style="opacity:.8" x-text="'ID: ' + taskLocator(currentTask)"></div>
        </div>

        {{-- Instruction text --}}
        <div class="q-text q-anim" :style="'animation-delay: 0.05s; margin-top: 16px'"
             x-html="formatRichText(currentTask.instruction || currentTask.text || '')"></div>

        {{-- Task text (when instruction is a generic heading and task body is separate) --}}
        <template x-if="currentTask.instruction && currentTask.text && currentTask.text !== currentTask.instruction">
          <div class="q-text q-anim" :style="'animation-delay: 0.07s; margin-top: 10px; font-size: 15px; font-weight: 600'"
               x-html="formatRichText(currentTask.text)"></div>
        </template>

        {{-- Expression (KaTeX) --}}
        <template x-if="currentTask.expression">
          <div class="q-expression q-anim" :style="'animation-delay: 0.08s; margin-top: 16px'"
               x-ref="katexExpr"
               :data-expr="currentTask.expression"
               x-effect="$nextTick(() => renderTaskMath())">
            <span x-text="'$' + currentTask.expression + '$'"></span>
          </div>
        </template>

        {{-- SVG image --}}
        <template x-if="currentTask.svg">
          <div class="q-svg-wrap q-anim"
               :class="{
                 'q-svg-wide': String(displayTaskNumber(currentTask)) === '11',
                 'is-clickable': canOpenImageViewer(currentTask)
               }"
               :style="'animation-delay: 0.08s; margin-top: 16px'"
               @click="openImageViewer(currentTask)"
               x-html="currentTask.svg"></div>
        </template>

        {{-- Image fallback (PNG/JPG/SVG file path or inline SVG string) --}}
        <template x-if="!currentTask.svg && currentTask.image && String(currentTask.image).trim().startsWith('<svg')">
          <div class="q-svg-wrap q-anim"
               :class="{
                 'q-svg-wide': String(displayTaskNumber(currentTask)) === '11',
                 'is-clickable': canOpenImageViewer(currentTask)
               }"
               :style="'animation-delay: 0.08s; margin-top: 16px'"
               @click="openImageViewer(currentTask)"
               x-html="currentTask.image"></div>
        </template>
        <template x-if="!currentTask.svg && currentTask.image && !String(currentTask.image).trim().startsWith('<svg')">
          <div class="q-svg-wrap q-anim"
               :class="{
                 'q-svg-wide': String(displayTaskNumber(currentTask)) === '11',
                 'is-clickable': canOpenImageViewer(currentTask)
               }"
               :style="'animation-delay: 0.08s; margin-top: 16px'"
               @click="openImageViewer(currentTask)">
            <img :src="resolveTaskImageSrc(currentTask)" alt="task image" style="max-width:100%; height:auto; display:block; margin:0 auto; border-radius:12px;">
          </div>
        </template>

        {{-- Answer section --}}
        <div class="answer-section q-anim" :style="'animation-delay: 0.1s; margin-top: 16px'">

          {{-- Choice/options type (for any task that carries options) --}}
          <template x-if="currentTask.type !== 'matching' && currentTask.type !== 'matching_signs' && normalizedOptions(currentTask).length > 0">
            <div>
              <div class="answer-label">Выбери ответ</div>
              <div class="test-options">
                <template x-for="(opt, oi) in normalizedOptions(currentTask)" :key="oi">
                  <div class="test-option"
                       :class="{ 'selected': answers[taskKey(currentTask)] === optionAnswerValue(opt, oi) }"
                       @click="selectOption(opt, oi)">
                    <div class="test-option-letter" x-text="optionDisplayLabel(opt, oi)"></div>
                    <div class="test-option-text" x-html="optionHtml(opt)"></div>
                  </div>
                </template>
              </div>
            </div>
          </template>

          {{-- Matching type (simplified as choice) --}}
          <template x-if="currentTask.type === 'matching' || currentTask.type === 'matching_signs'">
            <div>
              <template x-if="normalizedOptions(currentTask).length > 0">
                <div style="margin-bottom:12px">
                  <div class="answer-label">Варианты</div>
                  <div class="test-options">
                    <template x-for="(opt, oi) in normalizedOptions(currentTask)" :key="oi">
                      <div class="test-option" style="cursor:default">
                        <div class="test-option-letter" x-text="oi + 1"></div>
                        <div class="test-option-text" x-html="optionHtml(opt)"></div>
                      </div>
                    </template>
                  </div>
                </div>
              </template>

              <div class="answer-label">Введи ответ</div>
              <input class="answer-input"
                     type="text"
                     inputmode="text"
                     placeholder="Например: 123"
                     autocomplete="off"
                     x-model="answers[taskKey(currentTask)]"
                     x-ref="answerInput">
              <div class="answer-hint" x-text="normalizedOptions(currentTask).length > 0 ? 'Введи цифры по порядку' : 'Введи буквы по порядку'"></div>
            </div>
          </template>

          {{-- Input type (expression, geometry, word_problem, etc.) --}}
          <template x-if="normalizedOptions(currentTask).length === 0 && currentTask.type !== 'matching' && currentTask.type !== 'matching_signs'">
            <div>
              <div class="answer-label">Введи ответ</div>
              <input class="answer-input"
                     type="text"
                     inputmode="text"
                     placeholder="Ответ"
                     autocomplete="off"
                     x-model="answers[taskKey(currentTask)]"
                     x-ref="answerInput">
              <div class="answer-hint">Введи число и переходи дальше</div>
            </div>
          </template>

          {{-- Photo upload for Part 2 tasks --}}
          <template x-if="currentTask && displayTaskNumber(currentTask) >= 20">
            <div class="photo-upload-area">
              <input type="file" accept="image/*" capture="environment"
                     x-ref="photoInput" style="display:none"
                     @change="uploadPhoto($event)">
              <button type="button" class="photo-btn" @click="$refs.photoInput.click()"
                      :class="{ 'has-photo': photos[taskKey(currentTask)] }">
                <span x-text="photos[taskKey(currentTask)] ? '📷 Фото прикреплено' : '📷 Сфотографировать решение'"></span>
              </button>
              <div class="photo-hint">Необязательно, но поможет при проверке</div>
            </div>
          </template>

        </div>
      </div>

    </template>
  </div>

  {{-- ───── BOTTOM BAR ───── --}}
  <div class="test-bottom">
    <button class="test-btn-prev" :disabled="current === 0" @click="prev()">&#8249;</button>
    <button class="test-btn-next"
            :class="{ 'finish': current === total - 1 }"
            @click="next()">
      <span x-text="current === total - 1 ? 'Завершить тест' : 'Следующее'"></span>
      <span>&#8250;</span>
    </button>
  </div>

  {{-- ───── FINISH MODAL ───── --}}
  <div class="test-modal-overlay" :class="{ 'open': showModal }" @click.self="showModal = false">
    <div class="test-modal">
      <div class="test-modal-handle"></div>
      <div class="test-modal-title">Завершить тест?</div>
      <div class="test-modal-sub">
        <template x-if="answeredCount < total">
          <span>
            Ты ответил на <b x-text="answeredCount"></b> из <span x-text="total"></span> заданий.<br>
            Пропущенные будут засчитаны как без ответа.
          </span>
        </template>
        <template x-if="answeredCount >= total">
          <span>Все задания отвечены. Отправить на проверку?</span>
        </template>
      </div>
      <div class="test-modal-stats">
        <div class="test-modal-stat">
          <div class="test-modal-stat-val" x-text="answeredCount"></div>
          <div class="test-modal-stat-lbl">отвечено</div>
        </div>
        <div class="test-modal-stat">
          <div class="test-modal-stat-val" x-text="total - answeredCount"></div>
          <div class="test-modal-stat-lbl">пропущено</div>
        </div>
      </div>
      <button class="test-modal-confirm" @click="submitTest()" :disabled="submitting">
        <span x-text="submitting ? 'Отправка...' : 'Завершить и посмотреть результат'"></span>
      </button>
      <button class="test-modal-cancel" @click="showModal = false">Продолжить тест</button>
    </div>
  </div>

  @include('pwa.student.partials.image-viewer')

</div>
@endsection

@push('scripts')
<script>
  // Topic name mapping
  const TOPIC_NAMES = {
    '06': 'Дроби и степени',
    '07': 'Числа',
    '08': 'Корни и степени',
    '09': 'Уравнения',
    '10': 'Вероятность',
    '11': 'Графики',
    '12': 'Формулы',
    '13': 'Неравенства',
    '14': 'Прогрессии',
    '15': 'Треугольники',
    '16': 'Окружность',
    '17': 'Четырёхугольники',
    '18': 'Клетчатая бумага',
    '19': 'Высказывания',
  };

  function testApp() {
    return {
      // Data from server
      tasks: @json(collect($tasks)->values()),
      attemptId: @json($attempt->id),
      battleMode: @json($battleMode ?? false),

      // State
      current: 0,
      answers: @json($answers ?? (object)[]),
      photos: {},
      showModal: false,
      imageViewer: {
        open: false,
        src: '',
        inlineSvg: '',
        alt: '',
        landscape: false,
      },
      submitting: false,
      elapsed: 0,
      _timerInterval: null,
      _initialized: false,
      _hiddenAtMs: null,
      _lastAwayMs: 0,
      _returnedAtMs: null,
      battleStarted: false,

      // Computed
      get total() { return this.tasks.length; },
      get currentTask() { return this.tasks[this.current] || null; },
      get answeredCount() {
        return Object.values(this.answers).filter(v => v !== undefined && v !== '' && v !== null).length;
      },
      get progressPct() {
        return this.total > 0 ? Math.round((this.answeredCount / this.total) * 100) : 0;
      },
      get timerText() {
        const m = Math.floor(this.elapsed / 60);
        const s = this.elapsed % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      },

      // localStorage key for this attempt's draft answers
      get _draftKey() { return `oge_draft_${this.attemptId}`; },

      // Init
      init() {
        if (this._initialized) return;
        this._initialized = true;

        // Register page context for bug reports
        window.__bugContext = () => ({
          attempt_id: this.attemptId,
          current_task_number: this.currentTask?.display_task_number ?? null,
          current_task_index: this.current,
          topic_id: this.currentTask?.topic_id ?? null,
          task_key: this.currentTask ? (this.currentTask.topic_id + '_b' + this.currentTask.block_number + '_z' + this.currentTask.zadanie_number + (this.currentTask.task_index != null ? '_t' + this.currentTask.task_index : '')) : null,
        });

        // Restore draft answers from localStorage (survives page refresh)
        this._restoreDraft();

        // Auto-save draft to localStorage on any answer change
        this.$watch('answers', () => this._saveDraft(), { deep: true });

        // Warn before leaving with unsaved answers
        window.addEventListener('beforeunload', (e) => {
          if (this.answeredCount > 0 && !this.submitting) {
            e.preventDefault();
          }
        });

        // Visibility tracking for cheat detection
        document.addEventListener('visibilitychange', () => {
          if (document.hidden) {
            this._hiddenAtMs = Date.now();
          } else {
            this._lastAwayMs = this._hiddenAtMs ? Math.max(0, Date.now() - this._hiddenAtMs) : 0;
            this._returnedAtMs = Date.now();
            this._hiddenAtMs = null;
          }
        });

        // Paste tracking: detect "screenshot → AI → paste" pattern
        document.addEventListener('paste', async () => {
          if (!this.attemptId) return;
          const taskNum = this.currentTask?.task_number ?? null;
          const timeSinceReturn = this._returnedAtMs ? Date.now() - this._returnedAtMs : 0;
          try {
            await window.fetchPost(`/api/oge/attempts/${this.attemptId}/telemetry`, {
              event_type: 'answer_pasted',
              task_number: taskNum,
              payload: {
                away_ms_before: this._lastAwayMs,
                time_since_return_ms: timeSinceReturn,
              },
              client_ts: new Date().toISOString(),
            });
          } catch (_) { /* fire-and-forget */ }
        });

        // Start stopwatch only when allowed (battle mode waits for explicit start)
        if (!this.battleMode) {
          this.startTimer();
        }

        // Scroll active dot into view on init
        this.$nextTick(() => this.scrollActiveDot());

        // Render initial KaTeX (double-tick for x-for + x-html in options)
        this.$nextTick(() => {
          this.$nextTick(() => {
            document.fonts.ready.then(() => this.renderTaskMath());
          });
          // Fallback re-render after 1 second
          setTimeout(() => this.renderTaskMath(), 1000);
        });
      },

      _saveDraft() {
        try {
          localStorage.setItem(this._draftKey, JSON.stringify(this.answers));
        } catch (e) { /* quota exceeded — ignore */ }
      },

      _restoreDraft() {
        try {
          const saved = localStorage.getItem(this._draftKey);
          if (!saved) return;
          const draft = JSON.parse(saved);
          // Merge: draft fills in anything not already loaded from server
          for (const [k, v] of Object.entries(draft)) {
            if (!this.answers[k] || String(this.answers[k]).trim() === '') {
              this.answers[k] = v;
            }
          }
        } catch (e) { /* corrupted — ignore */ }
      },

      startTimer() {
        if (this._timerInterval) {
          clearInterval(this._timerInterval);
        }
        this._timerInterval = setInterval(() => { this.elapsed++; }, 1000);
      },

      startBattle() {
        this.battleStarted = true;
        this.startTimer();
      },

      normalizedOptions(task) {
        if (Array.isArray(task?.options) && task.options.length > 0) return task.options;
        // Topic 13: graph_options with SVG number lines as choices
        if (Array.isArray(task?.graph_options) && task.graph_options.length > 0) {
          return task.graph_options.map(go => ({
            id: String(go.index ?? ''),
            label: go.svg || go.text || '',
            text: go.text || '',
            _isSvg: !!go.svg,
            _isGraphOption: true,
          }));
        }
        return [];
      },

      optionAnswerValue(opt, idx) {
        // Graph options (topic 13): answer is the text value for scoring
        if (opt?._isGraphOption) {
          return opt.text || String(opt?.id ?? (idx + 1));
        }
        const id = String(opt?.id ?? '').trim();
        return id || String(idx + 1);
      },

      optionDisplayLabel(opt, idx) {
        const rawId = String(opt?.id ?? '').trim().toLowerCase();
        if (/^[a-z]$/.test(rawId)) {
          return this.optionLabelByIndex(rawId.charCodeAt(0) - 97);
        }
        const overflowMatch = rawId.match(/^o([1-9]\d*)$/);
        if (overflowMatch) {
          return overflowMatch[1];
        }
        if (/^[1-9]\d*$/.test(rawId)) {
          return rawId;
        }
        return this.optionLabelByIndex(idx);
      },

      optionLabelByIndex(idx) {
        const labels = ['А', 'Б', 'В', 'Г', 'Д', 'Е'];
        return labels[idx] || String(idx + 1);
      },

      optionHtml(opt) {
        // SVG option (graph_options from topic 13)
        if (opt?._isSvg && opt?.label?.startsWith('<svg')) {
          return opt.label;
        }
        const raw = String(opt?.label ?? opt?.text ?? opt ?? '').trim();
        if (!raw) return '';
        // Server pre-converts LaTeX to Unicode (√, π, etc.) so just return as-is.
        return raw;
      },

      formatRichText(value) {
        const raw = String(value || '');
        if (!raw) return '';
        // Render simple inline fractions like 7/12 with a horizontal bar.
        return raw.replace(/(^|[^\d])([+-]?\d{1,3})\s*\/\s*(\d{1,3})(?!\d)/g, (m, lead, a, b) => {
          return `${lead}<span class="inline-frac"><span class="top">${a}</span><span class="bottom">${b}</span></span>`;
        });
      },

      // Topic name helper
      taskKey(task) {
        const raw = Number(task?.attempt_task_number ?? task?.task_number ?? 0);
        return Number.isFinite(raw) && raw > 0 ? raw : 0;
      },

      displayTaskNumber(task) {
        const raw = Number(task?.display_task_number ?? task?.task_number ?? this.taskKey(task));
        return Number.isFinite(raw) && raw > 0 ? raw : this.taskKey(task);
      },

      taskLocator(t) {
        const tid = String(t?.topic_id ?? 'x').padStart(2, '0');
        const b = t?.block_number ?? 'x';
        const z = t?.zadanie_number ?? 'x';
        const i = t?.task_id ?? t?.id ?? t?.task?.id ?? 'x';
        return `t${tid}-b${b}-z${z}-i${i}`;
      },

      topicName(topicId) {
        const id = String(topicId).padStart(2, '0');
        return TOPIC_NAMES[id] || ('Тема ' + id);
      },

      resolveTaskImageSrc(task) {
        const raw = String(task?.image || '').trim();
        if (!raw) return '';
        if (/^https?:\/\//i.test(raw) || raw.startsWith('/')) return raw;
        const topicId = String(task?.topic_id ?? '').padStart(2, '0');
        return `/images/tasks/${topicId}/${raw}`;
      },

      canOpenImageViewer(task) {
        if (!task || task?.viewer_disabled) return false;

        const svg = String(task?.svg || '').trim();
        const image = String(task?.image || '').trim();

        return svg !== '' || image !== '';
      },

      isLandscapeViewer(task) {
        return String(task?.viewer_orientation || 'default') === 'landscape';
      },

      openImageViewer(task) {
        if (!this.canOpenImageViewer(task)) return;

        const svg = String(task?.svg || '').trim();
        const image = String(task?.image || '').trim();
        const inlineSvg = svg.startsWith('<svg')
          ? svg
          : (image.startsWith('<svg') ? image : '');

        this.imageViewer = {
          open: true,
          src: inlineSvg ? '' : this.resolveTaskImageSrc(task),
          inlineSvg,
          alt: String(task?.instruction || task?.text || 'Изображение задания').trim(),
          landscape: this.isLandscapeViewer(task),
        };

        document.body.style.overflow = 'hidden';
      },

      closeImageViewer() {
        this.imageViewer.open = false;
        document.body.style.overflow = '';
      },

      // Navigation
      goTo(idx) {
        this.current = idx;
        this.scrollActiveDot();
        this.$nextTick(() => {
          this.renderTaskMath();
          this.focusInput();
          this.$refs.questionArea?.scrollTo({ top: 0 });
          // Second pass: x-for + x-html in options may need an extra tick.
          this.$nextTick(() => this.renderTaskMath());
        });
      },

      prev() {
        if (this.current > 0) this.goTo(this.current - 1);
      },

      next() {
        if (this.current === this.total - 1) {
          this.showModal = true;
          return;
        }
        this.goTo(this.current + 1);
      },

      scrollActiveDot() {
        this.$nextTick(() => {
          const nav = this.$refs.navScroll;
          if (!nav) return;
          const dots = nav.querySelectorAll('.test-dot');
          const active = dots[this.current];
          if (active) {
            active.scrollIntoView({ inline: 'center', behavior: 'smooth', block: 'nearest' });
          }
        });
      },

      focusInput() {
        this.$nextTick(() => {
          const task = this.currentTask;
          if (!task) return;
          const isChoiceType = ['choice', 'simple_choice', 'fraction_choice', 'interval_choice'].includes(task.type);
          if (!isChoiceType && !('ontouchstart' in window)) {
            setTimeout(() => {
              // Find the visible input in the question area (desktop only — on touch devices, auto-focus opens keyboard immediately)
              const inp = this.$refs.questionArea?.querySelector('.answer-input');
              if (inp) inp.focus();
            }, 200);
          }
        });
      },

      // Answer handling
      selectOption(opt, idx) {
        const tn = this.taskKey(this.currentTask);
        this.answers[tn] = this.optionAnswerValue(opt, idx);
      },

      // Submit test — sends all answers in one request, no intermediate commits
      async submitTest() {
        if (this.submitting) return;
        this.submitting = true;

        // Collect all non-empty answers
        const finalAnswers = {};
        for (const [taskNum, answer] of Object.entries(this.answers)) {
          const trimmed = String(answer ?? '').trim();
          if (trimmed !== '') {
            finalAnswers[taskNum] = trimmed;
          }
        }

        try {
          const res = await window.fetchPost(`/api/oge/attempts/${this.attemptId}/submit`, {
            answers: finalAnswers,
            elapsed: this.elapsed,
          });
          const data = await res.json();
          if (res.ok) {
            try { localStorage.removeItem(this._draftKey); } catch (e) {}
            window.location.href = `/results/${this.attemptId}`;
          } else {
            alert(data.message || 'Ошибка при отправке');
            this.submitting = false;
          }
        } catch (e) {
          alert('Ошибка сети. Попробуй ещё раз.');
          this.submitting = false;
        }
      },

      // Photo upload for Part 2
      async uploadPhoto(event) {
        const file = event.target.files?.[0];
        if (!file) return;
        const tn = this.taskKey(this.currentTask);
        const formData = new FormData();
        formData.append('photo', file);
        try {
          const res = await fetch(`/api/oge/attempts/${this.attemptId}/tasks/${tn}/photo`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: formData,
          });
          if (res.ok) {
            this.photos[tn] = true;
          } else {
            alert('Ошибка загрузки фото');
          }
        } catch (e) {
          alert('Ошибка сети при загрузке фото');
        }
        event.target.value = '';
      },

      // Exit confirmation
      confirmExit() {
        if (this.answeredCount > 0) {
          if (confirm('Выйти из теста? Ответы будут сохранены при завершении теста.')) {
            window.location.href = '{{ route("pwa.student.dashboard") }}';
          }
        } else {
          window.location.href = '{{ route("pwa.student.dashboard") }}';
        }
      },

      // KaTeX rendering
      renderTaskMath() {
        if (typeof renderMathInElement !== 'function') return;
        const area = this.$refs.questionArea;
        if (!area) return;
        try {
          renderMathInElement(area, {
            delimiters: [
              { left: '$$', right: '$$', display: true },
              { left: '$', right: '$', display: false },
              { left: '\\(', right: '\\)', display: false },
              { left: '\\[', right: '\\]', display: true },
            ],
            throwOnError: false,
            trust: true,
          });
        } catch (e) {
          console.warn('KaTeX render error:', e);
        }
      },

      // Cleanup
      destroy() {
        if (this._timerInterval) clearInterval(this._timerInterval);
      },
    };
  }
</script>
@endpush
