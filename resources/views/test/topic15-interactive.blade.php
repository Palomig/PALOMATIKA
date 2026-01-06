<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>15. Треугольники - Интерактивные изображения</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
            onload="renderMathInElement(document.body, {delimiters: [{left: '$$', right: '$$', display: true}, {left: '$', right: '$', display: false}]});"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=PT+Serif:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'PT Serif', Georgia, serif;
            font-size: 17px;
            line-height: 1.6;
            padding: 40px 60px;
            max-width: 1400px;
            margin: 0 auto;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e0e0e0;
            min-height: 100vh;
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            backdrop-filter: blur(10px);
        }
        .nav-bar a { color: #60a5fa; text-decoration: none; font-size: 14px; transition: color 0.2s; }
        .nav-bar a:hover { color: #93c5fd; }

        .title {
            text-align: center;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 8px;
            color: #fff;
        }
        .subtitle {
            text-align: center;
            font-weight: 500;
            font-size: 16px;
            margin-bottom: 30px;
            color: #9ca3af;
        }

        .demos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 24px;
        }

        .demo-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
        }
        .demo-card:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .demo-title {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 18px;
            color: #fff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .demo-title .number {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
        }

        .svg-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .svg-container svg {
            width: 100%;
            height: auto;
            display: block;
        }

        .hint-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .hint-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .hint-btn.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .hint-text {
            margin-top: 12px;
            padding: 12px 16px;
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid #10b981;
            border-radius: 0 8px 8px 0;
            font-size: 14px;
            color: #a7f3d0;
            font-family: 'Inter', sans-serif;
        }

        /* SVG Styles - matching original */
        .geo-line {
            stroke: #8B1A1A;
            stroke-width: 2.5;
            fill: none;
            transition: all 0.3s ease;
        }
        .geo-line.highlight {
            stroke: #f59e0b;
            stroke-width: 4;
        }
        .geo-line.secondary {
            stroke: #8B1A1A;
            stroke-width: 2;
            stroke-dasharray: 6,4;
        }
        .geo-line.secondary.highlight {
            stroke: #f59e0b;
            stroke-dasharray: none;
            stroke-width: 3;
        }
        .geo-point {
            fill: #8B1A1A;
            transition: all 0.3s ease;
        }
        .geo-point.highlight {
            fill: #f59e0b;
        }
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            font-size: 20px;
            fill: #1e40af;
            transition: all 0.3s ease;
        }
        .geo-label.highlight {
            fill: #f59e0b;
            font-size: 22px;
        }
        .geo-arc {
            stroke: #27ae60;
            stroke-width: 2;
            fill: none;
            transition: all 0.3s ease;
        }
        .geo-arc.highlight {
            stroke: #f59e0b;
            stroke-width: 3;
        }
        .geo-square {
            stroke: #27ae60;
            stroke-width: 2;
            fill: none;
        }
        .geo-square.highlight {
            stroke: #f59e0b;
            stroke-width: 3;
        }
        .geo-fill {
            transition: all 0.3s ease;
        }
        .geo-fill.highlight {
            fill: rgba(245, 158, 11, 0.15);
        }

        .formula-box {
            margin-top: 12px;
            padding: 12px 16px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            color: #93c5fd;
        }

        @media (max-width: 900px) {
            body { padding: 20px; font-size: 15px; }
            .title { font-size: 22px; }
            .demos-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="nav-bar">
    <a href="{{ route('test.topic15') }}">← Назад к статичной версии</a>
    <div>
        <a href="{{ route('test.pdf.index') }}">Парсер PDF</a>
    </div>
</div>

<h1 class="title">15. Треугольники</h1>
<p class="subtitle">Интерактивные геометрические изображения с подсказками</p>

<div class="demos-grid">

    {{-- 1. Биссектриса треугольника - BD из B к AC --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">1</span>
            Биссектриса треугольника
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 200">
                {{-- Triangle ABC --}}
                <polygon points="30,170 270,170 200,30"
                         class="geo-line" stroke-linejoin="round"/>

                {{-- Bisector BD from B to AC --}}
                <line x1="200" y1="30" x2="150" y2="170"
                      class="geo-line secondary"
                      :class="{ 'highlight': showHint }"/>

                {{-- Angle arc at A (the angle we're bisecting is at A in the problem) --}}
                <path d="M 50 170 A 20 20 0 0 0 42 155"
                      class="geo-arc"
                      :class="{ 'highlight': showHint }"/>

                {{-- Two equal angle arcs at B when hint shown --}}
                <template x-if="showHint">
                    <g>
                        {{-- First half of angle ABD --}}
                        <path d="M 185 50 A 22 22 0 0 0 175 62" class="geo-arc highlight"/>
                        {{-- Second half of angle DBC --}}
                        <path d="M 175 62 A 22 22 0 0 0 210 55" class="geo-arc highlight"/>
                        {{-- Equal marks --}}
                        <line x1="178" y1="54" x2="182" y2="58" stroke="#f59e0b" stroke-width="2"/>
                        <line x1="192" y1="54" x2="196" y2="58" stroke="#f59e0b" stroke-width="2"/>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="30" cy="170" r="4" class="geo-point"/>
                <circle cx="270" cy="170" r="4" class="geo-point"/>
                <circle cx="200" cy="30" r="4" class="geo-point"/>
                <circle cx="150" cy="170" r="4" class="geo-point" :class="{ 'highlight': showHint }"/>

                {{-- Labels --}}
                <text x="15" y="175" class="geo-label">A</text>
                <text x="275" y="175" class="geo-label">C</text>
                <text x="205" y="22" class="geo-label">B</text>
                <text x="145" y="190" class="geo-label" :class="{ 'highlight': showHint }">D</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            <strong>Биссектриса</strong> делит угол пополам.<br>
            ∠ABD = ∠DBC = <strong>∠ABC / 2</strong>
        </div>
    </div>

    {{-- 2. Медиана треугольника - BM из B к середине AC --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">2</span>
            Медиана треугольника
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 200">
                {{-- Triangle ABC --}}
                <polygon points="30,170 270,170 200,30"
                         class="geo-line" stroke-linejoin="round"/>

                {{-- Median BM from B to midpoint of AC --}}
                <line x1="200" y1="30" x2="150" y2="170"
                      class="geo-line secondary"
                      :class="{ 'highlight': showHint }"/>

                {{-- Equal segments marks when hint is shown --}}
                <template x-if="showHint">
                    <g>
                        {{-- AM segment highlighted --}}
                        <line x1="30" y1="170" x2="150" y2="170" stroke="#f59e0b" stroke-width="4"/>
                        {{-- MC segment highlighted --}}
                        <line x1="150" y1="170" x2="270" y2="170" stroke="#f59e0b" stroke-width="4"/>
                        {{-- Equal marks --}}
                        <line x1="85" y1="165" x2="95" y2="175" stroke="#27ae60" stroke-width="3"/>
                        <line x1="205" y1="165" x2="215" y2="175" stroke="#27ae60" stroke-width="3"/>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="30" cy="170" r="4" class="geo-point"/>
                <circle cx="270" cy="170" r="4" class="geo-point"/>
                <circle cx="200" cy="30" r="4" class="geo-point"/>
                <circle cx="150" cy="170" r="5" class="geo-point" :class="{ 'highlight': showHint }"/>

                {{-- Labels --}}
                <text x="15" y="175" class="geo-label">A</text>
                <text x="275" y="175" class="geo-label">C</text>
                <text x="205" y="22" class="geo-label">B</text>
                <text x="145" y="190" class="geo-label" :class="{ 'highlight': showHint }">M</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            <strong>Медиана</strong> делит противоположную сторону пополам.<br>
            <strong>AM = MC = AC / 2</strong>
        </div>
    </div>

    {{-- 3. Сумма углов треугольника --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">3</span>
            Сумма углов треугольника
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 200">
                {{-- Triangle ABC --}}
                <polygon points="30,170 270,170 180,30"
                         class="geo-line" stroke-linejoin="round"
                         :class="{ 'geo-fill highlight': showHint }"/>

                {{-- Angle arcs --}}
                <path d="M 55 170 A 25 25 0 0 0 45 152"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>
                <path d="M 245 170 A 25 25 0 0 1 255 155"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>
                <path d="M 165 50 A 20 20 0 0 0 195 50"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>

                {{-- Angle labels when hint is shown --}}
                <template x-if="showHint">
                    <g>
                        <text x="65" y="155" fill="#f59e0b" font-size="16" font-style="italic">α</text>
                        <text x="230" y="155" fill="#f59e0b" font-size="16" font-style="italic">β</text>
                        <text x="175" y="70" fill="#f59e0b" font-size="16" font-style="italic">γ</text>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="30" cy="170" r="4" class="geo-point"/>
                <circle cx="270" cy="170" r="4" class="geo-point"/>
                <circle cx="180" cy="30" r="4" class="geo-point"/>

                {{-- Labels --}}
                <text x="15" y="178" class="geo-label">A</text>
                <text x="275" y="178" class="geo-label">C</text>
                <text x="183" y="22" class="geo-label">B</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            <strong>Сумма углов треугольника = 180°</strong><br>
            α + β + γ = 180° → <strong>γ = 180° − α − β</strong>
        </div>
    </div>

    {{-- 4. Внешний угол треугольника --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">4</span>
            Внешний угол треугольника
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 200">
                {{-- Triangle ABC --}}
                <polygon points="30,160 200,160 130,40"
                         class="geo-line" stroke-linejoin="round"/>

                {{-- Extended side CD --}}
                <line x1="200" y1="160" x2="280" y2="160"
                      stroke="#8B1A1A" stroke-width="2" stroke-dasharray="6,4"/>

                {{-- External angle arc at C --}}
                <path d="M 220 160 A 20 20 0 0 0 212 143"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>

                {{-- Internal angle at C --}}
                <path d="M 180 160 A 20 20 0 0 1 190 147"
                      stroke="#888" stroke-width="1.5" fill="none"/>

                {{-- Non-adjacent angles when hint is shown --}}
                <template x-if="showHint">
                    <g>
                        {{-- Angle at A --}}
                        <path d="M 55 160 A 25 25 0 0 0 43 145" class="geo-arc highlight"/>
                        {{-- Angle at B --}}
                        <path d="M 118 58 A 18 18 0 0 0 142 58" class="geo-arc highlight"/>
                        <text x="60" y="145" fill="#f59e0b" font-size="14" font-style="italic">α</text>
                        <text x="125" y="75" fill="#f59e0b" font-size="14" font-style="italic">β</text>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="30" cy="160" r="4" class="geo-point"/>
                <circle cx="200" cy="160" r="4" class="geo-point"/>
                <circle cx="130" cy="40" r="4" class="geo-point"/>

                {{-- Labels --}}
                <text x="15" y="168" class="geo-label">A</text>
                <text x="200" y="182" class="geo-label">C</text>
                <text x="130" y="28" class="geo-label">B</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            <strong>Внешний угол = 180° − внутренний угол</strong><br>
            Или: внешний угол = α + β (сумма двух несмежных внутренних)
        </div>
    </div>

    {{-- 5. Равнобедренный треугольник (AB = BC) --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">5</span>
            Равнобедренный треугольник
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 200">
                {{-- Triangle ABC (isosceles: AB = BC, vertex at B) --}}
                <polygon points="50,170 250,170 150,30"
                         class="geo-line" stroke-linejoin="round"/>

                {{-- Equal sides highlighting when hint shown --}}
                <template x-if="showHint">
                    <g>
                        {{-- Side AB highlighted --}}
                        <line x1="50" y1="170" x2="150" y2="30" stroke="#f59e0b" stroke-width="4"/>
                        {{-- Side BC highlighted --}}
                        <line x1="150" y1="30" x2="250" y2="170" stroke="#f59e0b" stroke-width="4"/>
                        {{-- Equal marks on AB --}}
                        <line x1="95" y1="105" x2="105" y2="95" stroke="#27ae60" stroke-width="3"/>
                        <line x1="100" y1="110" x2="110" y2="100" stroke="#27ae60" stroke-width="3"/>
                        {{-- Equal marks on BC --}}
                        <line x1="195" y1="95" x2="205" y2="105" stroke="#27ae60" stroke-width="3"/>
                        <line x1="190" y1="100" x2="200" y2="110" stroke="#27ae60" stroke-width="3"/>
                    </g>
                </template>

                {{-- Vertex angle at B --}}
                <path d="M 135 48 A 22 22 0 0 0 165 48"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>

                {{-- Equal base angles --}}
                <path d="M 75 170 A 25 25 0 0 0 62 152"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>
                <path d="M 225 170 A 25 25 0 0 1 238 152"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>

                {{-- Points --}}
                <circle cx="50" cy="170" r="4" class="geo-point"/>
                <circle cx="250" cy="170" r="4" class="geo-point"/>
                <circle cx="150" cy="30" r="4" class="geo-point"/>

                {{-- Labels --}}
                <text x="35" y="178" class="geo-label">A</text>
                <text x="255" y="178" class="geo-label">C</text>
                <text x="150" y="18" class="geo-label">B</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            В равнобедренном треугольнике <strong>AB = BC</strong>.<br>
            <strong>Углы при основании равны</strong>: ∠A = ∠C = (180° − ∠B) / 2
        </div>
    </div>

    {{-- 6. Внешний угол равнобедренного треугольника --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">6</span>
            Внешний угол равнобедренного
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 200">
                {{-- Triangle ABC (isosceles: AB = BC) --}}
                <polygon points="30,160 200,160 115,40"
                         class="geo-line" stroke-linejoin="round"/>

                {{-- Extended side --}}
                <line x1="200" y1="160" x2="280" y2="160"
                      stroke="#8B1A1A" stroke-width="2" stroke-dasharray="6,4"/>

                {{-- Equal sides highlighting when hint shown --}}
                <template x-if="showHint">
                    <g>
                        {{-- Equal marks on AB --}}
                        <line x1="68" y1="105" x2="78" y2="95" stroke="#27ae60" stroke-width="3"/>
                        <line x1="73" y1="110" x2="83" y2="100" stroke="#27ae60" stroke-width="3"/>
                        {{-- Equal marks on BC --}}
                        <line x1="152" y1="95" x2="162" y2="105" stroke="#27ae60" stroke-width="3"/>
                        <line x1="147" y1="100" x2="157" y2="110" stroke="#27ae60" stroke-width="3"/>
                    </g>
                </template>

                {{-- External angle at C --}}
                <path d="M 220 160 A 20 20 0 0 0 212 143"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>

                {{-- Equal base angles --}}
                <path d="M 55 160 A 25 25 0 0 0 42 145"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>
                <path d="M 180 160 A 20 20 0 0 1 190 148"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>

                {{-- Points --}}
                <circle cx="30" cy="160" r="4" class="geo-point"/>
                <circle cx="200" cy="160" r="4" class="geo-point"/>
                <circle cx="115" cy="40" r="4" class="geo-point"/>

                {{-- Labels --}}
                <text x="15" y="168" class="geo-label">A</text>
                <text x="200" y="182" class="geo-label">C</text>
                <text x="115" y="28" class="geo-label">B</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            Внешний угол = 180° − угол при основании.<br>
            Угол при основании = 180° − внешний угол.<br>
            <strong>∠ABC = 180° − 2 × (180° − внешний угол)</strong>
        </div>
    </div>

    {{-- 7. Прямоугольный треугольник (прямой угол при C) --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">7</span>
            Прямоугольный треугольник
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 200">
                {{-- Triangle ABC (right angle at C) --}}
                <polygon points="30,170 250,170 250,40"
                         class="geo-line" stroke-linejoin="round"/>

                {{-- Right angle marker at C (square) --}}
                <path d="M 235 170 L 235 155 L 250 155"
                      class="geo-square" :class="{ 'highlight': showHint }"/>

                {{-- Acute angle at A --}}
                <path d="M 60 170 A 30 30 0 0 0 48 152"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>

                {{-- Acute angle at B --}}
                <path d="M 250 60 A 20 20 0 0 0 230 48"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>

                {{-- Angle labels when hint is shown --}}
                <template x-if="showHint">
                    <g>
                        <text x="70" y="155" fill="#f59e0b" font-size="16" font-style="italic">α</text>
                        <text x="218" y="65" fill="#f59e0b" font-size="16" font-style="italic">β</text>
                        <text x="220" y="160" fill="#f59e0b" font-size="14">90°</text>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="30" cy="170" r="4" class="geo-point"/>
                <circle cx="250" cy="170" r="4" class="geo-point"/>
                <circle cx="250" cy="40" r="4" class="geo-point"/>

                {{-- Labels --}}
                <text x="15" y="178" class="geo-label">A</text>
                <text x="258" y="178" class="geo-label">C</text>
                <text x="258" y="40" class="geo-label">B</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            В прямоугольном треугольнике <strong>один угол = 90°</strong>.<br>
            Сумма острых углов: <strong>α + β = 90°</strong>
        </div>
    </div>

    {{-- 8. Высота треугольника (BH перпендикулярна AC) --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">8</span>
            Высота треугольника
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 200">
                {{-- Triangle ABC --}}
                <polygon points="30,170 270,170 180,30"
                         class="geo-line" stroke-linejoin="round"/>

                {{-- Height BH --}}
                <line x1="180" y1="30" x2="180" y2="170"
                      class="geo-line secondary"
                      :class="{ 'highlight': showHint }"/>

                {{-- Right angle marker at H (square) --}}
                <path d="M 165 170 L 165 155 L 180 155"
                      class="geo-square" :class="{ 'highlight': showHint }"/>

                {{-- Angle at A --}}
                <path d="M 58 170 A 28 28 0 0 0 47 152"
                      class="geo-arc" :class="{ 'highlight': showHint }"/>

                {{-- Angle ABH when hint shown --}}
                <template x-if="showHint">
                    <g>
                        <path d="M 175 48 A 18 18 0 0 1 180 65" stroke="#f59e0b" stroke-width="2.5" fill="none"/>
                        <text x="155" y="70" fill="#f59e0b" font-size="12" font-style="italic">90°-α</text>
                        <text x="62" y="152" fill="#f59e0b" font-size="14" font-style="italic">α</text>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="30" cy="170" r="4" class="geo-point"/>
                <circle cx="270" cy="170" r="4" class="geo-point"/>
                <circle cx="180" cy="30" r="4" class="geo-point"/>
                <circle cx="180" cy="170" r="5" class="geo-point" :class="{ 'highlight': showHint }"/>

                {{-- Labels --}}
                <text x="15" y="178" class="geo-label">A</text>
                <text x="275" y="178" class="geo-label">C</text>
                <text x="183" y="20" class="geo-label">B</text>
                <text x="175" y="190" class="geo-label" :class="{ 'highlight': showHint }">H</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            <strong>Высота</strong> перпендикулярна стороне (∠BHA = 90°).<br>
            В треугольнике ABH: <strong>∠ABH = 90° − ∠BAC</strong>
        </div>
    </div>

    {{-- 9. Площадь прямоугольного треугольника --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">9</span>
            Площадь прямоугольного треугольника
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 200">
                {{-- Triangle fill (shown when hint active) --}}
                <polygon points="30,170 250,170 250,50"
                         class="geo-line" stroke-linejoin="round"
                         :class="{ 'geo-fill highlight': showHint }"/>

                {{-- Catheti highlighting when hint shown --}}
                <template x-if="showHint">
                    <g>
                        {{-- Horizontal cathetus (a) --}}
                        <line x1="30" y1="170" x2="250" y2="170" stroke="#f59e0b" stroke-width="5"/>
                        {{-- Vertical cathetus (b) --}}
                        <line x1="250" y1="170" x2="250" y2="50" stroke="#3b82f6" stroke-width="5"/>
                        {{-- Labels for catheti --}}
                        <text x="140" y="192" fill="#f59e0b" font-size="18" font-style="italic" text-anchor="middle">a</text>
                        <text x="270" y="115" fill="#3b82f6" font-size="18" font-style="italic">b</text>
                    </g>
                </template>

                {{-- Right angle marker at C (square) --}}
                <path d="M 235 170 L 235 155 L 250 155"
                      class="geo-square"/>

                {{-- Points --}}
                <circle cx="30" cy="170" r="4" class="geo-point"/>
                <circle cx="250" cy="170" r="4" class="geo-point"/>
                <circle cx="250" cy="50" r="4" class="geo-point"/>

                {{-- Labels --}}
                <text x="15" y="178" class="geo-label">A</text>
                <text x="258" y="178" class="geo-label">C</text>
                <text x="258" y="45" class="geo-label">B</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            Площадь = половина произведения катетов.<br>
            <strong>S = (a × b) / 2</strong>
        </div>
        <div class="formula-box" x-show="showHint" x-transition>
            S = <span style="color: #f59e0b">a</span> × <span style="color: #3b82f6">b</span> / 2
        </div>
    </div>

</div>

<div style="text-align: center; margin-top: 40px; color: #6b7280; font-size: 14px;">
    Все изображения генерируются программно — никаких проблем с авторскими правами
</div>

</body>
</html>
