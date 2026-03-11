---
name: geometry-svg
description: "Use when working with SVG geometry diagrams for OGE/EGE math tasks — creating, editing, or debugging SVG, running svg:bake, or editing *_geometry.json files. Covers the Static SVG System and GEOMETRY_SPEC rules."
---

# Geometry SVG System

## Static SVG Baking Architecture

**Проблема (была):** SVG генерировались динамически, что приводило к разным результатам в разных местах.

**Решение:** Static SVG Baking — SVG генерируются ОДИН раз и сохраняются в JSON.

### Workflow

```
topic_XX_geometry.json  ← РЕДАКТИРУЙ ЗДЕСЬ (точки, типы, параметры)
        │
        ▼
php artisan svg:bake XX  ← Команда генерации
        │
        ▼
topic_XX.json            ← Результат (SVG в task['svg']), используется на сайте
```

### Структура файлов

```
storage/app/tasks/
├── topic_15_geometry.json   ← Источник (треугольники) — РЕДАКТИРУЙ
├── topic_15.json            ← Результат с SVG — НЕ РЕДАКТИРУЙ вручную
├── topic_16_geometry.json   ← Источник (окружности) — РЕДАКТИРУЙ
├── topic_16.json            ← Результат с SVG — НЕ РЕДАКТИРУЙ вручную
└── topic_17.json            ← Пока без geometry
```

### Ключевые файлы

| Файл | Назначение |
|------|------------|
| `app/Console/Commands/BakeSvgToJson.php` | Artisan-команда `svg:bake` |
| `app/Services/GeometrySvgRenderer.php` | Рендерер SVG из geometry данных |
| `storage/app/tasks/topic_XX_geometry.json` | Исходные geometry данные |
| `storage/app/tasks/topic_XX.json` | Итоговый JSON с SVG строками |

### Команды

```bash
php artisan svg:bake 16          # Перегенерировать SVG для темы
php artisan svg:bake-ege 13      # Для ЕГЭ
php artisan cache:clear          # Очистить кэш после изменений
```

### Структура geometry JSON

```json
{
    "topic_id": "16",
    "meta": { "title": "Окружность", "description": "...", "color": "purple" },
    "blocks": [{
        "number": 1,
        "title": "ФИПИ",
        "zadaniya": [{
            "number": 2,
            "instruction": "Касательные к окружности",
            "type": "geometry",
            "svg_type": "tangent_lines",
            "tasks": [{
                "id": 9,
                "text": "Условие задачи...",
                "answer": "45",
                "params": { "angle": 68 }
            }]
        }]
    }]
}
```

### Типы SVG (svg_type) в GeometrySvgRenderer

**Тема 15 (Треугольники):** `bisector`, `median`, `angles_sum`, `external_angle`, `isosceles`, `right_triangle`, `similar`, `midline`, `equilateral`, `circumcircle`, `trig`, `area_theorem`

**Тема 16 (Окружности):** `square_circle_vertex`, `tangent_lines`, `inscribed_angle`, `diameters`, `diameter_points`, `inscribed_trapezoid`, `inscribed_square`, `circumscribed_shapes`, `triangle_inscribed_circle`, `quad_in_circle`, `center_on_side`, `trapezoid_in_circle`, `sine_theorem`

### Как view использует SVG

**Файл:** `resources/views/tasks/types/geometry.blade.php`

**Приоритет:** `task['svg']` → `task['image']` (inline SVG) → `task['image']` (файл)

### Важные геометрические правила (касательные)

Для истинной касательной точка A должна удовлетворять условию **OA ⊥ AP**:
```
Дано: O=(ox, oy), P=(px, py), R=радиус
1. (ax - ox)² + (ay - oy)² = R²  (точка на окружности)
2. (ax - ox)(px - ax) + (ay - oy)(py - ay) = 0  (OA ⊥ AP)
→ Решение даёт 2 точки касания
```

---

## GEOMETRY_SPEC — Правила создания SVG для геометрии

### Базовые принципы

