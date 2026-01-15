{{--
    Partial для интервалов на координатной прямой (SVG)
    Референс: docs/oge_data/images/oge13_p*.png

    @param string $interval - интервал в формате "(-∞; -5]", "[-3; 8]", etc.
    @param int $index - номер варианта (1, 2, 3, 4)
--}}

@php
    $uniqueId = uniqid('int_');

    // Парсим интервал
    $interval = str_replace(' ', '', $interval);
    $interval = str_replace(',', '.', $interval); // 0,5 -> 0.5
    $interval = str_replace('−', '-', $interval); // юникод минус

    // Определяем тип скобок и значения
    $leftOpen = str_starts_with($interval, '(');
    $rightOpen = str_ends_with($interval, ')');

    // Извлекаем значения
    preg_match('/[\(\[](.+);(.+)[\)\]]/', $interval, $matches);

    $leftVal = trim($matches[1] ?? '-∞');
    $rightVal = trim($matches[2] ?? '+∞');

    // Проверяем бесконечности
    $leftInf = ($leftVal === '-∞' || $leftVal === '-inf' || $leftVal === '−∞');
    $rightInf = ($rightVal === '+∞' || $rightVal === '+inf' || $rightVal === '∞');

    // Числовые значения для отображения
    $leftNum = $leftInf ? null : floatval($leftVal);
    $rightNum = $rightInf ? null : floatval($rightVal);

    // Параметры SVG
    $svgWidth = 250;
    $svgHeight = 40;
    $lineY = 20;
    $lineStart = 20;
    $lineEnd = 230;
    $lineWidth = $lineEnd - $lineStart;

    // Вычисляем позиции точек
    // Центрируем видимые точки на линии
    if ($leftInf && !$rightInf) {
        // (-∞; a] или (-∞; a)
        $rightX = $lineStart + $lineWidth * 0.7;
        $leftX = $lineStart;
    } elseif (!$leftInf && $rightInf) {
        // [a; +∞) или (a; +∞)
        $leftX = $lineStart + $lineWidth * 0.3;
        $rightX = $lineEnd;
    } elseif (!$leftInf && !$rightInf) {
        // [a; b]
        $leftX = $lineStart + $lineWidth * 0.25;
        $rightX = $lineStart + $lineWidth * 0.75;
    } else {
        // (-∞; +∞)
        $leftX = $lineStart;
        $rightX = $lineEnd;
    }
@endphp

<svg viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" class="w-full h-10">
    <defs>
        {{-- Паттерн штриховки --}}
        <pattern id="hatch_{{ $uniqueId }}" patternUnits="userSpaceOnUse" width="6" height="6" patternTransform="rotate(45)">
            <line x1="0" y1="0" x2="0" y2="6" stroke="#10b981" stroke-width="1.5"/>
        </pattern>

        {{-- Маркер стрелки --}}
        <marker id="arrow_{{ $uniqueId }}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">
            <path d="M0,0 L0,6 L9,3 z" fill="#64748b"/>
        </marker>
    </defs>

    {{-- Основная линия --}}
    <line x1="{{ $lineStart }}" y1="{{ $lineY }}" x2="{{ $lineEnd }}" y2="{{ $lineY }}"
          stroke="#64748b" stroke-width="2" marker-end="url(#arrow_{{ $uniqueId }})"/>

    {{-- Штриховка интервала --}}
    @if($leftInf && !$rightInf)
        {{-- (-∞; a] - штриховка от начала до точки --}}
        <rect x="{{ $lineStart }}" y="{{ $lineY - 8 }}" width="{{ $rightX - $lineStart }}" height="16"
              fill="url(#hatch_{{ $uniqueId }})" opacity="0.8"/>
    @elseif(!$leftInf && $rightInf)
        {{-- [a; +∞) - штриховка от точки до конца --}}
        <rect x="{{ $leftX }}" y="{{ $lineY - 8 }}" width="{{ $lineEnd - $leftX }}" height="16"
              fill="url(#hatch_{{ $uniqueId }})" opacity="0.8"/>
    @elseif(!$leftInf && !$rightInf)
        {{-- [a; b] - штриховка между точками --}}
        <rect x="{{ $leftX }}" y="{{ $lineY - 8 }}" width="{{ $rightX - $leftX }}" height="16"
              fill="url(#hatch_{{ $uniqueId }})" opacity="0.8"/>
    @endif

    {{-- Левая точка --}}
    @if(!$leftInf)
        @if($leftOpen)
            {{-- Открытый интервал - пустой кружок --}}
            <circle cx="{{ $leftX }}" cy="{{ $lineY }}" r="5" fill="#1e293b" stroke="#10b981" stroke-width="2"/>
        @else
            {{-- Закрытый интервал - заполненный кружок --}}
            <circle cx="{{ $leftX }}" cy="{{ $lineY }}" r="5" fill="#10b981"/>
        @endif
        {{-- Подпись --}}
        <text x="{{ $leftX }}" y="{{ $lineY + 18 }}" text-anchor="middle" fill="#94a3b8" font-size="12" font-weight="500">
            {{ str_replace('.', ',', $leftVal) }}
        </text>
    @endif

    {{-- Правая точка --}}
    @if(!$rightInf)
        @if($rightOpen)
            {{-- Открытый интервал - пустой кружок --}}
            <circle cx="{{ $rightX }}" cy="{{ $lineY }}" r="5" fill="#1e293b" stroke="#10b981" stroke-width="2"/>
        @else
            {{-- Закрытый интервал - заполненный кружок --}}
            <circle cx="{{ $rightX }}" cy="{{ $lineY }}" r="5" fill="#10b981"/>
        @endif
        {{-- Подпись --}}
        <text x="{{ $rightX }}" y="{{ $lineY + 18 }}" text-anchor="middle" fill="#94a3b8" font-size="12" font-weight="500">
            {{ str_replace('.', ',', $rightVal) }}
        </text>
    @endif
</svg>
