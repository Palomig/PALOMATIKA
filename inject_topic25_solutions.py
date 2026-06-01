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


# ── Геометрия: вычисляемые точки, чтобы топология была корректной ────────────
import math as _m


def _R(p):
    return (round(p[0], 1), round(p[1], 1))


def mid(a, b):
    return ((a[0] + b[0]) / 2, (a[1] + b[1]) / 2)


def lerp(a, b, t):
    return (a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t)


def _len(a, b):
    return _m.hypot(a[0] - b[0], a[1] - b[1])


def _unit(p, q):
    dx, dy = q[0] - p[0], q[1] - p[1]
    d = _m.hypot(dx, dy) or 1
    return (dx / d, dy / d)


def xline(a, b, c, d):
    """Точка пересечения прямых ab и cd."""
    x1, y1 = a; x2, y2 = b; x3, y3 = c; x4, y4 = d
    den = (x1 - x2) * (y3 - y4) - (y1 - y2) * (x3 - x4)
    px = ((x1 * y2 - y1 * x2) * (x3 - x4) - (x1 - x2) * (x3 * y4 - y3 * x4)) / den
    py = ((x1 * y2 - y1 * x2) * (y3 - y4) - (y1 - y2) * (x3 * y4 - y3 * x4)) / den
    return (px, py)


def foot(p, a, b):
    """Основание перпендикуляра из p на прямую ab."""
    ax, ay = a; bx, by = b; px, py = p
    dx, dy = bx - ax, by - ay
    t = ((px - ax) * dx + (py - ay) * dy) / (dx * dx + dy * dy)
    return (ax + t * dx, ay + t * dy)


def ln(a, b, style=None):
    a, b = _R(a), _R(b)
    return f'<line x1="{a[0]}" y1="{a[1]}" x2="{b[0]}" y2="{b[1]}" {style or L}/>'


def circ(c, r, style=None):
    c = _R(c)
    return f'<circle cx="{c[0]}" cy="{c[1]}" r="{round(r, 1)}" {style or CIRC}/>'


def vtxP(p, name, dx=-13, dy=-6):
    p = _R(p)
    return vtx(p[0], p[1], name, dx, dy)


def dotP(p, name=None, dx=-15, dy=4):
    p = _R(p)
    s = f'<circle cx="{p[0]}" cy="{p[1]}" r="2.5" fill="#c8dce8"/>'
    if name:
        s += (f'<text x="{p[0] + dx}" y="{p[1] + dy}" font-size="13" '
              f'font-style="italic" fill="#c8dce8">{name}</text>')
    return s


def valAt(p, t, dx=0, dy=0):
    p = _R(p)
    return val(p[0] + dx, p[1] + dy, t)


def rightangle(corner, a, b, size=12):
    """Маркер прямого угла в вершине corner между лучами на a и b."""
    def u(q):
        dx, dy = q[0] - corner[0], q[1] - corner[1]
        d = _m.hypot(dx, dy) or 1
        return (dx / d, dy / d)
    ua, ub = u(a), u(b)
    p1 = _R((corner[0] + ua[0] * size, corner[1] + ua[1] * size))
    p2 = _R((corner[0] + (ua[0] + ub[0]) * size, corner[1] + (ua[1] + ub[1]) * size))
    p3 = _R((corner[0] + ub[0] * size, corner[1] + ub[1] * size))
    return f'<path d="M{p1[0]},{p1[1]} L{p2[0]},{p2[1]} L{p3[0]},{p3[1]}" {AUX}/>'


