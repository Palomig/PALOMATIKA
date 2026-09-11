{{--
  Подготовка условия ЕГЭ (разметка ФИПИ) к выводу через x-html на экранах
  урока. Единственная задача — отличить настоящую таблицу данных (гостиницы,
  тарифы: три и больше столбцов, без рисунков и вложенных таблиц) от
  таблицы-раскладки, которой ФИПИ верстает соответствие «А–Г ↔ 1–4» и
  «текст рядом с чертежом». Первая получает класс `fipi-data` и прокрутку,
  вторую CSS раскладывает flex'ом (см. fipi-condition-css).
--}}
<script>
  window.paloFipiHtml = window.paloFipiHtml || function (html) {
    const tpl = document.createElement('template');
    tpl.innerHTML = String(html || '');
    tpl.content.querySelectorAll('table').forEach((table) => {
      if (table.querySelector('table, img')) return;
      let cols = 0;
      table.querySelectorAll('tr').forEach((tr) => { cols = Math.max(cols, tr.children.length); });
      if (cols < 3) return;
      table.classList.add('fipi-data');
      const wrap = document.createElement('div');
      wrap.className = 'fipi-data-wrap';
      table.parentNode.insertBefore(wrap, table);
      wrap.appendChild(table);
    });
    return tpl.innerHTML;
  };

  // Плоское условие (`expression`) экранируется как текст, но растры
  // ФИПИ внутри предложения — обозначения вроде «SABCD» — должны остаться
  // картинками. Пропускается только тег ровно той формы, что пишет импорт;
  // всё остальное экранируется, как раньше.
  window.paloEscapeKeepingFipiImages = window.paloEscapeKeepingFipiImages || function (text) {
    const esc = (t) => t.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const img = /<img class="fipi-(?:inline|figure)" src="\/ege-bank\/[^"<>]*" alt="[^"<>]*">/g;
    let out = '';
    let last = 0;
    for (const m of String(text).matchAll(img)) {
      out += esc(String(text).slice(last, m.index)) + m[0];
      last = m.index + m[0].length;
    }
    return out + esc(String(text).slice(last));
  };
</script>
