#!/usr/bin/env python3
"""
Инжектор подробных решений (HTML + KaTeX + SVG) в поле `solution` каждой из 15
серий topic_25.json. Источник разборов — сборник Школково «Все прототипы №25»
(uploads/Все_прототипы_задач_№25_из_банка_ФИПИ.pdf). Чертежи перерисованы в SVG
(своя векторная графика, не картинки из PDF).

Решения видны ТОЛЬКО учителям (роут /part2/solution/* защищён role:teacher,admin).
Каждый разбор — метод на конкретном образце-прототипе (числа примера могут
отличаться от чисел 6 вариаций серии — это нормально, показан способ).

Запуск:  python3 inject_topic25_solutions.py
"""
import json
import os

# ── SVG helpers — стиль паломатики (как GeometrySvgRenderer, тёмная тема) ─────
# Палитра: bg #0a1628, стороны #c8dce8, вспом./окружности #5a9fcf,
# известные значения и дуги углов #d4a855, вершины-крестики #7eb8da,
# подписи 'Times New Roman' italic #c8dce8.
SVG_OPEN = ('<svg viewBox="0 0 340 240" xmlns="http://www.w3.org/2000/svg" '
            'class="w-full max-w-[350px] h-auto mx-auto" '
            'font-family="\'Times New Roman\', serif">')
BG = '<rect width="100%" height="100%" fill="#0a1628"/>'
L = 'stroke="#c8dce8" stroke-width="2" fill="none"'         # основные линии (стороны)
AUX = 'stroke="#5a9fcf" stroke-width="1.6" fill="none"'     # вспомогательные / углы
DASH = 'stroke="#5a9fcf" stroke-width="1.4" fill="none" stroke-dasharray="6 4"'  # пунктир
CIRC = 'stroke="#5a9fcf" stroke-width="1.6" fill="none"'    # окружности задачи


def fig(svg_inner):
    return f'<div class="sol-figure">{SVG_OPEN}{BG}{svg_inner}</svg></div>'


def vtx(x, y, name, dx=-12, dy=-6):
    # крестик-маркер вершины #7eb8da + подпись #c8dce8 (как в GeometrySvgRenderer)
    return (f'<g transform="translate({x}, {y})">'
            f'<line x1="-5" y1="0" x2="5" y2="0" stroke="#7eb8da" stroke-width="1"/>'
            f'<line x1="0" y1="-5" x2="0" y2="5" stroke="#7eb8da" stroke-width="1"/>'
            f'<circle cx="0" cy="0" r="2" fill="none" stroke="#7eb8da" stroke-width="0.8"/></g>'
            f'<text x="{x + dx}" y="{y + dy}" font-size="14" font-style="italic" fill="#c8dce8">{name}</text>')


def val(x, y, t):
    return f'<text x="{x}" y="{y}" font-size="13" font-weight="bold" fill="#d4a855">{t}</text>'


SOL = {}

# ══ Серия 1 — прототип 25.3 (#57256). Трапеция AB=12, CD=13, BC=4 → S=78 ══════
_svg1 = (
    '<line x1="30" y1="40" x2="238" y2="40" ' + L + '/>'       # P..B..C — верхняя линия
    '<line x1="174" y1="200" x2="174" y2="40" ' + L + '/>'     # A..B — боковая (вертикальная)
    '<line x1="174" y1="200" x2="318" y2="200" ' + L + '/>'    # A..E..D — нижнее основание
    '<line x1="238" y1="40" x2="238" y2="200" ' + L + '/>'     # C..E — высота
    '<line x1="238" y1="40" x2="318" y2="200" ' + L + '/>'     # C..D — боковая
    '<line x1="30" y1="40" x2="174" y2="200" ' + L + '/>'      # P..A
    '<line x1="30" y1="40" x2="318" y2="200" ' + L + '/>'      # P..D (проходит через M)
    '<path d="M238,186 L252,186 L252,200" ' + AUX + '/>'       # прямой угол при E
    + vtx(30, 40, 'P', -16, -2) + vtx(174, 40, 'B', -8, -10) + vtx(238, 40, 'C', 3, -10)
    + vtx(174, 200, 'A', -16, 14) + vtx(238, 200, 'E', -3, 18) + vtx(318, 200, 'D', 8, 4)
    + '<circle cx="174" cy="120" r="2.5" fill="#c8dce8"/>'
    + '<text x="156" y="113" font-size="14" font-style="italic" fill="#c8dce8">M</text>'
    + val(92, 32, '9') + val(200, 32, '4') + val(150, 150, '12') + val(284, 118, '13')
    + val(200, 218, '4') + val(272, 218, '5')
)
SOL[1] = (
    '<p>Обозначим середину <i>AB</i> за точку <i>M</i>, тогда <i>BM&nbsp;=&nbsp;AM</i>. '
    'Пусть прямая <i>DM</i> пересекает прямую <i>BC</i> в точке <i>P</i>.</p>'
    + fig(_svg1) +
    '<p>Треугольники <i>PBM</i> и <i>DAM</i> равны (по стороне и двум углам: '
    '∠PBM&nbsp;=&nbsp;∠DAM как накрест лежащие при <i>PC&nbsp;∥&nbsp;AD</i>, ∠PMB&nbsp;=&nbsp;∠AMD как вертикальные). '
    'Так как <i>DM</i> — биссектриса ∠CDA и <i>PC&nbsp;∥&nbsp;AD</i>, то ∠CPD&nbsp;=&nbsp;∠PDA&nbsp;=&nbsp;∠CDP, '
    'значит △PCD равнобедренный и <i>PC&nbsp;=&nbsp;CD&nbsp;=&nbsp;13</i>.</p>'
    '<p>Из равенства треугольников <i>BP&nbsp;=&nbsp;AD</i>, поэтому</p>'
    '<div class="formula">$$AD = BP = CP - BC = 13 - 4 = 9.$$</div>'
    '<p>Проведём <i>CE&nbsp;∥&nbsp;AB</i>; тогда <i>ABCE</i> — параллелограмм, откуда '
    '<i>CE&nbsp;=&nbsp;AB&nbsp;=&nbsp;12</i>, <i>AE&nbsp;=&nbsp;BC&nbsp;=&nbsp;4</i> и <i>ED&nbsp;=&nbsp;AD−AE&nbsp;=&nbsp;5</i>.</p>'
    '<p>Для △CED: $CE^2 + ED^2 = 12^2 + 5^2 = 169 = 13^2 = CD^2$, значит по обратной теореме '
    'Пифагора ∠CED&nbsp;=&nbsp;90°, то есть <i>CE</i> — высота трапеции. Тогда</p>'
    '<div class="formula">$$S_{ABCD} = \\frac{BC + AD}{2}\\cdot CE = \\frac{4 + 9}{2}\\cdot 12 = 78.$$</div>'
    '<div class="answer">Ответ: 78. <span class="step-note">(В банке у каждой из 6 задач серии — свои числа; '
    'способ тот же: $S=\\frac{AB\\cdot CD}{2}$.)</span></div>'
)