1. **Все координаты хранятся как объекты** `{x, y}`
2. **Центр фигуры** — центроид (для треугольника: среднее арифметическое координат вершин)
3. **ViewBox** — рекомендуется `0 0 300 220` для треугольников
4. **Отступы от краёв** — минимум 30px, чтобы подписи не обрезались
5. **Концептуальные диаграммы** — рисунок показывает СУТЬ задачи, а не точные значения

### Стандартные размеры SVG диаграмм

| Параметр | Стандартное значение |
|----------|---------------------|
| `max-w-[...]` | **250px** (всегда фиксированный!) |
| `viewBox` | Пропорционален содержимому (200-275 по ширине) |

**Правило масштабирования:**
1. `max-w-[250px]` — **ФИКСИРОВАННЫЙ** размер для ВСЕХ диаграмм, НЕ менять!
2. `viewBox` — масштабируется под содержимое
3. При запросе "увеличить масштаб" — увеличивать координаты внутри `viewBox`, НЕ менять `max-w`

```html
<svg viewBox="0 0 250 200" class="w-full max-w-[250px] h-auto">
    <!-- содержимое -->
</svg>
```

### Правило заполнения viewBox (85%)

Геометрическая фигура должна заполнять **~85%** площади viewBox.

| viewBox | Стандартный размер | Заполнение фигуры |
|---------|-------------------|-------------------|
| `0 0 220 200` | Универсальный | Ширина ~187px, высота ~170px |

**Минимальные отступы от края viewBox:**
- Для подписей вершин (A, B, C): минимум **25px** от края
- Для фигуры без подписей: минимум **5px** от края

**Чек-лист при создании SVG:**
- [ ] Фигура занимает ~85% площади viewBox
- [ ] Подписи вершин не обрезаются (отступ 25px)
- [ ] Окружности полностью помещаются в viewBox
- [ ] viewBox = `0 0 220 200` (стандарт)
- [ ] max-w = `250px` (фиксированный)

### Правило концептуальных диаграмм

Геометрический рисунок должен иллюстрировать концепцию задачи, а НЕ буквально отображать числовые значения из условия.

**Что показывает рисунок:** тип фигуры, какие элементы даны, что требуется найти (`?` или зелёный), взаимное расположение.

**Чего НЕ должен:** точные пропорции углов/сторон, буквальное отображение экстремальных значений.

| Условие | Неправильно | Правильно |
|---------|-------------|-----------|
| Угол C = 177° | Почти плоский треугольник | Нормальный треугольник с подписью |
| Угол = 3° | Едва видимый острый угол | Обычный острый угол с подписью |
| Радиус = 2√5 через точку A | Вычислять точный R (R = √12500 ≈ 112) | Подобрать R визуально (~60-70) |
| Окружность описана | Вычислять точный R = abc/4S | Визуально провести через вершины |

**Принцип:** Рисунок — это схема для понимания задачи, а не чертёж в масштабе. Радиус и положение центра подбираются так, чтобы вся фигура помещалась в viewBox с отступами минимум 5px.

### Цветовая схема

| Элемент | Цвет | HEX |
|---------|------|-----|
| Основные линии (стороны) | Красный | `#dc2626` |
| Highlight / известные значения | Янтарный | `#f59e0b` |
| Вспомогательные линии (медианы, биссектрисы, высоты) | Зелёный | `#10b981` |
| Вспомогательные элементы (маркеры равенства) | Синий | `#3b82f6` |
| Подписи точек | Голубой | `#60a5fa` |
| Второстепенный текст (длины сторон) | Серый | `#94a3b8` |
| Прямой угол | Серый | `#666666` |

### Функция 1: labelPos() — позиционирование подписей

```javascript
function labelPos(point, center, distance = 22) {
    const dx = point.x - center.x;
    const dy = point.y - center.y;
    const len = Math.sqrt(dx * dx + dy * dy);
    if (len === 0) return { x: point.x, y: point.y - distance };
    return {
        x: point.x + (dx / len) * distance,
        y: point.y + (dy / len) * distance
    };
}
```

- Подпись располагается **в направлении от центра фигуры**
- Расстояние по умолчанию: **22px**
- Всегда `text-anchor="middle"` и `dominant-baseline="middle"`

### Функция 2: makeAngleArc() — дуга угла

