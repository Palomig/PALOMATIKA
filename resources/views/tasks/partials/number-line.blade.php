{{--
    Partial для координатной прямой (SVG)
    @param array $points - точки [{value, label}]
    @param string $svgType - тип: single_point, two_points, three_points, four_points_abcd
--}}

@php
    $values = array_column($points, 'value');
    $minVal = min(array_merge($values, [0]));
    $maxVal = max($values);
    $minTick = floor($minVal) - 1;
    $maxTick = ceil($maxVal) + 1;
    $range = max($maxTick - $minTick, 1);
    $tickWidth = 280 / $range;
@endphp

<div class="bg-slate-900/50 rounded-lg p-4">
    <svg viewBox="0 0 320 55" class="w-full h-16 number-line">
        <defs>
            <marker id="arrowR" markerWidth="8" markerHeight="8" refX="0" refY="3" orient="auto">
                <path d="M0,0 L0,6 L8,3 z" fill="#8B0000"/>
            </marker>
        </defs>

        {{-- Number line --}}
        <line x1="15" y1="25" x2="305" y2="25" stroke="#8B0000" stroke-width="2" marker-end="url(#arrowR)"/>

        {{-- Tick marks --}}
        @for($i = $minTick; $i <= $maxTick; $i++)
            <line x1="{{ 15 + ($i - $minTick) * $tickWidth }}" y1="18"
                  x2="{{ 15 + ($i - $minTick) * $tickWidth }}" y2="32"
                  stroke="#8B0000" stroke-width="1.5"/>
        @endfor

        {{-- Label 0 --}}
        @if($minTick <= 0 && $maxTick >= 0)
            <text x="{{ 15 + (0 - $minTick) * $tickWidth }}" y="48"
                  text-anchor="middle" fill="#1e40af" font-size="13" font-weight="bold">0</text>
        @endif

        {{-- Points --}}
        @foreach($points as $pt)
            @php $px = 15 + ($pt['value'] - $minTick) * $tickWidth; @endphp
            <circle cx="{{ $px }}" cy="25" r="6" fill="#22c55e"/>
            <text x="{{ $px }}" y="12" text-anchor="middle" fill="#1e40af" font-size="14" font-weight="bold">{{ $pt['label'] }}</text>
        @endforeach
    </svg>
</div>