# ══ Серия 2 — прототип 25.4 (#105312). Углы 47°,43°, отрезки 16,14 → 30;2 ═════
_svg2 = (
    '<line x1="40" y1="180" x2="300" y2="180" ' + L + '/>'      # A..D
    '<line x1="110" y1="60" x2="210" y2="60" ' + L + '/>'       # B..C
    '<line x1="40" y1="180" x2="110" y2="60" ' + L + '/>'       # A..B (->P)
    '<line x1="300" y1="180" x2="210" y2="60" ' + L + '/>'      # D..C (->P)
    '<line x1="110" y1="60" x2="160" y2="20" ' + DASH + '/>'    # B..P
    '<line x1="210" y1="60" x2="160" y2="20" ' + DASH + '/>'    # C..P
    '<line x1="160" y1="20" x2="170" y2="180" ' + AUX + '/>'    # P..N (через M)
    + vtx(160, 20, 'P', -4, -6) + vtx(110, 60, 'B', -14, 0) + vtx(210, 60, 'C', 6, 0)
    + vtx(40, 180, 'A', -14, 6) + vtx(300, 180, 'D', 6, 6)
    + '<circle cx="160" cy="60" r="2.5" fill="#111"/><text x="150" y="52" font-size="13" font-style="italic" fill="#111">M</text>'
    + '<circle cx="170" cy="180" r="2.5" fill="#111"/><text x="166" y="198" font-size="13" font-style="italic" fill="#111">N</text>'
    + '<text x="60" y="172" font-size="12" fill="#2563eb">47°</text>'
    + '<text x="262" y="172" font-size="12" fill="#2563eb">43°</text>'
)
SOL[2] = (
    '<p>Пусть угол <i>BAD&nbsp;=&nbsp;47°</i>, угол <i>CDA&nbsp;=&nbsp;43°</i>; <i>M</i> — середина <i>BC</i>, '
    '<i>N</i> — середина <i>AD</i>, <i>P</i> — точка пересечения прямых <i>AB</i> и <i>CD</i>.</p>'
    + fig(_svg2) +
    '<p>В △<i>APD</i>: ∠APD&nbsp;=&nbsp;180°−47°−43°&nbsp;=&nbsp;90°, значит △<i>APD</i> и △<i>BPC</i> — прямоугольные. '
    'Медианы из вершины прямого угла: $PN = \\tfrac{AD}{2}=\\tfrac{b}{2}$ и $PM = \\tfrac{BC}{2}=\\tfrac{a}{2}$. Тогда</p>'
    '<div class="formula">$$MN = PN - PM = \\frac{b-a}{2}.$$</div>'
    '<p>Средняя линия (между боковыми сторонами) равна $\\dfrac{a+b}{2}$. По условию два отрезка равны 16 и 14:</p>'
    '<div class="formula">$$\\begin{cases}\\dfrac{a+b}{2}=16\\\\ \\dfrac{b-a}{2}=14\\end{cases}'
    '\\;\\Rightarrow\\;\\begin{cases}a+b=32\\\\ b-a=28\\end{cases}\\;\\Rightarrow\\;b=30,\\;a=2.$$</div>'
    '<div class="answer">Ответ: основания 30 и 2.</div>'
)

# ══ Серия 3 — прототип 25.6 (#56380). Равноб. трапеция P=40, S=80 → 1,6 ═══════
_svg3 = (
    '<line x1="110" y1="60" x2="230" y2="60" ' + L + '/>'      # B..C верх
    '<line x1="60" y1="190" x2="280" y2="190" ' + L + '/>'    # A..D низ
    '<line x1="110" y1="60" x2="60" y2="190" ' + L + '/>'     # B..A
    '<line x1="230" y1="60" x2="280" y2="190" ' + L + '/>'    # C..D
    '<line x1="60" y1="190" x2="230" y2="60" ' + L + '/>'     # A..C диаг
    '<line x1="280" y1="190" x2="110" y2="60" ' + L + '/>'    # D..B диаг
    '<line x1="170" y1="60" x2="170" y2="190" ' + DASH + '/>' # K..H высота через O
    '<circle cx="170" cy="125" r="2.5" fill="#111"/><text x="176" y="124" font-size="13" font-style="italic" fill="#111">O</text>'
    + vtx(110, 60, 'B', -12, -2) + vtx(230, 60, 'C', 6, -2)
    + vtx(60, 190, 'A', -14, 6) + vtx(280, 190, 'D', 6, 6)
    + '<circle cx="170" cy="60" r="2.5" fill="#111"/><text x="158" y="54" font-size="13" font-style="italic" fill="#111">K</text>'
    + '<circle cx="170" cy="190" r="2.5" fill="#111"/><text x="158" y="206" font-size="13" font-style="italic" fill="#111">H</text>'
    + val(206, 96, '8') + val(238, 110, '10')
)
SOL[3] = (
    '<p>Пусть <i>AD&nbsp;&gt;&nbsp;BC</i> — основания, <i>O&nbsp;=&nbsp;AC∩BD</i>. Трапеция описанная, поэтому '
    '$AB+CD=BC+AD$. Так как она равнобедренная ($AB=CD$):</p>'
    + fig(_svg3) +
    '<div class="formula">$$2(AB+CD)=P=40\\;\\Rightarrow\\;AB+CD=20,\\quad BC+AD=20.$$</div>'
    '<p>Высота из площади: $S=\\dfrac{BC+AD}{2}\\cdot h$, откуда $h=\\dfrac{2\\cdot 80}{20}=8$. '
    'Боковая $AB=CD=10$; через прямоугольный △ с катетом <i>h</i> и гипотенузой <i>CD</i> '
    'находим $AD=16,\\;BC=4$.</p>'
    '<p>Высота <i>KH</i> проходит через <i>O</i>. △<i>BOC&nbsp;∼&nbsp;△DOA</i> по двум углам, коэффициент '
    '$\\dfrac{BC}{AD}=\\dfrac{4}{16}=\\dfrac14$. Если <i>OK&nbsp;=&nbsp;x</i> (до меньшего основания), <i>OH&nbsp;=&nbsp;8−x</i>, то</p>'
    '<div class="formula">$$\\frac{x}{8-x}=\\frac14\\;\\Rightarrow\\;4x=8-x\\;\\Rightarrow\\;x=1{,}6.$$</div>'
    '<div class="answer">Ответ: 1,6.</div>'
)