```javascript
function makeAngleArc(vertex, point1, point2, radius) {
    const angle1 = Math.atan2(point1.y - vertex.y, point1.x - vertex.x);
    const angle2 = Math.atan2(point2.y - vertex.y, point2.x - vertex.x);
    const x1 = vertex.x + radius * Math.cos(angle1);
    const y1 = vertex.y + radius * Math.sin(angle1);
    const x2 = vertex.x + radius * Math.cos(angle2);
    const y2 = vertex.y + radius * Math.sin(angle2);
    let angleDiff = angle2 - angle1;
    while (angleDiff > Math.PI) angleDiff -= 2 * Math.PI;
    while (angleDiff < -Math.PI) angleDiff += 2 * Math.PI;
    const sweep = angleDiff > 0 ? 1 : 0;
    return `M ${x1} ${y1} A ${radius} ${radius} 0 0 ${sweep} ${x2} ${y2}`;
}
```

- `vertex` — вершина угла; `point1`, `point2` — точки на **сторонах** угла
- Радиус дуги: **20-35px**
- Дуга **ВСЕГДА** начинается и заканчивается на сторонах угла

### Функция 3: rightAnglePath() — прямой угол (квадратик)

```javascript
function rightAnglePath(vertex, p1, p2, size = 12) {
    const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
    const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);
    const c1 = { x: vertex.x + size * Math.cos(angle1), y: vertex.y + size * Math.sin(angle1) };
    const c2 = { x: vertex.x + size * Math.cos(angle2), y: vertex.y + size * Math.sin(angle2) };
    const diag = { x: c1.x + size * Math.cos(angle2), y: c1.y + size * Math.sin(angle2) };
    return `M ${c1.x} ${c1.y} L ${diag.x} ${diag.y} L ${c2.x} ${c2.y}`;
}
```

- Размер квадратика: **12-15px**, цвет: `#666666`
- **Порядок p1 и p2 влияет на направление!** Квадратик рисуется от p1 к p2 против часовой стрелки.
- Чтобы квадратик был **внутри** фигуры: точки p1 и p2 должны идти **по часовой стрелке** относительно vertex
- Если квадратик выходит наружу — **поменяйте p1 и p2 местами**

```
      B
     /|
    / |  ← прямой угол в C
   /  |
  A---C
```
- По часовой от C: сначала B, потом A
- `rightAnglePath(C, B, A, 15)` — квадратик внутри (правильно)
- `rightAnglePath(C, A, B, 15)` — квадратик СНАРУЖИ (неправильно!)

### Функция 3.1: isRightAngle() — определение прямого угла

```javascript
function isRightAngle(vertex, p1, p2) {
    const v1 = { x: p1.x - vertex.x, y: p1.y - vertex.y };
    const v2 = { x: p2.x - vertex.x, y: p2.y - vertex.y };
    const dot = v1.x * v2.x + v1.y * v2.y;
    return Math.abs(dot) < 1;
}
```

Всегда используйте `isRightAngle()` для определения вершины прямого угла, а не визуальную оценку координат.

### Функция 3.2: equalityTick() — маркер равенства сторон

```javascript
function equalityTick(p1, p2, t = 0.5, length = 8) {
    const mid = { x: p1.x + (p2.x - p1.x) * t, y: p1.y + (p2.y - p1.y) * t };
    const dx = p2.x - p1.x, dy = p2.y - p1.y;
    const len = Math.sqrt(dx * dx + dy * dy);
    const nx = -dy / len, ny = dx / len;
    const half = length / 2;
    return { x1: mid.x - nx * half, y1: mid.y - ny * half, x2: mid.x + nx * half, y2: mid.y + ny * half };
}
```

Черточка маркера **ДОЛЖНА быть перпендикулярна** стороне. НИКОГДА не используйте фиксированные смещения типа `x - 4, y - 5`.

### Функция 3.3: doubleEqualityTick() — двойной маркер равенства

