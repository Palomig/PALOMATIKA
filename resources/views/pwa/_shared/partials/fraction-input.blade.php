{{-- Ввод смешанной дроби: целая часть, а справа числитель над знаменателем.

     На телефоне «две целых семь одиннадцатых» в одну строку не записать без
     подсказки: ученик пишет то «2 7/11», то «2целых7/11», то «2,63». Виджет
     собирает ответ сам и кладёт готовую строку «2 7/11» в обычное поле —
     проверка ответов такую запись понимает давно (см. parseFractionValue).

     Обычный ввод остаётся основным: дробную раскладку ученик открывает сам,
     иначе вид поля подсказывал бы, что ответ — дробь.

     Ждёт $target — CSS-селектор поля ответа внутри ближайшего $scope.
     $scope — селектор общего предка поля и виджета (форма, строка урока). --}}
@once
@push('styles')
  .frac-toggle {
    align-self: flex-start; margin-top: 6px; padding: 4px 8px; cursor: pointer;
    background: none; border: none; color: var(--muted);
    font-size: 12px; font-weight: 700; text-decoration: underline dotted;
  }
  .frac-toggle:hover { color: var(--text); }
  .frac-box {
    display: flex; align-items: center; gap: 10px; margin-top: 8px;
    padding: 10px 12px; border-radius: 10px;
    background: var(--surface2); border: 1px solid var(--border);
  }
  .frac-whole {
    width: 64px; text-align: center; padding: 10px 6px; border-radius: 8px;
    border: 1px solid var(--border); background: var(--surface);
    color: var(--text); font-size: 18px; font-family: ui-monospace, monospace;
  }
  .frac-stack { display: flex; flex-direction: column; align-items: center; gap: 3px; }
  .frac-stack input {
    width: 72px; text-align: center; padding: 6px; border-radius: 8px;
    border: 1px solid var(--border); background: var(--surface);
    color: var(--text); font-size: 16px; font-family: ui-monospace, monospace;
  }
  .frac-line { width: 72px; height: 2px; background: var(--text); opacity: .75; border-radius: 2px; }
  .frac-preview { margin-left: auto; font-size: 12px; color: var(--muted); font-weight: 700; }
@endpush

@push('scripts')
<script>
  // Собирает «2 7/11» из трёх полей и пишет результат в поле ответа.
  function mixedFraction(config) {
    return {
      fracOpen: false,
      whole: '', num: '', den: '',

      target() {
        return this.$el.closest(config.scope)?.querySelector(config.target) || null;
      },

      toggleFraction() {
        this.fracOpen = !this.fracOpen;
        if (this.fracOpen) {
          this.readFromTarget();
          this.$nextTick(() => this.$refs.fracWhole?.focus());
        }
      },

      // Уже введённое разбираем обратно: «2 7/11» → 2 и 7/11.
      readFromTarget() {
        const raw = (this.target()?.value || '').trim();
        const m = raw.match(/^(-?\d+)?\s*(?:(\d+)\s*\/\s*(\d+))?$/);
        if (!m) return;
        this.whole = m[1] || '';
        this.num = m[2] || '';
        this.den = m[3] || '';
      },

      compose() {
        const whole = String(this.whole).trim();
        const num = String(this.num).trim();
        const den = String(this.den).trim();
        const frac = num !== '' && den !== '' ? `${num}/${den}` : '';
        return [whole, frac].filter(Boolean).join(' ');
      },

      push() {
        const input = this.target();
        if (!input) return;
        input.value = this.compose();
        // Сообщаем хозяину поля: в уроке ответ уходит по input/blur.
        input.dispatchEvent(new Event('input', { bubbles: true }));
      },
    };
  }
</script>
@endpush
@endonce

<div x-data="mixedFraction({ scope: '{{ $scope }}', target: '{{ $target }}' })"
     style="display:flex; flex-direction:column; width:100%;">
  <button type="button" class="frac-toggle" @click="toggleFraction()"
          x-text="fracOpen ? 'обычный ввод' : 'записать дробью'"></button>

  <div class="frac-box" x-show="fracOpen" x-cloak>
    <input class="frac-whole" type="text" inputmode="numeric" placeholder="целая"
           x-ref="fracWhole" x-model="whole" @input="push()" aria-label="Целая часть">
    <div class="frac-stack">
      <input type="text" inputmode="numeric" placeholder="числ." x-model="num" @input="push()"
             aria-label="Числитель">
      <span class="frac-line"></span>
      <input type="text" inputmode="numeric" placeholder="знам." x-model="den" @input="push()"
             aria-label="Знаменатель">
    </div>
    <span class="frac-preview" x-text="compose() || 'ответ'"></span>
  </div>
</div>
