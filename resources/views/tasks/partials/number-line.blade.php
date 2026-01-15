{{--
    Partial для координатной прямой (SVG)
    Референс: docs/oge_data/images/oge07_p*.png

    @param array $points - точки [{value, label}] или для single_point: просто value и label отдельно
    @param string $svgType - тип SVG:
        - single_point: одна точка, шкала 0-1-2... (p1)
        - two_points: две точки x,y с нулём (p8)
        - three_points: три точки БЕЗ шкалы (p2)
        - four_points_abcd: 4 точки A,B,C,D со шкалой (p3)
        - point_a_on_range: точка A на диапазоне 0-1 с десятичными делениями (p5)
    @param array $task - данные задачи (опционально, для доступа к point_value, point_label и т.д.)
--}}

@php
    // Уникальный ID для marker (чтобы не было конфликтов при множественных SVG)
    $uniqueId = uniqid('nl_');

    // Определяем параметры в зависимости от типа
    $svgType = $svgType ?? 'single_point';
@endphp

@if($svgType === 'three_points')
    {{-- ТИП: три точки БЕЗ шкалы (референс: oge07_p2_img*.png) --}}
    <div class="bg-slate-900/50 rounded-lg p-4">
        <svg viewBox="0 0 400 50" class="w-full max-w-md h-16 number-line">
            <defs>
                <marker id="arrow_{{ $uniqueId }}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L9,3 z" fill="#8B0000"/>
                </marker>
            </defs>

            {{-- Линия --}}
            <line x1="20" y1="20" x2="380" y2="20" stroke="#8B0000" stroke-width="2.5" marker-end="url(#arrow_{{ $uniqueId }})"/>

            {{-- Три точки равномерно распределены --}}
            @php
                $positions = [100, 200, 300]; // Примерные позиции для 3 точек
                $labels = array_column($points ?? [], 'label');
                if (empty($labels)) $labels = ['A', 'B', 'C'];
            @endphp

            @foreach($positions as $i => $px)
                <circle cx="{{ $px }}" cy="20" r="4" fill="#22c55e"/>
                <text x="{{ $px }}" y="42" text-anchor="middle" fill="#1e40af" font-size="15" font-weight="bold" font-style="italic">{{ $labels[$i] ?? '' }}</text>
            @endforeach
        </svg>
    </div>

@elseif($svgType === 'four_points_abcd')
    {{-- ТИП: 4 точки A,B,C,D со шкалой (референс: oge07_p3_img*.png) --}}
    @php
        $fourPts = $task['four_points'] ?? [5, 6, 7, 8];
        $rangeArr = $task['range'] ?? [min($fourPts) - 0.5, max($fourPts) + 0.5];
        $minV = floor($rangeArr[0]);
        $maxV = ceil($rangeArr[1]);
        $labels = ['A', 'B', 'C', 'D'];

        // Вычисляем позиции
        $lineStart = 30;
        $lineEnd = 370;
        $lineWidth = $lineEnd - $lineStart;

        $rangeV = max($maxV - $minV, 1); // Защита от деления на 0
        $getX = function($v) use ($minV, $rangeV, $lineStart, $lineWidth) {
            return $lineStart + (($v - $minV) / $rangeV) * $lineWidth;
        };
    @endphp

    <div class="bg-slate-900/50 rounded-lg p-4">
        <svg viewBox="0 0 400 55" class="w-full max-w-md h-16 number-line">
            <defs>
                <marker id="arrow_{{ $uniqueId }}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L9,3 z" fill="#8B0000"/>
                </marker>
            </defs>

            {{-- Линия --}}
            <line x1="{{ $lineStart }}" y1="22" x2="{{ $lineEnd }}" y2="22" stroke="#8B0000" stroke-width="2.5" marker-end="url(#arrow_{{ $uniqueId }})"/>

            {{-- Деления и подписи чисел --}}
            @for($i = $minV; $i <= $maxV; $i++)
                @php $tx = $getX($i); @endphp
                <line x1="{{ $tx }}" y1="15" x2="{{ $tx }}" y2="29" stroke="#8B0000" stroke-width="2"/>
                <text x="{{ $tx }}" y="48" text-anchor="middle" fill="#8B0000" font-size="14" font-weight="bold">{{ $i }}</text>
            @endfor

            {{-- Четыре точки A, B, C, D --}}
            @foreach($fourPts as $i => $ptVal)
                @php $px = $getX($ptVal); @endphp
                <circle cx="{{ $px }}" cy="22" r="4" fill="#22c55e"/>
                <text x="{{ $px }}" y="10" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold" font-style="italic">{{ $labels[$i] ?? '' }}</text>
            @endforeach
        </svg>
    </div>