def circle_2pt_tangent(P1, P2, La, Lb):
    """Окружность через P1,P2, касающаяся прямой La-Lb. -> (центр, радиус, точка касания)."""
    mP = mid(P1, P2)
    nP = _unit((0, 0), (-(P2[1] - P1[1]), P2[0] - P1[0]))
    nL = _unit((0, 0), (-(Lb[1] - La[1]), Lb[0] - La[0]))
    p = (mP[0] - La[0]) * nL[0] + (mP[1] - La[1]) * nL[1]
    q = nP[0] * nL[0] + nP[1] * nL[1]
    R0 = _len(mP, P1)
    aa = q * q - 1; bb = 2 * p * q; cc = p * p - R0 * R0
    disc = max(0.0, bb * bb - 4 * aa * cc)
    cand = [(-bb + _m.sqrt(disc)) / (2 * aa), (-bb - _m.sqrt(disc)) / (2 * aa)]
    dd = _len(La, Lb) ** 2 or 1
    best = None
    for s in cand:
        O = (mP[0] + s * nP[0], mP[1] + s * nP[1])
        E = foot(O, La, Lb)
        r = _len(O, P1)
        t = ((E[0] - La[0]) * (Lb[0] - La[0]) + (E[1] - La[1]) * (Lb[1] - La[1])) / dd
        if best is None:
            best = (O, r, E)
        if -0.25 <= t <= 1.25:
            return (O, r, E)
    return best


