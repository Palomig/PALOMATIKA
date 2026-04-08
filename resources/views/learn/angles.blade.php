<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Вписанный и центральный углы — PALOMATIKA</title>
    @include('partials.head-config')
    <style>
        .drag-point { cursor: grab; touch-action: none; }
        .drag-point:active { cursor: grabbing; }
        .angle-badge {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
        }
        .theorem-glow-green { box-shadow: 0 0 20px rgba(16,185,129,0.12); }
        .theorem-glow-amber { box-shadow: 0 0 20px rgba(245,158,11,0.12); }
        .theorem-glow-red   { box-shadow: 0 0 20px rgba(220,38,38,0.10); }
    </style>
</head>
<body class="min-h-screen" style="background: #060b14;">

<div class="max-w-5xl mx-auto px-4 py-8"
     x-data="anglesDemo()"
     x-init="init()"
     @mousemove.window="onMove($event)"
     @mouseup.window="stopDrag()"
     @touchmove.window.prevent="onMove($event)"
     @touchend.window="stopDrag()">

    {{-- ── Header ── --}}
    <div class="text-center mb-8">
        <a href="javascript:history.back()"
           class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-400 transition mb-5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            назад
        </a>

        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full mb-4"
             style="background: rgba(96,165,250,0.08); border: 1px solid rgba(96,165,250,0.2); color: #93c5fd;">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
            </svg>
            <span class="text-xs font-medium">Интерактивный урок</span>
        </div>

        <h1 class="text-2xl font-bold text-slate-100 mb-1">Вписанный и центральный углы</h1>
        <p class="text-slate-500 text-sm">Перетаскивай точки <span style="color:#60a5fa">A</span>, <span style="color:#60a5fa">B</span>, <span style="color:#60a5fa">C</span> по окружности</p>
    </div>

    {{-- ── Main layout: SVG + Info ── --}}
    <div class="flex flex-col lg:flex-row gap-6 items-start justify-center">

        {{-- ── SVG Canvas ── --}}
        <div class="w-full lg:w-auto flex-shrink-0">
            <div class="rounded-2xl p-4" style="background: #0b1421; border: 1px solid rgba(30,41,59,0.8);">
                <svg x-ref="svg"
                     viewBox="0 0 400 400"
                     class="w-full lg:w-[420px] select-none"
                     xmlns="http://www.w3.org/2000/svg">

                    {{-- ── Defs: gradient fills for angle sectors ── --}}
                    <defs>
                        <radialGradient id="centralFill" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.15"/>
                            <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                        </radialGradient>
                        <radialGradient id="inscribedFill" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.12"/>
                            <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
                        </radialGradient>
                    </defs>

                    {{-- ── Full circle ── --}}
                    <circle :cx="cx" :cy="cy" :r="r"
                            fill="none" stroke="#1e293b" stroke-width="1.5"/>

                    {{-- ── Subtle radial background ── --}}
                    <circle :cx="cx" :cy="cy" :r="r"
                            fill="rgba(15,25,50,0.4)" stroke="none"/>

                    {{-- ── Minor arc A→B (the arc "subtended") ── --}}
                    <path :d="minorArcPath()"
                          fill="none" stroke="#dc2626" stroke-width="4"
                          stroke-linecap="round" opacity="0.9"/>

                    {{-- ── Central angle sector fill ── --}}
                    <path :d="centralSectorPath()" fill="rgba(16,185,129,0.06)"/>

                    {{-- ── Central angle lines: O→A, O→B ── --}}
                    <line :x1="cx" :y1="cy" :x2="pA().x" :y2="pA().y"
                          stroke="#10b981" stroke-width="2.5" stroke-linecap="round" opacity="0.9"/>
                    <line :x1="cx" :y1="cy" :x2="pB().x" :y2="pB().y"
                          stroke="#10b981" stroke-width="2.5" stroke-linecap="round" opacity="0.9"/>

                    {{-- ── Inscribed angle lines: C→A, C→B ── --}}
                    <line :x1="pC().x" :y1="pC().y" :x2="pA().x" :y2="pA().y"
                          stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" opacity="0.9"/>
                    <line :x1="pC().x" :y1="pC().y" :x2="pB().x" :y2="pB().y"
                          stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" opacity="0.9"/>

                    {{-- ── Central angle arc at O ── --}}
                    <path :d="smallArc({x:cx,y:cy}, pA(), pB(), 38)"
                          fill="none" stroke="#10b981" stroke-width="2.5"
                          stroke-linecap="round" opacity="0.85"/>

                    {{-- ── Inscribed angle arc at C ── --}}
                    <path :d="smallArc(pC(), pA(), pB(), 28)"
                          fill="none" stroke="#f59e0b" stroke-width="2.5"
                          stroke-linecap="round" opacity="0.85"/>

                    {{-- ── Central angle label near O ── --}}
                    <text :x="centralAngleLabelPos().x" :y="centralAngleLabelPos().y"
                          fill="#10b981" font-size="13" font-weight="600"
                          text-anchor="middle" dominant-baseline="central"
                          class="angle-badge"
                          x-text="centralAngleDeg() + '°'"/>

                    {{-- ── Inscribed angle label near C ── --}}
                    <text :x="inscribedAngleLabelPos().x" :y="inscribedAngleLabelPos().y"
                          fill="#f59e0b" font-size="13" font-weight="600"
                          text-anchor="middle" dominant-baseline="central"
                          class="angle-badge"
                          x-text="inscribedAngleDeg() + '°'"/>

                    {{-- ── Center dot O ── --}}
                    <circle :cx="cx" :cy="cy" r="5" fill="#60a5fa"/>
                    <text :x="cx - 14" :y="cy" fill="#60a5fa" font-size="15" font-weight="700"
                          text-anchor="middle" dominant-baseline="central">O</text>

                    {{-- ── Point A ── --}}
                    <circle :cx="pA().x" :cy="pA().y" r="10"
                            fill="#10b981" stroke="#0b1421" stroke-width="2.5"
                            class="drag-point"
                            @mousedown.prevent="startDrag($event, 'A')"
                            @touchstart.prevent="startDrag($event, 'A')"/>
                    <text :x="labelPos(tA, 22).x" :y="labelPos(tA, 22).y"
                          fill="#60a5fa" font-size="16" font-weight="700"
                          text-anchor="middle" dominant-baseline="central">A</text>

                    {{-- ── Point B ── --}}
                    <circle :cx="pB().x" :cy="pB().y" r="10"
                            fill="#10b981" stroke="#0b1421" stroke-width="2.5"
                            class="drag-point"
                            @mousedown.prevent="startDrag($event, 'B')"
                            @touchstart.prevent="startDrag($event, 'B')"/>
                    <text :x="labelPos(tB, 22).x" :y="labelPos(tB, 22).y"
                          fill="#60a5fa" font-size="16" font-weight="700"
                          text-anchor="middle" dominant-baseline="central">B</text>

                    {{-- ── Point C ── --}}
                    <circle :cx="pC().x" :cy="pC().y" r="10"
                            fill="#f59e0b" stroke="#0b1421" stroke-width="2.5"
                            class="drag-point"
                            @mousedown.prevent="startDrag($event, 'C')"
                            @touchstart.prevent="startDrag($event, 'C')"/>
                    <text :x="labelPos(tC, 22).x" :y="labelPos(tC, 22).y"
                          fill="#60a5fa" font-size="16" font-weight="700"
                          text-anchor="middle" dominant-baseline="central">C</text>

                </svg>
            </div>

            {{-- ── Legend ── --}}
            <div class="flex flex-wrap gap-3 justify-center mt-3">
                <div class="flex items-center gap-1.5 text-xs text-slate-400">
                    <span class="w-3 h-3 rounded-full inline-block" style="background:#10b981"></span>
                    Центральный угол
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400">
                    <span class="w-3 h-3 rounded-full inline-block" style="background:#f59e0b"></span>
                    Вписанный угол
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400">
                    <span class="w-3 h-3 rounded-sm inline-block" style="background:#dc2626"></span>
                    Дуга AB
                </div>
            </div>
        </div>

        {{-- ── Info Panel ── --}}
        <div class="flex-1 flex flex-col gap-4 w-full">

            {{-- ── Live angle display ── --}}
            <div class="grid grid-cols-2 gap-3">

                {{-- Central angle ── --}}
                <div class="rounded-2xl p-4 theorem-glow-green"
                     style="background: #0b1421; border: 1px solid rgba(16,185,129,0.25);">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-2.5 h-2.5 rounded-full" style="background:#10b981"></div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Центральный</span>
                    </div>
                    <div class="text-3xl font-bold angle-badge" style="color:#10b981"
                         x-text="centralAngleDeg() + '°'">—</div>
                    <div class="text-xs text-slate-500 mt-1">∠AOB</div>
                </div>

                {{-- Inscribed angle ── --}}
                <div class="rounded-2xl p-4 theorem-glow-amber"
                     style="background: #0b1421; border: 1px solid rgba(245,158,11,0.25);">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-2.5 h-2.5 rounded-full" style="background:#f59e0b"></div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Вписанный</span>
                    </div>
                    <div class="text-3xl font-bold angle-badge" style="color:#f59e0b"
                         x-text="inscribedAngleDeg() + '°'">—</div>
                    <div class="text-xs text-slate-500 mt-1">∠ACB</div>
                </div>
            </div>

            {{-- ── Theorem status ── --}}
            <div class="rounded-2xl p-4"
                 :style="cOnMajorArc()
                     ? 'background:#0b1421; border:1px solid rgba(16,185,129,0.3)'
                     : 'background:#0b1421; border:1px solid rgba(245,158,11,0.3)'">

                <div class="flex items-center gap-2 mb-3">
                    <svg x-show="cOnMajorArc()" class="w-4 h-4 shrink-0" style="color:#10b981" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <svg x-show="!cOnMajorArc()" class="w-4 h-4 shrink-0" style="color:#f59e0b" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-semibold"
                          :style="cOnMajorArc() ? 'color:#34d399' : 'color:#fbbf24'"
                          x-text="cOnMajorArc() ? 'Теорема выполняется' : 'C на меньшей дуге'"></span>
                </div>

                <div x-show="cOnMajorArc()">
                    <div class="rounded-xl p-3 text-center" style="background:rgba(16,185,129,0.06);">
                        <span class="text-slate-300 text-sm">
                            <span style="color:#10b981" class="font-bold angle-badge" x-text="centralAngleDeg() + '°'"></span>
                            <span class="text-slate-500 mx-2">=</span>
                            <span class="text-slate-400">2</span>
                            <span class="text-slate-500 mx-1">×</span>
                            <span style="color:#f59e0b" class="font-bold angle-badge" x-text="inscribedAngleDeg() + '°'"></span>
                        </span>
                    </div>
                </div>

                <div x-show="!cOnMajorArc()">
                    <div class="rounded-xl p-3 text-center" style="background:rgba(245,158,11,0.06);">
                        <span class="text-xs text-slate-400 leading-relaxed">
                            Точка C попала на меньшую дугу AB.<br>
                            Вписанный угол = <span style="color:#f59e0b" class="font-medium">180° − </span>
                            <span style="color:#10b981" class="font-medium" x-text="(centralAngleDeg()/2).toFixed(0) + '°'"></span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- ── Theory cards ── --}}
            <div class="rounded-2xl overflow-hidden"
                 style="background: #0b1421; border: 1px solid rgba(30,41,59,0.8);">

                {{-- Central angle definition ── --}}
                <div class="px-4 py-3.5" style="border-bottom: 1px solid rgba(30,41,59,0.6);">
                    <div class="flex gap-3">
                        <div class="w-1 rounded-full flex-shrink-0 mt-0.5" style="background:#10b981; min-height:1rem"></div>
                        <div>
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Центральный угол</div>
                            <p class="text-slate-300 text-sm leading-relaxed">
                                Угол <span style="color:#10b981" class="font-semibold">∠AOB</span>, вершина которого совпадает
                                с центром окружности <span style="color:#60a5fa" class="font-semibold">O</span>.
                                Его стороны — радиусы <span style="color:#10b981">OA</span> и <span style="color:#10b981">OB</span>.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Inscribed angle definition ── --}}
                <div class="px-4 py-3.5" style="border-bottom: 1px solid rgba(30,41,59,0.6);">
                    <div class="flex gap-3">
                        <div class="w-1 rounded-full flex-shrink-0 mt-0.5" style="background:#f59e0b; min-height:1rem"></div>
                        <div>
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Вписанный угол</div>
                            <p class="text-slate-300 text-sm leading-relaxed">
                                Угол <span style="color:#f59e0b" class="font-semibold">∠ACB</span>, вершина которого лежит
                                на окружности в точке <span style="color:#60a5fa" class="font-semibold">C</span>.
                                Его стороны — хорды <span style="color:#f59e0b">CA</span> и <span style="color:#f59e0b">CB</span>.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Main theorem ── --}}
                <div class="px-4 py-3.5">
                    <div class="flex gap-3">
                        <div class="w-1 rounded-full flex-shrink-0 mt-0.5" style="background:#dc2626; min-height:1rem"></div>
                        <div>
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Теорема о вписанном угле</div>
                            <p class="text-slate-300 text-sm leading-relaxed">
                                Вписанный угол равен <strong class="text-slate-200">половине</strong> центрального угла,
                                опирающихся на одну и ту же дугу
                                <span style="color:#dc2626" class="font-semibold">AB</span>.
                            </p>
                            <div class="mt-2 rounded-lg px-3 py-2 text-center text-sm font-mono"
                                 style="background:rgba(220,38,38,0.06); border:1px solid rgba(220,38,38,0.15);">
                                <span style="color:#f59e0b">∠ACB</span>
                                <span class="text-slate-500 mx-2">=</span>
                                <span class="text-slate-400">½</span>
                                <span class="text-slate-500 mx-1">·</span>
                                <span style="color:#10b981">∠AOB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Corollary: all inscribed on same arc ── --}}
            <div class="rounded-2xl px-4 py-3.5"
                 style="background: #0b1421; border: 1px solid rgba(30,41,59,0.8);">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Следствие</div>
                <p class="text-slate-400 text-sm leading-relaxed">
                    Все вписанные углы, опирающиеся на одну дугу
                    <span style="color:#dc2626" class="font-medium">AB</span>, равны между собой —
                    куда бы точка <span style="color:#60a5fa" class="font-medium">C</span> ни
                    переместилась по большей дуге.
                </p>
            </div>

        </div>
    </div>

    {{-- ── Hint bar ── --}}
    <div class="mt-6 text-center">
        <p class="text-xs text-slate-600">
            Перетаскивай зелёные точки <span style="color:#10b981">A</span> и <span style="color:#10b981">B</span> для изменения дуги
            · Перетаскивай жёлтую точку <span style="color:#f59e0b">C</span> для изменения вписанного угла
        </p>
    </div>

