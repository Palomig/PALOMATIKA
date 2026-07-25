@extends('layouts.pwa')
@section('title', 'Урок — palomatika')

@push('katex')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/contrib/auto-render.min.js"></script>
@endpush

@push('styles')
  .lesson-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 16px; display: flex; flex-direction: column; gap: 10px; }
  /* Picker как отдельный полноэкранный экран — не видно уже добавленных задач */
  .picker-overlay { position: fixed; inset: 0; z-index: 1000; background: var(--bg); overflow-y: auto; padding: 16px calc(16px + var(--safe-right, 0px)) calc(24px + var(--safe-bottom, 0px)) calc(16px + var(--safe-left, 0px)); }
  .picker-overlay-inner { max-width: 640px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
  .picker-overlay-head { position: sticky; top: -16px; z-index: 1; background: var(--bg); padding: 8px 0; margin: -8px 0 0; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid var(--border); }
  .picker-overlay-head .title { font-size: 16px; font-weight: 700; color: var(--text); }
  .lesson-task { display: flex; gap: 10px; align-items: flex-start; padding: 10px; background: var(--surface2); border-radius: 10px; }
  .lesson-task-num { font-weight: 800; color: var(--accent); width: 24px; flex-shrink: 0; }
  .lesson-task-body { flex: 1; min-width: 0; }
  .lesson-task-expr { font-size: 15px; color: var(--text); margin-bottom: 4px; word-break: break-word; }
  .lesson-task-image { display: flex; justify-content: center; background: var(--surface); border-radius: 8px; padding: 8px; margin-bottom: 8px; }
  .lesson-task-image svg, .lesson-task-image img { max-width: 250px; width: 100%; height: auto; max-height: 220px; }
  .lesson-task-options { display: flex; flex-wrap: wrap; gap: 6px; margin: 6px 0; }
  .lesson-task-option { padding: 3px 9px; border: 1px solid var(--border); border-radius: 8px; font-size: 12px; color: var(--muted); }
  .lesson-task-meta { font-size: 11px; color: var(--muted); }
  .lesson-task-answer { font-family: ui-monospace, monospace; color: var(--green); font-weight: 700; font-size: 13px; }
  .picker-row { display: flex; gap: 8px; flex-wrap: wrap; }
  .picker-row select, .picker-row input { background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; padding: 8px 10px; font-size: 13px; min-width: 90px; }
  .picker-group-label { font-size: 11px; color: var(--muted); margin: 12px 0 6px; text-transform: uppercase; letter-spacing: 0.04em; }
  .picker-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px; }
  .picker-card { background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 10px; cursor: pointer; user-select: none; display: flex; flex-direction: column; gap: 6px; transition: border-color .12s, background .12s; }
  .picker-card:hover { border-color: var(--accent-bd); }
  .picker-card.active { background: var(--accent-bg); border-color: var(--accent); }
  .picker-card-expr { font-size: 13px; color: var(--text); word-break: break-word; line-height: 1.3; }
  .picker-card-meta { font-size: 11px; color: var(--muted); display: flex; justify-content: space-between; gap: 8px; }
  .picker-card-answer { font-family: ui-monospace, monospace; color: var(--green); font-weight: 700; }
  .picker-card-image { width: 100%; max-height: 140px; display: flex; align-items: center; justify-content: center; background: var(--surface); border-radius: 6px; overflow: hidden; }
  .picker-card-image svg { max-width: 100%; max-height: 140px; height: auto; }
  .code-block { background: var(--accent-bg); border: 1px solid var(--accent-bd); border-radius: 10px; padding: 12px; display: flex; flex-direction: column; gap: 8px; }
  .join-code { font-family: ui-monospace, monospace; font-size: 48px; letter-spacing: 8px; font-weight: 800; color: var(--text); text-align: center; user-select: all; }
  .participant-chips { display: flex; flex-wrap: wrap; gap: 6px; }
  .participant-chip { display: inline-flex; align-items: center; gap: 6px; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; padding: 4px 8px; font-size: 12px; color: var(--text); }
  .chip-release { background: none; border: none; color: var(--muted); cursor: pointer; font-size: 11px; padding: 0 2px; }
  .chip-release:hover { color: var(--red); }
  .chip-name { display: inline-flex; align-items: center; gap: 4px; background: none; border: none; padding: 0; font: inherit; color: var(--text); cursor: pointer; }
  .chip-name:hover { color: var(--accent); }
  .chip-notes { font-size: 10px; color: var(--muted); }
  /* Просмотр заметок ученика */
  .sn-item { padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; }
  .sn-item.current { border-color: var(--accent-bd); }
  .sn-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 11px; color: var(--muted); margin-bottom: 4px; }
  .sn-tag { background: var(--surface); border: 1px solid var(--border); border-radius: 6px; padding: 1px 6px; }
  .sn-now { color: var(--accent); font-weight: 700; }
  .sn-body { font-size: 14px; line-height: 1.5; color: var(--text); white-space: pre-wrap; }
  .sn-list { display: flex; flex-direction: column; gap: 8px; flex: 1 1 auto; }
  .note-input { width: 100%; resize: vertical; min-height: 48px; padding: 10px 12px; font-size: 13px; line-height: 1.5; font-family: inherit; background: var(--surface2); color: var(--text); border: 1px solid var(--border); border-radius: 10px; }
  .note-input:focus { outline: none; border-color: var(--accent-bd); }
  .activity-meta { font-size: 10px; color: var(--muted); margin-top: 3px; white-space: nowrap; }
  .assign-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
  .assign-label { font-size: 12px; font-weight: 700; color: var(--muted); }
  .assign-select { flex: 1; padding: 9px 10px; font-size: 13px; background: var(--surface2); color: var(--text); border: 1px solid var(--border); border-radius: 8px; }
  .personal-badge { display: inline-block; margin-top: 3px; font-size: 10px; font-weight: 800; padding: 1px 7px; border-radius: 6px; background: var(--purple-bg); color: var(--purple); border: 1px solid var(--purple-bd); white-space: nowrap; }
  .live-cell-na { background: var(--surface2); color: var(--muted); text-align: center; }
  .btn-row { display: flex; gap: 8px; flex-wrap: wrap; }
  .btn { padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; border: 1px solid var(--border); background: var(--surface2); color: var(--text); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
  .btn-primary { background: var(--accent); border-color: var(--accent); color: white; }
  .btn-danger { background: var(--red-bg); border-color: var(--red-bd); color: var(--red); }
  .btn-icon { padding: 6px 9px; font-size: 12px; }
  .status-row { display: flex; gap: 8px; align-items: center; font-size: 13px; }
  .status-badge-draft { background: var(--yellow-bg); border: 1px solid var(--yellow-bd); color: var(--yellow); padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
  .status-badge-live { background: var(--green-bg); border: 1px solid var(--green-bd); color: var(--green); padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
  .status-badge-ended { background: var(--red-bg); border: 1px solid var(--red-bd); color: var(--red); padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
  .live-grid { width: 100%; border-collapse: collapse; font-size: 12px; }
  .live-grid th, .live-grid td { border: 1px solid var(--border); padding: 8px; text-align: left; vertical-align: top; }
  .live-grid th { background: var(--surface2); color: var(--muted); font-weight: 700; }
  .live-cell-ok { background: var(--green-bg); color: var(--green); }
  .live-cell-bad { background: var(--red-bg); color: var(--red); }
  .live-cell-empty { color: var(--muted); }
  /* 📝 Заметки — шторка снизу */
  /* Попап заметок — на весь экран */
  .ns-overlay { position: fixed; inset: 0; z-index: 1000; background: var(--bg); display: flex; align-items: stretch; justify-content: center; }
  .ns-sheet { background: var(--bg); width: 100%; max-width: 560px; height: 100%; max-height: 100dvh; padding: calc(16px + var(--safe-top, 0px)) calc(18px + var(--safe-right, 0px)) calc(24px + var(--safe-bottom, 0px)) calc(18px + var(--safe-left, 0px)); overflow-y: auto; display: flex; flex-direction: column; gap: 14px; }
  .ns-handle { display: none; }
  .ns-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; position: sticky; top: calc(-16px - var(--safe-top, 0px)); background: var(--bg); padding: 4px 0 8px; margin-top: -4px; border-bottom: 1px solid var(--border); }
  .ns-title { font-size: 19px; font-weight: 800; color: var(--text); }
  .ns-close { background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 10px; font-size: 16px; line-height: 1; padding: 8px 12px; cursor: pointer; flex-shrink: 0; }
  .ns-close:hover { border-color: var(--accent-bd); }
  .ns-toggle-all { background: var(--surface2); border: 1px solid var(--border); color: var(--muted); border-radius: 8px; font-size: 12px; font-weight: 700; padding: 6px 10px; cursor: pointer; }
  .ns-toggle-all:hover { color: var(--text); border-color: var(--accent-bd); }
  .ns-sub { display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 12px; font-weight: 700; color: var(--muted); }
  .ns-students { display: flex; flex-direction: column; gap: 6px; }
  .ns-actions { display: flex; flex-direction: column; gap: 6px; position: sticky; bottom: 0; background: var(--bg); padding-top: 8px; }
  .ns-student { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; cursor: pointer; user-select: none; transition: border-color .12s, background .12s; }
  .ns-student.active { background: var(--accent-bg); border-color: var(--accent); }
  .ns-student input[type=checkbox] { width: 18px; height: 18px; flex-shrink: 0; accent-color: var(--accent); cursor: pointer; }
  .ns-student-name { font-size: 14px; color: var(--text); }
  .ns-empty { font-size: 13px; color: var(--muted); padding: 8px 4px; }
  .ns-textarea { width: 100%; flex: 1 1 auto; resize: vertical; min-height: 240px; padding: 14px; font-size: 15px; line-height: 1.55; font-family: inherit; background: var(--surface2); color: var(--text); border: 1px solid var(--border); border-radius: 12px; }
  .ns-textarea:focus { outline: none; border-color: var(--accent-bd); }
  .ns-btn { display: block; width: 100%; padding: 14px; border: none; border-radius: 14px; font-size: 15px; font-weight: 800; cursor: pointer; text-align: center; background: var(--accent); color: #fff; }
  .ns-btn:disabled { opacity: .5; cursor: default; }
  .ns-cancel { display: block; width: 100%; padding: 12px; background: none; border: none; color: var(--muted); font-size: 14px; font-weight: 700; cursor: pointer; }
  .notes-toast { position: fixed; left: 50%; bottom: calc(20px + var(--safe-bottom, 0px)); transform: translateX(-50%); z-index: 200; background: var(--surface); border: 1px solid var(--green-bd); color: var(--green); padding: 10px 16px; border-radius: 12px; font-size: 13px; font-weight: 700; box-shadow: 0 4px 20px rgba(0,0,0,.4); max-width: 90vw; text-align: center; }
  /* «не понимает» в live-гриде */
  .du-btn { background: var(--surface2); border: 1px solid var(--border); color: var(--muted); border-radius: 6px; font-size: 10px; padding: 2px 6px; cursor: pointer; font-weight: 700; white-space: nowrap; }
  .du-btn:hover { color: var(--red); border-color: var(--red-bd); }
  .du-pick { position: absolute; top: 100%; left: 0; z-index: 20; margin-top: 3px; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 4px; display: flex; flex-direction: column; gap: 2px; min-width: 120px; box-shadow: 0 4px 16px rgba(0,0,0,0.3); }
  .du-pick-item { background: none; border: none; color: var(--text); text-align: left; font-size: 12px; padding: 6px 8px; border-radius: 6px; cursor: pointer; white-space: nowrap; }
  .du-pick-item:hover { background: var(--accent-bg); }
  .du-done { font-size: 10px; color: var(--green); font-weight: 700; margin-top: 3px; }
  /* 📚 Домашка по уроку */
  .hw-muted { color: var(--muted); font-size: 12px; font-weight: 700; }
  .hw-prior { background: var(--accent-bg); border: 1px solid var(--accent-bd); border-radius: 10px; padding: 10px 12px; font-size: 13px; color: var(--text); display: flex; flex-direction: column; gap: 4px; }
  .hw-group { border: 1px solid var(--border); border-radius: 12px; padding: 12px; margin-bottom: 10px; display: flex; flex-direction: column; gap: 8px; }
  .hw-group-head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
  .hw-group-label { font-size: 14px; font-weight: 800; color: var(--text); }
  .hw-cards { display: flex; flex-direction: column; gap: 6px; }
  .hw-card { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; cursor: pointer; transition: border-color .12s, background .12s; }
  .hw-card.active { background: var(--accent-bg); border-color: var(--accent); }
  .hw-card input[type=checkbox] { width: 18px; height: 18px; flex-shrink: 0; margin-top: 2px; accent-color: var(--accent); cursor: pointer; }
  .hw-card-body { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
  .hw-card-svg { display: block; max-width: 160px; }
  .hw-card-svg :is(svg, img) { max-width: 100%; height: auto; }
  .hw-card-text { font-size: 14px; color: var(--text); word-break: break-word; }
  .hw-deadline { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); font-weight: 700; margin-top: 10px; }
  .hw-deadline input { background: var(--surface2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; padding: 8px 10px; font-size: 14px; }
@endpush

@section('body')
<div class="page" x-data="lessonPrep({{ $session->id }}, '{{ $session->status }}')" x-init="init()" @picker-add.window="onPickerAdd($event.detail.items)">
  <div class="topbar">
    <a href="{{ route('pwa.teacher.lessons') }}" class="back-btn">‹</a>
    <div class="topbar-title">Урок #{{ $session->id }}</div>
    <span :class="'status-badge-' + status" x-text="statusLabel(status)" class="status-badge-{{ $session->status }}">{{ $session->status }}</span>
  </div>

  {{-- Код входа (draft и live) --}}
  <template x-if="status !== 'ended' && joinCode">
    <div class="code-block">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div style="font-size: 13px; font-weight: 800; color: var(--text);">🔑 Код урока</div>
        <div style="font-size: 11px; color: var(--muted);" x-text="`${participants.length} в уроке`"></div>
      </div>
      <div class="join-code" x-text="joinCode"></div>
      <div style="font-size: 11px; color: var(--muted); line-height: 1.5;">
        Продиктуй код ученикам: они вводят его на своей странице «Урок» и попадают сюда.
        После входа ученик остаётся на странице урока 60 минут — отпустить раньше можно кнопкой ✕ у имени.
      </div>
      <template x-if="participants.length">
        <div class="participant-chips">
          <template x-for="p in participants" :key="'chip-' + p.id">
            <span class="participant-chip">
              <span x-text="activityDot(p)" :title="activityTitle(p)"></span>
              <button type="button" class="chip-name" @click="openStudentNotes(p)"
                      :title="'Заметки: ' + (p.name || ('#' + p.id))">
                <span x-text="p.name || ('#' + p.id)"></span>
                <span class="chip-notes" x-show="p.notes_count" x-text="'📝' + p.notes_count"></span>
              </button>
              <span x-show="p.locked" title="Лок активен">🔒</span>
              <button type="button" class="chip-release" x-show="p.locked"
                      @click="releaseStudent(p.id)" title="Отпустить с урока">✕ отпустить</button>
            </span>
          </template>
        </div>
      </template>
    </div>
  </template>

  {{-- 📝 Заметки об учениках (ученики не видят) --}}
  <button class="btn btn-primary" @click="openNotes()" style="align-self: flex-start;">📝 Заметки</button>

  {{-- Полноэкранный попап: заметка об учениках --}}
  <div class="ns-overlay" x-show="notesOpen" x-cloak>
    <div class="ns-sheet">
      <div class="ns-head">
        <span class="ns-title">📝 Заметка об учениках</span>
        <button type="button" class="ns-close" @click="notesOpen = false" aria-label="Закрыть">✕</button>
      </div>

      <div class="ns-sub">
        <span x-text="'Выбрано: ' + noteStudentIds.length"></span>
        <button type="button" class="ns-toggle-all" @click="toggleAllNoteStudents()"
                x-text="noteStudentIds.length === participants.length && participants.length ? 'Снять всех' : 'Выбрать всех'"></button>
      </div>

      <template x-if="participants.length">
        <div class="ns-students">
          <template x-for="p in participants" :key="'note-' + p.id">
            <label class="ns-student" :class="isNoteStudentSelected(p.id) ? 'active' : ''">
              <input type="checkbox" :checked="!!isNoteStudentSelected(p.id)" @change="toggleNoteStudent(p.id)">
              <span class="ns-student-name" x-text="p.name || ('#' + p.id)"></span>
            </label>
          </template>
        </div>
      </template>
      <div class="ns-empty" x-show="!participants.length">В уроке пока нет учеников.</div>

      <textarea class="ns-textarea" x-model="noteText"
                placeholder="Что заметил? Например: путается в раскрытии скобок, но хорошо считает в уме."></textarea>

      <div class="ns-actions">
        <button class="ns-btn" @click="submitNote()"
                :disabled="!!(!noteStudentIds.length || !noteText.trim() || noteSending)"
                x-text="noteSending ? 'Сохраняю…' : 'Отправить'"></button>
        <button type="button" class="ns-cancel" @click="notesOpen = false">Отмена</button>
      </div>
    </div>
  </div>

  {{-- Просмотр заметок конкретного ученика (тап по имени в чипе) --}}
  <div class="ns-overlay" x-show="viewOpen" x-cloak>
    <div class="ns-sheet">
      <div class="ns-head">
        <span class="ns-title" x-text="'📝 ' + (viewStudent ? viewStudent.name : '')"></span>
        <button type="button" class="ns-close" @click="viewOpen = false" aria-label="Закрыть">✕</button>
      </div>

      <div class="ns-empty" x-show="viewLoading">Загружаю…</div>
      <div class="ns-empty" x-show="viewError" x-cloak style="color: var(--red);" x-text="viewError"></div>
      <div class="ns-empty" x-show="!viewLoading && !viewError && !viewNotes.length">Заметок пока нет.</div>

      <div class="sn-list" x-show="!viewLoading && viewNotes.length">
        <template x-for="n in viewNotes" :key="'sn-' + n.id">
          <div class="sn-item" :class="n.is_current_lesson ? 'current' : ''">
            <div class="sn-meta">
              <span x-text="noteBadge(n.kind)" :title="n.kind"></span>
              <span x-text="n.created_at"></span>
              <span x-show="n.is_current_lesson" class="sn-now">этот урок</span>
              <template x-if="n.topic_tag">
                <span class="sn-tag" x-text="n.topic_tag"></span>
              </template>
            </div>
            <div class="sn-body" x-text="n.body"></div>
          </div>
        </template>
      </div>

      <div class="ns-actions">
        <button class="ns-btn" @click="addNoteForViewed()"
                x-text="'＋ Заметка про ' + (viewStudent ? viewStudent.name.split(' ')[0] : '')"></button>
        <button type="button" class="ns-cancel" @click="viewOpen = false">Закрыть</button>
      </div>
    </div>
  </div>

  {{-- Тост после сохранения заметки --}}
  <div class="notes-toast" x-show="noteToast" x-cloak x-text="noteToast"></div>

  {{-- Tasks list --}}
  <div class="lesson-card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <div style="font-size: 14px; font-weight: 700;">Задачи (<span x-text="tasks.length"></span>)</div>
      <button class="btn btn-icon" @click="pickerOpen = !pickerOpen" x-show="status !== 'ended'">+ Добавить</button>
    </div>

    {{-- Полный вид задания — как у ученика: картинка + текст + варианты --}}
    <template x-for="task in tasks" :key="task.id">
      <div class="lesson-task">
        <div class="lesson-task-num" x-text="task.position + ')'"></div>
        <div class="lesson-task-body">
          <div class="lesson-task-image" x-show="task.task_payload.image_svg" x-html="task.task_payload.image_svg"></div>
          <template x-if="!task.task_payload.image_svg && task.task_payload.image_url">
            <div class="lesson-task-image"><img :src="task.task_payload.image_url" alt=""></div>
          </template>
          <div class="lesson-task-expr" x-html="renderLatex(task.task_payload.expression)"></div>
          <template x-if="task.task_payload.type === 'choice'">
            <div class="lesson-task-options">
              <template x-for="(opt, oi) in task.task_payload.options" :key="opt.id">
                <span class="lesson-task-option" x-html="renderLatex(opt.label)"></span>
              </template>
            </div>
          </template>
          <div class="lesson-task-meta">
            <span x-text="task.bank"></span>
            · Ответ: <span class="lesson-task-answer" x-text="task.correct_answer || '(без автопроверки)'"></span>
            <span class="personal-badge" x-show="task.assigned_student_id"
                  x-text="'для ' + (task.assigned_name || '#' + task.assigned_student_id)"></span>
          </div>
        </div>
        <button class="btn btn-icon btn-danger" x-show="status === 'draft'" @click="removeTask(task.id)">×</button>
      </div>
    </template>

    <div x-show="tasks.length === 0" style="color: var(--muted); font-size: 13px; padding: 12px; text-align: center;">
      Пока ни одной задачи. Жми «Добавить» — выбери из банка alg-skill 7 класса.
    </div>
  </div>

  {{-- Task picker — отдельный полноэкранный экран выбора задач --}}
  <div class="picker-overlay" x-show="pickerOpen" x-cloak>
    <div class="picker-overlay-inner">
      <div class="picker-overlay-head">
        <span class="title">Выбор задач</span>
        <button class="btn" @click="pickerOpen = false">✕ Закрыть</button>
      </div>
      {{-- Кому добавляем: всем или персонально участнику --}}
      <div class="assign-row" x-show="participants.length">
        <span class="assign-label">Кому:</span>
        <select class="assign-select" x-model="assignTo">
          <option value="">Всем классу</option>
          <template x-for="p in participants" :key="'assign-' + p.id">
            <option :value="p.id" x-text="p.name || ('#' + p.id)"></option>
          </template>
        </select>
      </div>
      <div x-data="taskPicker({
            onAdd: (items) => $dispatch('picker-add', { items }),
            existingUids: () => tasks.map(t => t.uid).filter(Boolean),
          })">
        @include('pwa._shared.task-picker')
      </div>
    </div>
  </div>

  {{-- Action buttons --}}
  <div class="btn-row">
    <button class="btn btn-primary" x-show="status === 'draft'" @click="startLesson" :disabled="tasks.length === 0">▶ Запустить</button>
    <button class="btn btn-danger" x-show="status === 'live'" @click="endLesson">■ Завершить</button>
    <button class="btn" @click="openHomework()" :disabled="tasks.length === 0"
            title="Предложить ученикам аналогичные задачи как домашку">📚 Домашка по уроку</button>
    <button class="btn" :disabled="creatingNext" @click="createNextLesson"
            x-text="creatingNext ? 'создаём…' : '📅 Следующий урок'"
            title="Черновик на то же время через неделю — с заметкой и заданиями заранее"></button>
  </div>

  {{-- 📚 Домашка по итогам урока — аналоги разобранных задач --}}
  <div class="ns-overlay" x-show="hwOpen" x-cloak>
    <form method="POST" action="{{ route('pwa.teacher.homework.assign') }}" class="ns-sheet" @submit="hwSubmitting = true">
      @csrf
      <input type="hidden" name="type" value="topic_photo_practice">
      <input type="hidden" name="lesson_session_id" :value="sessionId">
      <input type="hidden" name="title" :value="hwTitle()">
      <input type="hidden" name="picker_tasks" :value="hwPickerTasksJson()">
      <template x-for="sid in hwSelectedStudents" :key="'hw-sid-' + sid">
        <input type="hidden" name="student_ids[]" :value="sid">
      </template>

      <div class="ns-head">
        <span class="ns-title">📚 Домашка по уроку</span>
        <button type="button" class="ns-close" @click="hwOpen = false" aria-label="Закрыть">✕</button>
      </div>

      <div class="hw-prior" x-show="hwPrior.length" x-cloak>
        <template x-for="h in hwPrior" :key="'prior-' + h.id">
          <div>По этому уроку уже отправлялось ДЗ: <b x-text="h.title"></b> <span class="hw-muted" x-text="h.date"></span></div>
        </template>
      </div>

      <div x-show="hwLoading" class="hw-muted" style="padding: 12px 0;">Подбираю аналоги…</div>

      <template x-if="!hwLoading">
        <div>
          <div class="ns-sub">
            <span x-text="'Выбрано задач: ' + hwSelectedCount()"></span>
            <span style="display: flex; gap: 8px;">
              <button type="button" class="ns-toggle-all" @click="hwPickTwoEach()">По 2 в каждой</button>
              <button type="button" class="ns-toggle-all" @click="hwClear()">Снять всё</button>
            </span>
          </div>

          <div x-show="!hwGroups.length" class="hw-muted" style="padding: 12px 0;">
            Для задач этого урока аналогов не нашлось.
          </div>

          <template x-for="g in hwGroups" :key="g.key">
            <div class="hw-group">
              <div class="hw-group-head">
                <span class="hw-group-label" x-text="g.label"></span>
                <span class="hw-muted" x-text="'на уроке: ' + g.lesson_stats.task_count + ', решено ' + g.lesson_stats.solved"></span>
              </div>
              <div x-show="g.no_analogs" class="hw-muted">аналогов нет</div>
              <div class="hw-cards" x-show="!g.no_analogs">
                <template x-for="(s, si) in g.suggestions" :key="g.key + '-' + si">
                  <label class="hw-card" :class="hwIsSelected(s) ? 'active' : ''">
                    <input type="checkbox" :checked="hwIsSelected(s)" @change="hwToggle(s)">
                    <span class="hw-card-body">
                      <span x-show="s.preview_svg" x-html="s.preview_svg" class="hw-card-svg"></span>
                      <span class="hw-card-text" x-html="renderLatex(s.preview_text)"></span>
                    </span>
                  </label>
                </template>
              </div>
            </div>
          </template>

          <div class="ns-sub" style="margin-top: 8px;">
            <span x-text="'Кому: ' + hwSelectedStudents.length"></span>
          </div>
          <div class="ns-students">
            <template x-for="p in hwStudents" :key="'hw-st-' + p.id">
              <label class="ns-student" :class="hwSelectedStudents.includes(p.id) ? 'active' : ''">
                <input type="checkbox" :checked="hwSelectedStudents.includes(p.id)" @change="hwToggleStudent(p.id)">
                <span class="ns-student-name" x-text="(p.name || ('#' + p.id)) + (p.participant ? '' : ' · вне урока')"></span>
              </label>
            </template>
          </div>
          <div class="hw-muted" x-show="!hwStudents.length" style="padding: 8px 0;">Нет учеников для назначения.</div>

          <label class="hw-deadline">
            Срок (необязательно):
            <input type="date" name="deadline" x-model="hwDeadline">
          </label>
        </div>
      </template>

      <div class="ns-actions">
        <button type="submit" class="ns-btn"
                :disabled="hwSubmitting || hwSelectedCount() === 0 || hwSelectedStudents.length === 0"
                x-text="hwSubmitting ? 'Отправляю…' : 'Отправить домашку'"></button>
        <button type="button" class="ns-cancel" @click="hwOpen = false">Отмена</button>
      </div>
    </form>
  </div>

  {{-- Live grid + summary (после завершения остаётся как итоги урока) --}}
  <div class="lesson-card" x-show="(status === 'live' || status === 'ended') && tasks.length">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <div style="font-size: 14px; font-weight: 700;" x-text="status === 'ended' ? 'Итоги урока' : 'Ответы'"></div>
      <div style="font-size: 11px; color: var(--muted);" x-show="status === 'live'">обновляется каждые 4 сек</div>
    </div>

    <div style="overflow-x: auto;">
      <table class="live-grid">
        <thead>
          <tr>
            <th>Ученик</th>
            <template x-for="t in tasks" :key="t.id">
              <th>
                <div style="font-weight: 800;" x-text="t.position + ')'"></div>
                <div style="font-size: 11px; color: var(--text);" x-html="headerHtml(t.task_payload.expression)"></div>
                <div style="color: var(--green); font-family: monospace;" x-text="t.correct_answer"></div>
                <div class="personal-badge" x-show="t.assigned_student_id"
                     x-text="'для ' + (t.assigned_name || '#' + t.assigned_student_id)"></div>
                {{-- «не понимает» — только в live: раскрывает выбор ученика под кнопкой --}}
                <div x-show="status === 'live'" style="position: relative; margin-top: 4px; font-weight: 400;">
                  <button type="button" class="du-btn" @click="toggleDu(t.id)"
                          x-text="duFor === t.id ? '✕ отмена' : 'не понимает'"></button>
                  <div class="du-pick" x-show="duFor === t.id" x-cloak>
                    <template x-for="p in participants" :key="'du-' + t.id + '-' + p.id">
                      <button type="button" class="du-pick-item" @click="dontUnderstand(t.id, p.id)"
                              x-text="p.name || ('#' + p.id)"></button>
                    </template>
                    <div x-show="!participants.length" style="color: var(--muted); font-size: 11px; padding: 4px;">нет учеников</div>
                  </div>
                  <div class="du-done" x-show="duDone === (t.id + '-done')" x-cloak>записано ✓</div>
                </div>
              </th>
            </template>
          </tr>
        </thead>
        <tbody>
          <template x-for="p in participants" :key="p.id">
            <tr>
              <td>
                <div><span x-text="activityDot(p)"></span> <span x-text="p.name || ('#' + p.id)"></span></div>
                <div class="activity-meta" x-show="p.activity" x-text="activityMeta(p)"></div>
              </td>
              <template x-for="t in tasks" :key="t.id">
                <td :class="cellClass(p.id, t.id)" x-text="cellLabel(p.id, t.id)"></td>
              </template>
              {{-- см. cellClass/cellLabel: персональная задача не для этого ученика → серый «·» --}}
            </tr>
          </template>
          {{-- Summary row: % правильных по задаче --}}
          <tr style="background: var(--surface2);">
            <td style="font-weight: 700; color: var(--muted);">% верно</td>
            <template x-for="t in tasks" :key="'sum-' + t.id">
              <td :style="`color: ${taskCorrectPct(t.id) >= 70 ? 'var(--green)' : (taskCorrectPct(t.id) >= 40 ? 'var(--yellow)' : 'var(--red)')}; font-weight: 700;`"
                  x-text="taskAnsweredCount(t.id) ? `${taskCorrectPct(t.id)}% (${taskCorrectCount(t.id)}/${taskAnsweredCount(t.id)})` : '—'"></td>
            </template>
          </tr>
        </tbody>
      </table>
    </div>

    {{-- Легенда сигналов списывания (видна только когда есть флаги) --}}
    <template x-if="hasBehaviorFlags">
      <div style="font-size: 11px; color: var(--muted); padding-top: 6px;">
        📥 — вставил ответ из буфера · ⚡ — ответил в первые секунды после возврата на страницу
      </div>
    </template>

    {{-- Кто не ответил вообще ни на одну задачу --}}
    <template x-if="silentStudents.length">
      <div style="font-size: 12px; color: var(--muted); padding-top: 8px; border-top: 1px solid var(--border);">
        Не отвечают: <span style="color: var(--red); font-weight: 700;" x-text="silentStudents.map(p => p.name || '#'+p.id).join(', ')"></span>
      </div>
    </template>
  </div>
</div>

<script>
  function lessonPrep(sessionId, initialStatus) {
    // Вне reactive-состояния (запись во время рендера не должна триггерить эффекты)
    let typesetTimer = null;
    let tasksJson = '';
    let noteLoaded = false; // заметку берём из state один раз, чтобы poll не затирал ввод

    return {
      sessionId,
      status: initialStatus,
      joinCode: null,
      tasks: [],
      participants: [],
      grid: {},
      pickerOpen: false,
      assignTo: '',        // '' = всем; id участника = персональная задача
      pollTimer: null,
      katexReady: false,
      note: '',
      noteSaved: false,
      creatingNext: false,
      // 📝 Заметки об учениках
      notesOpen: false,
      // Просмотр истории заметок одного ученика
      viewOpen: false,
      viewStudent: null,
      viewNotes: [],
      viewLoading: false,
      viewError: '',
      noteText: '',
      noteStudentIds: [],
      noteSending: false,
      noteToast: '',
      // «не понимает» в live-гриде
      duFor: null,
      duDone: null,
      // 📚 Домашка по уроку
      hwOpen: false,
      hwLoading: false,
      hwSubmitting: false,
      hwGroups: [],
      hwStudents: [],           // [{id, name, participant}]
      hwPrior: [],
      hwSelectedKeys: [],       // ключи выбранных задач-аналогов
      hwSelectedStudents: [],   // id выбранных учеников
      hwDeadline: '',

      async init() {
        await this.refreshState();
        this.startPolling();
        this.waitForKatex();
      },

      waitForKatex() {
        if (window.katex) { this.katexReady = true; return; }
        const t0 = Date.now();
        const tick = () => {
          if (window.katex) { this.katexReady = true; return; }
          if (Date.now() - t0 < 8000) setTimeout(tick, 80);
        };
        tick();
      },

      statusLabel(s) {
        return { draft: 'черновик', live: 'идёт', ended: 'завершён' }[s] || s;
      },

      async onPickerAdd(items) {
        // assignTo пусто = всем; иначе персонально выбранному участнику.
        const assigned = this.assignTo ? Number(this.assignTo) : null;
        for (const it of items) {
          const r = await fetch(`/lessons/${this.sessionId}/tasks`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ ...it, assigned_student_id: assigned }),
          });
          if (!r.ok) { alert('Не удалось добавить задачу'); break; }
        }
        await this.refreshState();
        // Picker остаётся открытым; он сам сбрасывается на выбор класса (reset в confirmAdd),
        // чтобы можно было сразу добрать задачи из другого класса. Закрытие — кнопкой «Отмена».
      },

      renderLatex(expr) {
        if (!expr) return '';
        const s = String(expr);
        // Проза (кириллица) или текст с $...$ — НЕ math целиком: KaTeX в math-режиме
        // съел бы пробелы и не переносил бы строку. Экранируем как текст, формулы
        // внутри $...$ дорендерит auto-render (typeset). Чистая формула (без кириллицы
        // и без $, напр. alg-skill) идёт в KaTeX целиком.
        if (s.includes('$') || /[а-яё]/i.test(s)) { this.typeset(); return this.escapeHtml(s); }
        // referencing katexReady makes this Alpine-reactive when KaTeX finishes loading
        const ready = this.katexReady;
        if (ready && window.katex) {
          try {
            return window.katex.renderToString(s, { throwOnError: false, output: 'html' });
          } catch (e) { /* fallthrough */ }
        }
        return this.escapeHtml(s);
      },

      // Компактный заголовок колонки грида: из $-текстов маркеры убираем и режем
      // (в узкой ячейке НЕ рендерим формулы), bare-latex рендерим как раньше.
      headerHtml(expr) {
        const s = String(expr || '');
        // В узкой ячейке грида формулы не рендерим — только компактный текст.
        if (s.includes('$') || /[а-яё]/i.test(s)) return this.escapeHtml(s.replace(/\$/g, '').slice(0, 40));
        return this.renderLatex(s.slice(0, 40));
      },

      // Прогон KaTeX auto-render по странице (тексты задач 2й части с $...$).
      typeset() {
        if (typesetTimer) return;
        const t0 = Date.now();
        const run = () => {
          typesetTimer = null;
          if (!window.renderMathInElement) {
            if (Date.now() - t0 < 8000) typesetTimer = setTimeout(run, 150);
            return;
          }
          window.renderMathInElement(this.$root, {
            delimiters: [
              { left: '$$', right: '$$', display: true },
              { left: '$', right: '$', display: false },
              { left: '\\(', right: '\\)', display: false },
              { left: '\\[', right: '\\]', display: true },
            ],
            throwOnError: false,
          });
        };
        typesetTimer = setTimeout(run, 60);
      },

      escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
      },

      // --- 📚 Домашка по уроку ---

      // Уникальный ключ задачи-аналога (bank + refs) для чекбоксов.
      hwKey(s) {
        return s.bank + '|' + JSON.stringify(s.refs);
      },
      hwIsSelected(s) {
        return this.hwSelectedKeys.includes(this.hwKey(s));
      },
      hwToggle(s) {
        const k = this.hwKey(s);
        const i = this.hwSelectedKeys.indexOf(k);
        if (i === -1) this.hwSelectedKeys.push(k);
        else this.hwSelectedKeys.splice(i, 1);
      },
      hwSelectedCount() {
        return this.hwSelectedKeys.length;
      },
      hwClear() {
        this.hwSelectedKeys = [];
      },
      hwPickTwoEach() {
        const keys = [];
        for (const g of this.hwGroups) {
          for (const s of (g.suggestions || []).slice(0, 2)) keys.push(this.hwKey(s));
        }
        this.hwSelectedKeys = keys;
      },
      hwToggleStudent(id) {
        const i = this.hwSelectedStudents.indexOf(id);
        if (i === -1) this.hwSelectedStudents.push(id);
        else this.hwSelectedStudents.splice(i, 1);
      },
      hwTitle() {
        const topics = [];
        for (const g of this.hwGroups) {
          const m = String(g.label).match(/Тема\s+([^\s·]+)/);
          if (m && !topics.includes(m[1])) topics.push(m[1]);
        }
        const d = new Date();
        const dm = String(d.getDate()).padStart(2, '0') + '.' + String(d.getMonth() + 1).padStart(2, '0');
        return topics.length ? `ДЗ по уроку ${dm} — темы ${topics.join(', ')}` : `ДЗ по уроку ${dm}`;
      },
      // Собирает [{bank, refs}] по выбранным ключам из всех групп.
      hwPickerTasksJson() {
        const picked = [];
        for (const g of this.hwGroups) {
          for (const s of (g.suggestions || [])) {
            if (this.hwSelectedKeys.includes(this.hwKey(s))) picked.push({ bank: s.bank, refs: s.refs });
          }
        }
        return JSON.stringify(picked);
      },

      async openHomework() {
        this.hwOpen = true;
        this.hwLoading = true;
        this.hwGroups = [];
        this.hwSelectedKeys = [];
        this.hwSubmitting = false;
        try {
          const r = await fetch(`/lessons/${this.sessionId}/homework-suggestions`,
            { headers: { 'Accept': 'application/json' }, credentials: 'include' });
          if (!r.ok) throw new Error('load failed');
          const data = await r.json();
          this.hwGroups = data.groups || [];
          this.hwPrior = data.prior_homeworks || [];
          const parts = (data.participants || []).map(p => ({ id: p.id, name: p.name, participant: true }));
          const others = (data.other_students || []).map(p => ({ id: p.id, name: p.name, participant: false }));
          this.hwStudents = [...parts, ...others];
          // Предотмечены участники урока.
          this.hwSelectedStudents = parts.map(p => p.id);
        } catch (e) {
          alert('Не удалось загрузить предложения для домашки');
          this.hwOpen = false;
        } finally {
          this.hwLoading = false;
          this.typeset();
        }
      },

      async refreshState() {
        const r = await fetch(`/lessons/${this.sessionId}/state`, { headers: { 'Accept': 'application/json' }, credentials: 'include' });
        if (!r.ok) return;
        const d = await r.json();
        this.status = d.session.status;
        this.joinCode = d.session.join_code;
        if (!noteLoaded) { this.note = d.session.note || ''; noteLoaded = true; }
        // tasks заменяем только при реальном изменении: иначе x-html при каждом
        // poll пересоздаёт DOM и сбрасывает дорендеренные KaTeX-формулы (мигание).
        const tj = JSON.stringify(d.tasks);
        if (tj !== tasksJson) { tasksJson = tj; this.tasks = d.tasks; }
        this.participants = d.participants;
        this.grid = d.grid || {};
      },

      // Поллим и в draft (видно, кто вошёл по коду до старта), и в live.
      startPolling() {
        if (this.pollTimer) clearInterval(this.pollTimer);
        if (this.status !== 'ended') {
          this.pollTimer = setInterval(() => {
            if (document.hidden) return;
            this.refreshState();
            if (this.status === 'ended' && this.pollTimer) clearInterval(this.pollTimer);
          }, 4000);
        }
      },

      async saveNote() {
        const r = await fetch(`/lessons/${this.sessionId}/note`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ note: this.note }),
        });
        if (r.ok) {
          this.noteSaved = true;
          setTimeout(() => { this.noteSaved = false; }, 2000);
        }
      },

      // --- 📝 Заметки об учениках ---
      // Бейджи те же, что в карточке ученика: один kind не должен выглядеть
      // по-разному на двух экранах.
      noteBadge(kind) {
        return { weakness: '🔴', strength: '🟢', todo: '📌', general: '💬' }[kind] || '💬';
      },

      async openStudentNotes(p) {
        this.viewStudent = { id: p.id, name: p.name || ('#' + p.id) };
        this.viewNotes = [];
        this.viewError = '';
        this.viewLoading = true;
        this.viewOpen = true;
        try {
          const r = await fetch(`/lessons/${this.sessionId}/students/${p.id}/notes`, {
            headers: { 'Accept': 'application/json' },
            credentials: 'include',
          });
          if (!r.ok) throw new Error('bad status');
          const d = await r.json();
          this.viewNotes = Array.isArray(d.notes) ? d.notes : [];
          if (d.student && d.student.name) this.viewStudent.name = d.student.name;
        } catch (e) {
          this.viewError = 'Не удалось загрузить заметки';
        }
        this.viewLoading = false;
      },

      // Из просмотра сразу в запись — с уже отмеченным этим учеником.
      addNoteForViewed() {
        const id = this.viewStudent ? this.viewStudent.id : null;
        this.viewOpen = false;
        this.openNotes();
        if (id !== null) this.noteStudentIds = [id];
      },

      openNotes() {
        this.noteStudentIds = [];
        this.noteText = '';
        this.notesOpen = true;
      },

      isNoteStudentSelected(id) {
        return this.noteStudentIds.includes(id);
      },

      toggleNoteStudent(id) {
        const i = this.noteStudentIds.indexOf(id);
        if (i === -1) this.noteStudentIds.push(id);
        else this.noteStudentIds.splice(i, 1);
      },

      toggleAllNoteStudents() {
        if (this.noteStudentIds.length === this.participants.length) {
          this.noteStudentIds = [];
        } else {
          this.noteStudentIds = this.participants.map(p => p.id);
        }
      },

      async submitNote() {
        const text = this.noteText.trim();
        if (!this.noteStudentIds.length || !text || this.noteSending) return;
        this.noteSending = true;
        try {
          const r = await fetch(`/lessons/${this.sessionId}/notes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ student_ids: this.noteStudentIds, text }),
          });
          if (!r.ok) {
            let msg = 'Не удалось сохранить';
            try { const e = await r.json(); if (e && e.error) msg = e.error; } catch (_) {}
            alert(msg);
            this.noteSending = false;
            return;
          }
          const d = await r.json();
          const kindRu = { weakness: 'западает', strength: 'сильная сторона', todo: 'todo', general: 'общее' }[d.kind] || (d.kind || '—');
          const n = Array.isArray(d.notes) ? d.notes.length : this.noteStudentIds.length;
          this.notesOpen = false;
          this.noteStudentIds = [];
          this.noteText = '';
          this.noteToast = `Записал ${n} ученикам: ${d.topic_tag || '—'} · ${kindRu}`;
          setTimeout(() => { this.noteToast = ''; }, 3000);
        } catch (e) {
          alert('Не удалось сохранить');
        }
        this.noteSending = false;
      },

      // --- «не понимает» на задаче ---
      toggleDu(taskId) {
        this.duFor = this.duFor === taskId ? null : taskId;
      },

      async dontUnderstand(taskId, studentId) {
        try {
          const r = await fetch(`/lessons/${this.sessionId}/dont-understand`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ student_id: studentId, task_id: taskId }),
          });
          if (!r.ok) { alert('Не удалось записать'); return; }
          this.duFor = null;
          const mark = taskId + '-done';
          this.duDone = mark;
          setTimeout(() => { if (this.duDone === mark) this.duDone = null; }, 2000);
        } catch (e) { alert('Ошибка сети'); }
      },

      async createNextLesson() {
        if (this.creatingNext) return;
        if (!confirm('Создать урок на то же время через неделю?')) return;
        this.creatingNext = true;
        try {
          const r = await fetch(`/lessons/${this.sessionId}/next`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            credentials: 'include',
          });
          const d = await r.json();
          if (r.ok && d.session) { window.location = `/lessons/${d.session.id}`; return; }
          alert(d.error || 'Не удалось создать');
        } catch (e) { alert('Ошибка сети'); }
        this.creatingNext = false;
      },

      // --- активность ученика ---
      activityDot(p) {
        const s = p.activity?.state;
        if (s === 'present') return '🟢';
        if (s === 'away') return '🔴';
        return '⚪';
      },
      activityTitle(p) {
        const s = p.activity?.state;
        return s === 'present' ? 'На странице урока' : (s === 'away' ? 'Отошёл / свернул' : 'Не заходил');
      },
      fmtMin(sec) {
        sec = Math.max(0, Math.round(sec || 0));
        if (sec < 60) return sec + ' сек';
        const m = Math.floor(sec / 60), s = sec % 60;
        return s ? `${m} мин ${s} сек` : `${m} мин`;
      },
      activityMeta(p) {
        const a = p.activity;
        if (!a) return '';
        const parts = [];
        if (a.away_count > 0) parts.push(`отходил ${a.away_count}×`);
        if (a.away_seconds > 0) parts.push(`вне ${this.fmtMin(a.away_seconds)}`);
        parts.push(`на странице ${this.fmtMin(a.present_seconds)}`);
        const b = p.behavior;
        if (b?.copy_count > 0) parts.push(`📋 копировал условие ${b.copy_count}×`);
        if (b?.paste_count > 0) parts.push(`📥 вставлял ответ ${b.paste_count}×`);
        return parts.join(' · ');
      },

      async releaseStudent(studentId) {
        if (!confirm('Отпустить ученика с урока?')) return;
        const r = await fetch(`/lessons/${this.sessionId}/participants/${studentId}/release`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
          credentials: 'include',
        });
        if (!r.ok) { alert('Не удалось отпустить'); return; }
        await this.refreshState();
      },

      async removeTask(taskId) {
        if (!confirm('Удалить задачу?')) return;
        await fetch(`/lessons/${this.sessionId}/tasks/${taskId}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
          credentials: 'include',
        });
        await this.refreshState();
      },

      async startLesson() {
        const r = await fetch(`/lessons/${this.sessionId}/start`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
          credentials: 'include',
        });
        if (!r.ok) { alert('Не удалось запустить'); return; }
        await this.refreshState();
        this.startPolling();
      },

      async endLesson() {
        if (!confirm('Завершить урок?')) return;
        await fetch(`/lessons/${this.sessionId}/end`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
          credentials: 'include',
        });
        await this.refreshState();
        if (this.pollTimer) clearInterval(this.pollTimer);
      },

      // Персональная задача не для этого ученика — ячейка неприменима.
      cellNotForStudent(studentId, taskId) {
        const t = this.tasks.find(x => x.id === taskId);
        return t && t.assigned_student_id && t.assigned_student_id !== studentId;
      },

      cellLabel(studentId, taskId) {
        if (this.cellNotForStudent(studentId, taskId)) return '·';
        const a = this.grid[studentId]?.[taskId];
        if (!a) return '—';
        const mark = a.is_correct === true ? '✓ ' : (a.is_correct === false ? '✗ ' : '');
        // 📥 ответ вставлен из буфера, ⚡ дан в первые секунды после возврата
        const flags = (a.pasted ? ' 📥' : '') + (a.quick_after_away ? ' ⚡' : '');
        return mark + a.answer + flags;
      },

      cellClass(studentId, taskId) {
        if (this.cellNotForStudent(studentId, taskId)) return 'live-cell-na';
        const a = this.grid[studentId]?.[taskId];
        if (!a) return 'live-cell-empty';
        return a.is_correct ? 'live-cell-ok' : 'live-cell-bad';
      },

      // Summary helpers
      taskAnsweredCount(taskId) {
        let n = 0;
        for (const p of this.participants) {
          if (this.grid[p.id]?.[taskId]) n++;
        }
        return n;
      },

      taskCorrectCount(taskId) {
        let n = 0;
        for (const p of this.participants) {
          if (this.grid[p.id]?.[taskId]?.is_correct === true) n++;
        }
        return n;
      },

      taskCorrectPct(taskId) {
        const a = this.taskAnsweredCount(taskId);
        return a === 0 ? 0 : Math.round((this.taskCorrectCount(taskId) / a) * 100);
      },

      get hasBehaviorFlags() {
        for (const row of Object.values(this.grid)) {
          for (const cell of Object.values(row)) {
            if (cell.pasted || cell.quick_after_away) return true;
          }
        }
        return false;
      },

      get silentStudents() {
        return this.participants.filter(p => {
          const myRow = this.grid[p.id];
          return !myRow || Object.keys(myRow).length === 0;
        });
      },
    };
  }
</script>
@endsection
