@extends('layouts.pwa')
@section('title', 'Урок — palomatika')

@push('katex')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.21/dist/contrib/auto-render.min.js"></script>
@endpush

@push('styles')
  .lesson-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 12px; display: flex; flex-direction: column; gap: 10px; }
  /* Picker как отдельный полноэкранный экран — не видно уже добавленных задач */
  .picker-overlay { position: fixed; inset: 0; z-index: 1000; background: var(--bg); overflow-y: auto; padding: 16px calc(16px + var(--safe-right, 0px)) calc(24px + var(--safe-bottom, 0px)) calc(16px + var(--safe-left, 0px)); }
  .picker-overlay-inner { max-width: 640px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
  .picker-overlay-head { position: sticky; top: -16px; z-index: 1; background: var(--bg); padding: 8px 0; margin: -8px 0 0; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid var(--border); }
  .picker-overlay-head .title { font-size: 16px; font-weight: 700; color: var(--text); }
  /* Отступы и колонки урезаны ради условия: на экране 390px обвязка
     карточки съедала 162px из 390, и формуле оставалось меньше 60%
     ширины — она не влезала в строку даже там, где сама по себе
     короче экрана. */
  .lesson-task { display: flex; gap: 6px; align-items: flex-start; padding: 8px; background: var(--surface2); border-radius: 10px; }
  .lesson-task-num { font-weight: 800; color: var(--accent); width: 16px; font-size: 13px; flex-shrink: 0; }
  .lesson-task-body { flex: 1; min-width: 0; }
  .lesson-task-expr { font-size: 15px; color: var(--text); margin-bottom: 4px; word-break: break-word; }
  /* Формула — неделимая коробка. Иначе строка рвётся посреди неё, и у задач
     ЕГЭ выходило «Решите неравенство log₁₆(x +» на одной строке и «5) + …»
     на следующей: условие начиналось на строке вводных слов, места ему не
     хватало, и КаТeX разъезжался по переносам. Как inline-block формула
     целиком уходит на свою строку, а если и там не помещается — прокручивается
     вбок, а не разваливается. */
  .lesson-task-expr .katex {
    display: inline-block;
    max-width: 100%;
    /* Одной строкой: перенос внутри формулы читается плохо, а ширину под
       карточку подбирает `fitFormulas()` кеглем. Прокрутка остаётся
       страховкой для формул, которым не хватило и нижней границы кегля. */
    white-space: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    /* У блока с прокруткой базовая линия — нижний край; без выравнивания
       формула съезжает относительно текста вокруг. */
    vertical-align: middle;
    /* Полосу прокрутки прячем. У KaTeX корень и дроби вылезают за коробку
       на доли пикселя, и браузер рисовал полосу со стрелками под «3√5» —
       коротким формулам, которым прокрутка не нужна вовсе. Сама прокрутка
       (колесо, свайп) остаётся страховкой для длинных условий. */
    scrollbar-width: none;
    -ms-overflow-style: none;
  }
  .lesson-task-expr .katex::-webkit-scrollbar { width: 0; height: 0; }
  /* Растры банка ЕГЭ: чертёж отдельным блоком, обозначения внутри
     предложения («SABCD», «AM = 2») — строкой. Оба чёрным по прозрачному,
     поэтому на тёмном фоне нужна подложка; display обязателен, иначе
     Tailwind-сброс делает их блочными и рвёт предложение. */
  .lesson-task-expr img.fipi-inline,
  .lesson-task-expr img.fipi-figure { background: #fff; border-radius: 4px; }
  .lesson-task-expr img.fipi-inline {
    display: inline-block; padding: 0 2px; height: 1.3em; width: auto; vertical-align: -0.26em;
  }
  .lesson-task-expr img.fipi-figure { display: block; max-width: 100%; padding: 6px; margin: 8px 0; }
  @include('pwa._shared.partials.fipi-condition-css')
  .lesson-task-expr.fipi-condition { font-size: 15px; }
  .lesson-task-image { display: flex; justify-content: center; background: var(--surface); border-radius: 8px; padding: 8px; margin-bottom: 8px; }
  /* Растр ФИПИ — чёрным по прозрачному, как и внутри условия: на тёмной
     подложке чертёж почти не читается, в банке он выведен на белом листе.
     Свои SVG рисуются под тему интерфейса, им белое не нужно. */
  .lesson-task-image.is-raster { background: #fff; }
  .lesson-task-image svg, .lesson-task-image img { max-width: 250px; width: 100%; height: auto; max-height: 220px; }
  .lesson-task-options { display: flex; flex-wrap: wrap; gap: 6px; margin: 6px 0; }
  .lesson-task-option { padding: 3px 9px; border: 1px solid var(--border); border-radius: 8px; font-size: 12px; color: var(--muted); }
  /* Кнопка удаления переехала сюда из строки с условием: там она
     отнимала у формулы 37px постоянно, а нужна раз в жизни задачи. */
  .lesson-task-meta { font-size: 11px; color: var(--muted); display: flex; align-items: center; justify-content: space-between; gap: 8px; }
  .lesson-task-meta-text { min-width: 0; }
  /* Подпункты «а) б) в)» второй части: маркер выступает влево, продолжение
     выравнивается под текстом — как в печатном варианте КИМ. Без этого
     задание 19 читалось сплошной стеной. */
  .cond-lead, .cond-sub { display: block; }
  .cond-sub { padding-left: 16px; text-indent: -16px; margin-top: 4px; }
  /* Формула плюс прилипшая к ней пунктуация — одним куском: см. glueTrailingPunctuation. */
  .lesson-task-expr .nb { white-space: nowrap; }
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
  /* Переключатель источника домашки: аналоги задач урока или банк «Скиллы» */
  .hw-mode { display: flex; gap: 4px; padding: 4px; background: var(--surface2); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 12px; }
  .hw-mode button { flex: 1; padding: 9px 10px; border: none; border-radius: 9px; background: transparent; color: var(--muted); font-size: 13px; font-weight: 800; cursor: pointer; }
  .hw-mode button.active { background: var(--accent-bg); color: var(--accent); box-shadow: inset 0 0 0 1px var(--accent-bd); }
  .hw-pills { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
  .hw-pill { padding: 8px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font-size: 13px; font-weight: 700; cursor: pointer; }
  .hw-pill.active { border-color: var(--accent-bd); background: var(--accent-bg); color: var(--accent); }

  /* Разбор домашки — вторая стадия проверки, приехавшая в урок */
  .review-card { border-color: var(--purple-bd); }
  .review-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; cursor: pointer; }
  .review-title { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .review-count { font-size: 11px; font-weight: 800; color: var(--purple); background: var(--purple-bg); border-radius: 6px; padding: 2px 7px; }
  .review-count-muted { color: var(--muted); background: var(--surface2); }
  .review-fold { border: none; background: none; color: var(--muted); font-size: 14px; cursor: pointer; padding: 4px 6px; }

  .review-item { margin-top: 10px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 12px; background: var(--surface2); }
  .review-item.is-done { opacity: .5; }
  .review-item-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
  .review-item-actions { display: flex; gap: 6px; }
  .review-who { font-size: 11px; font-weight: 800; color: var(--purple); text-transform: uppercase; letter-spacing: .04em; }
  .review-mini { border: 1px solid var(--border); background: var(--surface); color: var(--muted); border-radius: 8px; padding: 3px 8px; font-size: 11px; font-weight: 700; cursor: pointer; }
  .review-mini:active { opacity: .7; }
  .review-visual { margin: 4px 0; display: flex; justify-content: center; }
  .review-visual :is(svg, img) { max-width: 100%; height: auto; }
  .review-text { font-size: 13px; line-height: 1.45; color: var(--text); word-break: break-word; }

  .review-answers { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
  .review-chip { font-size: 12px; font-weight: 700; padding: 3px 8px; border-radius: 8px; background: var(--surface); border: 1px solid var(--border); color: var(--text); }
  .review-chip.is-wrong { color: var(--red); border-color: var(--red-bd); background: var(--red-bg); }
  .review-chip-label { color: var(--muted); font-weight: 600; }

  .review-photos { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
  .review-photo { padding: 0; border: none; background: none; cursor: zoom-in; width: 84px; }
  .review-photo img { width: 100%; aspect-ratio: 3 / 4; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); display: block; }
  .review-photo:active img { opacity: .8; }

  .review-note { display: block; margin-top: 8px; font-size: 12px; font-weight: 700; color: var(--yellow); }
  .review-link { display: inline-block; margin-top: 8px; font-size: 11px; font-weight: 700; color: var(--muted); text-decoration: none; }

  .review-queue { margin-top: 12px; padding-top: 10px; border-top: 1px dashed var(--border); }
  .review-queue-label { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; }
  .review-offer { display: flex; gap: 9px; align-items: flex-start; padding: 8px 10px; margin-bottom: 6px; border: 1px solid var(--border); border-radius: 10px; cursor: pointer; }
  .review-offer.is-picked { border-color: var(--purple); background: var(--purple-bg); }
  .review-offer input { margin-top: 3px; flex-shrink: 0; }
  .review-offer-body { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
  .review-offer-text { font-size: 12px; color: var(--text); line-height: 1.4; word-break: break-word; }
  .review-add { width: 100%; margin-top: 4px; padding: 10px; border: none; border-radius: 10px; background: var(--purple); color: #fff; font-family: var(--display); font-size: 13px; cursor: pointer; }
  .review-add:disabled { opacity: .45; cursor: default; }
  /* ── Компоновка урока под телефон (вариант A): код строкой, вкладки, панель внизу ── */
  .lp-page { padding-bottom: calc(112px + var(--safe-bottom, 0px)); }
  .lp-top { display: flex; align-items: center; gap: 10px; }
  .lp-top .topbar-title { flex: 1; min-width: 0; }
  .lp-more { background: none; border: none; color: var(--muted); font-size: 22px; line-height: 1; padding: 4px 8px; border-radius: 10px; cursor: pointer; }
  .lp-menu { position: absolute; right: 0; top: 44px; z-index: 60; background: var(--surface2); border: 1px solid var(--border); border-radius: 14px; padding: 6px; min-width: 230px; box-shadow: 0 12px 30px rgba(0,0,0,.4); }
  .lp-menu button { display: block; width: 100%; text-align: left; padding: 11px 12px; border: none; background: none; border-radius: 10px; font: inherit; font-size: 14px; font-weight: 700; color: var(--text); cursor: pointer; }
  .lp-menu button:hover { background: var(--surface); }
  .lp-codebar { display: flex; align-items: center; gap: 10px; padding: 8px 8px 8px 12px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border); }
  .lp-codebar-lbl { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
  .lp-code { background: none; border: none; padding: 0; font-family: ui-monospace, 'SF Mono', monospace; font-size: 22px; font-weight: 800; letter-spacing: 3px; color: var(--text); cursor: pointer; }
  .lp-grow { flex: 1; }
  .lp-who { display: inline-flex; align-items: center; gap: 7px; padding: 7px 11px; border-radius: 10px; border: none; background: var(--surface2); color: var(--text); font: inherit; font-size: 13px; font-weight: 800; cursor: pointer; }
  .lp-dots { display: inline-flex; gap: 3px; }
  .lp-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--muted2); flex-shrink: 0; }
  .lp-dot.present { background: var(--green); }
  .lp-dot.away { background: var(--red); }
  .lp-bigcode { position: fixed; inset: 0; z-index: 1100; background: var(--bg); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px; padding: 24px; text-align: center; cursor: pointer; }
  .lp-bigcode .c { font-family: ui-monospace, monospace; font-size: clamp(48px, 16vw, 96px); font-weight: 800; letter-spacing: 6px; line-height: 1; color: var(--text); }
  .lp-bigcode .s { color: var(--muted); font-size: 15px; }
  .lp-tabs { display: flex; gap: 4px; padding: 4px; border-radius: 13px; background: var(--surface); border: 1px solid var(--border); }
  .lp-tabs button { flex: 1; padding: 9px 4px; border-radius: 10px; border: none; background: none; color: var(--muted); font: inherit; font-size: 13px; font-weight: 800; cursor: pointer; }
  .lp-tabs button.on { background: var(--surface2); color: var(--text); }
  .lp-tabs .n { font-size: 11px; color: var(--muted); margin-left: 3px; }
  .lp-list { display: flex; flex-direction: column; gap: 8px; }
  .lp-empty { color: var(--muted); font-size: 14px; line-height: 1.5; padding: 24px 12px; text-align: center; }
  /* Ответы под условием задачи — чтобы помогать, глядя на условие */
  .lp-res { margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 6px; }
  .lp-res-head { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; color: var(--muted); }
  .lp-res-head .lp-grow { min-width: 0; }
  .lp-chips { display: flex; flex-wrap: wrap; gap: 5px; }
  .lp-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 8px; font-size: 12px; font-weight: 700; background: var(--surface); border: 1px solid var(--border); color: var(--muted); }
  .lp-chip.ok { color: var(--green); border-color: var(--green-bd); background: var(--green-bg); }
  .lp-chip.bad { color: var(--red); border-color: var(--red-bd); background: var(--red-bg); }
  .lp-chip b { color: var(--text); font-weight: 700; }
  .lp-chip .ans { font-family: ui-monospace, monospace; font-weight: 800; }
  /* Матрица ответов */
  .lp-mx-wrap { overflow-x: auto; border-radius: 14px; background: var(--surface); border: 1px solid var(--border); padding: 10px; }
  .lp-mx { display: grid; gap: 5px; align-items: center; min-width: min-content; }
  .lp-mx-hd { font-size: 12px; font-weight: 800; color: var(--muted); text-align: center; padding: 4px 0; border: none; background: none; border-radius: 6px; cursor: pointer; font-family: inherit; }
  .lp-mx-hd:hover { background: var(--surface2); color: var(--text); }
  .lp-mx-name { display: flex; align-items: center; gap: 6px; min-width: 0; font-size: 13px; font-weight: 700; color: var(--text); background: none; border: none; padding: 0; font-family: inherit; cursor: pointer; text-align: left; }
  .lp-mx-name span:last-child { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .lp-cell { position: relative; aspect-ratio: 1; min-width: 26px; border-radius: 7px; border: none; display: grid; place-items: center; font-size: 13px; font-weight: 800; cursor: pointer; background: var(--surface2); color: var(--muted2); font-family: inherit; }
  .lp-cell.ok { background: var(--green-bg); color: var(--green); box-shadow: inset 0 0 0 1px var(--green-bd); }
  .lp-cell.bad { background: var(--red-bg); color: var(--red); box-shadow: inset 0 0 0 1px var(--red-bd); }
  .lp-cell.na { background: none; box-shadow: inset 0 0 0 1px var(--border); cursor: default; }
  .lp-cell.sel { outline: 2px solid var(--accent); outline-offset: 1px; }
  .lp-cell .flag { position: absolute; top: 3px; right: 3px; width: 7px; height: 7px; border-radius: 50%; background: var(--yellow); }
  .lp-mx-sum { font-size: 11px; font-weight: 800; text-align: center; }
  .lp-mx-foot { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border); font-size: 11px; color: var(--muted); }
  .lp-mx-foot i { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--yellow); margin-right: 4px; }
  .lp-peek { padding: 10px 12px; border-radius: 12px; background: var(--surface2); font-size: 13px; line-height: 1.5; color: var(--text); }
  .lp-peek .ok { color: var(--green); font-weight: 800; }
  .lp-peek .bad { color: var(--red); font-weight: 800; }
  .lp-silent { font-size: 12px; color: var(--muted); }
  .lp-silent b { color: var(--red); }
  /* Шторка «кто в уроке» */
  .lp-veil { position: fixed; inset: 0; z-index: 900; background: rgba(0,0,0,.5); }
  .lp-sheet { position: fixed; left: 0; right: 0; bottom: 0; z-index: 901; max-width: 480px; margin: 0 auto; max-height: 80dvh; overflow-y: auto; background: var(--surface); border-radius: 22px 22px 0 0; border-top: 1px solid var(--border); padding: 8px 16px calc(18px + var(--safe-bottom, 0px)); }
  .lp-grab { width: 38px; height: 4px; border-radius: 4px; background: var(--muted2); margin: 2px auto 12px; }
  .lp-sheet-t { font-family: var(--display); font-size: 17px; color: var(--text); }
  .lp-sheet-s { font-size: 13px; color: var(--muted); margin: 4px 0 8px; }
  .lp-person { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-top: 1px solid var(--border); }
  .lp-person-b { flex: 1; min-width: 0; }
  .lp-person-n { font-size: 15px; font-weight: 700; color: var(--text); }
  .lp-person-m { font-size: 12px; color: var(--muted); margin-top: 2px; }
  .lp-mini { padding: 7px 10px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); font: inherit; font-size: 12px; font-weight: 800; cursor: pointer; white-space: nowrap; }
  .lp-mini.warn { color: var(--red); border-color: var(--red-bd); }
  /* Нижняя панель действий — всегда под пальцем */
  .lp-dock { position: fixed; left: 0; right: 0; bottom: 0; z-index: 50; }
  .lp-dock-in { max-width: 480px; margin: 0 auto; display: flex; gap: 8px; padding: 10px 12px calc(12px + var(--safe-bottom, 0px)); background: linear-gradient(to top, var(--bg) 72%, transparent); }
  .lp-ib { width: 58px; flex-shrink: 0; border-radius: 16px; border: 1px solid var(--border); background: var(--surface); color: var(--text); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; padding: 8px 0 7px; font: inherit; cursor: pointer; }
  .lp-ib small { font-size: 10px; font-weight: 800; color: var(--muted); }
  .lp-primary { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; border: none; border-radius: 16px; padding: 14px 10px; background: var(--accent); color: #fff; font-family: var(--display); font-size: 15px; cursor: pointer; }
  .lp-primary.stop { background: var(--red-bg); color: var(--red); box-shadow: inset 0 0 0 1px var(--red-bd); }
  .lp-primary.ghost { background: var(--surface); color: var(--text); box-shadow: inset 0 0 0 1px var(--border); }
  .lp-primary:disabled { opacity: .45; cursor: default; }
  /* Кнопка «сообщить о баге» из общего шаблона — над панелью, а не поверх «Завершить» */
  .bug-report-trigger { bottom: calc(96px + var(--safe-bottom, 0px)) !important; }
  /* В полноэкранных окнах (задачи, домашка, заметки) она ложилась на их главную кнопку */
  body:has(.ns-overlay:not([style*="none"])) .bug-report-trigger,
  body:has(.picker-overlay:not([style*="none"])) .bug-report-trigger { display: none; }
@endpush

@section('body')
<div class="page lp-page" x-data="lessonPrep({{ $session->id }}, '{{ $session->status }}')" x-init="init()"
     @picker-add.window="onPickerAdd($event.detail.items)"
     @keydown.escape.window="viewer && close()"
     @keydown.arrow-left.window="viewer && step(-1)"
     @keydown.arrow-right.window="viewer && step(1)">
  <div class="topbar lp-top" style="position: relative;">
    <a href="{{ route('pwa.teacher.lessons') }}" class="back-btn">‹</a>
    <div class="topbar-title">Урок #{{ $session->id }}</div>
    <span :class="'status-badge-' + status" x-text="statusLabel(status)" class="status-badge-{{ $session->status }}">{{ $session->status }}</span>
    <button type="button" class="lp-more" @click="menuOpen = !menuOpen" aria-label="Ещё">⋯</button>
    <div class="lp-menu" x-show="menuOpen" x-cloak @click.outside="menuOpen = false">
      <button type="button" @click="menuOpen = false; createNextLesson()"
              x-text="creatingNext ? 'Создаём…' : 'Следующий урок через неделю'"></button>
      <button type="button" x-show="joinCode && status !== 'ended'" @click="menuOpen = false; copyCode()">Скопировать код урока</button>
    </div>
  </div>

  {{-- Код входа одной строкой; тап — крупно, чтобы продиктовать --}}
  <template x-if="status !== 'ended' && joinCode">
    <div class="lp-codebar">
      <span class="lp-codebar-lbl">Код</span>
      <button type="button" class="lp-code" @click="codeBig = true" x-text="formatCode(joinCode)"></button>
      <span class="lp-grow"></span>
      <button type="button" class="lp-who" @click="peopleOpen = true" aria-label="Ученики в уроке">
        <span class="lp-dots">
          <template x-for="p in participants.slice(0, 8)" :key="'dot-' + p.id">
            <span class="lp-dot" :class="dotClass(p)"></span>
          </template>
        </span>
        <span x-text="participants.length"></span>
      </button>
    </div>
  </template>

  <div class="lp-bigcode" x-show="codeBig" x-cloak @click="codeBig = false">
    <div class="s">Код урока</div>
    <div class="c" x-text="formatCode(joinCode)"></div>
    <div class="s">Ученики вводят его на своей странице «Урок».<br>Тап — закрыть.</div>
  </div>

  {{-- Кто в уроке: активность, заметки, «отпустить» --}}
  <template x-if="peopleOpen">
    <div>
      <div class="lp-veil" @click="peopleOpen = false"></div>
      <div class="lp-sheet">
        <div class="lp-grab"></div>
        <div class="lp-sheet-t" x-text="'В уроке: ' + participants.length"></div>
        <div class="lp-sheet-s">Зелёный — на странице урока, красный — свернул, серый — не заходил.</div>
        <div class="lp-empty" x-show="!participants.length">Пока никто не вошёл по коду.</div>
        <template x-for="p in participants" :key="'pp-' + p.id">
          <div class="lp-person">
            <span class="lp-dot" :class="dotClass(p)"></span>
            <div class="lp-person-b">
              <div class="lp-person-n" x-text="p.name || ('#' + p.id)"></div>
              <div class="lp-person-m" x-show="p.activity" x-text="activityMeta(p)"></div>
            </div>
            <button type="button" class="lp-mini" @click="peopleOpen = false; openStudentNotes(p)"
                    x-text="p.notes_count ? 'Заметки · ' + p.notes_count : 'Заметки'"></button>
            <button type="button" class="lp-mini warn" x-show="p.locked" @click="releaseStudent(p.id)">Отпустить</button>
          </div>
        </template>
      </div>
    </div>
  </template>

  <div class="lp-tabs" role="tablist">
    <button type="button" :class="tab === 'tasks' ? 'on' : ''" @click="tab = 'tasks'">Задачи<span class="n" x-text="tasks.length"></span></button>
    <button type="button" :class="tab === 'answers' ? 'on' : ''" @click="tab = 'answers'" x-show="status !== 'draft'">Ответы</button>
    <button type="button" :class="tab === 'review' ? 'on' : ''" @click="tab = 'review'"
            x-show="reviewPending.length || reviewPlanned.length">Разбор<span class="n" x-text="reviewPlanned.length + reviewPending.length"></span></button>
  </div>

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


  @include('pwa._shared.photo-viewer')

  {{-- Вкладка «Задачи»: условия целиком — по ним учитель помогает --}}
  <div class="lp-list" x-show="tab === 'tasks'">
    <template x-for="task in tasks" :key="task.id">
      <div class="lesson-task" :id="'lt-' + task.id">
        <div class="lesson-task-num" x-text="task.position + ')'"></div>
        <div class="lesson-task-body">
          <div class="lesson-task-image" x-show="task.task_payload.image_svg" x-html="task.task_payload.image_svg"></div>
          <template x-if="!task.task_payload.image_svg && task.task_payload.image_url && !task.task_payload.condition_html">
            <div class="lesson-task-image is-raster"><img :src="task.task_payload.image_url" alt=""></div>
          </template>
          {{-- Банк ЕГЭ: условие целиком в разметке ФИПИ (таблицы соответствия,
               графики-варианты, обозначения-растры); плоский текст — для остальных
               банков и уроков, собранных до этого поля. --}}
          <div class="lesson-task-expr fipi-condition" x-show="task.task_payload.condition_html"
               x-html="fipiHtml(task.task_payload.condition_html)"></div>
          <div class="lesson-task-expr" x-show="!task.task_payload.condition_html"
               x-html="taskConditionHtml(task.task_payload.expression)"
               x-init="$nextTick(() => fitFormulas($el))"
               @resize.window.debounce.150ms="fitFormulas($el)"></div>
          <template x-if="task.task_payload.type === 'choice'">
            <div class="lesson-task-options">
              <template x-for="(opt, oi) in task.task_payload.options" :key="opt.id">
                <span class="lesson-task-option" x-html="renderLatex(opt.label)"></span>
              </template>
            </div>
          </template>
          <div class="lesson-task-meta">
            <span class="lesson-task-meta-text">
              <span x-text="task.bank"></span>
              · Ответ: <span class="lesson-task-answer" x-text="task.correct_answer || '(без автопроверки)'"></span>
              <span class="personal-badge" x-show="task.assigned_student_id"
                    x-text="'для ' + (task.assigned_name || '#' + task.assigned_student_id)"></span>
            </span>
            <button class="btn btn-icon btn-danger" x-show="status === 'draft'"
                    @click="removeTask(task.id)" title="Убрать задачу из урока">×</button>
          </div>
          {{-- Во время урока и после — ответы прямо под условием --}}
          <div class="lp-res" x-show="status !== 'draft'">
            <div class="lp-res-head">
              <span class="lp-grow" x-text="taskStatLine(task)"></span>
              <div x-show="status === 'live'" style="position: relative;">
                <button type="button" class="du-btn" @click="toggleDu(task.id)"
                        x-text="duFor === task.id ? '✕ отмена' : 'не понимает'"></button>
                <div class="du-pick" x-show="duFor === task.id" x-cloak style="left: auto; right: 0;">
                  <template x-for="p in participants" :key="'du-' + task.id + '-' + p.id">
                    <button type="button" class="du-pick-item" @click="dontUnderstand(task.id, p.id)"
                            x-text="p.name || ('#' + p.id)"></button>
                  </template>
                  <div x-show="!participants.length" style="color: var(--muted); font-size: 11px; padding: 4px;">нет учеников</div>
                </div>
                <div class="du-done" x-show="duDone === (task.id + '-done')" x-cloak>записано ✓</div>
              </div>
            </div>
            <div class="lp-chips">
              <template x-for="r in taskResults(task)" :key="'r-' + task.id + '-' + r.id">
                <span class="lp-chip" :class="r.cls">
                  <b x-text="r.name"></b>
                  <span class="ans" x-show="r.answer !== null" x-text="(r.cls === 'ok' ? '✓ ' : '✗ ') + r.answer + r.flags"></span>
                  <span x-show="r.answer === null">—</span>
                </span>
              </template>
            </div>
          </div>
        </div>
      </div>
    </template>
    <div class="lp-empty" x-show="tasks.length === 0">Пока ни одной задачи.<br>Добавь кнопкой «＋ задача» внизу.</div>
  </div>

  {{-- Вкладка «Ответы»: матрица — вся группа одним взглядом --}}
  <div class="lp-list" x-show="tab === 'answers'" x-cloak>
    <div class="lp-empty" x-show="!participants.length">В уроке пока нет учеников.</div>
    <template x-if="participants.length && tasks.length">
      <div class="lp-mx-wrap">
        <div class="lp-mx" :style="`grid-template-columns: 72px repeat(${tasks.length}, minmax(26px, 1fr))`">
          <span></span>
          <template x-for="t in tasks" :key="'h-' + t.id">
            <button type="button" class="lp-mx-hd" @click="goToTask(t.id)" x-text="t.position"
                    :title="'Условие задачи ' + t.position"></button>
          </template>
          <template x-for="p in participants" :key="'row-' + p.id">
            <div style="display: contents;">
              <button type="button" class="lp-mx-name" @click="openStudentNotes(p)">
                <span class="lp-dot" :class="dotClass(p)"></span><span x-text="firstName(p)"></span>
              </button>
              <template x-for="t in tasks" :key="'c-' + p.id + '-' + t.id">
                <button type="button" class="lp-cell" :class="mxCellClass(p.id, t.id)"
                        @click="togglePeek(p.id, t.id)">
                  <span x-text="mxCellMark(p.id, t.id)"></span>
                  <i class="flag" x-show="cellFlagged(p.id, t.id)"></i>
                </button>
              </template>
            </div>
          </template>
          <span class="lp-mx-sum" style="text-align: left; color: var(--muted);">верно</span>
          <template x-for="t in tasks" :key="'s-' + t.id">
            <span class="lp-mx-sum" :style="`color: ${pctColor(t.id)}`"
                  x-text="taskAnsweredCount(t.id) ? taskCorrectPct(t.id) + '%' : '—'"></span>
          </template>
        </div>
        <div class="lp-mx-foot">
          <span><i></i>ответ вставлен из буфера или дан сразу после возврата</span>
          <span x-show="status === 'live'">обновляется сам</span>
        </div>
      </div>
    </template>
    <div class="lp-peek" x-show="peek" x-cloak x-html="peekHtml()"></div>
    <div class="lp-silent" x-show="silentStudents.length">
      Не отвечают: <b x-text="silentStudents.map(p => p.name || '#' + p.id).join(', ')"></b>
    </div>
  </div>

  {{--
    Разбор домашки — вторая стадия проверки. В draft это очередь предложений
    («что взять на урок»), в live — раскрытые карточки с тетрадью ученика.
    В lesson_session_tasks эти пункты не попадают: там у строки есть поле
    ответа, а разбор — это «смотрим на то, что уже написано».
  --}}
  <div class="lp-list" x-show="tab === 'review'" x-cloak>
    <div class="lesson-card review-card">
        {{-- Уже в повестке урока --}}
        <template x-for="card in reviewPlanned" :key="'rp-' + card.id">
          <div class="review-item" :class="reviewDone.includes(card.id) ? 'is-done' : ''">
            <div class="review-item-head">
              <span class="review-who" x-text="card.student_name + ' · задача ' + card.task_order"></span>
              <div class="review-item-actions">
                <button type="button" class="review-mini" @click="markReviewDone(card.id)"
                        x-show="!reviewDone.includes(card.id)">разобрано</button>
                <button type="button" class="review-mini" @click="unplanReview(card.id)"
                        x-show="status === 'draft'">убрать</button>
              </div>
            </div>

            <div class="review-visual" x-show="card.svg" x-html="card.svg"></div>
            <div class="review-text" x-html="card.text"></div>

            <div class="review-answers">
              <span class="review-chip"><span class="review-chip-label">эталон:</span> <span x-text="card.correct"></span></span>
              <template x-if="card.first_answer !== null">
                <span class="review-chip is-wrong"><span class="review-chip-label">ответил:</span> <span x-text="card.first_answer"></span></span>
              </template>
            </div>

            <div class="review-photos" x-show="card.photos.length">
              <template x-for="(p, pi) in card.photos" :key="'rpp-' + card.id + '-' + pi">
                <button type="button" class="review-photo" @click="openReviewPhotos(card, pi)">
                  <img :src="p.url" :alt="p.label" loading="lazy">
                </button>
              </template>
            </div>

            <div class="review-note" x-show="card.teacher_note" x-text="card.teacher_note"></div>
            <a class="review-link" :href="card.homework_url" target="_blank" rel="noopener">вся домашка →</a>
          </div>
        </template>

        {{-- Очередь предложений: отмеченное на проверке, но ещё не взятое в урок --}}
        <template x-if="reviewPending.length && status !== 'ended'">
          <div class="review-queue">
            <div class="review-queue-label">Отмечено при проверке</div>
            <template x-for="card in reviewPending" :key="'rq-' + card.id">
              <label class="review-offer" :class="reviewPicked.includes(card.id) ? 'is-picked' : ''">
                <input type="checkbox" :checked="reviewPicked.includes(card.id)"
                       @change="toggleReviewPick(card.id)">
                <span class="review-offer-body">
                  <span class="review-who" x-text="card.student_name + ' · задача ' + card.task_order"></span>
                  <span class="review-offer-text" x-html="card.text"></span>
                  <span class="review-note" x-show="card.teacher_note" x-text="card.teacher_note"></span>
                </span>
              </label>
            </template>
            <button type="button" class="review-add" @click="planReview()"
                    :disabled="!!(!reviewPicked.length || reviewBusy)"
                    x-text="reviewBusy ? 'Добавляю…' : ('Добавить в урок' + (reviewPicked.length ? ' (' + reviewPicked.length + ')' : ''))"></button>
          </div>
        </template>
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

  {{-- Нижняя панель: главные действия всегда под пальцем --}}
  <div class="lp-dock">
    <div class="lp-dock-in">
      <button type="button" class="lp-ib" x-show="status !== 'ended'" @click="pickerOpen = true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><small>задача</small></button>
      <button type="button" class="lp-ib" @click="openHomework()" title="Аналоги задач урока или примеры из банка «Скиллы»"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/></svg><small>домашка</small></button>
      <button type="button" class="lp-ib" @click="openNotes()" title="Заметка об учениках — они её не видят"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="M13.5 6.5l4 4"/></svg><small>заметка</small></button>
      <button type="button" class="lp-primary" x-show="status === 'draft'" @click="startLesson" :disabled="!!(tasks.length === 0)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 4.5v15l12-7.5z" fill="currentColor" stroke="none"/></svg> Запустить</button>
      <button type="button" class="lp-primary stop" x-show="status === 'live'" @click="endLesson"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="6" width="12" height="12" rx="2" fill="currentColor" stroke="none"/></svg> Завершить</button>
      <button type="button" class="lp-primary ghost" x-show="status === 'ended'" @click="createNextLesson" :disabled="!!creatingNext"
              x-text="creatingNext ? 'Создаём…' : 'Следующий урок'"></button>
    </div>
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
        <span class="ns-title" x-text="hwMode === 'skills' ? '📚 Домашка по скиллам' : '📚 Домашка по уроку'"></span>
        <button type="button" class="ns-close" @click="hwOpen = false" aria-label="Закрыть">✕</button>
      </div>

      {{-- Источник задач: аналоги разобранного на уроке или сквозные навыки --}}
      <div class="hw-mode" role="tablist">
        <button type="button" :class="hwMode === 'lesson' ? 'active' : ''" @click="hwSetMode('lesson')">По уроку</button>
        <button type="button" :class="hwMode === 'skills' ? 'active' : ''" @click="hwSetMode('skills')">По скиллам</button>
      </div>

      <div class="hw-prior" x-show="hwPrior.length" x-cloak>
        <template x-for="h in hwPrior" :key="'prior-' + h.id">
          <div>По этому уроку уже отправлялось ДЗ: <b x-text="h.title"></b> <span class="hw-muted" x-text="h.date"></span></div>
        </template>
      </div>

      <div x-show="hwLoading" class="hw-muted" style="padding: 12px 0;">Подбираю аналоги…</div>

      <template x-if="!hwLoading">
        <div>
          {{-- По уроку: аналоги разобранных задач --}}
          <div x-show="hwMode === 'lesson'">
          <div class="ns-sub">
            <span x-text="'Выбрано задач: ' + hwSelectedCount()"></span>
            <span style="display: flex; gap: 8px;">
              <button type="button" class="ns-toggle-all" @click="hwPickTwoEach()">По 2 в каждой</button>
              <button type="button" class="ns-toggle-all" @click="hwClear()">Снять всё</button>
            </span>
          </div>

          <div x-show="!hwGroups.length" class="hw-muted" style="padding: 12px 0;">
            <span x-show="tasks.length">Для задач этого урока аналогов не нашлось.</span>
            <span x-show="!tasks.length">На уроке ещё нет задач — аналоги подбирать не из чего. Загляни в «По скиллам».</span>
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
          </div>

          {{-- По скиллам: навык → группы примеров из банка «Скиллы» --}}
          <div x-show="hwMode === 'skills'">
            <div x-show="hwSkillsLoading" class="hw-muted" style="padding: 12px 0;">Загружаю скиллы…</div>
            <div x-show="!hwSkillsLoading && !hwSkillTopics.length" class="hw-muted" style="padding: 12px 0;">
              В банке «Скиллы» пока нет тем.
            </div>
            <div class="hw-pills" x-show="hwSkillTopics.length > 1">
              <template x-for="t in hwSkillTopics" :key="'hw-sk-' + t.id">
                <button type="button" class="hw-pill" :class="hwSkillTopicId === String(t.id) ? 'active' : ''"
                        @click="hwChooseSkillTopic(t.id)" x-text="t.title"></button>
              </template>
            </div>
            <div class="ns-sub" x-show="hwSkillTopicId">
              <span x-text="hwSkillTopicTitle() + ' · выбрано: ' + hwSelectedCount()"></span>
              <span style="display: flex; gap: 8px;">
                <button type="button" class="ns-toggle-all" @click="hwSkillPickEach(3)">По 3 из каждой</button>
                <button type="button" class="ns-toggle-all" @click="hwSkillPickRandom(10)">Случайные 10</button>
                <button type="button" class="ns-toggle-all" @click="hwClear()">Снять всё</button>
              </span>
            </div>
            <div x-show="hwSkillTasksLoading" class="hw-muted" style="padding: 12px 0;">Загружаю примеры…</div>
            <template x-for="g in hwSkillGroups" :key="'hw-sg-' + g.key">
              <div class="hw-group" style="margin-top: 10px;">
                <div class="hw-group-head">
                  <span class="hw-group-label" x-text="g.label"></span>
                  <span class="hw-muted" x-text="g.suggestions.length + ' примеров · выбрано ' + hwGroupSelectedCount(g)"></span>

                </div>
                {{-- Сотня примеров уровня разложена на подуровни по двадцать:
                     внутри уровня они идут от простых к сложным. --}}
                <div class="hw-pills" x-show="hwChunks(g).length > 1" style="margin-bottom:0">
                  <template x-for="chunk in hwChunks(g)" :key="chunk.key">
                    <button type="button" class="hw-pill" :class="hwChunkKey[g.key] === chunk.key ? 'active' : ''"
                            @click="hwChooseChunk(g, chunk.key)">
                      <span x-text="chunk.short"></span>
                      <span x-show="hwGroupSelectedCount(chunk)" style="opacity:.7"
                            x-text="' · ' + hwGroupSelectedCount(chunk)"></span>
                    </button>
                  </template>
                </div>
                <div class="hw-cards">
                  <template x-for="(s, si) in hwSkillVisible(g)" :key="g.key + '-' + si">
                    <label class="hw-card" :class="hwIsSelected(s) ? 'active' : ''">
                      <input type="checkbox" :checked="hwIsSelected(s)" @change="hwToggle(s)">
                      <span class="hw-card-body">
                        <span class="hw-card-text" x-html="renderLatex(s.preview_text)"></span>
                      </span>
                    </label>
                  </template>
                  <button type="button" class="ns-toggle-all" style="align-self: flex-start;"
                          x-show="hwVisiblePool(g).length > hwSkillPreview"
                          @click="hwSkillExpanded[g.key] = !hwSkillExpanded[g.key]; typeset()"
                          x-text="hwSkillExpanded[g.key] ? 'Свернуть' : ('Ещё ' + (hwVisiblePool(g).length - hwSkillPreview))"></button>
                </div>
              </div>
            </template>
          </div>

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
      // 🔍 Разбор домашки: pending — предложено, planned — уже в повестке урока
      reviewPending: [],
      reviewPlanned: [],
      reviewPicked: [],
      reviewOpen: true,
      reviewBusy: false,
      reviewDone: [],
      // Просмотрщик тетради (контракт партиала pwa._shared.photo-viewer)
      photos: [],
      viewer: false,
      vi: 0,
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
      hwSelectedKeys: [],       // ключи выбранных задач (аналоги и скиллы вместе)
      hwSelectedStudents: [],   // id выбранных учеников
      hwDeadline: '',
      // 📚 По скиллам: банк «Скиллы» без привязки к задачам урока
      hwMode: 'lesson',         // lesson | skills
      hwSkillsLoading: false,
      hwSkillTopics: [],        // [{id, title}]
      hwSkillTopicId: '',
      hwSkillTasksLoading: false,
      hwSkillGroups: [],        // [{key, label, suggestions:[{bank, refs, preview_text}]}]
      hwSkillPreview: 6,        // сколько карточек группы видно до «Ещё N»
      hwSkillExpanded: {},      // group_key → развёрнута ли группа
      hwChunkSize: 20,          // размер подуровня
      hwChunkKey: {},           // group_key → выбранный подуровень
      // Компоновка под телефон: вкладки, меню «⋯», код крупно, шторка учеников
      tab: 'tasks',             // tasks | answers | review
      menuOpen: false,
      codeBig: false,
      peopleOpen: false,
      peek: null,               // [studentId, taskId] — открытая клетка матрицы

      async init() {
        await this.refreshState();
        await this.refreshReview();
        this.startPolling();
        this.waitForKatex();
      },

      /**
       * Разбор домашки — вторая стадия: что учитель отметил на проверке и что
       * уже поставил в повестку этого урока. Дёргается по действию, а не в
       * polling: список меняется только руками учителя.
       */
      async refreshReview() {
        const r = await fetch(`/lessons/${this.sessionId}/review-items`, {
          headers: { Accept: 'application/json' }, credentials: 'include',
        });
        if (!r.ok) return;
        const d = await r.json();
        this.reviewPending = d.pending || [];
        this.reviewPlanned = d.planned || [];
        // Погашенные локально карточки не воскрешаем: урок ещё идёт.
        this.reviewPicked = this.reviewPicked.filter(id => this.reviewPending.some(c => c.id === id));
        if (this.tab === 'review' && !this.reviewPending.length && !this.reviewPlanned.length) this.tab = 'tasks';
        this.typeset();
      },

      toggleReviewPick(id) {
        const i = this.reviewPicked.indexOf(id);
        if (i === -1) this.reviewPicked.push(id); else this.reviewPicked.splice(i, 1);
      },

      async planReview() {
        if (!this.reviewPicked.length || this.reviewBusy) return;
        this.reviewBusy = true;
        try {
          const r = await fetch(`/lessons/${this.sessionId}/review-items`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ item_ids: this.reviewPicked }),
          });
          if (!r.ok) { alert('Не удалось добавить разбор в урок'); return; }
          this.reviewPicked = [];
          await this.refreshReview();
        } finally {
          this.reviewBusy = false;
        }
      },

      async unplanReview(id) {
        const r = await fetch(`/lessons/${this.sessionId}/review-items/${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
          credentials: 'include',
        });
        if (!r.ok) { alert('Не удалось убрать разбор'); return; }
        await this.refreshReview();
      },

      /** «Разобрано» на уроке — визуальная отметка; в done пункт переводит конец урока. */
      markReviewDone(id) {
        if (!this.reviewDone.includes(id)) this.reviewDone.push(id);
      },

      /** Открыть тетрадь: фото берутся из конкретной карточки разбора. */
      openReviewPhotos(card, index) {
        this.photos = (card.photos || []).map(p => ({ src: p.url, full: p.full, label: p.label }));
        if (!this.photos.length) return;
        this.vi = index;
        this.viewer = true;
        document.body.style.overflow = 'hidden';
      },
      close() { this.viewer = false; document.body.style.overflow = ''; },
      step(d) { this.vi = (this.vi + d + this.photos.length) % this.photos.length; },

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

      /**
       * Ужать формулу до ширины карточки.
       *
       * Условие ЕГЭ — вводные слова плюс длинное выражение; даже с
       * освобождённым местом самые длинные из них шире экрана. Перенос внутри
       * формулы читается плохо, горизонтальная прокрутка прячет хвост, поэтому
       * кегль уменьшается ровно во столько раз, во сколько формула не влезла.
       * Ниже 0.75 не опускаемся: мельче строки «ege · Ответ» читать уже нельзя,
       * и такая формула уходит в прокрутку — на банке ЕГЭ таких нет.
       */
      /**
       * Приклеить к формуле точку или запятую, которая идёт сразу за ней.
       *
       * Формула выводится неделимой коробкой, а рядом с такой коробкой
       * браузеру можно переносить строку — и точка в конце предложения
       * уезжала на следующую строку одна («…треугольника ABC» / «. Окружность
       * с диаметром…»). Оборачиваем пару в неразрывный span.
       */
      glueTrailingPunctuation(el) {
        if (!el || !el.querySelectorAll) return;
        el.querySelectorAll('.katex').forEach((k) => {
          const host = this.mathHost(k, el);
          if (host.parentNode && host.parentNode.classList && host.parentNode.classList.contains('nb')) return;
          const next = host.nextSibling;
          if (!next || next.nodeType !== 3) return;
          const m = next.textContent.match(/^[.,;:!?)]+/);
          if (!m) return;
          const nb = document.createElement('span');
          nb.className = 'nb';
          host.parentNode.insertBefore(nb, host);
          nb.appendChild(host);
          nb.appendChild(document.createTextNode(m[0]));
          next.textContent = next.textContent.slice(m[0].length);
        });
      },

      /**
       * Внешний узел формулы.
       *
       * `renderMathInElement` кладёт `.katex` внутрь безымянного span, поэтому
       * у самой формулы `nextSibling` всегда пуст — и текст после неё искать
       * надо от этой обёртки, а не от `.katex`.
       */
      mathHost(k, root) {
        let node = k;
        while (node.parentNode && node.parentNode !== root && !node.nextSibling) node = node.parentNode;
        return node;
      },

      fitFormulas(el) {
        if (!el || !el.querySelectorAll) return;
        this.glueTrailingPunctuation(el);
        const base = parseFloat(getComputedStyle(el).fontSize) || 15;
        const floor = Math.min(1, 11 / base);       // не мельче строки «ege · Ответ»
        el.querySelectorAll('.katex').forEach((k) => {
          k.style.fontSize = '';                    // сброс: замеряем натуральную ширину
          // Хвост после формулы («.», «при a = 5») тоже просит места: без
          // запаса точка в конце условия уезжала на отдельную строку.
          const after = this.mathHost(k, el).nextSibling;
          const tail = after && after.textContent.trim() ? 14 : 0;
          const avail = el.clientWidth - tail;
          const need = k.scrollWidth;
          if (avail <= 0 || !need || need <= avail) return;
          k.style.fontSize = (Math.max(floor, avail / need) * 100).toFixed(1) + '%';
        });
      },

      /**
       * Условие с подпунктами «а) б) в)» — каждый со своей строки.
       *
       * В банке они лежат отдельными абзацами, и при выпрямлении разметки
       * перед каждым остаётся перевод строки. Остальные переносы там
       * случайные — в ОГЭ они рвут предложение посреди фразы («Окружность\nс
       * диаметром»), поэтому разбиваем ТОЛЬКО по подпунктам, а не по каждому
       * переводу строки.
       */
      // Условие ЕГЭ в разметке ФИПИ (см. fipi-condition-js); формулы $…$
      // внутри дорисует auto-render.
      fipiHtml(html) {
        if (!html) return '';
        this.typeset();
        return window.paloFipiHtml ? window.paloFipiHtml(html) : String(html);
      },

      taskConditionHtml(expr) {
        const s = String(expr || '');
        const parts = s.split(/\n(?=[ \t]*[абвгд]\))/);
        if (parts.length < 2) return this.renderLatex(s);
        return parts
          .map((part, i) => '<span class="' + (i === 0 ? 'cond-lead' : 'cond-sub') + '">'
            + this.renderLatex(part.trim()) + '</span>')
          .join('');
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
        if (/[а-яё]/i.test(s)) return this.escapeHtml(s.replace(/\$/g, '').slice(0, 40));
        // Чистая формула в $…$ (банк «Скиллы»): без маркеров это bare-latex,
        // текстом она показала бы «{,}» и «\cdot» как есть.
        const bare = s.replace(/^\s*\$+|\$+\s*$/g, '');
        if (bare.includes('$')) return this.escapeHtml(bare.replace(/\$/g, '').slice(0, 40));
        return this.renderLatex(bare.length > 40 ? bare.slice(0, 40).replace(/\\[a-z]*$/, '') : bare);
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
          // Только теперь в DOM появились .katex — до auto-render мерить нечего.
          this.$root.querySelectorAll('.lesson-task-expr').forEach((el) => this.fitFormulas(el));
        };
        typesetTimer = setTimeout(run, 60);
      },

      escapeHtml(s) {
        // Растры-обозначения ФИПИ внутри плоского условия остаются картинками.
        if (window.paloEscapeKeepingFipiImages) return window.paloEscapeKeepingFipiImages(s);
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
        const d = new Date();
        const dm = String(d.getDate()).padStart(2, '0') + '.' + String(d.getMonth() + 1).padStart(2, '0');
        // Выбранное из скиллов и из аналогов может лежать в одной домашке —
        // заголовок называет то, из чего она собрана.
        const picked = this.hwPickedSuggestions();
        const fromSkills = picked.some(s => s.bank === 'skills');
        const fromLesson = picked.some(s => s.bank !== 'skills');
        const topics = [];
        for (const g of this.hwGroups) {
          const m = String(g.label).match(/Тема\s+([^\s·]+)/);
          if (m && !topics.includes(m[1])) topics.push(m[1]);
        }
        const lessonTitle = topics.length ? `ДЗ по уроку ${dm} — темы ${topics.join(', ')}` : `ДЗ по уроку ${dm}`;
        const skillsTitle = `ДЗ по скиллам ${dm} — ${this.hwSkillTopicTitle() || 'скиллы'}`;
        if (fromSkills && !fromLesson) return skillsTitle;
        if (fromSkills && fromLesson) return `${lessonTitle} + скиллы`;
        return lessonTitle;
      },
      // Все карточки обоих режимов, отмеченные галочкой.
      hwPickedSuggestions() {
        const picked = [];
        for (const g of [...this.hwGroups, ...this.hwSkillGroups]) {
          for (const s of (g.suggestions || [])) {
            if (this.hwSelectedKeys.includes(this.hwKey(s))) picked.push(s);
          }
        }
        return picked;
      },
      // Собирает [{bank, refs}] по выбранным ключам из всех групп.
      hwPickerTasksJson() {
        return JSON.stringify(this.hwPickedSuggestions().map(s => ({ bank: s.bank, refs: s.refs })));
      },

      // --- 📚 По скиллам ---
      async hwSetMode(mode) {
        this.hwMode = mode;
        if (mode === 'skills' && !this.hwSkillTopics.length) await this.hwLoadSkillTopics();
      },
      hwSkillTopicTitle() {
        return (this.hwSkillTopics.find(t => String(t.id) === this.hwSkillTopicId) || {}).title || '';
      },
      async hwFetchSkills(params) {
        const q = new URLSearchParams({ bank: 'skills', ...params });
        const r = await fetch(`/lessons/picker-options?${q}`, { headers: { 'Accept': 'application/json' }, credentials: 'include' });
        if (!r.ok) throw new Error('load failed');
        return r.json();
      },
      async hwLoadSkillTopics() {
        this.hwSkillsLoading = true;
        try {
          const d = await this.hwFetchSkills({});
          this.hwSkillTopics = (d.topics || []).map(t => ({ id: String(t.id), title: t.title }));
          // Один навык — сразу показываем его примеры, пилюли ни к чему.
          if (this.hwSkillTopics.length && !this.hwSkillTopicId) await this.hwChooseSkillTopic(this.hwSkillTopics[0].id);
        } catch (e) {
          alert('Не удалось загрузить банк «Скиллы»');
        } finally {
          this.hwSkillsLoading = false;
        }
      },
      async hwChooseSkillTopic(id) {
        this.hwSkillTopicId = String(id);
        this.hwSkillGroups = [];
        this.hwSkillExpanded = {};
        this.hwSkillTasksLoading = true;
        try {
          const d = await this.hwFetchSkills({ topic_id: this.hwSkillTopicId });
          // Та же форма, что у аналогов урока: {bank, refs, preview_text} —
          // ключи, галочки и отправка общие.
          // Группа — подтип, если он есть: у «Сокращения дробей» это уровень
          // внутри класса, и без него три сотни карточек легли бы одной кучей.
          const groups = new Map();
          for (const t of (d.tasks || [])) {
            const key = String(t.subtype_key ?? t.group_key ?? '');
            const label = t.subtype_label
              ? `${t.group_label} · ${t.subtype_label}`
              : (t.group_label || '');
            if (!groups.has(key)) groups.set(key, { key, label, suggestions: [] });
            groups.get(key).suggestions.push({
              bank: 'skills',
              refs: { topic_id: this.hwSkillTopicId, zadanie_number: t.zadanie_number, task_id: t.id },
              preview_text: t.expression,
            });
          }
          this.hwSkillGroups = [...groups.values()];
          this.hwChunkKey = {};
        } catch (e) {
          alert('Не удалось загрузить примеры');
        } finally {
          this.hwSkillTasksLoading = false;
          this.typeset();
        }
      },
      // Подуровни уровня: по двадцать примеров, «№21–40» — следующая ступень.
      hwChunks(g) {
        const size = this.hwChunkSize;
        if (g.suggestions.length <= size * 2) return [];
        const chunks = [];
        for (let i = 0; i < g.suggestions.length; i += size) {
          const tasks = g.suggestions.slice(i, i + size);
          chunks.push({
            key: g.key + '#' + (i / size),
            short: '№' + (i + 1) + '–' + (i + tasks.length),
            suggestions: tasks,
          });
        }
        return chunks;
      },
      hwChooseChunk(g, key) {
        this.hwChunkKey[g.key] = key;
        this.hwSkillExpanded[g.key] = false;
        this.typeset();
      },
      // Пул текущего подуровня (или всей группы, если подуровней нет).
      hwVisiblePool(g) {
        const chunks = this.hwChunks(g);
        if (!chunks.length) return g.suggestions;
        const key = this.hwChunkKey[g.key] || chunks[0].key;
        return (chunks.find(c => c.key === key) || chunks[0]).suggestions;
      },
      hwSkillVisible(g) {
        const pool = this.hwVisiblePool(g);
        return this.hwSkillExpanded[g.key] ? pool : pool.slice(0, this.hwSkillPreview);
      },
      hwGroupSelectedCount(g) {
        return (g.suggestions || []).filter(s => this.hwIsSelected(s)).length;
      },
      // Быстрый набор: из каждой группы первые N ещё не выбранных — они идут
      // от простых к сложным, так что «по 3» даёт ровную домашку.
      hwSkillPickEach(n) {
        // Берём из каждого подуровня: иначе «по 3 из каждой» собирало бы
        // домашку из одних только самых простых примеров уровня.
        for (const g of this.hwSkillGroups) {
          for (const part of (this.hwChunks(g).length ? this.hwChunks(g) : [g])) {
            let added = 0;
            for (const s of part.suggestions) {
              if (added >= n) break;
              if (!this.hwIsSelected(s)) { this.hwSelectedKeys.push(this.hwKey(s)); added++; }
            }
          }
        }
      },
      hwSkillPickRandom(n) {
        const pool = this.hwSkillGroups.flatMap(g => g.suggestions).filter(s => !this.hwIsSelected(s));
        for (let i = pool.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1));
          [pool[i], pool[j]] = [pool[j], pool[i]];
        }
        for (const s of pool.slice(0, n)) this.hwSelectedKeys.push(this.hwKey(s));
      },

      async openHomework() {
        this.hwOpen = true;
        this.hwLoading = true;
        this.hwGroups = [];
        this.hwSelectedKeys = [];
        this.hwSubmitting = false;
        // Без задач на уроке аналогов не будет — открываем сразу скиллы.
        this.hwMode = this.tasks.length ? 'lesson' : 'skills';
        if (this.hwMode === 'skills' && !this.hwSkillTopics.length) this.hwLoadSkillTopics();
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
        // После урока следующий шаг почти всегда — домашка.
        this.openHomework();
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

      // --- компоновка под телефон ---
      formatCode(code) {
        const c = String(code || '');
        return c.length === 6 ? c.slice(0, 3) + ' ' + c.slice(3) : c;
      },

      async copyCode() {
        try { await navigator.clipboard.writeText(String(this.joinCode || '')); this.noteToast = 'Код скопирован'; }
        catch (e) { this.noteToast = 'Не удалось скопировать'; }
        setTimeout(() => { this.noteToast = ''; }, 1800);
      },

      dotClass(p) {
        const s = p.activity?.state;
        return s === 'present' ? 'present' : (s === 'away' ? 'away' : '');
      },

      firstName(p) {
        return String(p.name || ('#' + p.id)).split(' ')[0];
      },

      /** Из матрицы — к условию задачи на вкладке «Задачи». */
      goToTask(taskId) {
        this.tab = 'tasks';
        this.$nextTick(() => {
          const el = document.getElementById('lt-' + taskId);
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
      },

      /** Ученики, которым задача адресована, с их ответом — для строки под условием. */
      taskResults(task) {
        return this.participants
          .filter(p => !this.cellNotForStudent(p.id, task.id))
          .map(p => {
            const a = this.grid[p.id]?.[task.id];
            return {
              id: p.id,
              name: this.firstName(p),
              answer: a ? String(a.answer ?? '') : null,
              cls: a ? (a.is_correct ? 'ok' : 'bad') : '',
              flags: a ? (a.pasted ? ' 📥' : '') + (a.quick_after_away ? ' ⚡' : '') : '',
            };
          });
      },

      taskStatLine(task) {
        const rows = this.taskResults(task);
        const answered = rows.filter(r => r.answer !== null).length;
        const ok = rows.filter(r => r.cls === 'ok').length;
        if (!rows.length) return 'нет учеников';
        if (!answered) return 'ещё никто не ответил';
        return `верно ${ok} из ${answered}` + (answered < rows.length ? ` · ждём ${rows.length - answered}` : '');
      },

      mxCellClass(studentId, taskId) {
        const base = { 'live-cell-na': 'na', 'live-cell-ok': 'ok', 'live-cell-bad': 'bad' }[this.cellClass(studentId, taskId)] || '';
        const sel = this.peek && this.peek[0] === studentId && this.peek[1] === taskId ? ' sel' : '';
        return base + sel;
      },

      mxCellMark(studentId, taskId) {
        if (this.cellNotForStudent(studentId, taskId)) return '';
        const a = this.grid[studentId]?.[taskId];
        return a ? (a.is_correct ? '✓' : '✗') : '';
      },

      cellFlagged(studentId, taskId) {
        const a = this.grid[studentId]?.[taskId];
        return !!(a && (a.pasted || a.quick_after_away));
      },

      togglePeek(studentId, taskId) {
        if (this.cellNotForStudent(studentId, taskId)) return;
        const same = this.peek && this.peek[0] === studentId && this.peek[1] === taskId;
        this.peek = same ? null : [studentId, taskId];
      },

      peekHtml() {
        if (!this.peek) return '';
        const [sid, tid] = this.peek;
        const p = this.participants.find(x => x.id === sid);
        const t = this.tasks.find(x => x.id === tid);
        if (!p || !t) return '';
        const a = this.grid[sid]?.[tid];
        const e = v => this.escapeHtml(String(v ?? ''));
        let h = `<b>${e(p.name || '#' + p.id)} · задача ${t.position}</b><br>`;
        if (!a) return h + 'ещё не ответил';
        h += `ответил <span class="${a.is_correct ? 'ok' : 'bad'}">${e(a.answer)}</span>`;
        if (!a.is_correct && t.correct_answer) h += ` · верно <span class="ok">${e(t.correct_answer)}</span>`;
        if (a.pasted) h += ' · 📥 вставил из буфера';
        if (a.quick_after_away) h += ' · ⚡ ответил сразу после возврата';
        return h;
      },

      pctColor(taskId) {
        if (!this.taskAnsweredCount(taskId)) return 'var(--muted)';
        const pct = this.taskCorrectPct(taskId);
        return pct >= 70 ? 'var(--green)' : (pct >= 40 ? 'var(--yellow)' : 'var(--red)');
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