# ══ Серия 4 — прототип 25.15 (#50267). Параллелограмм, OA=25,OH=15,OK=7 → 924 ══
_svg4 = (
    '<line x1="60" y1="60" x2="240" y2="60" ' + DASH + '/>'    # B..M..C верх (прод.)
    '<line x1="40" y1="200" x2="290" y2="200" ' + L + '/>'    # A..H..D низ
    '<line x1="60" y1="60" x2="40" y2="200" ' + L + '/>'      # B..A
    '<line x1="240" y1="60" x2="290" y2="200" ' + DASH + '/>' # C..D
    '<line x1="40" y1="200" x2="240" y2="60" ' + L + '/>'     # A..C диаг
    '<circle cx="150" cy="130" r="42" ' + AUX + '/>'         # вписанная окр.
    '<circle cx="150" cy="130" r="2.5" fill="#111"/><text x="156" y="128" font-size="13" font-style="italic" fill="#111">O</text>'
    '<line x1="150" y1="130" x2="150" y2="200" ' + AUX + '/>' # O..H
    '<line x1="150" y1="130" x2="150" y2="88" ' + AUX + '/>'  # O..M
    + vtx(60, 60, 'B', -14, 0) + vtx(240, 60, 'C', 6, 0)
    + vtx(40, 200, 'A', -14, 6) + vtx(290, 200, 'D', 6, 6)
    + '<circle cx="150" cy="88" r="2.5" fill="#111"/><text x="150" y="80" font-size="13" font-style="italic" fill="#111">M</text>'
    + '<circle cx="150" cy="200" r="2.5" fill="#111"/><text x="150" y="216" font-size="13" font-style="italic" fill="#111">H</text>'
    + '<circle cx="183" cy="158" r="2.5" fill="#111"/><text x="188" y="160" font-size="13" font-style="italic" fill="#111">K</text>'
    + val(86, 150, '24') + val(120, 175, '15') + val(168, 140, '7')
)
SOL[4] = (
    '<p>Вписанная в △<i>ABC</i> окружность касается <i>AC, AB, BC</i> в точках <i>K, P, M</i>. '
    'Пусть <i>OH&nbsp;⊥&nbsp;AD</i>: по условию <i>OH&nbsp;=&nbsp;15</i>, <i>OA&nbsp;=&nbsp;25</i>, радиус <i>OK&nbsp;=&nbsp;7</i>.</p>'
    + fig(_svg4) +
    '<p>Так как <i>OM&nbsp;⊥&nbsp;BC</i>, <i>OH&nbsp;⊥&nbsp;AD</i>, <i>BC&nbsp;∥&nbsp;AD</i>, точки <i>M,O,H</i> на одной прямой и '
    '<i>MH&nbsp;=&nbsp;OM+OH&nbsp;=&nbsp;7+15&nbsp;=&nbsp;22</i> — высота параллелограмма.</p>'
    '<p>Из △<i>AOK</i>: $AK=\\sqrt{OA^2-OK^2}=\\sqrt{625-49}=24$. Обозначим $BP=BM=x,\\;CM=CK=y,\\;AP=AK=24$. '
    'Площадь △<i>ABC</i> двумя способами ($p\\,r=\\tfrac12 h\\cdot BC$):</p>'
    '<div class="formula">$$p=x+y+24,\\quad (x+y+24)\\cdot 7=\\tfrac12\\cdot 22\\cdot BC,\\;\\; BC=x+y.$$</div>'
    '<div class="formula">$$(BC+24)\\cdot 14 = 22\\,BC\\;\\Rightarrow\\;336=8\\,BC\\;\\Rightarrow\\;BC=42.$$</div>'
    '<div class="formula">$$S_{ABCD}=BC\\cdot MH = 42\\cdot 22 = 924.$$</div>'
    '<div class="answer">Ответ: 924.</div>'
)

# ══ Серия 5 — прототип 25.2 (#56382). Параллелограмм, BC=2, KH=1 → 4 ══════════
_svg5 = (
    '<line x1="70" y1="50" x2="250" y2="50" ' + L + '/>'      # B..M..C
    '<line x1="40" y1="190" x2="220" y2="190" ' + L + '/>'    # A..N..D
    '<line x1="70" y1="50" x2="40" y2="190" ' + L + '/>'      # B..A
    '<line x1="250" y1="50" x2="220" y2="190" ' + L + '/>'    # C..D
    '<line x1="40" y1="190" x2="150" y2="120" ' + AUX + '/>'  # бис A -> K
    '<line x1="70" y1="50" x2="150" y2="120" ' + AUX + '/>'   # бис B -> K
    '<line x1="150" y1="50" x2="150" y2="190" ' + DASH + '/>' # M..K..N высота
    '<line x1="55" y1="120" x2="150" y2="120" ' + AUX + '/>'  # H..K (=1)
    '<circle cx="150" cy="120" r="2.5" fill="#111"/><text x="156" y="118" font-size="13" font-style="italic" fill="#111">K</text>'
    + vtx(70, 50, 'B', -14, 0) + vtx(250, 50, 'C', 6, 0)
    + vtx(40, 190, 'A', -14, 6) + vtx(220, 190, 'D', 6, 6)
    + '<circle cx="150" cy="50" r="2.5" fill="#111"/><text x="148" y="42" font-size="13" font-style="italic" fill="#111">M</text>'
    + '<circle cx="150" cy="190" r="2.5" fill="#111"/><text x="148" y="206" font-size="13" font-style="italic" fill="#111">N</text>'
    + '<circle cx="55" cy="120" r="2.5" fill="#111"/><text x="40" y="118" font-size="13" font-style="italic" fill="#111">H</text>'
    + val(150, 44, '2') + val(95, 114, '1')
)
SOL[5] = (
    '<p>Опустим из <i>K</i> перпендикуляр <i>KH&nbsp;=&nbsp;1</i> на <i>AB</i> и проведём высоту <i>MN</i> к <i>BC</i> через <i>K</i>.</p>'
    + fig(_svg5) +
    '<p>△<i>AHK</i>&nbsp;=&nbsp;△<i>ANK</i> (по гипотенузе <i>AK</i> и острому углу, ведь <i>AK</i> — биссектриса), '
    'значит <i>NK&nbsp;=&nbsp;HK&nbsp;=&nbsp;1</i>. Аналогично △<i>HBK</i>&nbsp;=&nbsp;△<i>MBK</i>, поэтому <i>MK&nbsp;=&nbsp;HK&nbsp;=&nbsp;1</i>.</p>'
    '<div class="formula">$$MN = MK + KN = 1 + 1 = 2.$$</div>'
    '<p>Площадь параллелограмма — произведение основания на высоту:</p>'
    '<div class="formula">$$S_{ABCD}=BC\\cdot MN = 2\\cdot 2 = 4.$$</div>'
    '<div class="answer">Ответ: 4. <span class="step-note">(В общем виде $S = 2\\cdot BC\\cdot KH$.)</span></div>'
)

