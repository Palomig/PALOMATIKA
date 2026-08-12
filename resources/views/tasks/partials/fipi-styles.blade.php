{{--
    Оформление условия банка ФИПИ — одно на все страницы, где оно выводится.

    Тег <style> лежит внутри партиала, а решение, КУДА его поставить,
    остаётся за страницей: витрина отдаёт правила через @push('styles')
    (layouts/topic выводит стек в <head>), а самостоятельные страницы вроде
    печатного варианта включают партиал прямо в разметке — там @push уже
    поздний и молча теряется.
--}}
<style> обязательна: `head-config` выводит стек `styles`
         ПОСЛЕ закрытия своего тега, и push без тега печатает правила текстом
         поверх страницы. В PWA тот же стек лежит ВНУТРИ <style>, и там тег не
         нужен — одноимённые стеки с разной семантикой легко перепутать. --}}
    <style>
        /* Инлайновые SVG приходят с классами Tailwind (`max-w-[320px]`),
           ширину задают они; здесь только страховка от переполнения. */
        .fipi-condition svg, .fipi-options svg { max-width: 100%; height: auto; }
        .fipi-condition img, .fipi-options img { max-width: 100%; height: auto; }
        /* Растры ЕГЭ. Своих SVG для этого банка нет, и по решению Стаса
           чертежи остаются картинками ФИПИ — а они чёрным по прозрачному и
           на тёмном фоне интерфейса почти не читаются. Отсюда белая
           подложка: чертёж выглядит вклеенным листом, обозначение внутри
           предложения — просто набранным символом. */
        /* Как чертежи ОГЭ: во всю ширину колонки, до 460px. Растры ФИПИ
           мелкие, в натуральном размере читаются плохо. */
        .fipi-condition img.fipi-figure {
            display: block; width: 100%; max-width: 460px; height: auto;
            background: #fff; border-radius: 8px; padding: 8px; margin: .5rem 0;
        }
        .fipi-condition img.fipi-inline, .fipi-options img.fipi-inline {
            /* display обязателен: Tailwind Preflight делает картинки
               блочными, и обозначения вроде «SABCD» вставали каждое с
               новой строки, разрывая предложение. */
            display: inline-block;
            background: #fff; border-radius: 3px; padding: 0 2px;
            height: 1.35em; width: auto; vertical-align: -0.28em;
        }
        .fipi-condition p { margin: 0 0 .6rem; }
        .fipi-condition p:last-child { margin-bottom: 0; }
        /* Условие и чертёж — соседние ячейки таблицы; в узкой колонке
           рисунок сжимается. На малой ширине раскладываем в столбик. */
        @media (max-width: 700px) {
            .fipi-condition table, .fipi-condition tbody,
            .fipi-condition tr, .fipi-condition td { display: block; width: 100%; }
        }
        .fipi-condition table { border-collapse: collapse; }
        .fipi-condition td { vertical-align: top; padding: 2px 6px; }
    </style>
