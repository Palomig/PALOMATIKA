{{--
  Условие банка ЕГЭ (разметка ФИПИ) на экранах урока: выбор заданий,
  подготовка урока и карточка ученика. Правила без тега <style>: в PWA
  стек `styles` уже лежит внутри <style>, а выбор заданий включает партиал
  в свой собственный блок.

  Отличие от витрины (tasks.partials.fipi-styles): таблицы соответствия
  «А–Г ↔ 1–4» раскладываются flex'ом, а не блоками — подпись «А)» остаётся
  на одной строке со своим содержимым, а два столбца встают рядом, пока
  помещаются, и друг под другом на телефоне.
--}}
  .fipi-condition { white-space: normal; font-size: 14px; line-height: 1.45; color: var(--text); }
  .fipi-condition p { margin: 0 0 .45em; }
  .fipi-condition p:last-child { margin-bottom: 0; }
  .fipi-condition img { max-width: 100%; height: auto; }
  /* KaTeX рисует знак корня инлайновым SVG, высоту ему задаёт обёртка. */
  .fipi-condition .katex svg { height: inherit; max-width: none; }
  /* Растры ФИПИ чёрным по прозрачному — на тёмном фоне нужна подложка.
     display обязателен: Tailwind-сброс делает картинки блочными, и
     обозначения внутри предложения рвали строку. */
  .fipi-condition img.fipi-inline {
    display: inline-block; background: #fff; border-radius: 3px;
    padding: 0 2px; height: 1.3em; width: auto; vertical-align: -0.26em;
  }
  .fipi-condition img.fipi-figure {
    display: block; width: auto; max-width: 100%; max-height: 220px;
    background: #fff; border-radius: 8px; padding: 6px; margin: 6px 0;
  }
  /* Таблицы-раскладки ФИПИ (соответствие, текст рядом с чертежом). */
  .fipi-condition table:not(.fipi-data) { display: block; width: 100%; max-width: 100%; border-collapse: collapse; }
  .fipi-condition table:not(.fipi-data) > tbody { display: block; width: 100%; }
  .fipi-condition table:not(.fipi-data) > tbody > tr { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 0 6px; }
  .fipi-condition table:not(.fipi-data) > tbody > tr > td { display: block; padding: 1px 0; max-width: 100%; vertical-align: top; }
  .fipi-condition table:not(.fipi-data) > tbody > tr > td:has(> table) { flex: 1 1 auto; min-width: 0; }
  .fipi-condition td > p:last-child { margin-bottom: 0; }
  .fipi-condition u { text-decoration: none; font-weight: 700; letter-spacing: .02em; }
  /* Таблицы данных (класс ставит paloFipiHtml): строки и столбцы как есть,
     недостающую ширину на телефоне отдаём прокрутке. */
  .fipi-condition .fipi-data-wrap { overflow-x: auto; overscroll-behavior-inline: contain; scrollbar-width: thin; margin: 6px 0; }
  .fipi-condition table.fipi-data { border-collapse: collapse; font-size: 12px; }
  .fipi-condition table.fipi-data td { padding: 4px 8px; border: 1px solid var(--border); text-align: center; vertical-align: middle; white-space: nowrap; }
  .fipi-condition table.fipi-data td:first-child { text-align: left; white-space: normal; }
  .fipi-condition table.fipi-data td p { margin: 0; }