# ══ Серия 6 — прототип 25.1 (#105137). Бис.=медиана⊥, =32 → 8√13;16√13;24√5 ════
_svg6 = (
    '<line x1="50" y1="190" x2="290" y2="190" ' + L + '/>'    # A..E..C
    '<line x1="50" y1="190" x2="110" y2="40" ' + L + '/>'     # A..B
    '<line x1="110" y1="40" x2="290" y2="190" ' + L + '/>'    # B..C
    '<line x1="110" y1="40" x2="160" y2="190" ' + L + '/>'    # B..E (бис)
    '<line x1="50" y1="190" x2="210" y2="115" ' + L + '/>'    # A..D (медиана)
    '<rect x="118" y="120" width="11" height="11" ' + AUX + ' transform="rotate(20 123 125)"/>'
    + vtx(110, 40, 'B', -4, -8) + vtx(50, 190, 'A', -14, 6)
    + vtx(290, 190, 'C', 6, 6) + vtx(160, 190, 'E', -4, 18)
    + '<circle cx="210" cy="115" r="2.5" fill="#111"/><text x="216" y="114" font-size="13" font-style="italic" fill="#111">D</text>'
    + '<circle cx="124" cy="125" r="2.5" fill="#111"/><text x="106" y="124" font-size="13" font-style="italic" fill="#111">O</text>'
    + val(124, 90, '24') + val(78, 130, '16') + val(140, 145, '16') + val(150, 165, '8')
)
SOL[6] = (
    '<p>Пусть <i>O&nbsp;=&nbsp;BE∩AD</i>. В △<i>ABD</i> отрезок <i>BO</i> — биссектриса и высота '
    '(<i>BE&nbsp;⊥&nbsp;AD</i>), значит △<i>ABD</i> равнобедренный: <i>AB&nbsp;=&nbsp;BD</i>, а <i>BO</i> — ещё и медиана, '
    'поэтому $AO=OD=\\tfrac12 AD=16$.</p>'
    + fig(_svg6) +
    '<p>Так как <i>AD</i> — медиана △<i>ABC</i>, <i>BD&nbsp;=&nbsp;\\tfrac12 BC</i>, то $AB=BD=\\tfrac12 BC$. '
    'По свойству биссектрисы <i>BE</i>: $\\dfrac{AE}{EC}=\\dfrac{AB}{BC}=\\dfrac12$, откуда $\\dfrac{AE}{AC}=\\dfrac13$.</p>'
    '<p>По теореме Менелая (или через подобие с продолжением медианы) получаем '
    '$BO=3\\,OE$, а так как $BE=BO+OE$, то $OE=\\tfrac14 BE=\\tfrac14\\cdot 32=8$ и $BO=24$.</p>'
    '<p>Из прямоугольных △<i>BOA</i> и △<i>AOE</i>:</p>'
    '<div class="formula">$$AB=\\sqrt{AO^2+BO^2}=\\sqrt{16^2+24^2}=\\sqrt{832}=8\\sqrt{13},$$</div>'
    '<div class="formula">$$AE=\\sqrt{AO^2+OE^2}=\\sqrt{16^2+8^2}=\\sqrt{320}=8\\sqrt{5}.$$</div>'
    '<div class="formula">$$BC=2AB=16\\sqrt{13},\\qquad AC=3AE=24\\sqrt{5}.$$</div>'
    '<div class="answer">Ответ: $8\\sqrt{13};\\;16\\sqrt{13};\\;24\\sqrt{5}$.</div>'
)

# ══ Серия 7 — прототип 25.13 (#105612). Бис. делит высоту 25:24, BC=14 → 25 ════
_svg7 = (
    '<line x1="40" y1="200" x2="300" y2="200" ' + L + '/>'    # A..H..C
    '<line x1="40" y1="200" x2="150" y2="40" ' + L + '/>'     # A..B
    '<line x1="150" y1="40" x2="300" y2="200" ' + L + '/>'    # B..C
    '<line x1="150" y1="40" x2="150" y2="200" ' + L + '/>'    # B..H высота
    '<line x1="40" y1="200" x2="210" y2="95" ' + L + '/>'     # A..E бис
    '<rect x="139" y="189" width="11" height="11" ' + AUX + '/>'
    + vtx(150, 40, 'B', -4, -8) + vtx(40, 200, 'A', -14, 6) + vtx(300, 200, 'C', 6, 6)
    + '<circle cx="150" cy="200" r="2.5" fill="#111"/><text x="138" y="216" font-size="13" font-style="italic" fill="#111">H</text>'
    + '<circle cx="210" cy="95" r="2.5" fill="#111"/><text x="216" y="94" font-size="13" font-style="italic" fill="#111">E</text>'
    + '<circle cx="150" cy="120" r="2.5" fill="#111"/><text x="132" y="120" font-size="13" font-style="italic" fill="#111">F</text>'
    + val(90, 110, '25y') + val(156, 90, '25x') + val(156, 165, '24x') + val(95, 196, '24y')
)
SOL[7] = (
    '<p>Пусть <i>AE</i> — биссектриса ∠<i>BAC</i>, <i>BH</i> — высота, <i>F&nbsp;=&nbsp;AE∩BH</i>, причём <i>BF:FH&nbsp;=&nbsp;25:24</i>.</p>'
    + fig(_svg7) +
    '<p><i>AF</i> — биссектриса в △<i>ABH</i>, поэтому $\\dfrac{AH}{AB}=\\dfrac{HF}{FB}=\\dfrac{24}{25}$. '
    'В прямоугольном △<i>ABH</i>:</p>'
    '<div class="formula">$$\\cos\\angle BAH=\\frac{AH}{AB}=\\frac{24}{25}\\;\\Rightarrow\\;'
    '\\sin\\angle BAH=\\sqrt{1-\\Big(\\tfrac{24}{25}\\Big)^2}=\\frac{7}{25}.$$</div>'
    '<p>По теореме синусов в △<i>ABC</i>:</p>'
    '<div class="formula">$$R=\\frac{BC}{2\\sin\\angle BAH}=\\frac{14}{2\\cdot \\tfrac{7}{25}}=25.$$</div>'
    '<div class="answer">Ответ: 25.</div>'
)

