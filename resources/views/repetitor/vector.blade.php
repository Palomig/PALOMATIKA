<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Угол между векторами — Интерактивная визуализация</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: #1a1a24;
            --text-primary: #f0f0f5;
            --text-secondary: #8888a0;
            --accent-a: #ff6b6b;
            --accent-b: #4ecdc4;
            --accent-angle: #ffd93d;
            --glow-a: rgba(255, 107, 107, 0.3);
            --glow-b: rgba(78, 205, 196, 0.3);
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            text-align: center;
            margin-bottom: 2rem;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent-a), var(--accent-b));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 900px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        .canvas-container {
            background: var(--bg-secondary);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow:
                0 10px 40px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        canvas {
            display: block;
            width: 100%;
            border-radius: 12px;
            cursor: crosshair;
            background:
                radial-gradient(circle at 50% 50%, rgba(30, 30, 45, 1) 0%, var(--bg-primary) 100%);
        }

        .hint {
            text-align: center;
            margin-top: 1rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .hint span {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: var(--bg-card);
            border-radius: 20px;
            margin: 0.2rem;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .card-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title::before {
            content: '';
            width: 4px;
            height: 16px;
            background: linear-gradient(180deg, var(--accent-a), var(--accent-b));
            border-radius: 2px;
        }

        /* Angle Display */
        .angle-display {
            text-align: center;
            padding: 1.5rem 0;
        }

        .angle-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 4rem;
            font-weight: 600;
            color: var(--accent-angle);
            text-shadow: 0 0 40px rgba(255, 217, 61, 0.4);
            line-height: 1;
        }

        .angle-unit {
            font-size: 1.5rem;
            color: var(--text-secondary);
            margin-left: 0.3rem;
        }

        .angle-radians {
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        /* Vector Info */
        .vector-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .vector-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            background: var(--bg-secondary);
            border-radius: 10px;
        }

        .vector-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .vector-dot.a { background: var(--accent-a); box-shadow: 0 0 10px var(--glow-a); }
        .vector-dot.b { background: var(--accent-b); box-shadow: 0 0 10px var(--glow-b); }

        .vector-name {
            font-weight: 600;
            width: 20px;
        }

        .vector-coords {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .vector-length {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-left: auto;
        }

        /* Formula */
        .formula-section {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            line-height: 2;
        }

        .formula-line {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .formula-line:last-child {
            border-bottom: none;
        }

        .formula-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .formula-value {
            color: var(--text-primary);
        }

        .highlight-a { color: var(--accent-a); }
        .highlight-b { color: var(--accent-b); }
        .highlight-angle { color: var(--accent-angle); }

        /* Controls */
        .controls {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.7rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            flex: 1;
            min-width: 100px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-a), #ff8585);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px var(--glow-a);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: var(--bg-primary);
            border-color: var(--accent-b);
        }

        /* Theory section */
        .theory {
            margin-top: 2rem;
            padding: 2rem;
            background: var(--bg-secondary);
            border-radius: 20px;
        }

        .theory h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--accent-angle);
        }

        .theory p {
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .theory-formula {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.1rem;
            margin: 1.5rem 0;
            border-left: 4px solid var(--accent-angle);
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .step {
            background: var(--bg-card);
            padding: 1.2rem;
            border-radius: 12px;
        }

        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--accent-a), var(--accent-b));
            border-radius: 50%;
            font-weight: 800;
            font-size: 0.85rem;
            margin-bottom: 0.8rem;
        }

        .step-title {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .step-desc {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Угол между векторами</h1>
            <p class="subtitle">Интерактивная визуализация • Перетаскивай концы векторов</p>
        </header>

        <div class="main-content">
            <div class="canvas-container">
                <canvas id="canvas" width="600" height="500"></canvas>
                <p class="hint">
                    <span>🖱️ Перетаскивай точки</span>
                    <span>📐 Угол обновляется мгновенно</span>
                </p>
            </div>

            <div class="sidebar">
                <div class="card">
                    <div class="card-title">Угол θ</div>
                    <div class="angle-display">
                        <span class="angle-value" id="angleDegrees">45</span><span class="angle-unit">°</span>
                        <div class="angle-radians" id="angleRadians">≈ 0.785 рад</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Векторы</div>
                    <div class="vector-info">
                        <div class="vector-row">
                            <div class="vector-dot a"></div>
                            <span class="vector-name highlight-a">a⃗</span>
                            <span class="vector-coords" id="vectorA">(3, 2)</span>
                            <span class="vector-length" id="lengthA">|a⃗| = 3.61</span>
                        </div>
                        <div class="vector-row">
                            <div class="vector-dot b"></div>
                            <span class="vector-name highlight-b">b⃗</span>
                            <span class="vector-coords" id="vectorB">(1, 3)</span>
                            <span class="vector-length" id="lengthB">|b⃗| = 3.16</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Расчёт</div>
                    <div class="formula-section">
                        <div class="formula-line">
                            <div class="formula-label">Скалярное произведение:</div>
                            <div class="formula-value" id="dotProduct">a⃗ · b⃗ = 9</div>
                        </div>
                        <div class="formula-line">
                            <div class="formula-label">Косинус угла:</div>
                            <div class="formula-value" id="cosValue">cos(θ) = 0.789</div>
                        </div>
                        <div class="formula-line">
                            <div class="formula-label">Угол:</div>
                            <div class="formula-value highlight-angle" id="angleResult">θ = arccos(0.789) = 38°</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Управление</div>
                    <div class="controls">
                        <button class="btn btn-primary" onclick="randomVectors()">🎲 Случайные</button>
                        <button class="btn btn-secondary" onclick="setOrthogonal()">⊥ 90°</button>
                        <button class="btn btn-secondary" onclick="setParallel()">∥ 0°</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="theory">
            <h2>📚 Как найти угол между векторами?</h2>
            <p>
                Угол между двумя векторами находится через <strong>скалярное произведение</strong>.
                Это одна из важнейших операций в векторной алгебре, которая связывает геометрию и алгебру.
            </p>

            <div class="theory-formula">
                cos(θ) = (a⃗ · b⃗) / (|a⃗| · |b⃗|) = (a₁·b₁ + a₂·b₂) / (√(a₁² + a₂²) · √(b₁² + b₂²))
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-title">Скалярное произведение</div>
                    <div class="step-desc">a⃗ · b⃗ = a₁·b₁ + a₂·b₂</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-title">Длины векторов</div>
                    <div class="step-desc">|a⃗| = √(a₁² + a₂²)</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-title">Косинус угла</div>
                    <div class="step-desc">cos(θ) = (a⃗·b⃗)/(|a⃗|·|b⃗|)</div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-title">Найти угол</div>
                    <div class="step-desc">θ = arccos(cos(θ))</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');

        // High DPI support
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        canvas.style.width = rect.width + 'px';
        canvas.style.height = rect.height + 'px';

        const width = rect.width;
        const height = rect.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const scale = 40; // pixels per unit

        // Vectors (in mathematical coordinates)
        let vectorA = { x: 4, y: 3 };
        let vectorB = { x: -2, y: 4 };

        // Dragging state
        let dragging = null;
        const dragRadius = 20;

        // Convert math coords to canvas coords
        function toCanvas(x, y) {
            return {
                x: centerX + x * scale,
                y: centerY - y * scale
            };
        }

        // Convert canvas coords to math coords
        function toMath(canvasX, canvasY) {
            return {
                x: (canvasX - centerX) / scale,
                y: (centerY - canvasY) / scale
            };
        }

        function drawGrid() {
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
            ctx.lineWidth = 1;

            // Grid lines
            for (let i = -10; i <= 10; i++) {
                const p1 = toCanvas(i, -10);
                const p2 = toCanvas(i, 10);
                ctx.beginPath();
                ctx.moveTo(p1.x, p1.y);
                ctx.lineTo(p2.x, p2.y);
                ctx.stroke();

                const p3 = toCanvas(-10, i);
                const p4 = toCanvas(10, i);
                ctx.beginPath();
                ctx.moveTo(p3.x, p3.y);
                ctx.lineTo(p4.x, p4.y);
                ctx.stroke();
            }

            // Axes
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
            ctx.lineWidth = 2;

            // X axis
            ctx.beginPath();
            ctx.moveTo(0, centerY);
            ctx.lineTo(width, centerY);
            ctx.stroke();

            // Y axis
            ctx.beginPath();
            ctx.moveTo(centerX, 0);
            ctx.lineTo(centerX, height);
            ctx.stroke();

            // Axis labels
            ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
            ctx.font = '14px Nunito';
            ctx.fillText('x', width - 20, centerY - 10);
            ctx.fillText('y', centerX + 10, 20);
            ctx.fillText('0', centerX + 8, centerY + 18);
        }

        function drawVector(vec, color, label) {
            const origin = toCanvas(0, 0);
            const end = toCanvas(vec.x, vec.y);

            // Glow effect
            ctx.shadowColor = color;
            ctx.shadowBlur = 15;

            // Vector line
            ctx.strokeStyle = color;
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.moveTo(origin.x, origin.y);
            ctx.lineTo(end.x, end.y);
            ctx.stroke();

            // Arrowhead
            const angle = Math.atan2(origin.y - end.y, origin.x - end.x);
            const arrowSize = 15;

            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.moveTo(end.x, end.y);
            ctx.lineTo(
                end.x + arrowSize * Math.cos(angle - Math.PI / 6),
                end.y + arrowSize * Math.sin(angle - Math.PI / 6)
            );
            ctx.lineTo(
                end.x + arrowSize * Math.cos(angle + Math.PI / 6),
                end.y + arrowSize * Math.sin(angle + Math.PI / 6)
            );
            ctx.closePath();
            ctx.fill();

            // Draggable point
            ctx.beginPath();
            ctx.arc(end.x, end.y, 8, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
            ctx.strokeStyle = 'white';
            ctx.lineWidth = 2;
            ctx.stroke();

            ctx.shadowBlur = 0;

            // Label
            ctx.fillStyle = color;
            ctx.font = 'bold 18px Nunito';
            const labelOffset = 25;
            const labelAngle = Math.atan2(vec.y, vec.x);
            ctx.fillText(
                label,
                end.x + labelOffset * Math.cos(labelAngle),
                end.y - labelOffset * Math.sin(labelAngle)
            );
        }

        function drawAngleArc() {
            const origin = toCanvas(0, 0);
            const angleA = Math.atan2(vectorA.y, vectorA.x);
            const angleB = Math.atan2(vectorB.y, vectorB.x);

            // Calculate the actual angle between vectors
            const dot = vectorA.x * vectorB.x + vectorA.y * vectorB.y;
            const magA = Math.sqrt(vectorA.x ** 2 + vectorA.y ** 2);
            const magB = Math.sqrt(vectorB.x ** 2 + vectorB.y ** 2);
            const cosAngle = Math.max(-1, Math.min(1, dot / (magA * magB)));
            const angleBetween = Math.acos(cosAngle);

            // Draw arc (always the smaller angle)
            const arcRadius = 50;

            // Determine start and end angles for the smaller arc
            let startAngle = angleA;
            let endAngle = angleB;

            // Normalize angles to [0, 2π]
            while (startAngle < 0) startAngle += 2 * Math.PI;
            while (endAngle < 0) endAngle += 2 * Math.PI;

            // Ensure we draw the smaller arc
            let diff = endAngle - startAngle;
            while (diff < -Math.PI) diff += 2 * Math.PI;
            while (diff > Math.PI) diff -= 2 * Math.PI;

            ctx.strokeStyle = '#ffd93d';
            ctx.lineWidth = 3;
            ctx.shadowColor = '#ffd93d';
            ctx.shadowBlur = 10;

            ctx.beginPath();
            if (diff >= 0) {
                ctx.arc(origin.x, origin.y, arcRadius, -startAngle, -startAngle - diff, true);
            } else {
                ctx.arc(origin.x, origin.y, arcRadius, -startAngle, -startAngle - diff, false);
            }
            ctx.stroke();

            ctx.shadowBlur = 0;

            // Angle label
            const midAngle = (angleA + angleB) / 2;

            const labelRadius = arcRadius + 25;
            const degrees = Math.round(angleBetween * 180 / Math.PI);

            ctx.fillStyle = '#ffd93d';
            ctx.font = 'bold 16px JetBrains Mono';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(
                degrees + '°',
                origin.x + labelRadius * Math.cos(midAngle),
                origin.y - labelRadius * Math.sin(midAngle)
            );
            ctx.textAlign = 'left';
            ctx.textBaseline = 'alphabetic';
        }

        function draw() {
            ctx.clearRect(0, 0, width, height);

            drawGrid();
            drawAngleArc();
            drawVector(vectorA, '#ff6b6b', 'a⃗');
            drawVector(vectorB, '#4ecdc4', 'b⃗');

            updateInfo();
        }

        function updateInfo() {
            // Calculate values
            const dot = vectorA.x * vectorB.x + vectorA.y * vectorB.y;
            const magA = Math.sqrt(vectorA.x ** 2 + vectorA.y ** 2);
            const magB = Math.sqrt(vectorB.x ** 2 + vectorB.y ** 2);
            const cosAngle = dot / (magA * magB);
            const clampedCos = Math.max(-1, Math.min(1, cosAngle));
            const angleRad = Math.acos(clampedCos);
            const angleDeg = angleRad * 180 / Math.PI;

            // Update display
            document.getElementById('angleDegrees').textContent = Math.round(angleDeg);
            document.getElementById('angleRadians').textContent = `≈ ${angleRad.toFixed(3)} рад`;

            document.getElementById('vectorA').textContent = `(${vectorA.x.toFixed(1)}, ${vectorA.y.toFixed(1)})`;
            document.getElementById('vectorB').textContent = `(${vectorB.x.toFixed(1)}, ${vectorB.y.toFixed(1)})`;
            document.getElementById('lengthA').textContent = `|a⃗| = ${magA.toFixed(2)}`;
            document.getElementById('lengthB').textContent = `|b⃗| = ${magB.toFixed(2)}`;

            document.getElementById('dotProduct').textContent =
                `a⃗ · b⃗ = ${vectorA.x.toFixed(1)}·${vectorB.x.toFixed(1)} + ${vectorA.y.toFixed(1)}·${vectorB.y.toFixed(1)} = ${dot.toFixed(2)}`;
            document.getElementById('cosValue').textContent =
                `cos(θ) = ${dot.toFixed(2)} / (${magA.toFixed(2)}·${magB.toFixed(2)}) = ${clampedCos.toFixed(3)}`;
            document.getElementById('angleResult').textContent =
                `θ = arccos(${clampedCos.toFixed(3)}) = ${Math.round(angleDeg)}°`;
        }

        // Mouse interaction
        function getMousePos(e) {
            const rect = canvas.getBoundingClientRect();
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        }

        function isNearPoint(mouse, vec) {
            const point = toCanvas(vec.x, vec.y);
            const dx = mouse.x - point.x;
            const dy = mouse.y - point.y;
            return Math.sqrt(dx * dx + dy * dy) < dragRadius;
        }

        canvas.addEventListener('mousedown', (e) => {
            const mouse = getMousePos(e);
            if (isNearPoint(mouse, vectorA)) {
                dragging = 'A';
            } else if (isNearPoint(mouse, vectorB)) {
                dragging = 'B';
            }
        });

        canvas.addEventListener('mousemove', (e) => {
            const mouse = getMousePos(e);

            // Change cursor when hovering over draggable points
            if (isNearPoint(mouse, vectorA) || isNearPoint(mouse, vectorB)) {
                canvas.style.cursor = 'grab';
            } else {
                canvas.style.cursor = 'crosshair';
            }

            if (dragging) {
                canvas.style.cursor = 'grabbing';
                const math = toMath(mouse.x, mouse.y);

                // Snap to grid (optional, makes it easier to get clean values)
                const snapped = {
                    x: Math.round(math.x * 2) / 2,
                    y: Math.round(math.y * 2) / 2
                };

                // Prevent zero vector
                if (snapped.x === 0 && snapped.y === 0) {
                    snapped.x = 0.5;
                }

                if (dragging === 'A') {
                    vectorA = snapped;
                } else {
                    vectorB = snapped;
                }
                draw();
            }
        });

        canvas.addEventListener('mouseup', () => {
            dragging = null;
        });

        canvas.addEventListener('mouseleave', () => {
            dragging = null;
        });

        // Touch support
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            const touch = e.touches[0];
            const mouse = {
                x: touch.clientX - canvas.getBoundingClientRect().left,
                y: touch.clientY - canvas.getBoundingClientRect().top
            };
            if (isNearPoint(mouse, vectorA)) {
                dragging = 'A';
            } else if (isNearPoint(mouse, vectorB)) {
                dragging = 'B';
            }
        });

        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            if (dragging) {
                const touch = e.touches[0];
                const mouse = {
                    x: touch.clientX - canvas.getBoundingClientRect().left,
                    y: touch.clientY - canvas.getBoundingClientRect().top
                };
                const math = toMath(mouse.x, mouse.y);
                const snapped = {
                    x: Math.round(math.x * 2) / 2,
                    y: Math.round(math.y * 2) / 2
                };
                if (snapped.x === 0 && snapped.y === 0) {
                    snapped.x = 0.5;
                }
                if (dragging === 'A') {
                    vectorA = snapped;
                } else {
                    vectorB = snapped;
                }
                draw();
            }
        });

        canvas.addEventListener('touchend', () => {
            dragging = null;
        });

        // Control buttons
        function randomVectors() {
            vectorA = {
                x: (Math.random() - 0.5) * 8,
                y: (Math.random() - 0.5) * 8
            };
            vectorB = {
                x: (Math.random() - 0.5) * 8,
                y: (Math.random() - 0.5) * 8
            };
            // Round for cleaner numbers
            vectorA.x = Math.round(vectorA.x * 2) / 2;
            vectorA.y = Math.round(vectorA.y * 2) / 2;
            vectorB.x = Math.round(vectorB.x * 2) / 2;
            vectorB.y = Math.round(vectorB.y * 2) / 2;

            // Prevent zero vectors
            if (vectorA.x === 0 && vectorA.y === 0) vectorA.x = 1;
            if (vectorB.x === 0 && vectorB.y === 0) vectorB.x = 1;

            draw();
        }

        function setOrthogonal() {
            vectorA = { x: 3, y: 0 };
            vectorB = { x: 0, y: 3 };
            draw();
        }

        function setParallel() {
            vectorA = { x: 3, y: 2 };
            vectorB = { x: 1.5, y: 1 };
            draw();
        }

        // Initial draw
        draw();
    </script>
</body>
</html>