def arc(center, a, b, r):
    """Дужка угла в center от направления на a до направления на b (золото)."""
    def ang(q):
        return _m.atan2(q[1] - center[1], q[0] - center[0])
    a0, a1 = ang(a), ang(b)
    p0 = _R((center[0] + r * _m.cos(a0), center[1] + r * _m.sin(a0)))
    p1 = _R((center[0] + r * _m.cos(a1), center[1] + r * _m.sin(a1)))
    d = a1 - a0
    while d <= -_m.pi:
        d += 2 * _m.pi
    while d > _m.pi:
        d -= 2 * _m.pi
    large = 1 if abs(d) > _m.pi else 0
    sweep = 1 if d > 0 else 0
    return (f'<path d="M{p0[0]},{p0[1]} A{round(r,1)},{round(r,1)} 0 {large} {sweep} '
            f'{p1[0]},{p1[1]}" stroke="#d4a855" stroke-width="1.4" fill="none"/>')


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
_A = (45, 200); _D = (295, 200); _B = (120, 75); _C = (190, 75)
_P = xline(_A, _B, _D, _C); _M = mid(_B, _C); _N = mid(_A, _D)
_K = mid(_A, _B); _L = mid(_C, _D)
_svg2 = (
    ln(_A, _B) + ln(_B, _C) + ln(_C, _D) + ln(_D, _A)       # трапеция
    + ln(_B, _P, DASH) + ln(_C, _P, DASH)                  # боковые до вершины P
    + ln(_P, _N, AUX)                                       # прямая P–M–N
    + ln(_K, _L, AUX)                                       # средняя линия
    + rightangle(_P, _A, _D, 11)                            # прямой угол при P
    + arc(_A, _B, _D, 20) + arc(_D, _C, _A, 20)             # углы 47°, 43°
    + vtxP(_A, 'A', -15, 6) + vtxP(_B, 'B', -14, -2) + vtxP(_C, 'C', 6, -2)
    + vtxP(_D, 'D', 6, 6) + vtxP(_P, 'P', -4, -8)
    + dotP(_M, 'M', 7, -4) + dotP(_N, 'N', -4, 16)
    + dotP(_K, 'K', -14, 2) + dotP(_L, 'L', 8, 2)
    + valAt(_A, '47°', 18, -4) + valAt(_D, '43°', -34, -4)
    + valAt(mid(_M, _N), '14', 7, 0) + valAt(mid(_K, _L), '16', -6, -8)
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
_B = (110, 70); _C = (230, 70); _A = (55, 195); _D = (285, 195)   # равноб. трапеция
_O = xline(_A, _C, _B, _D)                                        # пересечение диагоналей
_cen = (170, 70 + (195 - 70) / 2); _r = (195 - 70) / 2            # вписанная окружность
_K = (round(_O[0], 1), 70); _H = (round(_O[0], 1), 195)           # вертикаль через O
_svg3 = (
    circ(_cen, _r)                                                # вписанная окружность
    + ln(_B, _C) + ln(_C, _D) + ln(_D, _A) + ln(_A, _B)           # трапеция
    + ln(_A, _C, AUX) + ln(_B, _D, AUX)                           # диагонали
    + ln(_K, _H, DASH)                                            # высота через O
    + rightangle(_H, _A, _K, 10)
    + vtxP(_B, 'B', -12, -2) + vtxP(_C, 'C', 6, -2)
    + vtxP(_A, 'A', -14, 6) + vtxP(_D, 'D', 6, 6)
    + dotP(_O, 'O', 8, 0) + dotP(_K, 'K', -4, -6) + dotP(_H, 'H', -4, 16)
    + valAt(mid(_O, _K), 'x', -12, 0)
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
_A = (45, 205); _B = (105, 70); _C = (295, 70)
_D = (_A[0] + _C[0] - _B[0], _A[1] + _C[1] - _B[1])              # параллелограмм
_la = _len(_B, _C); _lb = _len(_C, _A); _lc = _len(_A, _B)
_O = ((_la * _A[0] + _lb * _B[0] + _lc * _C[0]) / (_la + _lb + _lc),
      (_la * _A[1] + _lb * _B[1] + _lc * _C[1]) / (_la + _lb + _lc))  # инцентр ABC
_H = foot(_O, _A, _D); _K = foot(_O, _A, _C); _M = foot(_O, _B, _C)
_r = _len(_O, foot(_O, _A, _B))
_svg4 = (
    ln(_A, _B) + ln(_B, _C) + ln(_C, _D) + ln(_D, _A)           # параллелограмм
    + ln(_A, _C, AUX)                                           # диагональ AC
    + circ(_O, _r)                                              # вписанная окружность △ABC
    + ln(_O, _H, AUX) + ln(_O, _K, AUX) + ln(_O, _M, AUX)       # перпендикуляры
    + ln(_O, _A, DASH)                                          # OA
    + rightangle(_H, _A, _O, 8) + rightangle(_K, _A, _O, 8) + rightangle(_M, _B, _O, 8)
    + vtxP(_A, 'A', -14, 8) + vtxP(_B, 'B', -12, -2) + vtxP(_C, 'C', 6, -2) + vtxP(_D, 'D', 6, 8)
    + dotP(_O, 'O', 8, -2) + dotP(_H, 'H', -4, 16) + dotP(_K, 'K', 6, 4) + dotP(_M, 'M', -4, -6)
    + valAt(mid(_O, _A), '25', -16, 0) + valAt(mid(_O, _H), '15', 6, 0) + valAt(mid(_O, _K), '7', 5, -4)
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
_A = (55, 200); _B = (100, 65); _C = (270, 65)
_D = (_A[0] + _C[0] - _B[0], _A[1] + _C[1] - _B[1])             # параллелограмм
_bA = (_A[0] + _unit(_A, _B)[0] + _unit(_A, _D)[0], _A[1] + _unit(_A, _B)[1] + _unit(_A, _D)[1])
_bB = (_B[0] + _unit(_B, _A)[0] + _unit(_B, _C)[0], _B[1] + _unit(_B, _A)[1] + _unit(_B, _C)[1])
_K = xline(_A, _bA, _B, _bB)                                    # точка пересечения биссектрис
_N = foot(_K, _A, _D); _M = foot(_K, _B, _C); _H = foot(_K, _A, _B)
_svg5 = (
    ln(_A, _B) + ln(_B, _C) + ln(_C, _D) + ln(_D, _A)          # параллелограмм
    + ln(_A, _K, AUX) + ln(_B, _K, AUX)                        # биссектрисы A и B
    + ln(_M, _N, DASH)                                         # высота через K
    + ln(_H, _K, AUX)                                          # KH ⊥ AB
    + rightangle(_H, _A, _K, 8) + rightangle(_N, _A, _K, 8)
    + vtxP(_A, 'A', -14, 8) + vtxP(_B, 'B', -12, -2) + vtxP(_C, 'C', 6, -2) + vtxP(_D, 'D', 6, 8)
    + dotP(_K, 'K', 8, 2) + dotP(_M, 'M', -4, -6) + dotP(_N, 'N', -4, 16) + dotP(_H, 'H', -16, 2)
    + valAt(mid(_B, _C), '2', 0, -6) + valAt(mid(_H, _K), '1', 0, -4)
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
# координаты построены так, что AB=BD и медиана AD ⟂ биссектрисе BE (как в задаче)
_A = (150, 90); _B = (80, 130); _C = (220, 210)
_D = mid(_B, _C)                                  # середина BC (медиана AD)
_E = xline(_A, _C, _B, (_B[0] + 10, _B[1]))        # BE через B, пересечение с AC
_O = xline(_A, _D, _B, _E)
_svg6 = (
    ln(_A, _B) + ln(_B, _C) + ln(_C, _A)          # треугольник
    + ln(_A, _D, AUX)                             # медиана AD
    + ln(_B, _E, AUX)                             # биссектриса BE
    + rightangle(_O, _A, _B, 9)                   # прямой угол при O
    + vtxP(_A, 'A', -14, 4) + vtxP(_B, 'B', -14, 0) + vtxP(_C, 'C', 6, 6)
    + dotP(_D, 'D', 7, 2) + dotP(_E, 'E', 7, -2) + dotP(_O, 'O', -14, -4)
    + valAt(mid(_B, _O), '24', 0, -6) + valAt(mid(_A, _O), '16', -16, 0)
    + valAt(mid(_O, _D), '16', 6, 0) + valAt(mid(_O, _E), '8', 0, 14)
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
_A = (45, 205); _C = (305, 205); _B = (150, 55)
_H = foot(_B, _A, _C)                              # основание высоты BH
_AB = _len(_A, _B); _AC = _len(_A, _C)
_E = lerp(_B, _C, _AB / (_AB + _AC))               # AE — биссектриса, E на BC
_F = xline(_A, _E, _B, _H)                          # F = AE ∩ BH
_svg7 = (
    ln(_A, _B) + ln(_B, _C) + ln(_C, _A)          # треугольник
    + ln(_B, _H, AUX)                             # высота BH
    + ln(_A, _E, AUX)                             # биссектриса AE
    + rightangle(_H, _A, _B, 9)                   # прямой угол при H
    + vtxP(_B, 'B', -4, -8) + vtxP(_A, 'A', -14, 6) + vtxP(_C, 'C', 6, 6)
    + dotP(_H, 'H', -4, 16) + dotP(_E, 'E', 7, -2) + dotP(_F, 'F', -16, 0)
    + valAt(mid(_A, _B), '25y', -20, -2) + valAt(mid(_A, _H), '24y', -8, 16)
    + valAt(mid(_B, _F), '25x', 7, 0) + valAt(mid(_F, _H), '24x', 7, 0)
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
# BC ≪ AD (как в прототипе), поэтому B близко к вершине P; окружность помещается
_P = (168, 40); _th = _m.radians(38)
_dirA = (-_m.sin(_th), _m.cos(_th)); _dirD = (_m.cos(_th), _m.sin(_th))   # PA ⟂ PD
_A = (_P[0] + _dirA[0] * 150, _P[1] + _dirA[1] * 150)
_D = (_P[0] + _dirD[0] * 150, _P[1] + _dirD[1] * 150)
_B = lerp(_P, _A, 0.16); _C = lerp(_P, _D, 0.16)               # BC ∥ AD
_O, _r, _E = circle_2pt_tangent(_A, _B, _C, _D)               # окр. через A,B кас. CD в E
_svg8 = (
    circ(_O, _r, DASH)                                        # окружность через A,B кас. CD
    + ln(_A, _B) + ln(_B, _C) + ln(_C, _D) + ln(_D, _A)       # трапеция
    + ln(_B, _P, DASH) + ln(_C, _P, DASH)                     # боковые до вершины P
    + rightangle(_P, _A, _D, 11)                              # прямой угол при P
    + vtxP(_P, 'P', -4, -8) + vtxP(_B, 'B', -14, -2) + vtxP(_C, 'C', 6, -2)
    + vtxP(_A, 'A', -14, 8) + vtxP(_D, 'D', 7, 4)
    + dotP(_E, 'E', 7, 2)
    + valAt(mid(_A, _B), '24', -16, 0)
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
_B = (88, 54); _C = (150, 54); _A = (88, 178); _D = (242, 178)   # прямоуг. трапеция, AB верт.
_O9, _r9, _E = circle_2pt_tangent(_C, _D, _A, _B)               # окр. через C,D кас. AB в E
_H = foot(_E, _C, _D)
_svg9 = (
    circ(_O9, _r9, DASH)                                        # окружность через C,D
    + ln(_B, _C) + ln(_B, _A) + ln(_A, _D) + ln(_C, _D)         # трапеция
    + ln(_E, _C, AUX) + ln(_E, _D, AUX) + ln(_E, _H, AUX)       # EC, ED, EH⊥CD
    + rightangle(_B, _A, _C, 9) + rightangle(_A, _B, _D, 9)
    + vtxP(_B, 'B', -14, 0) + vtxP(_C, 'C', 3, -6)
    + vtxP(_A, 'A', -14, 8) + vtxP(_D, 'D', 6, 8)
    + dotP(_E, 'E', -16, 4) + dotP(_H, 'H', 5, -2)
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
# радиусы намеренно усилены (30:60) для наглядности (концепт-схема), числа — подписи
_O1 = (150, 135); _r1 = 30; _O2 = (240, 135); _r2 = 60
_nx = (_r2 - _r1) / (_r1 + _r2); _ny = _m.sqrt(1 - _nx * _nx)
_nU = (_nx, -_ny); _nD = (_nx, _ny)
_Apt = (_O1[0] + _r1 * _nU[0], _O1[1] + _r1 * _nU[1])
_Cpt = (_O2[0] + _r2 * _nU[0], _O2[1] + _r2 * _nU[1])
_Bpt = (_O1[0] + _r1 * _nD[0], _O1[1] + _r1 * _nD[1])
_Dpt = (_O2[0] + _r2 * _nD[0], _O2[1] + _r2 * _nD[1])
_Ept = xline(_Apt, _Cpt, _Bpt, _Dpt)                            # вершина касательных
_Nn = (round(_Apt[0], 1), 135); _Kk = (round(_Cpt[0], 1), 135)
_svg10 = (
    circ(_O1, _r1) + circ(_O2, _r2)                             # две окружности
    + ln(_Ept, _O2, DASH)                                       # линия центров
    + ln(_Ept, _Cpt) + ln(_Ept, _Dpt)                          # общие касательные AC, BD
    + ln(_Apt, _Bpt, AUX) + ln(_Cpt, _Dpt, AUX)               # хорды AB, CD
    + dotP(_O1, 'O', -4, -6) + dotP(_O2, 'Q', 6, -6) + dotP(_Ept, 'E', -15, 2)
    + vtxP(_Apt, 'A', -14, -2) + vtxP(_Bpt, 'B', -14, 10)
    + vtxP(_Cpt, 'C', 6, -2) + vtxP(_Dpt, 'D', 6, 12)
    + valAt(_O1, '45', -8, 2) + valAt(_O2, '55', -8, 2)
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
_O = (175, 135); _Rr = 96


def _on11(a):
    return (_O[0] + _Rr * _m.cos(_m.radians(a)), _O[1] + _Rr * _m.sin(_m.radians(a)))


_A = _on11(203); _B = _on11(305); _C = _on11(35); _E = _on11(23)   # AE — диаметр
_K = foot(_B, _A, _O)                                              # K = BD ∩ AO, BD⟂AO
_D = xline(_B, _K, _A, _C)                                         # D на AC
_svg11 = (
    circ(_O, _Rr)                                                  # описанная окружность
    + ln(_A, _B) + ln(_B, _C) + ln(_C, _A)                        # треугольник
    + ln(_B, _D)                                                  # BD
    + ln(_A, _E, AUX)                                            # прямая AO (диаметр AE)
    + rightangle(_K, _A, _B, 9)                                   # BD ⟂ AO
    + vtxP(_A, 'A', -15, 4) + vtxP(_B, 'B', -4, -8) + vtxP(_C, 'C', 6, 4)
    + dotP(_O, 'O', 6, -4) + dotP(_K, 'K', -6, -8) + dotP(_D, 'D', -4, 16) + dotP(_E, 'E', 7, 2)
    + valAt(mid(_A, _B), '36', -16, 0) + valAt(mid(_A, _C), '54', 0, 16)
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
_B = (88, 138); _C = (252, 138); _O = mid(_B, _C); _Rr = _len(_B, _C) / 2  # окр. с диам. BC
_A = (150, 50)
_D = foot(_A, _B, _C)                                            # высота AD
# M, P — пересечения вертикали AD с окружностью
_dy = _m.sqrt(max(0.0, _Rr * _Rr - (_D[0] - _O[0]) ** 2))
_M = (_D[0], _O[1] - _dy); _P = (_D[0], _O[1] + _dy)
_N = foot(_B, _A, _C)                                            # высота BN
_H = xline(_A, _D, _B, _N)                                       # ортоцентр
_svg12 = (
    circ(_O, _Rr)                                                # окружность (диаметр BC)
    + ln(_B, _C) + ln(_A, _B) + ln(_A, _C)                      # треугольник
    + ln(_A, _P)                                                # высота AD (до P)
    + ln(_B, _N)                                                # высота BN
    + rightangle(_D, _A, _C, 9)
    + vtxP(_A, 'A', -4, -8) + vtxP(_B, 'B', -16, 4) + vtxP(_C, 'C', 6, 4)
    + dotP(_D, 'D', -14, 14) + dotP(_M, 'M', 6, -2) + dotP(_N, 'N', 6, -2)
    + dotP(_H, 'H', -15, 2) + dotP(_O, 'O', 4, 14) + dotP(_P, 'P', 4, 16)
    + valAt(mid(_A, _M), '3', 7, 0) + valAt(mid(_M, _D), '12', 7, 0)
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
_M = (175, 132); _Rr = 94


def _on13(a):
    return (_M[0] + _Rr * _m.cos(_m.radians(a)), _M[1] + _Rr * _m.sin(_m.radians(a)))


_A = _on13(180); _D = _on13(0)              # AD — диаметр
_B = _on13(214); _C = _on13(326)            # B, C сверху (симметрично)
_H = mid(_B, _C)                            # основание высоты MH к BC
_svg13 = (
    circ(_M, _Rr)                           # окружность с центром M
    + ln(_A, _B) + ln(_B, _C) + ln(_C, _D) + ln(_D, _A)   # четырёхугольник (AD — диаметр)
    + ln(_M, _B, AUX) + ln(_M, _C, AUX)     # радиусы MB, MC
    + ln(_M, _H, DASH)                       # высота MH ⟂ BC
    + rightangle(_H, _B, _M, 9)
    + vtxP(_A, 'A', -15, 4) + vtxP(_B, 'B', -14, -2) + vtxP(_C, 'C', 6, -2) + vtxP(_D, 'D', 6, 4)
    + dotP(_M, 'M', -4, 16) + dotP(_H, 'H', -2, -6)
    + valAt(mid(_M, _A), 'R', 0, -5) + valAt(mid(_M, _D), 'R', 0, -5)
    + valAt(mid(_M, _B), 'R', -10, 0) + valAt(mid(_M, _C), 'R', 6, 0)
    + valAt(mid(_B, _H), '6', 0, -5) + valAt(mid(_H, _C), '6', 0, -5)
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
_O = (178, 135); _Rr = 96


def _on14(a):
    return (_O[0] + _Rr * _m.cos(_m.radians(a)), _O[1] + _Rr * _m.sin(_m.radians(a)))


_A = _on14(248); _B = _on14(335); _C = _on14(40); _D = _on14(158)
_K = xline(_A, _C, _B, _D)                                  # пересечение диагоналей
# M на окружности: DM ∥ AC (вторая точка пересечения прямой через D с окружностью)
_u = _unit(_A, _C)
_tM = -2 * ((_D[0] - _O[0]) * _u[0] + (_D[1] - _O[1]) * _u[1])
_M = (_D[0] + _tM * _u[0], _D[1] + _tM * _u[1])
_svg14 = (
    circ(_O, _Rr)                                          # описанная окружность
    + ln(_A, _B) + ln(_B, _C) + ln(_C, _D) + ln(_D, _A)   # четырёхугольник
    + ln(_A, _C, AUX) + ln(_B, _D, AUX)                  # диагонали
    + ln(_D, _M, DASH) + ln(_A, _M, DASH)                # DM ∥ AC, AM
    + arc(_K, _A, _B, 16)                                  # угол 60° при K
    + vtxP(_A, 'A', -2, -8) + vtxP(_B, 'B', 6, 0) + vtxP(_C, 'C', 6, 10) + vtxP(_D, 'D', -16, 6)
    + dotP(_K, 'K', 6, 4) + dotP(_M, 'M', -15, 2)
    + valAt(_K, '60°', 8, -6)
    + valAt(mid(_A, _B), '39', 4, -4) + valAt(mid(_C, _D), '12', 0, 16)
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
_A = (64, 116); _C = (274, 116)                              # AC — горизонтально
_uB = _unit((0, 0), (_m.cos(_m.radians(-15)), _m.sin(_m.radians(-15))))  # луч AB под малым углом
_B = (_A[0] + 135 * _uB[0], _A[1] + 135 * _uB[1])
_M = lerp(_A, _C, 4.0 / 26); _N = lerp(_A, _C, 15.0 / 26)    # AM=4, AN=15 (масштаб)
_O15, _r15, _K = circle_2pt_tangent(_M, _N, _A, _B)          # окр. через M,N кас. AB в K
_svg15 = (
    circ(_O15, _r15, DASH)                                   # окружность через M, N
    + ln(_A, _C) + ln(_A, _B)                               # AC и луч AB
    + ln(_K, _M, AUX) + ln(_K, _N, AUX)                    # KM, KN
    + vtxP(_A, 'A', -15, 4) + vtxP(_C, 'C', 6, 4) + vtxP(_B, 'B', 5, -2)
    + dotP(_M, 'M', -4, 16) + dotP(_N, 'N', -4, 16) + dotP(_K, 'K', -14, -2)
    + valAt(mid(_A, _M), '4', 0, 16) + valAt(mid(_M, _N), '11', 0, 16)
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


# Чертёж серии как самостоятельный SVG — для поля illustration (страница темы /topics/25)
FIG = {1: _svg1, 2: _svg2, 3: _svg3, 4: _svg4, 5: _svg5, 6: _svg6, 7: _svg7, 8: _svg8,
       9: _svg9, 10: _svg10, 11: _svg11, 12: _svg12, 13: _svg13, 14: _svg14, 15: _svg15}

patched = 0
for block in data["blocks"]:
    for z in block["zadaniya"]:
        n = int(z["number"])
        if n in SOL:
            z["solution"] = palomatika_palette(SOL[n])
            z["illustration"] = palomatika_palette(SVG_OPEN + BG + FIG[n] + "</svg>")
            patched += 1
with open(path, "w", encoding="utf-8") as f:
    json.dump(data, f, ensure_ascii=False, indent=2)
print(f"✅ Добавлено решений: {patched}/15 в {path}")