# ══ Серия 8 — прототип 25.14 (#54132). Трапеция AD=34,BC=2, AB=24 → 13,5 ══════
_svg8 = (
    '<line x1="60" y1="170" x2="290" y2="170" ' + L + '/>'    # A..D
    '<line x1="100" y1="100" x2="170" y2="100" ' + L + '/>'   # B..C
    '<line x1="60" y1="170" x2="150" y2="40" ' + L + '/>'     # A..B..P
    '<line x1="290" y1="170" x2="150" y2="40" ' + L + '/>'    # D..C..P
    '<rect x="139" y="48" width="11" height="11" ' + AUX + ' transform="rotate(35 144 53)"/>'
    '<circle cx="195" cy="135" r="62" ' + DASH + '/>'        # окружность через A,B кас. CD
    + vtx(150, 40, 'P', -4, -6) + vtx(100, 100, 'B', -14, 0) + vtx(170, 100, 'C', 6, 0)
    + vtx(60, 170, 'A', -14, 6) + vtx(290, 170, 'D', 6, 6)
    + '<circle cx="205" cy="118" r="2.5" fill="#111"/><text x="210" y="116" font-size="13" font-style="italic" fill="#111">E</text>'
    + val(96, 145, '24')
)
SOL[8] = (
    '<p>Пусть <i>P&nbsp;=&nbsp;AB∩CD</i>. Так как сумма углов при <i>AD</i> равна 90°, то ∠<i>APD&nbsp;=&nbsp;90°</i>. '
    'Окружность через <i>A, B</i> касается <i>CD</i> в точке <i>E</i>.</p>'
    + fig(_svg8) +
    '<p>△<i>BPC&nbsp;∼&nbsp;△APD</i> (∠P общий, <i>BC&nbsp;∥&nbsp;AD</i>), коэффициент $\\dfrac{BC}{AD}=\\dfrac{2}{34}$. '
    'Если <i>BP&nbsp;=&nbsp;x</i>, то $\\dfrac{x}{24+x}=\\dfrac{2}{34}$, откуда $x=1{,}5$ и <i>AP&nbsp;=&nbsp;25,5</i>.</p>'
    '<p>Центр <i>O</i> равноудалён от <i>A,B</i>: <i>OH&nbsp;⊥&nbsp;AB</i> — медиана равнобедренного △<i>AOB</i>, '
    '$AH=\\tfrac{AB}{2}=12$, тогда <i>HP&nbsp;=&nbsp;HB+BP&nbsp;=&nbsp;12+1,5&nbsp;=&nbsp;13,5</i>. '
    'В прямоугольнике <i>PEOH</i> радиус $R=OE=HP=13{,}5$.</p>'
    '<div class="answer">Ответ: 13,5. <span class="step-note">(В общем виде $R=\\tfrac{PA+PB}{2}$, '
    '$PA=\\tfrac{AB\\cdot AD}{AD-BC}$.)</span></div>'
)

# ══ Серия 9 — прототип 25.7 (#55905). Трапеция AB⊥BC, AD=4,BC=2 → 2√2 ═════════
_svg9 = (
    '<line x1="70" y1="60" x2="140" y2="60" ' + L + '/>'      # B..C
    '<line x1="70" y1="60" x2="70" y2="200" ' + L + '/>'      # B..A (AB верт)
    '<line x1="70" y1="200" x2="250" y2="200" ' + L + '/>'    # A..D
    '<line x1="140" y1="60" x2="250" y2="200" ' + L + '/>'    # C..D
    '<circle cx="160" cy="135" r="78" ' + DASH + '/>'        # окр. через C,D кас. AB
    '<line x1="70" y1="135" x2="140" y2="60" ' + AUX + '/>'   # E..C
    '<line x1="70" y1="135" x2="250" y2="200" ' + AUX + '/>'  # E..D
    '<line x1="70" y1="135" x2="118" y2="103" ' + AUX + '/>'  # E..H (⊥CD)
    '<rect x="62" y="64" width="9" height="9" ' + AUX + '/>'  # прямой угол при B
    '<rect x="62" y="190" width="9" height="9" ' + AUX + '/>' # прямой угол при A
    + vtx(70, 60, 'B', -14, 0) + vtx(140, 60, 'C', 2, -6)
    + vtx(70, 200, 'A', -14, 8) + vtx(250, 200, 'D', 6, 8)
    + '<circle cx="70" cy="135" r="2.5" fill="#111"/><text x="54" y="135" font-size="13" font-style="italic" fill="#111">E</text>'
    + '<circle cx="118" cy="103" r="2.5" fill="#111"/><text x="122" y="100" font-size="13" font-style="italic" fill="#111">H</text>'
)
SOL[9] = (
    '<p>Проведём <i>EH&nbsp;⊥&nbsp;CD</i> — искомое расстояние. Так как <i>AB&nbsp;⊥&nbsp;BC</i>, трапеция '
    'прямоугольная и <i>AB&nbsp;⊥&nbsp;AD</i>. Проведём <i>EC</i> и <i>ED</i>.</p>'
    + fig(_svg9) +
    '<p>По теореме об угле между касательной и хордой: ∠<i>ECD&nbsp;=&nbsp;∠AED</i> и ∠<i>CDE&nbsp;=&nbsp;∠BEC</i>. '
    'Отсюда △<i>AED&nbsp;∼&nbsp;△HCE</i> и △<i>BEC&nbsp;∼&nbsp;△HDE</i>, что даёт</p>'
    '<div class="formula">$$\\frac{HE}{AD}=\\frac{CE}{ED},\\qquad \\frac{CE}{ED}=\\frac{BC}{HE}'
    '\\;\\Rightarrow\\;HE^2=BC\\cdot AD.$$</div>'
    '<div class="formula">$$HE=\\sqrt{BC\\cdot AD}=\\sqrt{2\\cdot 4}=2\\sqrt{2}.$$</div>'
    '<div class="answer">Ответ: $2\\sqrt{2}$.</div>'
)