```javascript
function doubleEqualityTick(p1, p2, t = 0.5, length = 8, gap = 4) {
    const dx = p2.x - p1.x, dy = p2.y - p1.y;
    const len = Math.sqrt(dx * dx + dy * dy);
    const ux = dx / len, uy = dy / len;
    const nx = -dy / len, ny = dx / len;
    const mid = { x: p1.x + dx * t, y: p1.y + dy * t };
    const half = length / 2, halfGap = gap / 2;
    const tick1 = {
        x1: mid.x - ux * halfGap - nx * half, y1: mid.y - uy * halfGap - ny * half,
        x2: mid.x - ux * halfGap + nx * half, y2: mid.y - uy * halfGap + ny * half
    };
    const tick2 = {
        x1: mid.x + ux * halfGap - nx * half, y1: mid.y + uy * halfGap - ny * half,
        x2: mid.x + ux * halfGap + nx * half, y2: mid.y + uy * halfGap + ny * half
    };
    return { tick1, tick2 };
}
```

**Правило:** Разные пары равных отрезков — разное количество черточек:
| Пара | Черточки | Функция |
|------|----------|---------|
| Первая (AM = MB) | 1 | `equalityTick()` |
| Вторая (BN = NC) | 2 | `doubleEqualityTick()` |

### Функция 4: pointOnLine() — точка на отрезке

```javascript
function pointOnLine(p1, p2, t) {
    return { x: p1.x + (p2.x - p1.x) * t, y: p1.y + (p2.y - p1.y) * t };
}
```

### Функция 5: labelOnSegment() — подпись длины стороны

```javascript
function labelOnSegment(p1, p2, offset = 15) {
    const mid = { x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2 };
    const dx = p2.x - p1.x, dy = p2.y - p1.y;
    const len = Math.sqrt(dx * dx + dy * dy);
    const nx = -dy / len, ny = dx / len;
    return { x: mid.x + nx * offset, y: mid.y + ny * offset };
}
```

### Функция 6: bisectorDirection() — направление биссектрисы

```javascript
function bisectorDirection(vertex, p1, p2) {
    const dx1 = p1.x - vertex.x, dy1 = p1.y - vertex.y;
    const len1 = Math.sqrt(dx1*dx1 + dy1*dy1);
    const u1 = { x: dx1/len1, y: dy1/len1 };
    const dx2 = p2.x - vertex.x, dy2 = p2.y - vertex.y;
    const len2 = Math.sqrt(dx2*dx2 + dy2*dy2);
    const u2 = { x: dx2/len2, y: dy2/len2 };
    const bx = u1.x + u2.x, by = u1.y + u2.y;
    const blen = Math.sqrt(bx*bx + by*by);
    return { x: bx/blen, y: by/blen };
}
```

### Функция 7: bisectorEndpoint() — пересечение биссектрисы со стороной

```javascript
function bisectorEndpoint(vertex, p1, p2, targetP1, targetP2) {
    const dir = bisectorDirection(vertex, p1, p2);
    const intersection = raySegmentIntersection(vertex, dir, targetP1, targetP2);
    if (intersection) return intersection;
    return { x: vertex.x + dir.x * 200, y: vertex.y + dir.y * 200 };
}

function raySegmentIntersection(rayOrigin, rayDir, segP1, segP2) {
    const dx = segP2.x - segP1.x, dy = segP2.y - segP1.y;
    const denom = rayDir.x * dy - rayDir.y * dx;
    if (Math.abs(denom) < 1e-10) return null;
    const t = ((segP1.x - rayOrigin.x) * dy - (segP1.y - rayOrigin.y) * dx) / denom;
    const s = ((segP1.x - rayOrigin.x) * rayDir.y - (segP1.y - rayOrigin.y) * rayDir.x) / denom;
    if (t > 0 && s >= 0 && s <= 1) {
        return { x: rayOrigin.x + t * rayDir.x, y: rayOrigin.y + t * rayDir.y };
    }
    return null;
}
```

### Функция: angleLabelPos() — позиция метки угла

```javascript
function angleLabelPos(vertex, p1, p2, labelRadius, bias = 0.5) {
    const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
    const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);
    let diff = angle2 - angle1;
    while (diff > Math.PI) diff -= 2 * Math.PI;
    while (diff < -Math.PI) diff += 2 * Math.PI;
    const midAngle = angle1 + diff * bias;
    return { x: vertex.x + labelRadius * Math.cos(midAngle), y: vertex.y + labelRadius * Math.sin(midAngle) };
}
```

