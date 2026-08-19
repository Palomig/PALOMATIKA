{{-- Карточка задачи в выборе заданий: рисунок, условие, ответ. Alpine-переменная `t`. --}}
<div class="task-item tp-card" :class="{ 'tp-selected': isSelected(t), 'is-existing': isExisting(t) }" @click="toggle(t)">
  <span class="tp-check" x-show="isSelected(t)">✓</span>
  <div class="tp-illus" x-show="cardSvg(t)" x-html="cardSvg(t)"></div>
  <template x-if="cardImage(t)">
    <div class="tp-illus">
      {{-- Путь из банка ЕГЭ уже абсолютный (/ege-bank/img/…),
           у ОГЭ — имя файла внутри папки темы. --}}
      <img :src="cardImageSrc(t)" alt="" loading="lazy">
    </div>
  </template>
  <div class="task-item-text" x-show="t.text" x-text="t.text"></div>
  <div class="task-item-text tp-expr" x-show="!t.text && t.expression" x-html="renderLatex(t.expression)"></div>
  <div class="answer-row">
    <template x-if="t.answer">
      <span><span class="answer-label">Ответ:</span> <span class="tp-answer" x-text="t.answer"></span></span>
    </template>
    <template x-if="!t.answer">
      <span class="tp-badge">без автопроверки</span>
    </template>
    <span class="tp-existing-note" x-show="isExisting(t)">уже добавлено</span>
  </div>
</div>