# ══ Серия 10 — прототип 25.10 (#105374). Окружности 45,55 → 99 ════════════════
_svg10 = (
    '<circle cx="120" cy="130" r="48" ' + L + '/>'
    '<circle cx="232" cy="130" r="58" ' + L + '/>'
    '<line x1="30" y1="130" x2="300" y2="130" ' + DASH + '/>'  # линия центров E..
    '<line x1="40" y1="130" x2="110" y2="86" ' + L + '/>'      # E..A касат верх
    '<line x1="40" y1="130" x2="110" y2="174" ' + L + '/>'     # E..B касат низ
    '<line x1="110" y1="86" x2="206" y2="78" ' + L + '/>'      # A..C
    '<line x1="110" y1="174" x2="206" y2="182" ' + L + '/>'    # B..D
    '<line x1="110" y1="86" x2="110" y2="174" ' + AUX + '/>'   # AB
    '<line x1="206" y1="78" x2="206" y2="182" ' + AUX + '/>'   # CD
    + '<circle cx="120" cy="130" r="2.5" fill="#111"/><text x="112" y="126" font-size="12" font-style="italic" fill="#111">O</text>'
    + '<circle cx="232" cy="130" r="2.5" fill="#111"/><text x="236" y="126" font-size="12" font-style="italic" fill="#111">Q</text>'
    + '<circle cx="110" cy="86" r="2.5" fill="#111"/><text x="100" y="80" font-size="12" font-style="italic" fill="#111">A</text>'
    + '<circle cx="110" cy="174" r="2.5" fill="#111"/><text x="100" y="190" font-size="12" font-style="italic" fill="#111">B</text>'
    + '<circle cx="206" cy="78" r="2.5" fill="#111"/><text x="210" y="74" font-size="12" font-style="italic" fill="#111">C</text>'
    + '<circle cx="206" cy="182" r="2.5" fill="#111"/><text x="210" y="196" font-size="12" font-style="italic" fill="#111">D</text>'
    + val(96, 150, '45') + val(238, 165, '55')
)
SOL[10] = (
    '<p>Пусть <i>O, Q</i> — центры (радиусы 45 и 55), <i>E</i> — точка пересечения касательных <i>AC</i> и <i>BD</i>, '
    '<i>M</i> — точка касания окружностей. Точки <i>E, O, M, Q</i> лежат на одной прямой (биссектриса угла <i>E</i>).</p>'
    + fig(_svg10) +
    '<p>△<i>AEB</i> и △<i>CED</i> равнобедренные, их биссектрисы <i>EN</i> и <i>EK</i> — высоты, поэтому '
    '<i>AB&nbsp;∥&nbsp;CD</i>, и искомое расстояние — <i>NK</i> (на линии центров).</p>'
    '<p>Через прямоугольные △<i>OHQ</i> (где <i>OH&nbsp;⊥&nbsp;CQ</i>): $HQ=CQ-AO=10$, $OQ=45+55=100$, '
    'и подобия с △<i>CKQ</i>, △<i>ANO</i> дают</p>'
    '<div class="formula">$$KQ=\\frac{CQ\\cdot HQ}{OQ}=\\frac{55\\cdot 10}{100}=5{,}5,\\qquad '
    'NO=\\frac{AO\\cdot HQ}{OQ}=\\frac{45\\cdot 10}{100}=4{,}5.$$</div>'
    '<div class="formula">$$NK = OQ - KQ + NO = 100 - 5{,}5 + 4{,}5 = 99.$$</div>'
    '<div class="answer">Ответ: 99.</div>'
)

# ══ Серия 11 — прототип 25.8 (#55284). AB=36,AC=54, O центр опис. → CD=30 ══════
_svg11 = (
    '<circle cx="170" cy="125" r="95" ' + L + '/>'
    '<line x1="80" y1="155" x2="262" y2="155" ' + L + '/>'    # A..D..C
    '<line x1="80" y1="155" x2="150" y2="42" ' + L + '/>'     # A..B
    '<line x1="150" y1="42" x2="262" y2="155" ' + L + '/>'    # B..C
    '<line x1="150" y1="42" x2="150" y2="155" ' + L + '/>'    # B..D
    '<line x1="80" y1="155" x2="170" y2="125" ' + AUX + '/>'  # A..O..E
    '<line x1="170" y1="125" x2="258" y2="95" ' + AUX + '/>'
    '<rect x="144" y="144" width="11" height="11" ' + AUX + '/>'
    + '<circle cx="170" cy="125" r="2.5" fill="#111"/><text x="176" y="124" font-size="12" font-style="italic" fill="#111">O</text>'
    + vtx(80, 155, 'A', -14, 6) + vtx(150, 42, 'B', -4, -8) + vtx(262, 155, 'C', 6, 6)
    + '<circle cx="150" cy="155" r="2.5" fill="#111"/><text x="140" y="172" font-size="12" font-style="italic" fill="#111">D</text>'
    + '<circle cx="258" cy="95" r="2.5" fill="#111"/><text x="262" y="92" font-size="12" font-style="italic" fill="#111">E</text>'
    + val(96, 110, '36') + val(190, 170, '54')
)
SOL[11] = (
    '<p>Продлим <i>AO</i> до пересечения с описанной окружностью в точке <i>E</i> (<i>AE</i> — диаметр). '
    'Пусть <i>K&nbsp;=&nbsp;BD∩AO</i>; так как <i>BD&nbsp;⊥&nbsp;AO</i>, то ∠<i>AKB&nbsp;=&nbsp;∠AKD&nbsp;=&nbsp;90°</i>.</p>'
    + fig(_svg11) +
    '<p>∠<i>ABE&nbsp;=&nbsp;∠ACE&nbsp;=&nbsp;90°</i> (опираются на диаметр). Из подобий △<i>ABK∼△AEB</i> и '
    '△<i>AKD∼△ACE</i>:</p>'
    '<div class="formula">$$AB^2=AK\\cdot AE=AC\\cdot AD\\;\\Rightarrow\\;AD=\\frac{AB^2}{AC}=\\frac{36^2}{54}=24.$$</div>'
    '<div class="formula">$$CD = AC - AD = 54 - 24 = 30.$$</div>'
    '<div class="answer">Ответ: 30. <span class="step-note">(В общем виде $CD=\\tfrac{AC^2-AB^2}{AC}$.)</span></div>'
)