@elseif($svgType === 'two_points')
    {{-- ТИП: две точки x,y с нулём (референс: oge07_p8_img*.png) --}}
    @php
        $pts = $points ?? $task['points'] ?? [];
        $values = array_column($pts, 'value');
        if (empty($values)) $values = [-2, 3];

        $minVal = min(min($values), 0);
        $maxVal = max($values);
        $minTick = floor($minVal) - 1;
        $maxTick = ceil($maxVal) + 1;
        $range = max($maxTick - $minTick, 1); // Защита от деления на 0

        $lineStart = 30;
        $lineEnd = 340;
        $lineWidth = $lineEnd - $lineStart;
        $tickWidth = $lineWidth / $range;

        $getX = function($v) use ($minTick, $lineStart, $tickWidth) {
            return $lineStart + ($v - $minTick) * $tickWidth;
        };
    @endphp

    <div class="bg-slate-900/50 rounded-lg p-4">
        <svg viewBox="0 0 370 55" class="w-full max-w-md h-16 number-line">
            <defs>
                <marker id="arrow_{{ $uniqueId }}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L9,3 z" fill="#8B0000"/>
                </marker>
            </defs>

            {{-- Линия --}}
            <line x1="{{ $lineStart }}" y1="22" x2="{{ $lineEnd }}" y2="22" stroke="#8B0000" stroke-width="2.5" marker-end="url(#arrow_{{ $uniqueId }})"/>

            {{-- Деление только для 0 --}}
            @php $zeroX = $getX(0); @endphp
            <line x1="{{ $zeroX }}" y1="15" x2="{{ $zeroX }}" y2="29" stroke="#8B0000" stroke-width="2"/>
            <text x="{{ $zeroX }}" y="48" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">0</text>

            {{-- Две точки --}}
            @foreach($pts as $pt)
                @php $px = $getX($pt['value']); @endphp
                <circle cx="{{ $px }}" cy="22" r="4" fill="#22c55e"/>
                <text x="{{ $px }}" y="10" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold" font-style="italic">{{ $pt['label'] }}</text>
            @endforeach
        </svg>
    </div>

@elseif($svgType === 'point_a_on_range')
    {{-- ТИП: точка A на диапазоне 0-1 с десятичными делениями (референс: oge07_p5_img*.png) --}}
    @php
        $pointVal = $task['point_value'] ?? 0.3;
        $pointLabel = $task['point_label'] ?? 'A';
        $divisions = 10; // 0, 0.1, 0.2, ..., 1

        $lineStart = 25;
        $lineEnd = 375;
        $lineWidth = $lineEnd - $lineStart;
        $tickWidth = $lineWidth / $divisions;

        $getX = function($v) use ($lineStart, $lineWidth) {
            return $lineStart + $v * $lineWidth;
        };
    @endphp

    <div class="bg-slate-900/50 rounded-lg p-4">
        <svg viewBox="0 0 400 55" class="w-full max-w-md h-16 number-line">
            <defs>
                <marker id="arrow_{{ $uniqueId }}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L9,3 z" fill="#8B0000"/>
                </marker>
            </defs>

            {{-- Линия --}}
            <line x1="{{ $lineStart }}" y1="20" x2="{{ $lineEnd }}" y2="20" stroke="#8B0000" stroke-width="2.5" marker-end="url(#arrow_{{ $uniqueId }})"/>

            {{-- Деления 0, 0.1, 0.2, ..., 1 --}}
            @for($i = 0; $i <= $divisions; $i++)
                @php
                    $tx = $lineStart + $i * $tickWidth;
                    $val = $i / $divisions;
                    // Формат: 0, 0,1, 0,2, ..., 1 (запятая как десятичный разделитель)
                    $label = $i == 0 ? '0' : ($i == $divisions ? '1' : '0,' . $i);
                @endphp
                <line x1="{{ $tx }}" y1="13" x2="{{ $tx }}" y2="27" stroke="#8B0000" stroke-width="1.5"/>
                <text x="{{ $tx }}" y="45" text-anchor="middle" fill="#1e40af" font-size="11">{{ $label }}</text>
            @endfor

            {{-- Точка A --}}
            @php $px = $getX($pointVal); @endphp
            <circle cx="{{ $px }}" cy="20" r="4" fill="#22c55e"/>
            <text x="{{ $px }}" y="8" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold" font-style="italic">{{ $pointLabel }}</text>
        </svg>
    </div>

@else
    {{-- ТИП ПО УМОЛЧАНИЮ: single_point - одна точка, шкала 0-1-2... (референс: oge07_p1_img*.png) --}}
    @php
        // Получаем значение точки
        $pointVal = $task['point_value'] ?? ($points[0]['value'] ?? 6.5);
        $pointLabel = $task['point_label'] ?? ($points[0]['label'] ?? 'a');

        // Определяем диапазон: от -1 до точки + 2
        $maxTick = ceil($pointVal) + 2;
        $minTick = -1;
        $range = max($maxTick - $minTick, 1); // Защита от деления на 0

        $lineStart = 15;
        $lineEnd = 305;
        $lineWidth = $lineEnd - $lineStart;
        $tickWidth = $lineWidth / $range;

        $getX = function($v) use ($minTick, $lineStart, $tickWidth) {
            return $lineStart + ($v - $minTick) * $tickWidth;
        };
    @endphp

    <div class="bg-slate-900/50 rounded-lg p-4">
        <svg viewBox="0 0 320 55" class="w-full max-w-md h-16 number-line">
            <defs>
                <marker id="arrow_{{ $uniqueId }}" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L9,3 z" fill="#8B0000"/>
                </marker>
            </defs>

            {{-- Линия --}}
            <line x1="{{ $lineStart }}" y1="22" x2="{{ $lineEnd }}" y2="22" stroke="#8B0000" stroke-width="2.5" marker-end="url(#arrow_{{ $uniqueId }})"/>

            {{-- Деления на каждом целом числе --}}
            @for($i = $minTick; $i <= $maxTick; $i++)
                @php $tx = $getX($i); @endphp
                <line x1="{{ $tx }}" y1="15" x2="{{ $tx }}" y2="29" stroke="#8B0000" stroke-width="1.5"/>
            @endfor

            {{-- Подписи только 0 и 1 --}}
            <text x="{{ $getX(0) }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">0</text>
            <text x="{{ $getX(1) }}" y="48" text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">1</text>

            {{-- Точка --}}
            @php $px = $getX($pointVal); @endphp
            <circle cx="{{ $px }}" cy="22" r="4" fill="#22c55e"/>
            <text x="{{ $px }}" y="10" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold" font-style="italic">{{ $pointLabel }}</text>
        </svg>
    </div>
@endif
