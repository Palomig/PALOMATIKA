{{--
  Панель ввода математического ответа: кнопки символов, которых нет на
  клавиатуре телефона (√, ±, ∞, ∪, ≠, ≥, ≤), и предпросмотр набранного
  через KaTeX.

  Нужна там, где ответ содержит подкоренное выражение или знаки сравнения:
  вторая часть ОГЭ (№20 и №23) и вступительная работа в 10 класс. В первой
  части ОГЭ ответ — число или последовательность цифр, панель туда не
  подключается.

  Подключение (страница уже должна включать partials.head-katex — оттуда
  берётся KaTeX для предпросмотра): подключить этот партиал один раз на
  страницу и проставить полю ввода атрибут data-mathpad:
    roots   — набор «√ ( ) /», для ответа-числа с корнем;
    full    — плюс «[ ] ; ± ∞ ∪», для множеств корней и промежутков;
    compare — «≠ > < ≥ ≤», для условий на параметр вида «b ≠ 1, b > 0».

  Панель добавляется скриптом после самого input, либо после элемента
  с data-mathpad-anchor, если input лежит внутри flex-строки с кнопкой.
  Новые input-ы (Alpine перерисовывает задания) подхватываются наблюдателем.
--}}
@once
  @push('styles')
    .mathpad { margin-top: 8px; }
    .mathpad-keys { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 2px; scrollbar-width: none; }
    .mathpad-keys::-webkit-scrollbar { display: none; }
    .mathpad-key {
      flex: 0 0 auto; min-width: 38px; height: 36px; padding: 0 10px;
      border: 1.5px solid var(--border); border-radius: 10px;
      background: var(--surface); color: var(--text);
      font-size: 16px; font-weight: 700; line-height: 1; cursor: pointer;
      display: inline-flex; align-items: center; justify-content: center;
      -webkit-tap-highlight-color: transparent;
    }
    .mathpad-key:active { background: var(--accent-bg); border-color: var(--accent); color: var(--accent); }
    .mathpad-preview {
      margin-top: 7px; min-height: 20px; font-size: 14px; color: var(--muted);
      display: flex; align-items: center; gap: 7px; overflow-x: auto; scrollbar-width: none;
    }
    .mathpad-preview::-webkit-scrollbar { display: none; }
    .mathpad-preview-label { flex: 0 0 auto; font-size: 10px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .mathpad-preview-body { color: var(--text); }
    .mathpad-preview-body .katex { font-size: 1.05em; }
  @endpush

  @push('scripts')
  <script>
  (function () {
    // Порядок важен: строка прокручивается, и то, что нужнее, должно быть
    // видно без скролла. Для промежутков это скобки обоих типов.
    var KEYS = {
      roots:   ['√', '(', ')', '/'],
      full:    ['√', '(', ')', '[', ']', ';', '/', '±', '∞', '∪'],
      compare: ['≠', '>', '<', '≥', '≤'],
    };

    function insertAtCursor(input, text) {
      var start = input.selectionStart, end = input.selectionEnd;
      if (start === null || start === undefined) {
        input.value += text;
      } else {
        input.value = input.value.slice(0, start) + text + input.value.slice(end);
        var caret = start + text.length;
        input.setSelectionRange(caret, caret);
      }
      // Alpine слушает input, без события x-model не обновится.
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.focus();
    }

    /**
     * Запись ученика → LaTeX для предпросмотра. Переводим только то, что
     * читается однозначно: корень, знаки и умножение. Дробь оставляем косой
     * чертой — «(3+√5)/2» и так понятно, а \frac тут легко наврать.
     */
    function toLatex(raw) {
      var s = String(raw);
      s = s.replace(/[−–—]/g, '-');
      s = s.replace(/\\?sqrt\s*/gi, '√');
      s = s.replace(/корень\s+из\s+/gi, '√');
      s = s.replace(/корень\s*/gi, '√');
      // √(...) и √12 → \sqrt{...}
      s = s.replace(/√\s*\(([^()]*)\)/g, '\\sqrt{$1}');
      s = s.replace(/√\s*(\d+(?:[.,]\d+)?)/g, '\\sqrt{$1}');
      s = s.replace(/√/g, '\\sqrt{\\;}');
      s = s.replace(/\*/g, '\\cdot ');
      s = s.replace(/±/g, '\\pm ');
      s = s.replace(/∞/g, '\\infty ');
      s = s.replace(/∪/g, '\\cup ');
      s = s.replace(/<=/g, '\\le ').replace(/>=/g, '\\ge ');
      s = s.replace(/!=|<>/g, '\\ne ');
      s = s.replace(/≠/g, '\\ne ').replace(/≥/g, '\\ge ').replace(/≤/g, '\\le ');
      return s;
    }

    function renderPreview(pad) {
      var input = pad._input;
      var body = pad.querySelector('.mathpad-preview-body');
      var wrap = pad.querySelector('.mathpad-preview');
      var value = (input.value || '').trim();
      if (!value) {
        wrap.style.visibility = 'hidden';
        body.textContent = '';
        return;
      }
      wrap.style.visibility = 'visible';
      if (window.katex) {
        try {
          window.katex.render(toLatex(value), body, { throwOnError: false, displayMode: false });
          return;
        } catch (e) { /* ниже — обычный текст */ }
      }
      body.textContent = value;
    }

    function detach(input) {
      if (input._mathpad) {
        input._mathpad.parentNode && input._mathpad.parentNode.removeChild(input._mathpad);
        input._mathpad = null;
      }
    }

    function attach(input) {
      if (!input.hasAttribute('data-mathpad')) { detach(input); return; }
      if (input._mathpad) return;

      var mode = input.getAttribute('data-mathpad') || 'roots';
      var keys = KEYS[mode] || KEYS.roots;

      var pad = document.createElement('div');
      pad.className = 'mathpad';
      pad._input = input;

      var row = document.createElement('div');
      row.className = 'mathpad-keys';
      keys.forEach(function (key) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mathpad-key';
        btn.textContent = key;
        // mousedown вместо click: не даём полю потерять фокус и каретку.
        btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          insertAtCursor(input, key);
          renderPreview(pad);
        });
        row.appendChild(btn);
      });
      pad.appendChild(row);

      var preview = document.createElement('div');
      preview.className = 'mathpad-preview';
      preview.style.visibility = 'hidden';
      preview.innerHTML = '<span class="mathpad-preview-label">видим так</span>'
        + '<span class="mathpad-preview-body"></span>';
      pad.appendChild(preview);

      var anchor = input.closest('[data-mathpad-anchor]') || input;
      anchor.parentNode.insertBefore(pad, anchor.nextSibling);
      input._mathpad = pad;

      input.addEventListener('input', function () { renderPreview(pad); });
      renderPreview(pad);
    }

    function scan(root) {
      if (root.querySelectorAll) {
        Array.prototype.forEach.call(root.querySelectorAll('[data-mathpad]'), attach);
      }
      if (root.matches && root.matches('[data-mathpad]')) attach(root);
    }

    function init() {
      scan(document);
      // Alpine пересоздаёт карточку задания при переходе — ловим новые поля.
      // Атрибут отслеживаем отдельно: :data-mathpad проставляется уже после
      // вставки узла, к моменту childList-события его может ещё не быть.
      new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
          if (m.type === 'attributes') {
            attach(m.target);
            return;
          }
          Array.prototype.forEach.call(m.addedNodes, function (node) {
            if (node.nodeType === 1) scan(node);
          });
        });
      }).observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['data-mathpad'],
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
    // KaTeX грузится с defer — перерисуем предпросмотры, когда появится.
    window.addEventListener('load', function () {
      document.querySelectorAll('.mathpad').forEach(renderPreview);
    });
  })();
  </script>
  @endpush
@endonce
