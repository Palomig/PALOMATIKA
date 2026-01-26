/**
 * GEOMETRY_SPEC — Общие функции для геометрических SVG диаграмм
 * Используется во всех темах: 15 (Треугольники), 16 (Окружности), 17 (Четырёхугольники) и т.д.
 */

// 1. Позиционирует подписи в направлении от центра фигуры
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

// 2. Рисует дугу угла строго между двумя сторонами
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

// 3. Рисует квадратик для прямого угла
function rightAnglePath(vertex, p1, p2, size = 12) {
    const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
    const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);
    const c1 = { x: vertex.x + size * Math.cos(angle1), y: vertex.y + size * Math.sin(angle1) };
    const c2 = { x: vertex.x + size * Math.cos(angle2), y: vertex.y + size * Math.sin(angle2) };
    const diag = { x: c1.x + size * Math.cos(angle2), y: c1.y + size * Math.sin(angle2) };
    return `M ${c1.x} ${c1.y} L ${diag.x} ${diag.y} L ${c2.x} ${c2.y}`;
}

// 4. Точка на отрезке (t=0 → p1, t=1 → p2, t=0.5 → середина)
function pointOnLine(p1, p2, t) {
    return {
        x: p1.x + (p2.x - p1.x) * t,
        y: p1.y + (p2.y - p1.y) * t
    };
}

// 5. Подпись длины стороны — перпендикулярно отрезку
function labelOnSegment(p1, p2, offset = 15, flipSide = false) {
    const mid = { x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2 };
    const dx = p2.x - p1.x;
    const dy = p2.y - p1.y;
    const len = Math.sqrt(dx * dx + dy * dy);
    let nx = -dy / len;
    let ny = dx / len;
    if (flipSide) { nx = -nx; ny = -ny; }
    return {
        x: mid.x + nx * offset,
        y: mid.y + ny * offset
    };
}

// 6. Позиция метки угла — ровно посередине между двумя сторонами
//    bias: 0.5 = точная середина, <0.5 = ближе к p1, >0.5 = ближе к p2
function angleLabelPos(vertex, p1, p2, labelRadius, bias = 0.5) {
    const angle1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
    const angle2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);

    let diff = angle2 - angle1;
    while (diff > Math.PI) diff -= 2 * Math.PI;
    while (diff < -Math.PI) diff += 2 * Math.PI;

    const midAngle = angle1 + diff * bias;

    return {
        x: vertex.x + labelRadius * Math.cos(midAngle),
        y: vertex.y + labelRadius * Math.sin(midAngle)
    };
}

// 7. Точка D на стороне BC для биссектрисы из A
function bisectorPoint(A, B, C) {
    const AB = Math.sqrt((B.x - A.x)**2 + (B.y - A.y)**2);
    const AC = Math.sqrt((C.x - A.x)**2 + (C.y - A.y)**2);
    const t = AB / (AB + AC);
    return pointOnLine(B, C, t);
}

// 7.1. Единичный вектор направления биссектрисы угла
function bisectorDirection(vertex, p1, p2) {
    // Единичный вектор от vertex к p1
    const dx1 = p1.x - vertex.x;
    const dy1 = p1.y - vertex.y;
    const len1 = Math.sqrt(dx1 * dx1 + dy1 * dy1);
    const u1 = { x: dx1 / len1, y: dy1 / len1 };

    // Единичный вектор от vertex к p2
    const dx2 = p2.x - vertex.x;
    const dy2 = p2.y - vertex.y;
    const len2 = Math.sqrt(dx2 * dx2 + dy2 * dy2);
    const u2 = { x: dx2 / len2, y: dy2 / len2 };

    // Биссектриса = сумма единичных векторов (нормализованная)
    const bx = u1.x + u2.x;
    const by = u1.y + u2.y;
    const blen = Math.sqrt(bx * bx + by * by);

    if (blen < 1e-10) {
        // Вырожденный случай — угол 180°, возвращаем перпендикуляр
        return { x: -u1.y, y: u1.x };
    }

    return { x: bx / blen, y: by / blen };
}

// 8. Проверка: является ли угол в вершине прямым (90°)
function isRightAngle(vertex, p1, p2) {
    const v1 = { x: p1.x - vertex.x, y: p1.y - vertex.y };
    const v2 = { x: p2.x - vertex.x, y: p2.y - vertex.y };
    const dot = v1.x * v2.x + v1.y * v2.y;
    return Math.abs(dot) < 1;
}

// 9. Маркер равенства сторон (черточка перпендикулярна отрезку)
function equalityTick(p1, p2, t = 0.5, length = 8) {
    const mid = {
        x: p1.x + (p2.x - p1.x) * t,
        y: p1.y + (p2.y - p1.y) * t
    };
    const dx = p2.x - p1.x;
    const dy = p2.y - p1.y;
    const len = Math.sqrt(dx * dx + dy * dy);
    const nx = -dy / len;
    const ny = dx / len;
    const half = length / 2;
    return {
        x1: mid.x - nx * half,
        y1: mid.y - ny * half,
        x2: mid.x + nx * half,
        y2: mid.y + ny * half
    };
}

// 10. Двойная черточка (для второй пары равных отрезков)
function doubleEqualityTick(p1, p2, t = 0.5, length = 8, gap = 4) {
    const dx = p2.x - p1.x;
    const dy = p2.y - p1.y;
    const len = Math.sqrt(dx * dx + dy * dy);
    const ux = dx / len;
    const uy = dy / len;
    const nx = -dy / len;
    const ny = dx / len;
    const mid = {
        x: p1.x + dx * t,
        y: p1.y + dy * t
    };
    const half = length / 2;
    const halfGap = gap / 2;
    const tick1 = {
        x1: mid.x - ux * halfGap - nx * half,
        y1: mid.y - uy * halfGap - ny * half,
        x2: mid.x - ux * halfGap + nx * half,
        y2: mid.y - uy * halfGap + ny * half
    };
    const tick2 = {
        x1: mid.x + ux * halfGap - nx * half,
        y1: mid.y + uy * halfGap - ny * half,
        x2: mid.x + ux * halfGap + nx * half,
        y2: mid.y + uy * halfGap + ny * half
    };
    return { tick1, tick2 };
}

// 11. Вычисление центроида треугольника
function centroid(A, B, C) {
    return {
        x: (A.x + B.x + C.x) / 3,
        y: (A.y + B.y + C.y) / 3
    };
}

// 12. Расстояние между двумя точками
function distance(p1, p2) {
    return Math.sqrt((p2.x - p1.x) ** 2 + (p2.y - p1.y) ** 2);
}

// Экспортируем в глобальную область
window.labelPos = labelPos;
window.makeAngleArc = makeAngleArc;
window.rightAnglePath = rightAnglePath;
window.pointOnLine = pointOnLine;
window.labelOnSegment = labelOnSegment;
window.angleLabelPos = angleLabelPos;
window.bisectorPoint = bisectorPoint;
window.bisectorDirection = bisectorDirection;
window.isRightAngle = isRightAngle;
window.equalityTick = equalityTick;
window.doubleEqualityTick = doubleEqualityTick;
window.centroid = centroid;
window.distance = distance;
