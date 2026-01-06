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
            background: rgba(0,0,0,0.2);
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

        /* SVG Styles */
        .geo-line { transition: all 0.3s ease; }
        .geo-line.highlight { stroke-width: 4; }
        .geo-point { transition: all 0.3s ease; }
        .geo-point.highlight { r: 7; }
        .geo-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .geo-label.highlight { font-size: 20px; }
        .geo-arc { transition: all 0.3s ease; }
        .geo-arc.highlight { stroke-width: 3; }
        .geo-fill { transition: all 0.3s ease; }
        .geo-fill.highlight { fill-opacity: 0.3; }

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

    {{-- 1. Биссектриса треугольника --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">1</span>
            Биссектриса треугольника
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 220">
                {{-- Triangle ABC --}}
                <polygon points="40,180 260,180 180,40" fill="none" stroke="#8B1A1A" stroke-width="3" stroke-linejoin="round"/>

                {{-- Bisector AD --}}
                <line x1="180" y1="40" x2="150" y2="180"
                      stroke="#2563eb" stroke-width="2" stroke-dasharray="6,4"
                      :class="{ 'highlight': showHint }"
                      :style="showHint ? 'stroke: #f59e0b; stroke-dasharray: none; stroke-width: 4' : ''"/>

                {{-- Angle arc at A (full angle BAC) --}}
                <path d="M 165 60 A 25 25 0 0 0 195 60" fill="none" stroke="#8B1A1A" stroke-width="2"/>

                {{-- Two equal angle arcs when hint is shown --}}
                <template x-if="showHint">
                    <g>
                        {{-- First half angle BAD --}}
                        <path d="M 168 55 A 20 20 0 0 0 180 70" fill="none" stroke="#10b981" stroke-width="3"/>
                        {{-- Second half angle DAC --}}
                        <path d="M 180 70 A 20 20 0 0 0 192 55" fill="none" stroke="#10b981" stroke-width="3"/>
                        {{-- Equal marks --}}
                        <line x1="172" y1="62" x2="174" y2="58" stroke="#10b981" stroke-width="2"/>
                        <line x1="186" y1="62" x2="188" y2="58" stroke="#10b981" stroke-width="2"/>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="40" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="260" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="180" cy="40" r="4" fill="#8B1A1A"/>
                <circle cx="150" cy="180" r="4" fill="#2563eb" :class="{ 'highlight': showHint }" :style="showHint ? 'fill: #f59e0b' : ''"/>

                {{-- Labels --}}
                <text x="25" y="185" fill="#fff" font-size="18" class="geo-label">A</text>
                <text x="268" y="185" fill="#fff" font-size="18" class="geo-label">C</text>
                <text x="180" y="28" fill="#fff" font-size="18" class="geo-label">B</text>
                <text x="148" y="200" fill="#60a5fa" font-size="16" class="geo-label" :style="showHint ? 'fill: #f59e0b' : ''">D</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            <strong>Биссектриса</strong> делит угол пополам.<br>
            Угол BAD = Угол DAC = <strong>Угол BAC / 2</strong>
        </div>
    </div>

    {{-- 2. Медиана треугольника --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">2</span>
            Медиана треугольника
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 220">
                {{-- Triangle ABC --}}
                <polygon points="40,180 260,180 150,40" fill="none" stroke="#8B1A1A" stroke-width="3" stroke-linejoin="round"/>

                {{-- Median BM --}}
                <line x1="150" y1="40" x2="150" y2="180"
                      stroke="#2563eb" stroke-width="2" stroke-dasharray="6,4"
                      :style="showHint ? 'stroke: #f59e0b; stroke-dasharray: none; stroke-width: 4' : ''"/>

                {{-- Equal segments marks when hint is shown --}}
                <template x-if="showHint">
                    <g>
                        {{-- AM segment mark --}}
                        <line x1="90" y1="175" x2="100" y2="185" stroke="#10b981" stroke-width="3"/>
                        {{-- MC segment mark --}}
                        <line x1="200" y1="175" x2="210" y2="185" stroke="#10b981" stroke-width="3"/>
                        {{-- Highlight AM and MC --}}
                        <line x1="40" y1="180" x2="150" y2="180" stroke="#10b981" stroke-width="4"/>
                        <line x1="150" y1="180" x2="260" y2="180" stroke="#10b981" stroke-width="4"/>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="40" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="260" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="150" cy="40" r="4" fill="#8B1A1A"/>
                <circle cx="150" cy="180" r="5" fill="#2563eb" :style="showHint ? 'fill: #f59e0b; r: 7' : ''"/>

                {{-- Labels --}}
                <text x="25" y="185" fill="#fff" font-size="18" class="geo-label">A</text>
                <text x="268" y="185" fill="#fff" font-size="18" class="geo-label">C</text>
                <text x="150" y="28" fill="#fff" font-size="18" class="geo-label">B</text>
                <text x="148" y="200" fill="#60a5fa" font-size="16" class="geo-label" :style="showHint ? 'fill: #f59e0b' : ''">M</text>
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
            <svg viewBox="0 0 300 220">
                {{-- Triangle ABC --}}
                <polygon points="40,180 260,180 180,40" fill="none" stroke="#8B1A1A" stroke-width="3" stroke-linejoin="round"/>

                {{-- Angle arcs --}}
                <path d="M 60 180 A 20 20 0 0 0 52 163" fill="none"
                      :stroke="showHint ? '#f59e0b' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>
                <path d="M 240 180 A 20 20 0 0 1 235 162" fill="none"
                      :stroke="showHint ? '#10b981' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>
                <path d="M 168 55 A 18 18 0 0 0 192 55" fill="none"
                      :stroke="showHint ? '#3b82f6' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>

                {{-- Angle labels when hint is shown --}}
                <template x-if="showHint">
                    <g>
                        <text x="70" y="165" fill="#f59e0b" font-size="14" class="geo-label">α</text>
                        <text x="225" y="165" fill="#10b981" font-size="14" class="geo-label">β</text>
                        <text x="175" y="70" fill="#3b82f6" font-size="14" class="geo-label">γ</text>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="40" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="260" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="180" cy="40" r="4" fill="#8B1A1A"/>

                {{-- Labels --}}
                <text x="25" y="185" fill="#fff" font-size="18" class="geo-label">A</text>
                <text x="268" y="185" fill="#fff" font-size="18" class="geo-label">C</text>
                <text x="180" y="28" fill="#fff" font-size="18" class="geo-label">B</text>
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
            <svg viewBox="0 0 300 220">
                {{-- Triangle ABC --}}
                <polygon points="40,180 200,180 120,50" fill="none" stroke="#8B1A1A" stroke-width="3" stroke-linejoin="round"/>

                {{-- Extended side CD --}}
                <line x1="200" y1="180" x2="280" y2="180" stroke="#8B1A1A" stroke-width="2" stroke-dasharray="6,4"/>

                {{-- External angle arc --}}
                <path d="M 220 180 A 20 20 0 0 0 210 162" fill="none"
                      :stroke="showHint ? '#f59e0b' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 4' : ''"/>

                {{-- Internal angle at C --}}
                <path d="M 180 180 A 20 20 0 0 1 188 165" fill="none"
                      :stroke="showHint ? '#10b981' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>

                {{-- Non-adjacent angles when hint is shown --}}
                <template x-if="showHint">
                    <g>
                        {{-- Angle at A --}}
                        <path d="M 60 180 A 20 20 0 0 0 50 165" fill="none" stroke="#3b82f6" stroke-width="3"/>
                        {{-- Angle at B --}}
                        <path d="M 110 65 A 18 18 0 0 0 130 65" fill="none" stroke="#3b82f6" stroke-width="3"/>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="40" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="200" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="120" cy="50" r="4" fill="#8B1A1A"/>
                <circle cx="280" cy="180" r="3" fill="#666"/>

                {{-- Labels --}}
                <text x="25" y="185" fill="#fff" font-size="18" class="geo-label">A</text>
                <text x="200" y="200" fill="#fff" font-size="18" class="geo-label">C</text>
                <text x="120" y="38" fill="#fff" font-size="18" class="geo-label">B</text>
                <text x="280" y="200" fill="#888" font-size="14" class="geo-label">D</text>

                {{-- Angle labels --}}
                <template x-if="showHint">
                    <g>
                        <text x="230" y="168" fill="#f59e0b" font-size="12">внеш.</text>
                    </g>
                </template>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            <strong>Внешний угол = 180° − внутренний угол</strong><br>
            Или: внешний угол = сумма двух несмежных внутренних
        </div>
    </div>

    {{-- 5. Равнобедренный треугольник --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">5</span>
            Равнобедренный треугольник
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 220">
                {{-- Triangle ABC (isosceles: AB = BC) --}}
                <polygon points="50,180 250,180 150,40" fill="none" stroke="#8B1A1A" stroke-width="3" stroke-linejoin="round"/>

                {{-- Equal sides marks --}}
                <template x-if="showHint">
                    <g>
                        {{-- Mark on AB --}}
                        <line x1="95" y1="115" x2="105" y2="105" stroke="#f59e0b" stroke-width="3"/>
                        <line x1="98" y1="118" x2="108" y2="108" stroke="#f59e0b" stroke-width="3"/>
                        {{-- Mark on BC --}}
                        <line x1="195" y1="105" x2="205" y2="115" stroke="#f59e0b" stroke-width="3"/>
                        <line x1="192" y1="108" x2="202" y2="118" stroke="#f59e0b" stroke-width="3"/>
                        {{-- Highlight equal sides --}}
                        <line x1="50" y1="180" x2="150" y2="40" stroke="#f59e0b" stroke-width="4"/>
                        <line x1="150" y1="40" x2="250" y2="180" stroke="#f59e0b" stroke-width="4"/>
                    </g>
                </template>

                {{-- Equal angle arcs at base --}}
                <path d="M 70 180 A 20 20 0 0 0 60 163" fill="none"
                      :stroke="showHint ? '#10b981' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>
                <path d="M 230 180 A 20 20 0 0 1 240 163" fill="none"
                      :stroke="showHint ? '#10b981' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>

                {{-- Vertex angle --}}
                <path d="M 135 55 A 20 20 0 0 0 165 55" fill="none" stroke="#3b82f6" stroke-width="2"/>

                {{-- Points --}}
                <circle cx="50" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="250" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="150" cy="40" r="4" fill="#8B1A1A"/>

                {{-- Labels --}}
                <text x="35" y="185" fill="#fff" font-size="18" class="geo-label">A</text>
                <text x="258" y="185" fill="#fff" font-size="18" class="geo-label">C</text>
                <text x="150" y="28" fill="#fff" font-size="18" class="geo-label">B</text>
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
            <svg viewBox="0 0 300 220">
                {{-- Triangle ABC (isosceles: AB = BC) --}}
                <polygon points="40,180 200,180 120,50" fill="none" stroke="#8B1A1A" stroke-width="3" stroke-linejoin="round"/>

                {{-- Extended side --}}
                <line x1="200" y1="180" x2="280" y2="180" stroke="#8B1A1A" stroke-width="2" stroke-dasharray="6,4"/>

                {{-- Equal sides marks --}}
                <template x-if="showHint">
                    <g>
                        {{-- Mark on AB --}}
                        <line x1="75" y1="120" x2="85" y2="110" stroke="#f59e0b" stroke-width="3"/>
                        <line x1="78" y1="123" x2="88" y2="113" stroke="#f59e0b" stroke-width="3"/>
                        {{-- Mark on BC --}}
                        <line x1="155" y1="110" x2="165" y2="120" stroke="#f59e0b" stroke-width="3"/>
                        <line x1="152" y1="113" x2="162" y2="123" stroke="#f59e0b" stroke-width="3"/>
                    </g>
                </template>

                {{-- External angle --}}
                <path d="M 220 180 A 20 20 0 0 0 210 163" fill="none"
                      :stroke="showHint ? '#f59e0b' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 4' : ''"/>

                {{-- Equal base angles --}}
                <path d="M 60 180 A 20 20 0 0 0 50 165" fill="none"
                      :stroke="showHint ? '#10b981' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>
                <path d="M 180 180 A 20 20 0 0 1 188 167" fill="none"
                      :stroke="showHint ? '#10b981' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>

                {{-- Points --}}
                <circle cx="40" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="200" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="120" cy="50" r="4" fill="#8B1A1A"/>

                {{-- Labels --}}
                <text x="25" y="185" fill="#fff" font-size="18" class="geo-label">A</text>
                <text x="200" y="200" fill="#fff" font-size="18" class="geo-label">C</text>
                <text x="120" y="38" fill="#fff" font-size="18" class="geo-label">B</text>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            Внешний угол = 180° − угол при основании.<br>
            <strong>∠ABC = 180° − 2 × (180° − внешний угол)</strong>
        </div>
    </div>

    {{-- 7. Прямоугольный треугольник --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">7</span>
            Прямоугольный треугольник
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 220">
                {{-- Triangle ABC (right angle at C) --}}
                <polygon points="50,180 250,180 250,50" fill="none" stroke="#8B1A1A" stroke-width="3" stroke-linejoin="round"/>

                {{-- Right angle marker --}}
                <path d="M 235 180 L 235 165 L 250 165" fill="none"
                      :stroke="showHint ? '#f59e0b' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>

                {{-- Acute angles --}}
                <path d="M 75 180 A 25 25 0 0 0 62 163" fill="none"
                      :stroke="showHint ? '#10b981' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>
                <path d="M 250 70 A 20 20 0 0 0 232 58" fill="none"
                      :stroke="showHint ? '#3b82f6' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>

                {{-- Angle labels when hint is shown --}}
                <template x-if="showHint">
                    <g>
                        <text x="85" y="165" fill="#10b981" font-size="14" class="geo-label">α</text>
                        <text x="225" y="75" fill="#3b82f6" font-size="14" class="geo-label">β</text>
                        <text x="220" y="170" fill="#f59e0b" font-size="14" class="geo-label">90°</text>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="50" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="250" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="250" cy="50" r="4" fill="#8B1A1A"/>

                {{-- Labels --}}
                <text x="35" y="185" fill="#fff" font-size="18" class="geo-label">A</text>
                <text x="258" y="190" fill="#fff" font-size="18" class="geo-label">C</text>
                <text x="258" y="50" fill="#fff" font-size="18" class="geo-label">B</text>
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

    {{-- 8. Высота треугольника --}}
    <div class="demo-card" x-data="{ showHint: false }">
        <div class="demo-title">
            <span class="number">8</span>
            Высота треугольника
        </div>
        <div class="svg-container">
            <svg viewBox="0 0 300 220">
                {{-- Triangle ABC --}}
                <polygon points="40,180 260,180 180,40" fill="none" stroke="#8B1A1A" stroke-width="3" stroke-linejoin="round"/>

                {{-- Height BH --}}
                <line x1="180" y1="40" x2="180" y2="180"
                      stroke="#2563eb" stroke-width="2" stroke-dasharray="6,4"
                      :style="showHint ? 'stroke: #f59e0b; stroke-dasharray: none; stroke-width: 4' : ''"/>

                {{-- Right angle marker --}}
                <path d="M 165 180 L 165 165 L 180 165" fill="none"
                      :stroke="showHint ? '#f59e0b' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>

                {{-- Angle at A --}}
                <path d="M 60 180 A 20 20 0 0 0 52 165" fill="none"
                      :stroke="showHint ? '#10b981' : '#666'" stroke-width="2"
                      :style="showHint ? 'stroke-width: 3' : ''"/>

                {{-- Angle ABH (complement) --}}
                <template x-if="showHint">
                    <g>
                        <path d="M 175 55 A 15 15 0 0 1 180 70" fill="none" stroke="#3b82f6" stroke-width="3"/>
                        <text x="160" y="75" fill="#3b82f6" font-size="12" class="geo-label">90°-α</text>
                    </g>
                </template>

                {{-- Points --}}
                <circle cx="40" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="260" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="180" cy="40" r="4" fill="#8B1A1A"/>
                <circle cx="180" cy="180" r="5" fill="#2563eb" :style="showHint ? 'fill: #f59e0b' : ''"/>

                {{-- Labels --}}
                <text x="25" y="185" fill="#fff" font-size="18" class="geo-label">A</text>
                <text x="268" y="185" fill="#fff" font-size="18" class="geo-label">C</text>
                <text x="180" y="28" fill="#fff" font-size="18" class="geo-label">B</text>
                <text x="178" y="200" fill="#60a5fa" font-size="16" class="geo-label" :style="showHint ? 'fill: #f59e0b' : ''">H</text>

                <template x-if="showHint">
                    <text x="70" y="165" fill="#10b981" font-size="12" class="geo-label">α</text>
                </template>
            </svg>
        </div>
        <button class="hint-btn" :class="{ 'active': showHint }" @click="showHint = !showHint">
            <span x-text="showHint ? 'Скрыть' : 'Подсказка'"></span>
        </button>
        <div class="hint-text" x-show="showHint" x-transition>
            <strong>Высота</strong> перпендикулярна стороне (∠BHC = 90°).<br>
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
            <svg viewBox="0 0 300 220">
                {{-- Triangle fill (shown when hint active) --}}
                <polygon points="50,180 250,180 250,60"
                         :fill="showHint ? 'rgba(16, 185, 129, 0.2)' : 'none'"
                         stroke="#8B1A1A" stroke-width="3" stroke-linejoin="round"/>

                {{-- Catheti highlighting --}}
                <template x-if="showHint">
                    <g>
                        {{-- Horizontal cathetus (a) --}}
                        <line x1="50" y1="180" x2="250" y2="180" stroke="#f59e0b" stroke-width="5"/>
                        {{-- Vertical cathetus (b) --}}
                        <line x1="250" y1="180" x2="250" y2="60" stroke="#3b82f6" stroke-width="5"/>
                        {{-- Labels for catheti --}}
                        <text x="150" y="200" fill="#f59e0b" font-size="16" text-anchor="middle" class="geo-label">a</text>
                        <text x="270" y="125" fill="#3b82f6" font-size="16" class="geo-label">b</text>
                    </g>
                </template>

                {{-- Right angle marker --}}
                <path d="M 235 180 L 235 165 L 250 165" fill="none" stroke="#666" stroke-width="2"/>

                {{-- Points --}}
                <circle cx="50" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="250" cy="180" r="4" fill="#8B1A1A"/>
                <circle cx="250" cy="60" r="4" fill="#8B1A1A"/>

                {{-- Labels --}}
                <text x="35" y="185" fill="#fff" font-size="18" class="geo-label">A</text>
                <text x="258" y="190" fill="#fff" font-size="18" class="geo-label">C</text>
                <text x="258" y="55" fill="#fff" font-size="18" class="geo-label">B</text>
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
