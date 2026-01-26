{{--
    SVG Geometry Editor - Редактор геометрических фигур

    Использование:
    @include('components.geometry-editor', ['taskId' => '15OGE123'])
--}}

<style>
    /* SVG visibility fix for Alpine.js */
    #geometry-canvas g[x-show] {
        display: inline !important;
    }
    #geometry-canvas g[x-show][style*="display: none"] {
        display: none !important;
    }
</style>

<div id="geometry-editor-modal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm"
     x-data="geometryEditor()"
     x-show="isOpen"
     x-cloak
     @keydown.escape.window="close()"
     @keydown.ctrl.z.window.prevent="undo()"
     @keydown.ctrl.shift.z.window.prevent="redo()"
     @keydown.ctrl.y.window.prevent="redo()">

    <div class="bg-[#0f0f1a] rounded-2xl shadow-2xl w-[95vw] max-w-[1400px] h-[90vh] flex flex-col overflow-hidden border border-purple-500/30">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-purple-500/20 bg-[#1a1a2e]">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>
                    Редактор геометрии
                </h2>
                <span class="text-sm text-gray-400" x-text="'Задание: ' + taskId"></span>
                <span x-show="mode === 'legacy_view'" class="px-2 py-1 text-xs bg-amber-500/20 text-amber-400 rounded">Legacy</span>
            </div>

            <div class="flex items-center gap-2">
                {{-- Undo/Redo --}}
                <button @click="undo()" :disabled="!canUndo"
                        class="p-2 rounded-lg hover:bg-purple-500/20 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        title="Отменить (Ctrl+Z)">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </button>
                <button @click="redo()" :disabled="!canRedo"
                        class="p-2 rounded-lg hover:bg-purple-500/20 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                        title="Повторить (Ctrl+Y)">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/>
                    </svg>
                </button>

                <div class="w-px h-6 bg-gray-600 mx-2"></div>

                {{-- Close --}}
                <button @click="close()" class="p-2 rounded-lg hover:bg-red-500/20 transition-colors" title="Закрыть (Esc)">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Main content --}}
        <div class="flex flex-1 overflow-hidden">

            {{-- Canvas area --}}
            <div class="flex-1 flex flex-col bg-[#0a0a14]">

                {{-- Toolbar --}}
                <div class="flex items-center gap-2 px-4 py-3 border-b border-purple-500/10 bg-[#12121f]">
                    {{-- Add figure --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-2 bg-purple-600 hover:bg-purple-500 rounded-lg text-white text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Добавить фигуру
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute top-full left-0 mt-2 w-56 bg-[#1e1e32] rounded-lg shadow-xl border border-purple-500/20 py-2 z-50">
                            <div class="px-3 py-1 text-xs text-gray-500 uppercase tracking-wider">Планиметрия</div>
                            <button @click="addFigure('triangle'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">△</span> Треугольник
                            </button>
                            <button @click="addFigure('quadrilateral'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">▢</span> Четырёхугольник
                            </button>
                            <button @click="addFigure('circle'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">○</span> Окружность
                            </button>
                            <div class="border-t border-gray-700 my-2"></div>
                            <div class="px-3 py-1 text-xs text-gray-500 uppercase tracking-wider">Стереометрия</div>
                            <button @click="addFigure('cube'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">⬡</span> Куб
                            </button>
                            <button @click="addFigure('prism'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">⬡</span> Призма
                            </button>
                            <button @click="addFigure('pyramid'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">△</span> Пирамида
                            </button>
                            <button @click="addFigure('cylinder'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">⬭</span> Цилиндр
                            </button>
                            <button @click="addFigure('cone'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">▲</span> Конус
                            </button>
                            <button @click="addFigure('sphere'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20 flex items-center gap-2">
                                <span class="text-lg">●</span> Шар
                            </button>
                        </div>
                    </div>

                    {{-- Presets --}}
                    <div class="relative" x-data="{ open: false }" x-show="selectedFigure && selectedFigure.type === 'triangle'">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded-lg text-gray-200 text-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
                            </svg>
                            Пресеты
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute top-full left-0 mt-2 w-48 bg-[#1e1e32] rounded-lg shadow-xl border border-purple-500/20 py-2 z-50">
                            <button @click="applyPreset('isosceles'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20">
                                Равнобедренный
                            </button>
                            <button @click="applyPreset('equilateral'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20">
                                Равносторонний
                            </button>
                            <button @click="applyPreset('right'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20">
                                Прямоугольный
                            </button>
                            <button @click="applyPreset('free'); open = false" class="w-full px-3 py-2 text-left text-gray-200 hover:bg-purple-500/20">
                                Произвольный
                            </button>
                        </div>
                    </div>

                    {{-- Grid toggle --}}
                    <button @click="toggleGrid()"
                            :class="showGrid ? 'bg-purple-600 text-white' : 'bg-[#1e1e32] text-gray-200'"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9h16M4 15h16M9 4v16M15 4v16"/>
                        </svg>
                        Сетка
                    </button>

                    {{-- Grid size (when grid is on) --}}
                    <div x-show="showGrid" class="flex items-center gap-2 ml-2">
                        <label class="text-xs text-gray-400">Клетка:</label>
                        <input type="range" min="10" max="50" x-model="gridSize"
                               class="w-20 h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                        <input type="number" x-model="gridSize" min="10" max="50"
                               class="w-12 px-1 py-1 text-xs bg-[#1e1e32] text-gray-200 rounded border border-gray-600 text-center">
                        <span class="text-xs text-gray-500">px</span>
                    </div>

                    <div class="flex-1"></div>

                    {{-- Delete selected --}}
                    <button @click="deleteSelected()" x-show="selectedFigure"
                            class="flex items-center gap-2 px-3 py-2 bg-red-600/20 hover:bg-red-600/40 rounded-lg text-red-400 text-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Удалить
                    </button>
                </div>

                {{-- SVG Canvas --}}
                <div class="flex-1 relative overflow-hidden" id="canvas-container">
                    <svg id="geometry-canvas"
                         class="w-full h-full"
                         :viewBox="`0 0 ${canvasWidth} ${canvasHeight}`"
                         @mousedown="onCanvasMouseDown($event)"
                         @mousemove="onCanvasMouseMove($event)"
                         @mouseup="onCanvasMouseUp($event)"
                         @mouseleave="onCanvasMouseUp($event)">

                        {{-- Grid --}}
                        <template x-if="showGrid">
                            <g class="grid-layer">
                                <defs>
                                    <pattern :id="'grid-' + gridSize" :width="gridSize" :height="gridSize" patternUnits="userSpaceOnUse">
                                        <path :d="`M ${gridSize} 0 L 0 0 0 ${gridSize}`" fill="none" stroke="#2a2a4a" stroke-width="0.5"/>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" :fill="`url(#grid-${gridSize})`"/>
                            </g>
                        </template>

                        {{-- Debug: static rectangle to test SVG rendering --}}
                        <rect x="10" y="10" width="50" height="50" fill="red" opacity="0.5"/>

                        {{-- Debug: count of figures --}}
                        <text x="70" y="35" fill="white" font-size="14" x-text="'Figures: ' + figures.length"></text>

                        {{-- Figures --}}
                        <template x-for="(figure, index) in figures" :key="figure.id">
                            <g :class="{'selected-figure': selectedFigure && selectedFigure.id === figure.id}"
                               @click.stop="selectFigure(figure)">

                                {{-- Debug: marker for each figure --}}
                                <circle :cx="50 + index * 30" cy="80" r="10" fill="lime"/>
                                <text :x="50 + index * 30" y="100" fill="white" font-size="10" text-anchor="middle" x-text="figure.type.charAt(0).toUpperCase()"></text>

                                {{-- Triangle --}}
                                <g x-show="figure.type === 'triangle'" style="display: inline;">
                                        {{-- Main shape --}}
                                        <polygon :points="getTrianglePoints(figure)"
                                                 fill="none"
                                                 :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                 stroke-width="2"/>

                                        {{-- Angle arcs --}}
                                        <template x-for="(vertex, vName) in figure.vertices" :key="vName">
                                            <g x-show="figure.angles && figure.angles[vName] && figure.angles[vName].showArc">
                                                {{-- Right angle marker --}}
                                                <template x-if="isVertexRightAngle(figure, vName)">
                                                    <path :d="getRightAnglePath(figure, vName)"
                                                          fill="none" stroke="#666" stroke-width="1.5"/>
                                                </template>
                                                {{-- Regular arc --}}
                                                <template x-if="!isVertexRightAngle(figure, vName)">
                                                    <path :d="getAngleArc(figure, vName)"
                                                          fill="none" stroke="#fbbf24" stroke-width="1.5"/>
                                                </template>
                                                {{-- Angle value --}}
                                                <text x-show="figure.angles[vName].showValue"
                                                      :x="getAngleLabelPos(figure, vName).x"
                                                      :y="getAngleLabelPos(figure, vName).y"
                                                      fill="#fbbf24" font-size="12" text-anchor="middle" dominant-baseline="middle"
                                                      x-text="getAngleValue(figure, vName) + '°'"></text>
                                            </g>
                                        </template>

                                        {{-- Lines (bisector, median, altitude) --}}
                                        <template x-if="figure.lines">
                                            <g class="auxiliary-lines">
                                                {{-- Bisectors --}}
                                                <template x-for="vName in ['A', 'B', 'C']" :key="'bisector-' + vName">
                                                    <g x-show="figure.lines['bisector_' + vName.toLowerCase()] && figure.lines['bisector_' + vName.toLowerCase()].enabled">
                                                        <line :x1="figure.vertices[vName].x"
                                                              :y1="figure.vertices[vName].y"
                                                              :x2="getBisectorEnd(figure, vName).x"
                                                              :y2="getBisectorEnd(figure, vName).y"
                                                              stroke="#a855f7" stroke-width="1.5" stroke-dasharray="5,3"/>
                                                        <circle :cx="getBisectorEnd(figure, vName).x"
                                                                :cy="getBisectorEnd(figure, vName).y"
                                                                r="3" fill="#a855f7"/>
                                                    </g>
                                                </template>

                                                {{-- Medians --}}
                                                <template x-for="vName in ['A', 'B', 'C']" :key="'median-' + vName">
                                                    <g x-show="figure.lines['median_' + vName.toLowerCase()] && figure.lines['median_' + vName.toLowerCase()].enabled">
                                                        <line :x1="figure.vertices[vName].x"
                                                              :y1="figure.vertices[vName].y"
                                                              :x2="getMedianEnd(figure, vName).x"
                                                              :y2="getMedianEnd(figure, vName).y"
                                                              stroke="#3b82f6" stroke-width="1.5" stroke-dasharray="8,4"/>
                                                        <circle :cx="getMedianEnd(figure, vName).x"
                                                                :cy="getMedianEnd(figure, vName).y"
                                                                r="3" fill="#3b82f6"/>
                                                    </g>
                                                </template>

                                                {{-- Altitudes --}}
                                                <template x-for="vName in ['A', 'B', 'C']" :key="'altitude-' + vName">
                                                    <g x-show="figure.lines['altitude_' + vName.toLowerCase()] && figure.lines['altitude_' + vName.toLowerCase()].enabled">
                                                        <line :x1="figure.vertices[vName].x"
                                                              :y1="figure.vertices[vName].y"
                                                              :x2="getAltitudeEnd(figure, vName).x"
                                                              :y2="getAltitudeEnd(figure, vName).y"
                                                              stroke="#22c55e" stroke-width="1.5"/>
                                                        <path :d="getAltitudeRightAngle(figure, vName)"
                                                              fill="none" stroke="#666" stroke-width="1"/>
                                                    </g>
                                                </template>
                                            </g>
                                        </template>

                                        {{-- Equal side marks --}}
                                        <template x-if="figure.equalGroups && figure.equalGroups.sides">
                                            <g class="equal-marks">
                                                <template x-for="group in figure.equalGroups.sides" :key="'eq-' + group.group">
                                                    <template x-for="side in group.sides" :key="side">
                                                        <g>
                                                            <template x-if="group.group === 1">
                                                                <line :x1="getEqualityTick(figure, side).x1"
                                                                      :y1="getEqualityTick(figure, side).y1"
                                                                      :x2="getEqualityTick(figure, side).x2"
                                                                      :y2="getEqualityTick(figure, side).y2"
                                                                      stroke="#f97316" stroke-width="2"/>
                                                            </template>
                                                            <template x-if="group.group === 2">
                                                                <g>
                                                                    <line :x1="getDoubleEqualityTick(figure, side).tick1.x1"
                                                                          :y1="getDoubleEqualityTick(figure, side).tick1.y1"
                                                                          :x2="getDoubleEqualityTick(figure, side).tick1.x2"
                                                                          :y2="getDoubleEqualityTick(figure, side).tick1.y2"
                                                                          stroke="#f97316" stroke-width="2"/>
                                                                    <line :x1="getDoubleEqualityTick(figure, side).tick2.x1"
                                                                          :y1="getDoubleEqualityTick(figure, side).tick2.y1"
                                                                          :x2="getDoubleEqualityTick(figure, side).tick2.x2"
                                                                          :y2="getDoubleEqualityTick(figure, side).tick2.y2"
                                                                          stroke="#f97316" stroke-width="2"/>
                                                                </g>
                                                            </template>
                                                        </g>
                                                    </template>
                                                </template>
                                            </g>
                                        </template>

                                        {{-- Vertex points --}}
                                        <template x-for="(vertex, vName) in figure.vertices" :key="vName">
                                            <g>
                                                <circle :cx="vertex.x" :cy="vertex.y" r="6"
                                                        :fill="draggingVertex && draggingVertex.figure === figure && draggingVertex.vertex === vName ? '#f97316' : '#f97316'"
                                                        :stroke="draggingVertex && draggingVertex.figure === figure && draggingVertex.vertex === vName ? '#fff' : 'transparent'"
                                                        stroke-width="2"
                                                        class="cursor-grab hover:r-8 transition-all"
                                                        @mousedown.stop="startDragVertex(figure, vName, $event)"/>
                                                <text :x="getLabelPosition(figure, vName).x"
                                                      :y="getLabelPosition(figure, vName).y"
                                                      fill="#f97316" font-size="16" font-weight="bold" font-style="italic"
                                                      text-anchor="middle" dominant-baseline="middle"
                                                      class="pointer-events-none"
                                                      x-text="vertex.label || vName"></text>
                                            </g>
                                        </template>
                                    </g>

                                {{-- Circle --}}
                                <g x-show="figure.type === 'circle'" style="display: inline;">
                                        <circle :cx="figure.center.x" :cy="figure.center.y" :r="figure.radius"
                                                fill="none"
                                                :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#5a9fcf'"
                                                stroke-width="2"/>

                                        {{-- Diameter --}}
                                        <g x-show="figure.showDiameter">
                                            <line :x1="figure.center.x - figure.radius"
                                                  :y1="figure.center.y"
                                                  :x2="figure.center.x + figure.radius"
                                                  :y2="figure.center.y"
                                                  stroke="#f97316" stroke-width="2"/>
                                            <circle :cx="figure.center.x - figure.radius" :cy="figure.center.y" r="4" fill="#f97316"/>
                                            <circle :cx="figure.center.x + figure.radius" :cy="figure.center.y" r="4" fill="#f97316"/>
                                        </g>

                                        {{-- Radius --}}
                                        <g x-show="figure.showRadius">
                                            <line :x1="figure.center.x"
                                                  :y1="figure.center.y"
                                                  :x2="figure.center.x + figure.radius * 0.707"
                                                  :y2="figure.center.y - figure.radius * 0.707"
                                                  stroke="#22c55e" stroke-width="2"/>
                                            <text :x="figure.center.x + figure.radius * 0.4"
                                                  :y="figure.center.y - figure.radius * 0.4"
                                                  fill="#22c55e" font-size="12" font-style="italic">r</text>
                                        </g>

                                        {{-- Chords --}}
                                        <template x-for="chord in (figure.chords || [])" :key="chord.id">
                                            <g>
                                                <line :x1="chord.point1.x" :y1="chord.point1.y"
                                                      :x2="chord.point2.x" :y2="chord.point2.y"
                                                      stroke="#3b82f6" stroke-width="2"/>
                                                <circle :cx="chord.point1.x" :cy="chord.point1.y" r="4"
                                                        fill="#3b82f6" class="cursor-grab"
                                                        @mousedown.stop="startDragChordPoint(figure, chord, 'point1', $event)"/>
                                                <circle :cx="chord.point2.x" :cy="chord.point2.y" r="4"
                                                        fill="#3b82f6" class="cursor-grab"
                                                        @mousedown.stop="startDragChordPoint(figure, chord, 'point2', $event)"/>
                                                <text :x="chord.point1.x - 15" :y="chord.point1.y"
                                                      fill="#3b82f6" font-size="13" font-style="italic"
                                                      x-text="chord.label1 || 'P'"></text>
                                                <text :x="chord.point2.x + 8" :y="chord.point2.y"
                                                      fill="#3b82f6" font-size="13" font-style="italic"
                                                      x-text="chord.label2 || 'Q'"></text>
                                            </g>
                                        </template>

                                        {{-- Tangents --}}
                                        <template x-for="tangent in (figure.tangents || [])" :key="tangent.id">
                                            <g>
                                                <line :x1="tangent.externalPoint.x" :y1="tangent.externalPoint.y"
                                                      :x2="getTangentPoint(figure, tangent).x"
                                                      :y2="getTangentPoint(figure, tangent).y"
                                                      stroke="#a855f7" stroke-width="2"/>
                                                <circle :cx="tangent.externalPoint.x" :cy="tangent.externalPoint.y" r="4"
                                                        fill="#a855f7" class="cursor-grab"
                                                        @mousedown.stop="startDragTangentPoint(figure, tangent, $event)"/>
                                                <circle :cx="getTangentPoint(figure, tangent).x"
                                                        :cy="getTangentPoint(figure, tangent).y" r="3" fill="#a855f7"/>
                                                <text :x="tangent.externalPoint.x + 10"
                                                      :y="tangent.externalPoint.y - 10"
                                                      fill="#a855f7" font-size="13" font-style="italic"
                                                      x-text="tangent.label || 'T'"></text>
                                            </g>
                                        </template>

                                        {{-- Secants --}}
                                        <template x-for="secant in (figure.secants || [])" :key="secant.id">
                                            <g>
                                                <line :x1="secant.point1.x" :y1="secant.point1.y"
                                                      :x2="secant.point2.x" :y2="secant.point2.y"
                                                      stroke="#fbbf24" stroke-width="2"/>
                                                {{-- Intersection points with circle --}}
                                                <template x-for="(pt, idx) in getSecantIntersections(figure, secant)" :key="idx">
                                                    <circle :cx="pt.x" :cy="pt.y" r="3" fill="#fbbf24"/>
                                                </template>
                                                <circle :cx="secant.point1.x" :cy="secant.point1.y" r="4"
                                                        fill="#fbbf24" class="cursor-grab"
                                                        @mousedown.stop="startDragSecantPoint(figure, secant, 'point1', $event)"/>
                                                <circle :cx="secant.point2.x" :cy="secant.point2.y" r="4"
                                                        fill="#fbbf24" class="cursor-grab"
                                                        @mousedown.stop="startDragSecantPoint(figure, secant, 'point2', $event)"/>
                                            </g>
                                        </template>

                                        {{-- Inscribed angles --}}
                                        <template x-for="inscribed in (figure.inscribedAngles || [])" :key="inscribed.id">
                                            <g>
                                                <path :d="getInscribedAnglePath(figure, inscribed)"
                                                      fill="none" stroke="#10b981" stroke-width="2"/>
                                                <path :d="getInscribedAngleArc(figure, inscribed)"
                                                      fill="none" stroke="#fbbf24" stroke-width="1.5"/>
                                            </g>
                                        </template>

                                        {{-- Arc highlights --}}
                                        <template x-for="arc in (figure.highlightedArcs || [])" :key="arc.id">
                                            <path :d="getArcPath(figure, arc)"
                                                  fill="none" :stroke="arc.color || '#22c55e'" stroke-width="3"/>
                                        </template>

                                        {{-- Center point --}}
                                        <circle :cx="figure.center.x" :cy="figure.center.y" r="4"
                                                fill="#f97316"
                                                class="cursor-grab"
                                                @mousedown.stop="startDragCenter(figure, $event)"/>
                                        <text :x="figure.center.x + 12" :y="figure.center.y - 12"
                                              fill="#f97316" font-size="14" font-weight="bold"
                                              x-text="figure.centerLabel || 'O'"></text>
                                </g>

                                {{-- Quadrilateral --}}
                                <g x-show="figure.type === 'quadrilateral'" style="display: inline;">
                                        <polygon :points="getQuadrilateralPoints(figure)"
                                                 fill="none"
                                                 :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                 stroke-width="2"/>
                                        {{-- Vertex points --}}
                                        <template x-for="(vertex, vName) in figure.vertices" :key="vName">
                                            <g>
                                                <circle :cx="vertex.x" :cy="vertex.y" r="6"
                                                        fill="#f97316"
                                                        class="cursor-grab"
                                                        @mousedown.stop="startDragVertex(figure, vName, $event)"/>
                                                <text :x="getLabelPositionQuad(figure, vName).x"
                                                      :y="getLabelPositionQuad(figure, vName).y"
                                                      fill="#f97316" font-size="16" font-weight="bold" font-style="italic"
                                                      text-anchor="middle" dominant-baseline="middle"
                                                      x-text="vertex.label || vName"></text>
                                            </g>
                                        </template>
                                </g>

                                {{-- Stereometry figures --}}
                                <g x-show="figure.type === 'stereometry'" style="display: inline;">
                                        {{-- Polyhedra (cube, prism, pyramid) --}}
                                        <template x-if="figure.edges">
                                            <g>
                                                {{-- Hidden edges (dashed) --}}
                                                <template x-for="edge in figure.edges.filter(e => !e.visible)" :key="edge.from + '-' + edge.to + '-hidden'">
                                                    <line :x1="figure.vertices[edge.from].x"
                                                          :y1="figure.vertices[edge.from].y"
                                                          :x2="figure.vertices[edge.to].x"
                                                          :y2="figure.vertices[edge.to].y"
                                                          stroke="#6b7280" stroke-width="1.5" stroke-dasharray="5,5"/>
                                                </template>
                                                {{-- Visible edges (solid) --}}
                                                <template x-for="edge in figure.edges.filter(e => e.visible)" :key="edge.from + '-' + edge.to + '-visible'">
                                                    <line :x1="figure.vertices[edge.from].x"
                                                          :y1="figure.vertices[edge.from].y"
                                                          :x2="figure.vertices[edge.to].x"
                                                          :y2="figure.vertices[edge.to].y"
                                                          :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                          stroke-width="2"/>
                                                </template>
                                                {{-- Vertex points and labels --}}
                                                <template x-for="(vertex, vName) in figure.vertices" :key="vName">
                                                    <g>
                                                        <circle :cx="vertex.x" :cy="vertex.y" r="5"
                                                                :fill="vertex.visible !== false ? '#f97316' : '#6b7280'"
                                                                class="cursor-grab"
                                                                @mousedown.stop="startDragVertex(figure, vName, $event)"/>
                                                        <text :x="getStereometryLabelPos(figure, vName).x"
                                                              :y="getStereometryLabelPos(figure, vName).y"
                                                              :fill="vertex.visible !== false ? '#f97316' : '#6b7280'"
                                                              font-size="14" font-weight="bold" font-style="italic"
                                                              text-anchor="middle" dominant-baseline="middle"
                                                              x-text="vertex.label || vName"></text>
                                                    </g>
                                                </template>
                                            </g>
                                        </template>

                                        {{-- Cylinder --}}
                                        <template x-if="figure.stereometryType === 'cylinder'">
                                            <g>
                                                {{-- Bottom ellipse --}}
                                                <ellipse :cx="figure.center.x" :cy="figure.center.y"
                                                         :rx="figure.radiusX" :ry="figure.radiusY"
                                                         fill="none" stroke="#8b5cf6" stroke-width="2"/>
                                                {{-- Top ellipse --}}
                                                <ellipse :cx="figure.center.x" :cy="figure.center.y - figure.height"
                                                         :rx="figure.radiusX" :ry="figure.radiusY"
                                                         fill="none"
                                                         :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                         stroke-width="2"/>
                                                {{-- Side lines --}}
                                                <line :x1="figure.center.x - figure.radiusX" :y1="figure.center.y"
                                                      :x2="figure.center.x - figure.radiusX" :y2="figure.center.y - figure.height"
                                                      :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                      stroke-width="2"/>
                                                <line :x1="figure.center.x + figure.radiusX" :y1="figure.center.y"
                                                      :x2="figure.center.x + figure.radiusX" :y2="figure.center.y - figure.height"
                                                      :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                      stroke-width="2"/>
                                                {{-- Back arc (hidden) --}}
                                                <path :d="getCylinderBackArc(figure, 'bottom')"
                                                      fill="none" stroke="#6b7280" stroke-width="1.5" stroke-dasharray="5,5"/>
                                                {{-- Axis --}}
                                                <line x-show="figure.showAxis"
                                                      :x1="figure.center.x" :y1="figure.center.y + figure.radiusY"
                                                      :x2="figure.center.x" :y2="figure.center.y - figure.height - figure.radiusY"
                                                      stroke="#22c55e" stroke-width="1.5" stroke-dasharray="8,4"/>
                                                {{-- Center points --}}
                                                <circle :cx="figure.center.x" :cy="figure.center.y" r="4" fill="#f97316"
                                                        class="cursor-grab" @mousedown.stop="startDragCenter(figure, $event)"/>
                                                <circle :cx="figure.center.x" :cy="figure.center.y - figure.height" r="4" fill="#f97316"/>
                                                {{-- Labels --}}
                                                <text :x="figure.center.x + 12" :y="figure.center.y + 15"
                                                      fill="#f97316" font-size="14" font-weight="bold">O</text>
                                                <text :x="figure.center.x + 12" :y="figure.center.y - figure.height - 10"
                                                      fill="#f97316" font-size="14" font-weight="bold">O₁</text>
                                            </g>
                                        </template>

                                        {{-- Cone --}}
                                        <template x-if="figure.stereometryType === 'cone'">
                                            <g>
                                                {{-- Base ellipse --}}
                                                <ellipse :cx="figure.center.x" :cy="figure.center.y"
                                                         :rx="figure.radiusX" :ry="figure.radiusY"
                                                         fill="none"
                                                         :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                         stroke-width="2"/>
                                                {{-- Back arc (hidden) --}}
                                                <path :d="getConeBackArc(figure)"
                                                      fill="none" stroke="#6b7280" stroke-width="1.5" stroke-dasharray="5,5"/>
                                                {{-- Side lines to apex --}}
                                                <line :x1="figure.center.x - figure.radiusX" :y1="figure.center.y"
                                                      :x2="figure.apex.x" :y2="figure.apex.y"
                                                      :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                      stroke-width="2"/>
                                                <line :x1="figure.center.x + figure.radiusX" :y1="figure.center.y"
                                                      :x2="figure.apex.x" :y2="figure.apex.y"
                                                      :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                      stroke-width="2"/>
                                                {{-- Axis --}}
                                                <line x-show="figure.showAxis"
                                                      :x1="figure.center.x" :y1="figure.center.y + figure.radiusY"
                                                      :x2="figure.apex.x" :y2="figure.apex.y - 20"
                                                      stroke="#22c55e" stroke-width="1.5" stroke-dasharray="8,4"/>
                                                {{-- Center and apex points --}}
                                                <circle :cx="figure.center.x" :cy="figure.center.y" r="4" fill="#f97316"
                                                        class="cursor-grab" @mousedown.stop="startDragCenter(figure, $event)"/>
                                                <circle :cx="figure.apex.x" :cy="figure.apex.y" r="5" fill="#f97316"
                                                        class="cursor-grab" @mousedown.stop="startDragApex(figure, $event)"/>
                                                {{-- Labels --}}
                                                <text :x="figure.center.x + 12" :y="figure.center.y + 15"
                                                      fill="#f97316" font-size="14" font-weight="bold">O</text>
                                                <text :x="figure.apex.x + 10" :y="figure.apex.y - 10"
                                                      fill="#f97316" font-size="14" font-weight="bold">S</text>
                                            </g>
                                        </template>

                                        {{-- Sphere --}}
                                        <template x-if="figure.stereometryType === 'sphere'">
                                            <g>
                                                {{-- Main circle --}}
                                                <circle :cx="figure.center.x" :cy="figure.center.y" :r="figure.radius"
                                                        fill="none"
                                                        :stroke="selectedFigure && selectedFigure.id === figure.id ? '#a855f7' : '#8b5cf6'"
                                                        stroke-width="2"/>
                                                {{-- Equator ellipse --}}
                                                <ellipse :cx="figure.center.x" :cy="figure.center.y"
                                                         :rx="figure.radius" :ry="figure.radius * 0.3"
                                                         fill="none" stroke="#8b5cf6" stroke-width="1.5"/>
                                                {{-- Back half of equator (hidden) --}}
                                                <path :d="getSphereBackArc(figure)"
                                                      fill="none" stroke="#6b7280" stroke-width="1.5" stroke-dasharray="5,5"/>
                                                {{-- Vertical meridian (optional) --}}
                                                <ellipse x-show="figure.showMeridian"
                                                         :cx="figure.center.x" :cy="figure.center.y"
                                                         :rx="figure.radius * 0.3" :ry="figure.radius"
                                                         fill="none" stroke="#6b7280" stroke-width="1"/>
                                                {{-- Center point --}}
                                                <circle :cx="figure.center.x" :cy="figure.center.y" r="4" fill="#f97316"
                                                        class="cursor-grab" @mousedown.stop="startDragCenter(figure, $event)"/>
                                                {{-- Radius line --}}
                                                <line x-show="figure.showRadius"
                                                      :x1="figure.center.x" :y1="figure.center.y"
                                                      :x2="figure.center.x + figure.radius * 0.707"
                                                      :y2="figure.center.y - figure.radius * 0.707"
                                                      stroke="#22c55e" stroke-width="2"/>
                                                <text x-show="figure.showRadius"
                                                      :x="figure.center.x + figure.radius * 0.4"
                                                      :y="figure.center.y - figure.radius * 0.4"
                                                      fill="#22c55e" font-size="12" font-style="italic">R</text>
                                                {{-- Label --}}
                                                <text :x="figure.center.x + 12" :y="figure.center.y - 12"
                                                      fill="#f97316" font-size="14" font-weight="bold">O</text>
                                            </g>
                                        </template>

                                        {{-- Sections (cross-sections) --}}
                                        <template x-for="section in (figure.sections || [])" :key="section.id">
                                            <g>
                                                <polygon :points="getSectionPoints(figure, section)"
                                                         fill="rgba(34, 197, 94, 0.15)" stroke="#22c55e" stroke-width="2"/>
                                                <template x-for="(pt, idx) in section.points" :key="idx">
                                                    <circle :cx="pt.x" :cy="pt.y" r="3" fill="#22c55e"/>
                                                </template>
                                            </g>
                                        </template>
                                </g>
                            </g>
                        </template>
                    </svg>

                    {{-- Legacy view overlay --}}
                    <div x-show="mode === 'legacy_view'"
                         class="absolute inset-0 flex flex-col items-center justify-center bg-black/60">
                        <div class="text-center p-8">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-500/20 flex items-center justify-center">
                                <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Legacy изображение</h3>
                            <p class="text-gray-400 mb-6 max-w-md">
                                Это изображение создано в старой системе.<br>
                                Для редактирования нужно пересоздать его в редакторе.
                            </p>
                            <button @click="recreateInEditor()"
                                    class="px-6 py-3 bg-purple-600 hover:bg-purple-500 rounded-lg text-white font-medium transition-colors">
                                🔄 Пересоздать в редакторе
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Bottom toolbar --}}
                <div class="flex items-center justify-between px-4 py-3 border-t border-purple-500/10 bg-[#12121f]">
                    <div class="flex items-center gap-4">
                        <button @click="resetCanvas()"
                                class="px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-gray-200 text-sm transition-colors">
                            🔄 Сбросить
                        </button>
                        <button @click="exportSvg()"
                                class="px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-gray-200 text-sm transition-colors">
                            📥 Экспорт SVG
                        </button>
                    </div>
                    <div class="flex items-center gap-4">
                        <button @click="copySvgCode()"
                                class="px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-gray-200 text-sm transition-colors">
                            📋 Копировать код
                        </button>
                        <button @click="save()" :disabled="saving"
                                class="px-6 py-2 bg-green-600 hover:bg-green-500 disabled:opacity-50 rounded-lg text-white font-medium transition-colors flex items-center gap-2">
                            <span x-show="!saving">💾 Сохранить</span>
                            <span x-show="saving" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Сохранение...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Properties panel --}}
            <div class="w-80 border-l border-purple-500/20 bg-[#1a1a2e] overflow-y-auto">
                <div class="p-4 space-y-4">

                    {{-- No selection --}}
                    <div x-show="!selectedFigure" class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                        <p>Выберите фигуру для редактирования</p>
                    </div>

                    {{-- Triangle properties --}}
                    <template x-if="selectedFigure && selectedFigure.type === 'triangle'">
                        <div class="space-y-4">
                            {{-- Vertices --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>📍</span> Вершины
                                </h3>
                                <div class="space-y-2">
                                    <template x-for="vName in ['A', 'B', 'C']" :key="vName">
                                        <div class="flex items-center gap-2">
                                            <input type="text" :value="selectedFigure.vertices[vName].label || vName"
                                                   @change="updateVertexLabel(vName, $event.target.value)"
                                                   class="w-10 px-2 py-1 text-sm bg-[#1e1e32] text-orange-400 rounded border border-gray-600 text-center font-bold">
                                            <span class="text-gray-500 text-xs">x:</span>
                                            <input type="number" :value="Math.round(selectedFigure.vertices[vName].x)"
                                                   @change="updateVertexCoord(vName, 'x', $event.target.value)"
                                                   class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                            <span class="text-gray-500 text-xs">y:</span>
                                            <input type="number" :value="Math.round(selectedFigure.vertices[vName].y)"
                                                   @change="updateVertexCoord(vName, 'y', $event.target.value)"
                                                   class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Angles --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>📐</span> Углы
                                </h3>
                                <div class="space-y-2">
                                    <template x-for="vName in ['A', 'B', 'C']" :key="'angle-' + vName">
                                        <div class="flex items-center gap-2">
                                            <span class="w-8 text-orange-400 font-bold">∠<span x-text="selectedFigure.vertices[vName].label || vName"></span></span>
                                            <input type="number" min="1" max="178"
                                                   :value="getAngleValue(selectedFigure, vName)"
                                                   @change="setAngleValue(vName, $event.target.value)"
                                                   class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                            <span class="text-gray-500 text-xs">°</span>
                                            <label class="flex items-center gap-1 text-xs text-gray-400 ml-auto">
                                                <input type="checkbox"
                                                       :checked="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showArc"
                                                       @change="toggleAngleArc(vName, $event.target.checked)"
                                                       class="rounded bg-gray-700 border-gray-600">
                                                дуга
                                            </label>
                                            <label class="flex items-center gap-1 text-xs text-gray-400">
                                                <input type="checkbox"
                                                       :checked="selectedFigure.angles && selectedFigure.angles[vName] && selectedFigure.angles[vName].showValue"
                                                       @change="toggleAngleValue(vName, $event.target.checked)"
                                                       class="rounded bg-gray-700 border-gray-600">
                                                °
                                            </label>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Lines --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>📏</span> Линии
                                </h3>
                                <div class="space-y-3">
                                    <template x-for="vName in ['A', 'B', 'C']" :key="'lines-' + vName">
                                        <div class="space-y-1">
                                            <div class="text-xs text-gray-500 mb-1">Из вершины <span x-text="selectedFigure.vertices[vName].label || vName" class="text-orange-400"></span></div>
                                            <div class="flex flex-wrap gap-2">
                                                <label class="flex items-center gap-1 text-xs text-gray-300">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.lines && selectedFigure.lines['bisector_' + vName.toLowerCase()] && selectedFigure.lines['bisector_' + vName.toLowerCase()].enabled"
                                                           @change="toggleLine('bisector_' + vName.toLowerCase(), $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-purple-400">Биссектриса</span>
                                                </label>
                                                <label class="flex items-center gap-1 text-xs text-gray-300">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.lines && selectedFigure.lines['median_' + vName.toLowerCase()] && selectedFigure.lines['median_' + vName.toLowerCase()].enabled"
                                                           @change="toggleLine('median_' + vName.toLowerCase(), $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-blue-400">Медиана</span>
                                                </label>
                                                <label class="flex items-center gap-1 text-xs text-gray-300">
                                                    <input type="checkbox"
                                                           :checked="selectedFigure.lines && selectedFigure.lines['altitude_' + vName.toLowerCase()] && selectedFigure.lines['altitude_' + vName.toLowerCase()].enabled"
                                                           @change="toggleLine('altitude_' + vName.toLowerCase(), $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-green-400">Высота</span>
                                                </label>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Equal sides --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>═</span> Равные стороны
                                </h3>
                                <div class="space-y-2">
                                    <template x-for="group in [1, 2, 3]" :key="'equal-group-' + group">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500 w-16">Группа <span x-text="group"></span>:</span>
                                            <template x-for="side in ['AB', 'BC', 'AC']" :key="side">
                                                <label class="flex items-center gap-1 text-xs">
                                                    <input type="checkbox"
                                                           :checked="isSideInEqualGroup(group, side)"
                                                           @change="toggleSideInEqualGroup(group, side, $event.target.checked)"
                                                           class="rounded bg-gray-700 border-gray-600">
                                                    <span class="text-gray-300" x-text="side"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Circle properties --}}
                    <template x-if="selectedFigure && selectedFigure.type === 'circle'">
                        <div class="space-y-4">
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">⭕ Окружность</h3>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр:</span>
                                        <input type="text" :value="selectedFigure.centerLabel || 'O'"
                                               @change="selectedFigure.centerLabel = $event.target.value; saveState()"
                                               class="w-10 px-2 py-1 text-sm bg-[#1e1e32] text-orange-400 rounded border border-gray-600 text-center font-bold">
                                        <span class="text-gray-500 text-xs">x:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center.x)"
                                               @change="selectedFigure.center.x = parseInt($event.target.value); saveState()"
                                               class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        <span class="text-gray-500 text-xs">y:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center.y)"
                                               @change="selectedFigure.center.y = parseInt($event.target.value); saveState()"
                                               class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Радиус:</span>
                                        <input type="number" :value="Math.round(selectedFigure.radius)" min="10"
                                               @change="selectedFigure.radius = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        <span class="text-gray-500 text-xs">px</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Circle elements --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">📏 Элементы</h3>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showDiameter"
                                               @change="selectedFigure.showDiameter = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Диаметр
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showRadius"
                                               @change="selectedFigure.showRadius = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Радиус
                                    </label>
                                    <button @click="addChord()"
                                            class="w-full px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded text-sm text-gray-300 text-left">
                                        + Добавить хорду
                                    </button>
                                    <button @click="addTangent()"
                                            class="w-full px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded text-sm text-gray-300 text-left">
                                        + Добавить касательную
                                    </button>
                                    <button @click="addSecant()"
                                            class="w-full px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded text-sm text-gray-300 text-left">
                                        + Добавить секущую
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Quadrilateral properties --}}
                    <template x-if="selectedFigure && selectedFigure.type === 'quadrilateral'">
                        <div class="space-y-4">
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">▢ Четырёхугольник</h3>
                                <div class="space-y-2">
                                    <template x-for="vName in ['A', 'B', 'C', 'D']" :key="vName">
                                        <div class="flex items-center gap-2">
                                            <input type="text" :value="selectedFigure.vertices[vName].label || vName"
                                                   @change="updateVertexLabel(vName, $event.target.value)"
                                                   class="w-10 px-2 py-1 text-sm bg-[#1e1e32] text-orange-400 rounded border border-gray-600 text-center font-bold">
                                            <span class="text-gray-500 text-xs">x:</span>
                                            <input type="number" :value="Math.round(selectedFigure.vertices[vName].x)"
                                                   @change="updateVertexCoord(vName, 'x', $event.target.value)"
                                                   class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                            <span class="text-gray-500 text-xs">y:</span>
                                            <input type="number" :value="Math.round(selectedFigure.vertices[vName].y)"
                                                   @change="updateVertexCoord(vName, 'y', $event.target.value)"
                                                   class="w-16 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Presets --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">📐 Тип</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="applyQuadPreset('parallelogram')"
                                            :class="selectedFigure.preset === 'parallelogram' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Параллелограмм
                                    </button>
                                    <button @click="applyQuadPreset('rectangle')"
                                            :class="selectedFigure.preset === 'rectangle' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Прямоугольник
                                    </button>
                                    <button @click="applyQuadPreset('rhombus')"
                                            :class="selectedFigure.preset === 'rhombus' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Ромб
                                    </button>
                                    <button @click="applyQuadPreset('square')"
                                            :class="selectedFigure.preset === 'square' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Квадрат
                                    </button>
                                    <button @click="applyQuadPreset('trapezoid')"
                                            :class="selectedFigure.preset === 'trapezoid' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Трапеция
                                    </button>
                                    <button @click="applyQuadPreset('free')"
                                            :class="selectedFigure.preset === 'free' ? 'bg-purple-600' : 'bg-[#1e1e32]'"
                                            class="px-3 py-2 rounded text-sm text-gray-200 hover:bg-purple-500/50">
                                        Произвольный
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Stereometry properties --}}
                    <template x-if="selectedFigure && selectedFigure.type === 'stereometry'">
                        <div class="space-y-4">
                            {{-- Type indicator --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3 flex items-center gap-2">
                                    <span>🎲</span>
                                    <span x-text="getStereometryTypeName(selectedFigure)"></span>
                                </h3>

                                {{-- Polyhedra vertices --}}
                                <div x-show="selectedFigure.vertices" class="space-y-2 max-h-40 overflow-y-auto">
                                    <template x-for="(vertex, vName) in selectedFigure.vertices" :key="vName">
                                        <div class="flex items-center gap-2">
                                            <input type="text" :value="vertex.label || vName"
                                                   @change="updateVertexLabel(vName, $event.target.value)"
                                                   class="w-12 px-2 py-1 text-xs bg-[#1e1e32] text-orange-400 rounded border border-gray-600 text-center font-bold">
                                            <span class="text-gray-500 text-xs">x:</span>
                                            <input type="number" :value="Math.round(vertex.x)"
                                                   @change="updateVertexCoord(vName, 'x', $event.target.value)"
                                                   class="w-14 px-1 py-1 text-xs bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                            <span class="text-gray-500 text-xs">y:</span>
                                            <input type="number" :value="Math.round(vertex.y)"
                                                   @change="updateVertexCoord(vName, 'y', $event.target.value)"
                                                   class="w-14 px-1 py-1 text-xs bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                        </div>
                                    </template>
                                </div>

                                {{-- Cylinder/Cone/Sphere params --}}
                                <div x-show="selectedFigure.stereometryType === 'cylinder' || selectedFigure.stereometryType === 'cone'" class="space-y-2 mt-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр X:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center?.x || 0)"
                                               @change="selectedFigure.center.x = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр Y:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center?.y || 0)"
                                               @change="selectedFigure.center.y = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Радиус X:</span>
                                        <input type="number" :value="Math.round(selectedFigure.radiusX || 0)" min="10"
                                               @change="selectedFigure.radiusX = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Радиус Y:</span>
                                        <input type="number" :value="Math.round(selectedFigure.radiusY || 0)" min="5"
                                               @change="selectedFigure.radiusY = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Высота:</span>
                                        <input type="number" :value="Math.round(selectedFigure.height || 0)" min="10"
                                               @change="selectedFigure.height = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                </div>

                                {{-- Sphere params --}}
                                <div x-show="selectedFigure.stereometryType === 'sphere'" class="space-y-2 mt-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр X:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center?.x || 0)"
                                               @change="selectedFigure.center.x = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Центр Y:</span>
                                        <input type="number" :value="Math.round(selectedFigure.center?.y || 0)"
                                               @change="selectedFigure.center.y = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs w-16">Радиус:</span>
                                        <input type="number" :value="Math.round(selectedFigure.radius || 0)" min="10"
                                               @change="selectedFigure.radius = parseInt($event.target.value); saveState()"
                                               class="w-20 px-2 py-1 text-sm bg-[#1e1e32] text-gray-200 rounded border border-gray-600">
                                    </div>
                                </div>
                            </div>

                            {{-- Edge visibility --}}
                            <div x-show="selectedFigure.edges" class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">👁 Видимость рёбер</h3>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.autoVisibility"
                                               @change="toggleAutoVisibility($event.target.checked)"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Автоматически
                                    </label>
                                    <div x-show="!selectedFigure.autoVisibility" class="mt-2 space-y-1 max-h-32 overflow-y-auto">
                                        <template x-for="(edge, idx) in selectedFigure.edges" :key="idx">
                                            <label class="flex items-center gap-2 text-xs text-gray-400">
                                                <input type="checkbox"
                                                       :checked="edge.visible"
                                                       @change="toggleEdgeVisibility(idx, $event.target.checked)"
                                                       class="rounded bg-gray-700 border-gray-600">
                                                <span x-text="edge.from + ' → ' + edge.to"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Display options --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">📐 Отображение</h3>
                                <div class="space-y-2">
                                    <label x-show="selectedFigure.stereometryType === 'cylinder' || selectedFigure.stereometryType === 'cone'"
                                           class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showAxis"
                                               @change="selectedFigure.showAxis = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Показать ось
                                    </label>
                                    <label x-show="selectedFigure.stereometryType === 'sphere'"
                                           class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showRadius"
                                               @change="selectedFigure.showRadius = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Показать радиус
                                    </label>
                                    <label x-show="selectedFigure.stereometryType === 'sphere'"
                                           class="flex items-center gap-2 text-sm text-gray-300">
                                        <input type="checkbox"
                                               :checked="selectedFigure.showMeridian"
                                               @change="selectedFigure.showMeridian = $event.target.checked; saveState()"
                                               class="rounded bg-gray-700 border-gray-600">
                                        Показать меридиан
                                    </label>
                                </div>
                            </div>

                            {{-- Sections --}}
                            <div class="bg-[#12121f] rounded-lg p-3">
                                <h3 class="text-sm font-semibold text-purple-400 mb-3">✂️ Сечения</h3>
                                <button @click="addSection()"
                                        class="w-full px-3 py-2 bg-[#1e1e32] hover:bg-[#2a2a42] rounded text-sm text-gray-300">
                                    + Добавить сечение
                                </button>
                                <div class="mt-2 space-y-1">
                                    <template x-for="(section, idx) in (selectedFigure.sections || [])" :key="section.id">
                                        <div class="flex items-center justify-between text-xs text-gray-400 bg-[#1a1a2e] p-2 rounded">
                                            <span>Сечение <span x-text="idx + 1"></span></span>
                                            <button @click="removeSection(idx)"
                                                    class="text-red-400 hover:text-red-300">✕</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Include geometry helpers --}}
<script src="{{ asset('js/geometry-helpers.js') }}"></script>
<script src="{{ asset('js/geometry-editor.js') }}"></script>