</div>

<script>
function anglesDemo() {
    return {
        // SVG dimensions
        W: 400, H: 400,
        cx: 200, cy: 200, r: 155,

        // Angles in SVG coords (0 = right, increases clockwise due to Y-down)
        // Initial: A at ~135°, B at ~45°, C at bottom ~90°
        tA: Math.PI * 0.75,   // 135° → upper-left
        tB: Math.PI * 0.25,   // 45°  → upper-right (goes CW so 45° is lower-right... let me adjust)
        tC: Math.PI * 0.5,    // 90°  → bottom

        dragging: null,
        svgRect: null,

        init() {
            // A at upper-left, B at upper-right, C at bottom
            this.tA = Math.PI + Math.PI * 0.33;  // ~240° → lower-left
            this.tB = -Math.PI * 0.33;            // ~-60° (300°) → lower-right
            this.tC = -Math.PI * 0.5;             // -90° (270°) → top... let me think

            // Let me use cleaner initial values
            // In SVG: 0=right, π/2=bottom, π=left, 3π/2=top
            // I want A upper-left, B upper-right, C at bottom
            this.tA = -Math.PI * 0.67;  // ~-120° (240°) ≡ going to upper-left area
            this.tB = -Math.PI * 0.33;  // ~-60° → upper-right area... no

            // Simpler: let's just set explicit angles
            // 3 o'clock = 0, going clockwise
            // A at 10 o'clock = -60° from top = -π/2 - π/3 = about -150° = -5π/6 → upper-left
            // Actually upper-left in SVG means negative x, negative y (since y is down)
            // That's angle 180° to 270° mathematically = π to 3π/2... no wait
            // In SVG convention I'm using: x = cx + r*cos(t), y = cy + r*sin(t)
            // t=0: right (positive x, cy)
            // t=π/2: bottom (cx, positive y - going DOWN) → bottom of circle
            // t=π: left
            // t=3π/2 or -π/2: top

            // So: A at upper-left → t ≈ -2π/3 (or 4π/3) = about 120° counterclockwise from right = 10 o'clock position
            // B at upper-right → t ≈ -π/3 (or 5π/3) = about 60° clockwise from right... wait
            // -π/3 ≈ -60° → cos(-60°) = 0.5 (right-ish), sin(-60°) = -0.87 (up, since negative y)
            // So -π/3 is at upper-right ✓

            // C at bottom → t = π/2

            this.tA = -2 * Math.PI / 3;   // upper-left (10 o'clock)
            this.tB = -Math.PI / 3;        // upper-right (2 o'clock-ish)
            this.tC = Math.PI / 2;         // bottom (6 o'clock)

            this.$nextTick(() => {
                this.svgRect = this.$refs.svg.getBoundingClientRect();
            });
        },

        // Point coordinates
        pA() { return { x: this.cx + this.r * Math.cos(this.tA), y: this.cy + this.r * Math.sin(this.tA) }; },
        pB() { return { x: this.cx + this.r * Math.cos(this.tB), y: this.cy + this.r * Math.sin(this.tB) }; },
        pC() { return { x: this.cx + this.r * Math.cos(this.tC), y: this.cy + this.r * Math.sin(this.tC) }; },

        labelPos(angle, offset) {
            return {
                x: this.cx + (this.r + offset) * Math.cos(angle),
                y: this.cy + (this.r + offset) * Math.sin(angle)
            };
        },

        // ── Angle computations ──

        centralAngleDeg() {
            let diff = ((this.tB - this.tA) + 2 * Math.PI) % (2 * Math.PI);
            let minorArc = Math.min(diff, 2 * Math.PI - diff);
            return Math.round(minorArc * 180 / Math.PI);
        },

        inscribedAngleDeg() {
            const a = this.pA(), b = this.pB(), c = this.pC();
            const cax = a.x - c.x, cay = a.y - c.y;
            const cbx = b.x - c.x, cby = b.y - c.y;
            const dot = cax * cbx + cay * cby;
            const mag = Math.sqrt(cax * cax + cay * cay) * Math.sqrt(cbx * cbx + cby * cby);
            if (mag < 0.001) return 0;
            return Math.round(Math.acos(Math.max(-1, Math.min(1, dot / mag))) * 180 / Math.PI);
        },

        // C is on major arc when inscribed angle ≈ central/2
        cOnMajorArc() {
            const central = this.centralAngleDeg();
            const inscribed = this.inscribedAngleDeg();
            return Math.abs(inscribed - central / 2) < 2;
        },

        // ── SVG paths ──

        // Minor arc from A to B
        minorArcPath() {
            const a = this.pA(), b = this.pB();
            let diff = ((this.tB - this.tA) + 2 * Math.PI) % (2 * Math.PI);
            // If diff ≤ π, CW from A to B is the short arc (sweep=1)
            // If diff > π, CCW from A to B is the short arc (sweep=0)
            const sweep = diff <= Math.PI ? 1 : 0;
            return `M ${a.x.toFixed(2)} ${a.y.toFixed(2)} A ${this.r} ${this.r} 0 0 ${sweep} ${b.x.toFixed(2)} ${b.y.toFixed(2)}`;
        },

        // Filled sector for central angle
        centralSectorPath() {
            const a = this.pA(), b = this.pB();
            let diff = ((this.tB - this.tA) + 2 * Math.PI) % (2 * Math.PI);
            const sweep = diff <= Math.PI ? 1 : 0;
            const largeArc = diff <= Math.PI ? 0 : 0; // always minor arc sector
            return `M ${this.cx} ${this.cy} L ${a.x.toFixed(2)} ${a.y.toFixed(2)} A ${this.r} ${this.r} 0 ${largeArc} ${sweep} ${b.x.toFixed(2)} ${b.y.toFixed(2)} Z`;
        },

        // Small angle arc at a vertex showing the angle between two lines
        smallArc(vertex, p1, p2, arcR) {
            const t1 = Math.atan2(p1.y - vertex.y, p1.x - vertex.x);
            const t2 = Math.atan2(p2.y - vertex.y, p2.x - vertex.x);
            const sx = vertex.x + arcR * Math.cos(t1);
            const sy = vertex.y + arcR * Math.sin(t1);
            const ex = vertex.x + arcR * Math.cos(t2);
            const ey = vertex.y + arcR * Math.sin(t2);
            let diff = ((t2 - t1) + 2 * Math.PI) % (2 * Math.PI);
            // Always draw the shorter arc between the two lines
            const sweep = diff <= Math.PI ? 1 : 0;
            return `M ${sx.toFixed(2)} ${sy.toFixed(2)} A ${arcR} ${arcR} 0 0 ${sweep} ${ex.toFixed(2)} ${ey.toFixed(2)}`;
        },

        // Position for the central angle degree label (midpoint of central angle arc)
        centralAngleLabelPos() {
            const midT = this.midAngle(this.tA, this.tB);
            const labelR = 58;
            return {
                x: this.cx + labelR * Math.cos(midT),
                y: this.cy + labelR * Math.sin(midT)
            };
        },

        // Position for the inscribed angle degree label
        inscribedAngleLabelPos() {
            const c = this.pC();
            const midT = this.midAngle(
                Math.atan2(this.pA().y - c.y, this.pA().x - c.x),
                Math.atan2(this.pB().y - c.y, this.pB().x - c.x)
            );
            const labelR = 44;
            return {
                x: c.x + labelR * Math.cos(midT),
                y: c.y + labelR * Math.sin(midT)
            };
        },

        // Midpoint angle between t1 and t2 (shorter arc direction)
        midAngle(t1, t2) {
            let diff = ((t2 - t1) + 2 * Math.PI) % (2 * Math.PI);
            if (diff <= Math.PI) {
                return t1 + diff / 2;
            } else {
                return t1 - (2 * Math.PI - diff) / 2;
            }
        },

        // ── Drag handling ──

        startDrag(e, point) {
            this.dragging = point;
            this.svgRect = this.$refs.svg.getBoundingClientRect();
            e.preventDefault();
        },

        onMove(e) {
            if (!this.dragging) return;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            const mx = (clientX - this.svgRect.left) / this.svgRect.width * this.W;
            const my = (clientY - this.svgRect.top) / this.svgRect.height * this.H;
            const angle = Math.atan2(my - this.cy, mx - this.cx);

            if (this.dragging === 'A') this.tA = angle;
            else if (this.dragging === 'B') this.tB = angle;
            else if (this.dragging === 'C') this.tC = angle;
        },

        stopDrag() {
            this.dragging = null;
        },
    };
}
</script>

</body>
</html>