# ══ Серия 12 — прототип 25.9 (#55906). AD=15, MD=12 → AH=5,4 ══════════════════
_svg12 = (
    '<circle cx="170" cy="150" r="92" ' + L + '/>'
    '<line x1="78" y1="150" x2="262" y2="150" ' + L + '/>'    # B..D..O..C
    '<line x1="150" y1="62" x2="78" y2="150" ' + L + '/>'     # A..B
    '<line x1="150" y1="62" x2="262" y2="150" ' + L + '/>'    # A..C
    '<line x1="150" y1="62" x2="150" y2="242" ' + L + '/>'    # A..D..P высота
    '<line x1="78" y1="150" x2="220" y2="98" ' + L + '/>'     # B..N высота
    '<rect x="144" y="139" width="11" height="11" ' + AUX + '/>'
    + vtx(150, 62, 'A', -4, -8) + vtx(78, 150, 'B', -16, 4) + vtx(262, 150, 'C', 6, 6)
    + '<circle cx="150" cy="150" r="2.5" fill="#111"/><text x="138" y="166" font-size="12" font-style="italic" fill="#111">D</text>'
    + '<circle cx="150" cy="112" r="2.5" fill="#111"/><text x="156" y="110" font-size="12" font-style="italic" fill="#111">M</text>'
    + '<circle cx="150" cy="135" r="2.5" fill="#111"/><text x="130" y="133" font-size="12" font-style="italic" fill="#111">H</text>'
    + '<circle cx="220" cy="98" r="2.5" fill="#111"/><text x="224" y="96" font-size="12" font-style="italic" fill="#111">N</text>'
    + '<circle cx="150" cy="242" r="2.5" fill="#111"/><text x="150" y="258" font-size="12" font-style="italic" fill="#111">P</text>'
    + val(156, 92, '3') + val(156, 200, '12')
)
SOL[12] = (
    '<p>Достроим полуокружность до окружности с центром <i>O</i> на <i>BC</i>. <i>BN</i> — высота '
    '(∠<i>BNC&nbsp;=&nbsp;90°</i> опирается на диаметр), значит <i>H</i> — ортоцентр на <i>AD</i>.</p>'
    + fig(_svg12) +
    '<p>Из △<i>AHN&nbsp;∼&nbsp;△ACD</i>: $AH\\cdot AD=AN\\cdot AC$. Продлим <i>AD</i> до точки <i>P</i> на окружности; '
    '<i>OD</i> — высота равнобедренного △<i>MOP</i>, поэтому <i>MD&nbsp;=&nbsp;PD&nbsp;=&nbsp;12</i>. Тогда '
    '<i>AM&nbsp;=&nbsp;AD−MD&nbsp;=&nbsp;3</i>, <i>AP&nbsp;=&nbsp;AD+PD&nbsp;=&nbsp;27</i>.</p>'
    '<p>По теореме о двух секущих $AN\\cdot AC=AM\\cdot AP=3\\cdot 27$, поэтому</p>'
    '<div class="formula">$$AH=\\frac{AM\\cdot AP}{AD}=\\frac{3\\cdot 27}{15}=\\frac{81}{15}=5{,}4.$$</div>'
    '<div class="answer">Ответ: 5,4. <span class="step-note">(В общем виде $AH=AD-\\tfrac{MD^2}{AD}$.)</span></div>'
)

# ══ Серия 13 — прототип 25.5 (#56386). Серед. равноуд., BC=12, ∠B=115,∠C=95 → 8√3 ═
_svg13 = (
    '<circle cx="175" cy="130" r="92" ' + L + '/>'
    '<line x1="83" y1="130" x2="267" y2="130" ' + L + '/>'    # A..M..D
    '<line x1="83" y1="130" x2="120" y2="55" ' + L + '/>'     # A..B
    '<line x1="120" y1="55" x2="235" y2="58" ' + L + '/>'     # B..C
    '<line x1="235" y1="58" x2="267" y2="130" ' + L + '/>'    # C..D
    '<line x1="175" y1="130" x2="120" y2="55" ' + AUX + '/>'  # M..B (R)
    '<line x1="175" y1="130" x2="235" y2="58" ' + AUX + '/>'  # M..C (R)
    '<line x1="175" y1="130" x2="177" y2="56" ' + DASH + '/>' # M..H высота
    + '<circle cx="175" cy="130" r="2.5" fill="#111"/><text x="170" y="146" font-size="12" font-style="italic" fill="#111">M</text>'
    + vtx(83, 130, 'A', -14, 6) + vtx(120, 55, 'B', -14, -2) + vtx(235, 58, 'C', 6, -2) + vtx(267, 130, 'D', 6, 6)
    + '<circle cx="177" cy="57" r="2.5" fill="#111"/><text x="180" y="52" font-size="12" font-style="italic" fill="#111">H</text>'
    + val(140, 100, 'R') + val(205, 100, 'R') + val(120, 122, 'R') + val(220, 122, 'R')
    + val(140, 52, '6') + val(195, 52, '6')
)
SOL[13] = (
    '<p>Точки <i>A, B, C, D</i> равноудалены от <i>M</i>, значит лежат на окружности с центром <i>M</i> '
    'и радиусом <i>R&nbsp;=&nbsp;AM</i>; <i>AD</i> — диаметр. Четырёхугольник вписанный, поэтому '
    '∠<i>A&nbsp;=&nbsp;180°−∠C&nbsp;=&nbsp;85°</i>.</p>'
    + fig(_svg13) +
    '<p>△<i>ABM</i> равнобедренный (<i>MA&nbsp;=&nbsp;MB&nbsp;=&nbsp;R</i>): ∠<i>ABM&nbsp;=&nbsp;∠BAM&nbsp;=&nbsp;85°</i>, '
    'тогда ∠<i>MBC&nbsp;=&nbsp;115°−85°&nbsp;=&nbsp;30°</i>. В равнобедренном △<i>MBC</i> высота '
    '<i>MH</i> — медиана: $BH=\\tfrac12 BC=6$.</p>'
    '<p>Из прямоугольного △<i>BHM</i>:</p>'
    '<div class="formula">$$\\cos 30^\\circ=\\frac{BH}{BM}=\\frac{6}{R}\\;\\Rightarrow\\;'
    'R=\\frac{6}{\\tfrac{\\sqrt3}{2}}=4\\sqrt3,\\qquad AD=2R=8\\sqrt3.$$</div>'
    '<div class="answer">Ответ: $8\\sqrt3$.</div>'
)