- `labelRadius` должен быть **больше** радиуса дуги на 15-20px
- Минимальный отступ от линий: 20px от стороны/хорды, 15px от дуги, 8px от вершины

**ВАЖНО: Углы с биссектрисой** — метку полного угла размещать в **половине угла** (между стороной и биссектрисой), а не через `angleLabelPos(vertex, p1, p2)`:

```javascript
// Угол BAC = 68°, AD — биссектриса
const D = bisectorPoint(A, B, C);
angleLabelPos(A, B, D, 62, 0.6)  // ✅ В половине угла BAD, bias=0.6
```

Рекомендуемые параметры: радиус дуги = 45px, labelRadius = 62px, bias = 0.6, радиус половинных дуг = 30px.

### Правила для вспомогательных линий

#### Биссектриса
1. **ОБЯЗАТЕЛЬНО** вычисляется через `bisectorDirection()` / `bisectorEndpoint()` — нельзя хардкодить!
2. Должна доходить до противоположной стороны
3. Линия: `stroke-dasharray="6,4"`, цвет `#10b981`
4. Точка пересечения: круг r=3-4px, цвет `#10b981`
5. Две дуги половинных углов для визуализации деления

Альтернатива для треугольников:
```javascript
function bisectorPointTriangle(A, B, C) {
    const AB = Math.sqrt((B.x-A.x)**2 + (B.y-A.y)**2);
    const AC = Math.sqrt((C.x-A.x)**2 + (C.y-A.y)**2);
    return pointOnLine(B, C, AB / (AB + AC));
}
```

#### Медиана
- От вершины до **середины** противоположной стороны: `M = pointOnLine(A, C, 0.5)`
- Линия: `stroke-dasharray="6,4"`, цвет `#10b981`
- Метка длины медианы: от **середины медианы** `(B.x+M.x)/2 + 18`, цвет `#10b981`
- Метка длины основания: по центру, **ниже** на 38px, цвет `#f59e0b`

#### Высота
- Перпендикулярна основанию; H — основание высоты на стороне
- Прямой угол обозначается квадратиком через `rightAnglePath()`

### Правила для прямоугольного треугольника

1. Прямой угол: `rightAnglePath(C, A, B, 15)` — вершина первым аргументом
2. Катеты offset=**8px**, гипотенуза offset=**16px** для `labelOnSegment()`

### Правила размещения в viewBox

1. Вершины не ближе **35px** к краю viewBox
2. Окружности: `O.x - R > 5`, `O.x + R < width - 5`, аналогично по Y
3. Внешний угол: подпись вершины фиксировано `y + 25`, не через `labelPos()`

### Подписи вершин

```html
<text :x="labelPos(A, center, 24).x" :y="labelPos(A, center, 24).y"
    fill="#60a5fa" font-size="18" class="geo-label"
    text-anchor="middle" dominant-baseline="middle">A</text>
```

### CSS стили для SVG

```css
.geo-line { transition: stroke 0.2s ease, stroke-width 0.2s ease; }
.geo-point { transition: r 0.2s ease, fill 0.2s ease; }
.geo-label { font-family: 'Times New Roman', serif; font-style: italic; font-weight: 500; user-select: none; pointer-events: none; }
```

### Чек-лист перед отправкой SVG

- [ ] Подписи вершин через `labelPos()`, не накладываются на фигуру
- [ ] Дуги углов начинаются/заканчиваются на сторонах
- [ ] Биссектриса/медиана/высота доходит до противоположной стороны
- [ ] Метки углов не накладываются на дуги
- [ ] Для биссектрис: метка в половине угла с bias=0.6
- [ ] Для медианы: метка от середины линии `(B.x+M.x)/2 + 18`
- [ ] Метки длин не накладываются на линии
- [ ] Прямой угол обозначен квадратиком в правильной вершине
- [ ] Теорема Пифагора: катеты offset=8, гипотенуза offset=16
- [ ] Вспомогательные точки на сторонах треугольника
- [ ] Цветовая схема соответствует спецификации