# ══ Серия 14 — прототип 25.11 (#105602). Вписанный AB=39,CD=12,∠AKB=60 → 3√79 ══
_svg14 = (
    '<circle cx="175" cy="135" r="98" ' + L + '/>'
    '<line x1="150" y1="40" x2="262" y2="95" ' + L + '/>'     # A..B
    '<line x1="262" y1="95" x2="225" y2="225" ' + L + '/>'    # B..C
    '<line x1="225" y1="225" x2="110" y2="200" ' + L + '/>'   # C..D
    '<line x1="110" y1="200" x2="150" y2="40" ' + L + '/>'    # D..A
    '<line x1="150" y1="40" x2="225" y2="225" ' + AUX + '/>'  # A..C
    '<line x1="262" y1="95" x2="110" y2="200" ' + AUX + '/>'  # B..D
    '<line x1="150" y1="40" x2="90" y2="100" ' + DASH + '/>'  # A..M (DM∥AC)
    '<line x1="90" y1="100" x2="110" y2="200" ' + DASH + '/>'
    + vtx(150, 40, 'A', -2, -8) + vtx(262, 95, 'B', 6, 0) + vtx(225, 225, 'C', 6, 10) + vtx(110, 200, 'D', -16, 8)
    + '<circle cx="90" cy="100" r="2.5" fill="#111"/><text x="74" y="100" font-size="12" font-style="italic" fill="#111">M</text>'
    + '<text x="172" y="150" font-size="11" fill="#2563eb">60°</text>'
    + val(200, 60, '39') + val(150, 215, '12')
)
SOL[14] = (
    '<p>Проведём <i>DM&nbsp;∥&nbsp;AC</i> (<i>M</i> на окружности). Тогда ∠<i>BMD&nbsp;=&nbsp;∠AKB&nbsp;=&nbsp;60°</i> '
    '(соответственные), а ∠<i>CAD&nbsp;=&nbsp;∠ADM</i> (накрест), откуда дуги <i>CD</i> и <i>AM</i> равны и '
    '<i>AM&nbsp;=&nbsp;CD&nbsp;=&nbsp;12</i>.</p>'
    + fig(_svg14) +
    '<p>Вписанный <i>ABDM</i> даёт ∠<i>MAB&nbsp;=&nbsp;180°−60°&nbsp;=&nbsp;120°</i>. По теореме косинусов в △<i>ABM</i>:</p>'
    '<div class="formula">$$BM^2=AB^2+AM^2-2\\,AB\\cdot AM\\cos120^\\circ=1521+144+468=2133,$$</div>'
    '<div class="formula">$$BM=3\\sqrt{237}.$$</div>'
    '<p>По теореме синусов (та же окружность описана и около △<i>ABM</i>):</p>'
    '<div class="formula">$$2R=\\frac{BM}{\\sin120^\\circ}=\\frac{3\\sqrt{237}}{\\tfrac{\\sqrt3}{2}}'
    '\\;\\Rightarrow\\;R=3\\sqrt{\\tfrac{237}{3}}=3\\sqrt{79}.$$</div>'
    '<div class="answer">Ответ: $3\\sqrt{79}$.</div>'
)

# ══ Серия 15 — прототип 25.12 (#105706). AM=4,AN=15, cos=√15/4 → R=8 ══════════
_svg15 = (
    '<line x1="40" y1="180" x2="300" y2="180" ' + L + '/>'    # A..M..N..C
    '<line x1="40" y1="180" x2="210" y2="60" ' + L + '/>'     # A..K..B луч AB
    '<line x1="130" y1="120" x2="100" y2="180" ' + AUX + '/>' # K..M
    '<line x1="130" y1="120" x2="235" y2="180" ' + AUX + '/>' # K..N
    '<circle cx="167" cy="160" r="55" ' + DASH + '/>'        # окружность через M,N кас. AB в K
    + vtx(40, 180, 'A', -14, 6)
    + '<circle cx="100" cy="180" r="2.5" fill="#111"/><text x="92" y="196" font-size="12" font-style="italic" fill="#111">M</text>'
    + '<circle cx="235" cy="180" r="2.5" fill="#111"/><text x="230" y="196" font-size="12" font-style="italic" fill="#111">N</text>'
    + vtx(300, 180, 'C', 6, 6)
    + '<circle cx="130" cy="120" r="2.5" fill="#111"/><text x="120" y="114" font-size="12" font-style="italic" fill="#111">K</text>'
    + vtx(210, 60, 'B', 4, -4)
    + val(64, 174, '4') + val(160, 174, '11')
)
SOL[15] = (
    '<p>Пусть <i>K</i> — точка касания окружности и луча <i>AB</i>. По теореме о касательной и секущей '
    '$AK^2=AM\\cdot AN=4\\cdot 15$, поэтому $AK=2\\sqrt{15}$.</p>'
    + fig(_svg15) +
    '<p>По теореме косинусов в △<i>AKM</i> (∠<i>KAM&nbsp;=&nbsp;∠BAC</i>, $\\cos=\\tfrac{\\sqrt{15}}{4}$):</p>'
    '<div class="formula">$$KM^2=AM^2+AK^2-2\\,AM\\cdot AK\\cos\\angle KAM=16+60-60=16,\\;\\; KM=4.$$</div>'
    '<p>△<i>AKM</i> равнобедренный, и по теореме об угле между касательной <i>AK</i> и хордой <i>KM</i>: '
    '∠<i>KNM&nbsp;=&nbsp;∠KAM</i>, значит $\\cos\\angle KNM=\\tfrac{\\sqrt{15}}{4}$, откуда $\\sin\\angle KNM=\\tfrac14$.</p>'
    '<p>По теореме синусов в △<i>KNM</i>:</p>'
    '<div class="formula">$$2R=\\frac{KM}{\\sin\\angle KNM}=\\frac{4}{\\tfrac14}=16\\;\\Rightarrow\\;R=8.$$</div>'
    '<div class="answer">Ответ: 8.</div>'
)

# ── Патчим topic_25.json ─────────────────────────────────────────────────────
path = os.path.join(os.path.dirname(os.path.abspath(__file__)),
                    "storage", "app", "tasks", "topic_25.json")
data = json.load(open(path, encoding="utf-8"))
def palomatika_palette(html: str) -> str:
    # Перекрашиваем оставшиеся инлайновые цвета вторичных точек/градусов
    # в палитру паломатики (тёмная тема).
    return (html
            .replace('fill="#111"', 'fill="#c8dce8"')   # вторичные точки и их подписи
            .replace('fill="#2563eb"', 'fill="#d4a855"')  # подписи углов (градусы)
            .replace('stroke="#2563eb"', 'stroke="#d4a855"'))


patched = 0
for block in data["blocks"]:
    for z in block["zadaniya"]:
        n = int(z["number"])
        if n in SOL:
            z["solution"] = palomatika_palette(SOL[n])
            patched += 1
with open(path, "w", encoding="utf-8") as f:
    json.dump(data, f, ensure_ascii=False, indent=2)
print(f"✅ Добавлено решений: {patched}/15 в {path}")
